<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";
require_once __DIR__ . "/functions.php";
require_once PROJECT_PATH . "/functions.php";

// Set up filter variables from POST data
$college            = $_POST['college'] ?? '';
$program            = $_POST['program'] ?? '';
$filter_year_start  = $_POST['yearBatchStart'] ?? '';
$filter_year_end    = $_POST['yearBatchEnd'] ?? '';
$filter_board_batch = $_POST['boardBatch'] ?? '';
$metric1             = $_POST['field1'] ?? '';
$metric2             = $_POST['field2'] ?? '';
$sub_metric_1             = $_POST['subMetricSelect1'] ?? '';
$sub_metric_2             = $_POST['subMetricSelect2'] ?? '';

// Store filter values in session
$_SESSION['filter_college'] = $college;
$_SESSION['filter_program'] = $program;
$_SESSION['filter_year_start'] = $filter_year_start;
$_SESSION['filter_year_end'] = $filter_year_end;
$_SESSION['filter_board_batch'] = $filter_board_batch;

// Retrieve students for the selected range
$students = getBatchRange($con, $college, $program, $filter_year_start, $filter_year_end, $filter_board_batch);

// If nothing returned
if (empty($students)) {
    echo json_encode([
        'success' => false,
        'error' => 'Insufficient data to compute T Test.']);
    exit;
}

// Extract arrays for later use
// $studentNumbersAcad is fine as it just needs unique student numbers
$studentNumbersAcad = array_values(array_unique(array_column($students, 'student_number'))); 
// $studentNumbers is fine as it just needs all batch_id values
$studentNumbers = array_values(array_unique(array_column($students, 'batch_id')));

// Map student_number → batch_id(s) (UPDATED)
$studentMap = [];
$studentYearMap = []; // for grouping later

foreach ($students as $st) {
    $studentNumber = $st['student_number'];
    $batchId = $st['batch_id'];

    // 1. Update $studentMap to handle multiple batch IDs per student number
    if (!isset($studentMap[$studentNumber])) {
        // Initialize the student number's value as an array
        $studentMap[$studentNumber] = [];
    }
    // Add the current batch ID to the array for this student number
    // Use an array check to prevent duplicates if $students array already contains duplicates
    if (!in_array($batchId, $studentMap[$studentNumber])) {
        $studentMap[$studentNumber][] = $batchId;
    }
    
    // 2. $studentYearMap remains the same (Batch ID is the unique key)
    // Map batch_id → year. Since batch_id is generally unique, this map is safe.
    $studentYearMap[$batchId] = $st['year']; // from getBatchRange SELECT
}

$context = "";

switch ($metric1) {
    case 'BoardGrades':
        $scoresByBatch = getBatchBoardGrades($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_1);
        $dataset1 = array_values($scoresByBatch);
        $context .= "1st Data Set is composed of Student's Grades in a Board Subject";
    break;
    
    case 'PerformanceRating':
        $scoresByBatch = getBatchRating($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_1);
        $dataset1 = array_values($scoresByBatch);
        $context .= "1st Data Set is composed of Student's Performance Rating";
    break;
    
    case 'SimExam':
        $scoresByBatch = getBatchSimulation($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_1);
        $dataset1 = array_values($scoresByBatch);
        $context .= "1st Data Set is composed of Student's Scores in Percentage in a Simulation Exam";
    break;
        
    case 'MockScores':
        $scoresByBatch = getBatchMockScores($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_1);
        $dataset1 = array_values($scoresByBatch);
        $context .= "1st Data Set is composed of Student's Scores in Percentage in a Mock Board Exam's Subject";
    break;
}

