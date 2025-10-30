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
$metric1            = $_POST['field1'] ?? '';
$metric2            = $_POST['field2'] ?? '';
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
        'error' => 'Insufficient data to compute Pearson R.']);
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
    case 'age':
        $ageByBatch = getBatchAge($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $dataset1 = ($ageByBatch);
    break;

    case 'socioeconomicStatus':
        $statusStmt = $con->query('SELECT status, minimum, maximum FROM socioeconomic_status');
        $incomeBrackets = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
        $context .= "1st Data Set is composed of Students' Age";
        
        $statusByBatch = getBatchSocioeconomicStatus($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $dataset1 = ($statusByBatch);
        $context .= "1st Data Set is composed of Students' Socioeconomic Status in Philippine Peso";
    break;
    
    case 'livingArrangement':
    break;
    
    case 'workStatus':
    break;
    
    case 'scholarship':
    break;
    
    case 'language':
    break;

    case 'GWA':
        $scoresByBatch = getBatchGWA($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_1);
        $dataset1 = ($scoresByBatch);
        $context .= "1st Data Set is composed of Student's GWA in a specific Year and Semester";
    break;

    case 'BoardGrades':
        $scoresByBatch = getBatchBoardGrades($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_1);
        $dataset1 = ($scoresByBatch);
        $context .= "1st Data Set is composed of Student's Grades in a Board Subject";
    break;
    
    case 'Retakes':
        $retakesByBatch = getBatchRetakes($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_1);
        $dataset1 = ($retakesByBatch);
        $context .= "1st Data Set is composed of Student's Amount of Retakes in a Subject";
    break;
    
    case 'PerformanceRating':
        $scoresByBatch = getBatchRating($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_1);
        $dataset1 = ($scoresByBatch);
        $context .= "1st Data Set is composed of Student's Performance Rating";
    break;
    
    case 'SimExam':
        $scoresByBatch = getBatchSimulation($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_1);
        $dataset1 = ($scoresByBatch);
        $context .= "1st Data Set is composed of Student's Scores in Percentage in a Simulation Exam";
    break;
    
    case 'Attendance':
        $scoresByBatch = getBatchAttendance($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $dataset1 = ($scoresByBatch);
        $context .= "1st Data Set is composed of Student's Review Classes Attendance in Percentage";
    break;
    
    case 'Recognition':
    break;
        
    case 'MockScores':
        $scoresByBatch = getBatchMockScores($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_1);
        $dataset1 = ($scoresByBatch);
        $context .= "1st Data Set is composed of Student's Scores in Percentage in a Mock Board Exam's Subject";
    break;
    
    case 'TakeAttempt':
        $dataset1 = getBatchAttemptsCumulative($con, $college, $program, $filter_year_start, $filter_year_end, $filter_board_batch);
        $context .= "1st Data Set is composed of Student's Attempts";
    break;
}

switch ($metric2) {
    case 'age':
        $ageByBatch = getBatchAge($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $dataset2 = ($ageByBatch);
        $context .= " and the 2nd Data Set is composed of Students' Age";
    break;

    case 'socioeconomicStatus':
        $statusStmt = $con->query('SELECT status, minimum, maximum FROM socioeconomic_status');
        $incomeBrackets = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $statusByBatch = getBatchSocioeconomicStatus($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $dataset2 = ($statusByBatch);
        $context .= " and the 2nd Data Set is composed of Students' Socioeconomic Status in Philippine Peso";
    break;
    
    case 'livingArrangement':
    break;
    
    case 'workStatus':
    break;
    
    case 'scholarship':
    break;
    
    case 'language':
    break;

    case 'GWA':
        $scoresByBatch = getBatchGWA($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_2);
        $dataset2 = ($scoresByBatch);
        $context .= " and the 2nd Data Set is composed of Student's GWA in a specific Year and Semester";
    break;

    case 'BoardGrades':
        $scoresByBatch = getBatchBoardGrades($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_2);
        $dataset2 = ($scoresByBatch);
        $context .= " and the 2nd Data Set is composed of Student's Grades in a Board Subject";
    break;
    
    case 'Retakes':
        $retakesByBatch = getBatchRetakes($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_2);
        $dataset2 = ($retakesByBatch);
        $context .= " and the 2nd Data Set is composed of Student's Amount of Retakes in a Subject";
    break;
    
    case 'PerformanceRating':
        $scoresByBatch = getBatchRating($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_2);
        $dataset2 = ($scoresByBatch);
        $context .= " and the 2nd Data Set is composed of Student's Performance Rating";
    break;
    
    case 'SimExam':
        $scoresByBatch = getBatchSimulation($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_2);
        $dataset2 = ($scoresByBatch);
        $context .= " and the 2nd Data Set is composed of Student's Scores in Percentage in a Simulation Exam";
    break;
    
    case 'Attendance':
        $scoresByBatch = getBatchAttendance($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $dataset2 = ($scoresByBatch);
        $context .= " and the 2nd Data Set is composed of Student's Review Classes Attendance in Percentage";
    break;
    
    case 'Recognition':
    break;
        
    case 'MockScores':
        $scoresByBatch = getBatchMockScores($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_2);
        $dataset2 = ($scoresByBatch);
        $context .= " and the 2nd Data Set is composed of Student's Scores in Percentage in a Mock Board Exam's Subject";
    break;
    
    case 'TakeAttempt':
        $dataset2 = getBatchAttemptsCumulative($con, $college, $program, $filter_year_start, $filter_year_end, $filter_board_batch);
        $context .= " and the 2nd Data Set is composed of Student's Attempts";
    break;
}

$groupedByYear = [];

$avgX = [];
$avgY = [];

    // 🔹 Multi-year mode — use per-year averages
if ($filter_year_end - $filter_year_start >= 3) {
    // 🧮 Group by year and handle single/multi-year logic
    foreach ($studentYearMap as $studentNumber => $year) {
        if (isset($dataset1[$studentNumber]) && isset($dataset2[$studentNumber])) {
            $groupedByYear[$year]['x'][] = (float)$dataset1[$studentNumber];
            $groupedByYear[$year]['y'][] = (float)$dataset2[$studentNumber];
        } else {
            $groupedByYear[$year]['x'][] = $dataset1[$studentNumber];
            $groupedByYear[$year]['y'][] = $dataset2[$studentNumber];
        }
    }

    foreach ($groupedByYear as $year => $sets) {
        $avgX[$year] = getMetricAverage($sets['x']);
        $avgY[$year] = getMetricAverage($sets['y']);
        $name[] = $year;
    }
}


    if (count($avgX) < 2 || count($avgY) < 2) {
        $dataset1 = array_values($dataset1);
        $dataset2 = array_values($dataset2);
        $name = array_column($students, 'student_number');
        $context .= " (correlating averages of each year range from {$filter_year_start}–{$filter_year_end})";
    } else {
        $dataset1 = array_values($avgX);
        $dataset2 = array_values($avgY);
        $context .= " (correlating averages of each year range from {$filter_year_start}–{$filter_year_end})";
    }


        $tool = "Pearson R";
        $result = pearsonR($dataset1, $dataset2, array_values($name));

        // 1. Prepare the Raw Data (X, Y pairs)
        $raw_data_points = [];
        $x_min = PHP_INT_MAX;
        $x_max = PHP_INT_MIN;

        // Create data pairs for the scatter plot and find min/max X for the line
        $n = count($dataset1);
        for ($i = 0; $i < $n; $i++) {
            $x = (float)$dataset1[$i];
            $y = (float)$dataset2[$i];
            
            $raw_data_points[] = ['x' => $x, 'y' => $y];
            
            // Find min and max for the regression line endpoints
            if ($x < $x_min) $x_min = $x;
            if ($x > $x_max) $x_max = $x;
        }

        // 2. Consolidate ALL data (raw data and stats) into a single object
        $graph_data = [
            'raw_data' => $raw_data_points,
            'stats' => $result, // Contains slope, intercept, r_squared
            'r_value' => $result['r_value'],
            'x_min' => $x_min,
            'x_max' => $x_max
        ];

$htmlDisplay = "<h3>Pearson Correlation (r)</h3>";
$htmlDisplay .= "<table class='report-table'>
<tr>
<th>Group</th>
<th>X Values</th><th>Y Values</th>
<th>(X - Mx)</th><th>(Y - My)</th>
<th>(X - Mx)²</th><th>(Y - My)²</th><th>(X - Mx)(Y - My)</th>
</tr>";

foreach ($result['table_data'] as $row) {
    $htmlDisplay .= "<tr>
        <td><b>{$row['group']}</b></td>
        <td>{$row['x']}</td>
        <td>{$row['y']}</td>
        <td>" . number_format($row['x_minus_mean'], 3) . "</td>
        <td>" . number_format($row['y_minus_mean'], 3) . "</td>
        <td>" . number_format($row['x_diff_squared'], 3) . "</td>
        <td>" . number_format($row['y_diff_squared'], 3) . "</td>
        <td>" . number_format($row['product_of_diffs'], 3) . "</td>
    </tr>";
}

$htmlDisplay .= "<tr>
<td colspan='5'><b>Sum</b></td>
<td><b>" . number_format($result['sum_x_diff_squared'], 3) . "</b></td>
<td><b>" . number_format($result['sum_y_diff_squared'], 3) . "</b></td>
<td><b>" . number_format($result['sum_product_of_diffs'], 3) . "</b></td>
</tr></table>";

// Summary calculations
$htmlDisplay .= "<h3>Result Details & Calculation</h3>";
$htmlDisplay .= "<p>N = {$result['n']}</p>";
$htmlDisplay .= "<p>Mx = " . number_format($result['x_mean'], 3) . 
                ", My = " . number_format($result['y_mean'], 3) . "</p>";
$htmlDisplay .= "<p>Σ(X - Mx)(Y - My) = " . number_format($result['sum_product_of_diffs'], 3) . "</p>";
$htmlDisplay .= "<p>Σ(X - Mx)² = " . number_format($result['sum_x_diff_squared'], 3) . "</p>";
$htmlDisplay .= "<p>Σ(Y - My)² = " . number_format($result['sum_y_diff_squared'], 3) . "</p>";
$htmlDisplay .= "<p><b>r = " . number_format($result['r_value'], 3) . "</b></p>";
$htmlDisplay .= "<p><b>Critical Value" . number_format($result['crit_value'], 3) . "</b></p>";
$htmlDisplay .= "<p><b>Significance = " . $result['significance'] . "</b></p>";

    echo json_encode([
        'success' => $result['success'] ?? true,
        'error' => $result['error'] ?? '',
        'htmlDisplay' => $htmlDisplay,
        'consolidatedData' => $graph_data,
        'students' => $students,
        'groupedByYear' => $groupedByYear,
        'studentYearMap' => $studentYearMap,
        'studentMap' => $studentMap,
        'dataset1' => $dataset1,
        'dataset2' => $dataset2,
    ], JSON_NUMERIC_CHECK)

?>