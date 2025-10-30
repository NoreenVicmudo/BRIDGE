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
    case 'year':
        $fetchArray = getBatchYear($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $dataset1 = array_values($fetchArray);
        $context .= "Independent Variable is composed of Students' Year Batch";
    break;

    case 'gender':
        $fetchArray = getBatchGender($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $dataset1 = array_values($fetchArray);
        $context .= "Independent Variable is composed of Students' Gender";
    break;
    
    case 'scholarship':
        //$scholarshipBrackets = ['INTERNAL', 'EXTERNAL', 'NONE'];
        $scholarByBatch = getBatchScholarship($con, $studentNumbers, $studentNumbersAcad, $studentMap);

        $dataset1 = [];
        foreach ($scholarByBatch as $id) {
            $matched = "NONE"; // default if no bracket matches
            //foreach ($scholarshipBrackets as $scholarship) {
                if ($id != "NONE") {
                    $matched = "HAS SCHOLARSHIP";
                }
            //}
            $dataset1[] = $matched;
        }
        $context .= "Independent Variable is composed of Students' Scholarship Status";
    break;
    
    case 'language':
        $languageByBatch = getBatchArrangement($con, $studentNumbers, $studentNumbersAcad, $studentMap);

        $dataset1 = [];
        foreach ($languageByBatch as $id) {
            $matched = "FILIPINO"; // default if no bracket matches
                if ($id != "FILIPINO") {
                    $matched = "OTHER";
                }
            $dataset1[] = $matched;
        }

        $context .= "Independent Variable is composed of Students' Language Spoken at Home";
    break;

    case 'lastSchool':
        $fetchArray = getBatchSchool($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $dataset1 = array_values($fetchArray);
        $context .= "Independent Variable is composed of Students' Last School Attended";
    break;
    
    case 'Retakes':
        $retakesByBatch = getBatchRetakes($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_1);

        $dataset1 = [];
        foreach ($retakesByBatch as $id) {
            $matched = "NONE"; // default if no bracket matches
                if ($id > 0) {
                    $matched = "HAS RETAKES";
                }
            $dataset1[] = $matched;
        }
        
        $context .= "Independent Variable is composed of Student's Amount of Retakes in a Subject";
    break;
    
    case 'LicensureResult':
        $fetchArray = getBatchResult($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $dataset1 = array_values($fetchArray);
        $context .= "Independent Variable is composed of Student's Licensure Exam Results";
    break;
    
    case 'TakeAttempt':
        $retakesByBatch = getBatchAttempts($con, $college, $program, $filter_year_batch, $filter_board_batch);

        $dataset1 = [];
        foreach ($retakesByBatch as $id) {
            $matched = "1ST TIME TAKER"; // default if no bracket matches
                if ($id > 1) {
                    $matched = "NOT 1ST TIME";
                }
            $dataset1[] = $matched;
        }

        $context .= "Independent Variable is composed of Student's Attempts";
    break;
}

switch ($metric2) {
    case 'age':
        $ageByBatch = getBatchAge($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $dataset2 = array_values($ageByBatch);
        $context .= " and the Dependent Variable is composed of Students' Age";
    break;
    
    case 'BoardGrades':
        $scoresByBatch = getBatchBoardGrades($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_2);
        $dataset2 = array_values($scoresByBatch);
        $context .= " and the Dependent Variable is composed of Student's Grades in a Board Subject";
    break;
    
    case 'PerformanceRating':
        $scoresByBatch = getBatchRating($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_2);
        $dataset2 = array_values($scoresByBatch);
        $context .= " and the Dependent Variable is composed of Student's Performance Rating";
    break;
    
    case 'SimExam':
        $scoresByBatch = getBatchSimulation($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_2);
        $dataset2 = array_values($gradeBracketsByStudent);
        $context .= " and the Dependent Variable is composed of Student's Scores in Percentage in a Simulation Exam";
    break;
    
    case 'Attendance':
        $scoresByBatch = getBatchAttendance($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $dataset2 = array_values($scoresByBatch);
        $context .= " and the Dependent Variable is composed of Student's Review Classes Attendance in Percentage";
    break;
        
    case 'MockScores':
        $scoresByBatch = getBatchMockScores($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_2);
        $dataset2 = array_values($scoresByBatch);
        $context .= " and the Dependent Variable is composed of Student's Scores in Percentage in a Mock Board Exam's Subject";
    break;
}

// Split dependent variable (DV) values by Independent Variable (IV)
$groups = [];
foreach ($dataset1 as $i => $iv) {
    $dv = $dataset2[$i];
    $groups[$iv][] = $dv;
}

// Ensure exactly 2 groups for an independent t-test
if (count($groups) !== 2) {
        echo json_encode([
        'htmlDisplay' => "<h4></h4>Independent t-test requires exactly 2 groups.</h4>",
        //'consolidatedData' => $consolidatedData
    ], JSON_NUMERIC_CHECK);

    exit;
}

// Extract the two groups (regardless of IV labels)
$groupKeys = array_keys($groups);
$group1 = $groups[$groupKeys[0]];
$group2 = $groups[$groupKeys[1]];

// Run the test
$tool = "Independent T-Test";
$result = independent_t_test($group1, $group2);

$chartData = [
    "type" => "independent_ttest",
    "test_kind" => "independent",
    "t" => $result["t_value"],
    "df" => $result["df"],
    "p" => $result["crit_value"],   // depends if you're treating crit_value as p-value; adjust if needed
    "significance" => $result["significance"],
    "groups" => [
        [
            "name" => "Group 1",
            "mean" => $result["mean1"],
            "sd"   => sqrt($result["var1"])
        ],
        [
            "name" => "Group 2",
            "mean" => $result["mean2"],
            "sd"   => sqrt($result["var2"])
        ]
    ]
];

$htmlDisplay  = "<h3>Independent T-Test Results</h3>";
// Group 1 Table
$htmlDisplay .= "<h3>Treatment 1 (X₁)</h3>
<table class='report-table'>
<tr><th>Score</th><th>X - M₁</th><th>(X - M₁)²</th></tr>";

foreach ($result['group1'] as $i => $x) {
    $htmlDisplay .= "<tr>
        <td>{$x}</td>
        <td>" . number_format($result['dev1'][$i], 2) . "</td>
        <td>" . number_format($result['sq_dev1'][$i], 2) . "</td>
    </tr>";
}
$htmlDisplay .= "<tr><td colspan='2'><b>SS₁</b></td><td><b>" . number_format($result['SS1'], 2) . "</b></td></tr>";
$htmlDisplay .= "</table>";

// Group 2 Table
$htmlDisplay .= "<h3>Treatment 2 (X₂)</h3>
<table class='report-table'>
<tr><th>Score</th><th>X - M₂</th><th>(X - M₂)²</th></tr>";

foreach ($result['group2'] as $i => $x) {
    $htmlDisplay .= "<tr>
        <td>{$x}</td>
        <td>" . number_format($result['dev2'][$i], 2) . "</td>
        <td>" . number_format($result['sq_dev2'][$i], 2) . "</td>
    </tr>";
}
$htmlDisplay .= "<tr><td colspan='2'><b>SS₂</b></td><td><b>" . number_format($result['SS2'], 2) . "</b></td></tr>";
$htmlDisplay .= "</table>";

// Summary Calculations
$htmlDisplay .= "<h3>Difference Scores Calculations</h3>
<table class='report-table'>
<tr><td>Mean₁ (M₁)</td><td>" . number_format($result['mean1'], 3) . "</td></tr>
<tr><td>Mean₂ (M₂)</td><td>" . number_format($result['mean2'], 3) . "</td></tr>
<tr><td>Variance₁ (s₁²)</td><td>" . number_format($result['var1'], 3) . "</td></tr>
<tr><td>Variance₂ (s₂²)</td><td>" . number_format($result['var2'], 3) . "</td></tr>
<tr><td>Pooled Variance (s<sub>p</sub>²)</td><td>" . number_format($result['sp2'], 3) . "</td></tr>
<tr><td>Standard Error (SE)</td><td>" . number_format($result['se'], 3) . "</td></tr>
<tr><td>t-Value</td><td>" . number_format($result['t_value'], 3) . "</td></tr>
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
    ], JSON_NUMERIC_CHECK)

?>