switch ($metric2) {    
    case 'BoardGrades':
        $scoresByBatch = getBatchBoardGrades($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_2);
        $dataset2 = array_values($scoresByBatch);
        $context .= " and the 2nd Data Set is composed of Student's Grades in a Board Subject";
    break;
    
    case 'PerformanceRating':
        $scoresByBatch = getBatchRating($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_2);
        $dataset2 = array_values($scoresByBatch);
        $context .= " and the 2nd Data Set is composed of Student's Performance Rating";
    break;
    
    case 'SimExam':
        $scoresByBatch = getBatchSimulation($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_2);
        $dataset2 = array_values($scoresByBatch);
        $context .= " and the 2nd Data Set is composed of Student's Scores in Percentage in a Simulation Exam";
    break;
        
    case 'MockScores':
        $scoresByBatch = getBatchMockScores($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_2);
        $dataset2 = array_values($scoresByBatch);
        $context .= " and the 2nd Data Set is composed of Student's Scores in Percentage in a Mock Board Exam's Subject";
    break;
}

// Ensure exactly 2 groups for an independent t-test
$n = count($dataset1);
if ($n !== count($dataset2)) {
        echo json_encode([
        'htmlDisplay' => "<h4></h4>Arrays must have the same length.</h4>",
        //'consolidatedData' => $consolidatedData
    ], JSON_NUMERIC_CHECK);

    exit;
}

// Run the test
$tool = "Dependent T-Test";
$result = dependent_t_test($dataset1, $dataset2);
$chartData = [
    "type" => "dependent_ttest",
    "test_kind" => "paired",
    "t" => $result["t_value"],
    "p" => $result["crit_value"],   // depends if you're treating crit_value as p-value; adjust if needed
    "df" => $result["df"],
    "significance" => $result["significance"],
    "groups" => [
        [
            "name" => "Condition 1",
            "mean" => $result["mean_var1"],
            "sd" => $result["sd_difference"] // or sd of var1 if you prefer
        ],
        [
            "name" => "Condition 2",
            "mean" => $result["mean_var2"],
            "sd" => $result["sd_difference"]
        ]
    ]
];


$htmlDisplay  = "<h3>Dependent (Paired) T-Test Results</h3>";
$htmlDisplay .= "<table class='report-table'>";

// Header row
$htmlDisplay .= "<tr>
                    <th>Treatment 1</th>
                    <th>Treatment 2</th>
                    <th>Diff (T2 - T1)</th>
                    <th>Dev (Diff - M)</th>
                    <th>Sq. Dev</th>
                 </tr>";

// Data rows
for ($i = 0; $i < $result['n']; $i++) {
    $htmlDisplay .= "<tr>
                        <td>{$dataset1[$i]}</td>
                        <td>{$dataset2[$i]}</td>
                        <td>{$result['differences'][$i]}</td>
                        <td>" . number_format($result['deviations'][$i], 2) . "</td>
                        <td>" . number_format($result['sq_devs'][$i], 2) . "</td>
                     </tr>";
}

$htmlDisplay .= "</table>";

// Summary
$htmlDisplay .= "<br><h4>Difference Scores Calculations</h4>";
$htmlDisplay .= "<table class='report-table'>
<tr><td>Mean Difference (M): </td><td>" . number_format($result['mean_difference'], 2) . "</td></tr>
<tr><td>Variance (S²): </td><td>" . number_format($result['variance'], 2) . "<br>
<tr><td>SD of Differences (S): </td><td>" . number_format($result['sd_difference'], 2) . "</td></tr>
<tr><td>Standard Error (SM): </td><td>" . number_format($result['se'], 2) . "</td></tr>
<tr><td>t = (M - μ) / SM = (" . number_format($result['mean_difference'], 2) . " - 0) / " . number_format($result['se'], 2) . 
    " = </td><td>" . number_format($result['t_value'], 2) . "</td></tr>
<tr><td>Degrees of Freedom (df): </td><td>{$result['df']}</td></tr>
<tr><td>Degrees of Freedom</td><td>{$result['df']}</td></tr>
<tr><td>Critical Value (α=0.05, two-tailed)</td><td>±" . number_format($result['crit_value'], 3) . "</td></tr>
<tr><td>Decision</td><td>{$result['significance']}</td></tr>
</table>";

echo json_encode([
        'success' => $result['success'] ?? true,
        'error' => $result['error'] ?? '',
    'htmlDisplay' => $htmlDisplay,
    'consolidatedData' => $chartData,
    'students' => $students,
], JSON_NUMERIC_CHECK);

?>