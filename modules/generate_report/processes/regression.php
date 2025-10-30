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
        'error' => 'Insufficient data to compute Linear Regression.']);
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
        $dataset2 = getBatchScholarship($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $context .= "1st Data Set is composed of Students' Socioeconomic Status in Philippine Peso";
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
        $scoresByBatch = getBatchAwards($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $dataset1 = ($scoresByBatch);
        $context .= "1st Data Set is composed of Student's Award Count";
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
        $scoresByBatch = getBatchAwards($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $dataset2 = ($scoresByBatch);
        $context .= " and the 2nd Data Set is composed of Student's Award Count";
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

// 🧮 Group by year and handle single/multi-year logic
$groupedByYear = [];

foreach ($studentYearMap as $studentNumber => $year) {
    if (isset($dataset1[$studentNumber]) && isset($dataset2[$studentNumber])) {
        $groupedByYear[$year]['x'][] = (float)$dataset1[$studentNumber];
        $groupedByYear[$year]['y'][] = (float)$dataset2[$studentNumber];
    } else {
         $groupedByYear[$year]['x'][] = $dataset1[$studentNumber];
        $groupedByYear[$year]['y'][] = $dataset2[$studentNumber];
    }
}

    // 🔹 Multi-year mode — use per-year averages
    $avgX = [];
    $avgY = [];

    foreach ($groupedByYear as $year => $sets) {
        $avgX[$year] = getMetricAverage($sets['x']);
        $avgY[$year] = getMetricAverage($sets['y']);
        $name[] = $year;
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

        $tool = "Regression";
        $stats = linearRegression($dataset1, $dataset2, array_values($name));

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

        // 2. Prepare the Regression Line Points
        $slope = $stats['slope'];
        $intercept = $stats['intercept'];

        // Point 1: Calculate Y value for the minimum X
        $y1 = ($slope * $x_min) + $intercept; 
        // Point 2: Calculate Y value for the maximum X
        $y2 = ($slope * $x_max) + $intercept; 

        $regression_line_points = [
            ['x' => $x_min, 'y' => $y1],
            ['x' => $x_max, 'y' => $y2]
        ];


        // 3. Consolidate ALL data (raw data and stats) into a single object
        $graph_data = [
            'raw_data' => $raw_data_points,
            'regression_line' => $regression_line_points,
            'stats' => $stats, // Contains slope, intercept, r_squared
            'x_min' => $x_min,
            'x_max' => $x_max
        ];
        //$summary = generateReport($stats, $context, $tool, $groq);

// Assuming the data and the result array ($stats) are available
$htmlDisplay = "
    <div style='display: flex; gap: 20px; margin-bottom: 20px;'>
        
        <div style='flex: 1;'>
            <h4 style='border-bottom: 1px solid #ccc; padding-bottom: 5px;'>Intermediate Calculation Components</h4>
            <table class='report-table' style='width: 100%;'>
                <thead>
                    <tr>
                        <th>Group</th>
                        <th>X</th>
                        <th>Y</th>
                        <th>$\mathbf{X - M_x}$</th>
                        <th>$\mathbf{Y - M_y}$</th>
                        <th>$\mathbf{(X - M_x)^2}$</th>
                        <th>$\mathbf{(X - M_x)(Y - M_y)}$</th>
                    </tr>
                </thead>
                <tbody>";

// Loop through the data points for the table
// NOTE: This assumes you have 'x_minus_mean', 'y_minus_mean', 'x_diff_squared', and 'product_of_diffs' 
//       in your $stats['table_data'], which you should copy from the pearsonR function.
foreach ($stats['table_data'] as $row) {
    $htmlDisplay .= "
                    <tr>
                        <td><b>{$row['group']}</b></td>
                        <td>{$row['x']}</td>
                        <td>{$row['y']}</td>
                        <td>" . number_format($row['x_minus_mean'], 4) . "</td>
                        <td>" . number_format($row['y_minus_mean'], 4) . "</td>
                        <td>" . number_format($row['x_diff_squared'], 4) . "</td>
                        <td>" . number_format($row['product_of_diffs'], 4) . "</td>
                    </tr>";
}

$htmlDisplay .= "
                    <tr>
                        <td colspan='5'><b>Sums</b></td>
                        <td><b>" . number_format($stats['sum_x_diff_squared'], 4) . "</b> ($\mathbf{SS_x}$)</td>
                        <td><b>" . number_format($stats['sum_product_of_diffs'], 4) . "</b> ($\mathbf{SP}$)</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

            <h3 style='text-align: center; margin-top: 0;'>Calculation Summary</h3>
            
            <p>Number of Pairs ($\mathbf{N}$) = " . $stats['n'] . "</p>
            <p>Sum of $\mathbf{X}$ = " . number_format($stats['sum_x'], 4) . "</p>
            <p>Sum of $\mathbf{Y}$ = " . number_format($stats['sum_y'], 4) . "</p>
            <p>Mean $\mathbf{X}$ ($\mathbf{M_x}$) = " . number_format($stats['x_mean'], 4) . "</p>
            <p>Mean $\mathbf{Y}$ ($\mathbf{M_y}$) = " . number_format($stats['y_mean'], 4) . "</p>
            
            <p>Sum of squares ($\mathbf{SS_x}$) = " . number_format($stats['sum_x_diff_squared'], 4) . "</p>
            <p>Sum of products ($\mathbf{SP}$) = " . number_format($stats['sum_product_of_diffs'], 4) . "</p>
            
            <hr>
            
            <p>Regression Equation $\mathbf{\hat{Y} = bX + a}$</p>
            
            <p>$\mathbf{b} = SP / SS_x = " . number_format($stats['sum_product_of_diffs'], 4) . " / " . number_format($stats['sum_x_diff_squared'], 4) . " = \mathbf{" . number_format($stats['slope'], 5) . "}$</p>
            
            <p>$\mathbf{a} = M_y - bM_x = " . number_format($stats['y_mean'], 4) . " - (" . number_format($stats['slope'], 4) . " \times " . number_format($stats['x_mean'], 4) . ") = \mathbf{" . number_format($stats['intercept'], 4) . "}$</p>
            
            <p>$\mathbf{\hat{Y} = " . number_format($stats['slope'], 5) . " X + " . number_format($stats['intercept'], 5) . "}$</p>

";
    echo json_encode([
        'success' => $stats['success'] ?? true,
        'error' => $stats['error'] ?? '',
        'htmlDisplay' => $htmlDisplay,
        'consolidatedData' => $graph_data,
        'dataset1' => $dataset1,
        'dataset2' => $dataset2,
        'students' => $students,
    ], JSON_NUMERIC_CHECK)



?>