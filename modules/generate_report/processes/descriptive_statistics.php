<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";
require_once __DIR__ . "/functions.php";
require_once PROJECT_PATH . "/functions.php";

// 🧮 Filter variables from POST
$college            = $_POST['college'] ?? '';
$program            = $_POST['program'] ?? '';
$filter_year_start  = $_POST['yearBatchStart'] ?? '';
$filter_year_end    = $_POST['yearBatchEnd'] ?? '';
$filter_board_batch = $_POST['boardBatch'] ?? '';
$metric             = $_POST['field0'] ?? '';
$sub_metric         = $_POST['subMetricSelect'] ?? '';

// 🧠 Store filters in session
$_SESSION['filter_college'] = $college;
$_SESSION['filter_program'] = $program;
$_SESSION['filter_year_start'] = $filter_year_start;
$_SESSION['filter_year_end'] = $filter_year_end;
$_SESSION['filter_board_batch'] = $filter_board_batch;

// 🧩 Retrieve students in range
$students = getBatchRange($con, $college, $program, $filter_year_start, $filter_year_end, $filter_board_batch);

if (empty($students)) {
    echo json_encode([
        'success' => false,
        'error' => 'Insufficient data to compute Descriptive Statistics.']);
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

$context = "Data Set is composed of ";

switch ($metric) {
    case 'age':
        $ageByBatch = getBatchAge($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $dataset = ($ageByBatch);
        $context .= "Data Set is composed of Students' Age";
    break;

    case 'socioeconomicStatus':
        $statusStmt = $con->query('SELECT status, minimum, maximum FROM socioeconomic_status');
        $incomeBrackets = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $statusByBatch = getBatchSocioeconomicStatus($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $dataset = ($statusByBatch);
        $context .= "Data Set is composed of Students' Socioeconomic Status in Philippine Peso";
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
        $scoresByBatch = getBatchGWA($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric);
        $dataset = ($scoresByBatch);
        $context .= "Data Set is composed of Student's GWA in a specific Year and Semester";
    break;

    case 'BoardGrades':
        $scoresByBatch = getBatchBoardGrades($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric);
        $dataset = ($scoresByBatch);
        $context .= "Data Set is composed of Student's Grades in a Board Subject";
    break;
    
    case 'Retakes':
        $retakesByBatch = getBatchRetakes($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric);
        $dataset = ($retakesByBatch);
        $context .= "Data Set is composed of Student's Amount of Retakes in a Subject";
    break;
    
    case 'PerformanceRating':
        $scoresByBatch = getBatchRating($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric);
        $dataset = ($scoresByBatch);
        $context .= "Data Set is composed of Student's Performance Rating";
    break;
    
    case 'SimExam':
        $scoresByBatch = getBatchSimulation($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric);
        $dataset = ($scoresByBatch);
        $context .= "Data Set is composed of Student's Scores in Percentage in a Simulation Exam";
    break;
    
    case 'Attendance':
        $scoresByBatch = getBatchAttendance($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $dataset = ($scoresByBatch);
        $context .= "Data Set is composed of Student's Review Classes Attendance in Percentage";
    break;
    
    case 'Recognition':
    break;
        
    case 'MockScores':
        $scoresByBatch = getBatchMockScores($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric);
        $dataset = ($scoresByBatch);
        $context .= "Data Set is composed of Student's Scores in Percentage in a Mock Board Exam's Subject";
    break;
    
    case 'TakeAttempt':
        $scoresByBatch = getBatchAttemptsCumulative($con, $college, $program, $filter_year_start, $filter_year_end, $filter_board_batch);
        $dataset = $scoresByBatch;
        $context .= "Data Set is composed of Student's Attempts";
    break;
}

// 🧮 1. Group by year
$groupedByYear = [];

foreach ($studentYearMap as $studentNumber => $year) {
    // Only process if data for the student exists. Convert score to float.
    if (isset($dataset[$studentNumber])) {
        $groupedByYear[$year][] = (float)$dataset[$studentNumber];
    }
    // Note: No else block needed; silently skip students without a score in $dataset.
}

// --- Multi-year mode: Calculate per-year averages ---
$avgByYear = [];
$statsDataset = []; // This will hold ONLY the scores for statistical analysis
$tableData = [];    // This will hold the structured data for the final HTML table

foreach ($groupedByYear as $year => $sets) {
    // Calculate the average score for the current year
    $averageScore = getMetricAverage($sets);
    
    // Store the average score and the year label in the $tableData structure
    $tableData[] = [
        'year_label' => $year,
        'score'      => $averageScore
    ];
    
    // Collect the scores into a simple array for descriptive statistics calculation
    $statsDataset[] = $averageScore;
}

    // Decide whether to use per-year averages (multi-year mode) or raw per-student data
    $tableData = [];
    $statsDataset = [];

    // If we have two or more distinct years, compute averages per year and use those
    if (count($groupedByYear) >= 2) {
        $avgX = [];
        $name = [];
        foreach ($groupedByYear as $year => $sets) {
            // $sets is an array of numeric scores for that year
            $averageScore = getMetricAverage($sets);
            $avgX[$year] = $averageScore;
            $name[] = $year;

            $tableData[] = [
                'year_label' => $year,
                'score'      => $averageScore
            ];
            $statsDataset[] = $averageScore;
        }

        $dataset1 = array_values($avgX);
        $context .= " (correlating averages of each year range from {$filter_year_start}–{$filter_year_end})";

    } else {
        // Fewer than 2 years: fall back to raw per-student dataset values
        // Build a raw list by iterating students and looking up the score from $dataset
        $raw = [];
        $name = [];

        foreach ($students as $st) {
            $sn = $st['student_number'];
            $batchId = $st['batch_id'];

            // Prefer student-number keyed dataset, otherwise try batch_id keyed dataset
            if (isset($dataset[$sn])) {
                $val = (float)$dataset[$sn];
            } else if (isset($dataset[$batchId])) {
                $val = (float)$dataset[$batchId];
            } else {
                // skip if no value
                continue;
            }

            $raw[] = $val;
            $statsDataset[] = $val;
            $tableData[] = [
                'year_label' => $sn,
                'score'      => $val
            ];
            $name[] = $sn;
        }

        $dataset1 = array_values($raw);
        $context .= " (using raw per-student data for {$filter_year_start}–{$filter_year_end})";
    }

// 🧮 2. Run Descriptive Statistics
$tool = "Descriptive Statistics";
$stats = descriptiveStats($statsDataset); // Pass the array of annual averages

// 📊 3. Prepare Consolidated Data (for Charting/JSON output)
$consolidatedData = [];
foreach ($tableData as $i => $row) {
    $consolidatedData[$i] = [
        'label' => $row['year_label'],
        'y' => $row['score']
    ];
}

// 📝 4. Generate HTML Table Display

$htmlDisplay = '
    <div style="display: flex; gap: 40px; justify-content: flex-start; align-items: flex-start;">
        
        <div>
            <h3>Data Set (Annual Averages)</h3>
            <table class="report-table">
                <thead>
                    <tr><th>Year</th><th>Average Score</th></tr>
                </thead>
                <tbody>
';

// Loop directly over the correctly structured $tableData
foreach ($tableData as $row) {
    $formattedScore = number_format($row['score'], 3); // Format score for display
    $htmlDisplay .= "<tr><td>{$row['year_label']}</td><td>{$formattedScore}</td></tr>";
}

$htmlDisplay .= '
                </tbody>
            </table>
        </div>

        <div>
            <h3>Descriptive Statistics</h3>
            <table class="report-table">
                <tr><th>Metric</th><th>Value</th></tr>
                <tr><td>Count</td><td>' . number_format($stats["count"], 0) . '</td></tr>
                <tr><td>Mean</td><td>' . number_format($stats["mean"], 4) . '</td></tr>
                <tr><td>Median</td><td>' . number_format($stats["median"], 4) . '</td></tr>
                <tr><td>Minimum</td><td>' . number_format($stats["min"], 4) . '</td></tr>
                <tr><td>Maximum</td><td>' . number_format($stats["max"], 4) . '</td></tr>
                <tr><td>Std. Deviation</td><td>' . number_format($stats["stdDev"], 4) . '</td></tr>
                <tr><td>Variance</td><td>' . number_format($stats["variance"], 4) . '</td></tr>
            </table>
        </div>
        
    </div>
';

    echo json_encode([
        'success' => $stats['success'] ?? true,
        'error' => $stats['error'] ?? '',
        'htmlDisplay' => $htmlDisplay,
        'consolidatedData' => $consolidatedData
    ], JSON_NUMERIC_CHECK)
            
?>