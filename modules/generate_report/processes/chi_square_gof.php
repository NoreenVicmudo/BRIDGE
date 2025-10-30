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
$metric1            = $_POST['field'] ?? '';
$sub_metric_1       = $_POST['sub_metric_1'] ?? '';

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
        'error' => 'Insufficient data to compute Chi Square.']);
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
        case 'gender':
            $dataset1 = (["MALE", "FEMALE"]);
            $dataset2 = getBatchGender($con, $studentNumbers, $studentNumbersAcad, $studentMap);
            $context .= "Variable is composed of Students' Gender";
        break;

        case 'socioeconomicStatus':
            $statusStmt = $con->query('SELECT status, minimum, maximum FROM socioeconomic_status ORDER BY minimum ASC');
            $incomeBrackets = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
            $statusByBatch = getBatchSocioeconomicStatus($con, $studentNumbers, $studentNumbersAcad, $studentMap);

            $dataset1 = [];
                foreach ($incomeBrackets as $bracket) {
                    $matched = $bracket['status'];
                    $dataset1[] = $matched;
                }

            $dataset2 = [];
            foreach ($statusByBatch as $pesoValue) {
                $matched = "UNCLASSIFIED"; // default if no bracket matches
                foreach ($incomeBrackets as $bracket) {
                    if ($pesoValue >= $bracket['minimum'] && $pesoValue <= $bracket['maximum']) {
                        $matched = $bracket['status'];
                        break; // stop once we found the right bracket
                    }
                }
                $dataset2[] = $matched;
            }
            $context .= "Variable is composed of Students' Socioeconomic Status";
        break;
        
        case 'livingArrangement':
            $statusStmt = $con->query('SELECT arrangement_id, arrangement_name FROM living_arrangement ORDER BY arrangement_id ASC');
            $arrangementBrackets = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
            $statusByBatch = getBatchArrangement($con, $studentNumbers, $studentNumbersAcad, $studentMap);

            $dataset1 = [];
                foreach ($arrangementBrackets as $bracket) {
                    $matched = $bracket['arrangement_name'];
                    $dataset1[] = $matched;
                }
                
            $dataset2 = [];
            foreach ($statusByBatch as $id) {
                $matched = "UNCLASSIFIED"; // default if no bracket matches
                foreach ($arrangementBrackets as $arrangement) {
                    if ($id == $arrangement['arrangement_id']) {
                        $matched = $arrangement['arrangement_name'];
                        break; // stop once we found the right bracket
                    }
                }
                $dataset2[] = $matched;
            }
            $context .= "Variable is composed of Students' Living Arrangement";
        break;

        case 'workStatus':
            $dataset1 = array_values(["NOT-WORKING", "PART-TIME", "FULL-TIME"]);
            $dataset2 = getBatchWork($con, $studentNumbers, $studentNumbersAcad, $studentMap);
            $context .= "Variable is composed of Students' Work Status";
        break;
        
        case 'scholarship':
            $dataset1 = (["NONE", "INTERNAL", "EXTERNAL"]);
            $dataset2 = getBatchScholarship($con, $studentNumbers, $studentNumbersAcad, $studentMap);
            $context .= "Variable is composed of Students' Scholarship Status";
        break;
        
        case 'language':
            $languageStmt = $con->query('SELECT language_id, language_name FROM language_spoken ORDER BY language_id ASC');
            $languageBrackets = $languageStmt->fetchAll(PDO::FETCH_ASSOC);
            $languageByBatch = getBatchLanguage($con, $studentNumbers, $studentNumbersAcad, $studentMap);

            $dataset1 = [];
                foreach ($languageBrackets as $bracket) {
                    $matched = $bracket['language_name'];
                    $dataset1[] = $matched;
                }

            $dataset2 = [];
            foreach ($languageByBatch as $id) {
                $matched = "UNCLASSIFIED"; // default if no bracket matches
                foreach ($languageBrackets as $language) {
                    if ($id == $language['language_id']) {
                        $matched = $language['language_name'];
                        break; // stop once we found the right bracket
                    }
                }
                $dataset2[] = $matched;
            }
            $context .= "Variable is composed of Students' Language Spoken at Home";
        break;

        case 'lastSchool':
            $dataset1 = (["PUBLIC", "PRIVATE"]);
            $dataset2 = getBatchSchool($con, $studentNumbers, $studentNumbersAcad, $studentMap);
            $context .= "Variable is composed of Students' Last School Attended";
        break;
        
        case 'BoardGrades':
            $gradeBrackets = ["<50%", "50%-70%", "70%-80%", "80%-90%", ">90%"];
            $dataset1 = ($gradeBrackets);
            $scoresByBatch = getBatchBoardGrades($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_1);
            
            $gradeBracketsByStudent = [];
            // Loop through each grade and apply the categorization function
            foreach ($scoresByBatch as $grade) {
                // Call the new function to get the bracket for the current grade
                $bracket = getGradeBracket($grade);
                
                // Store the result
                $gradeBracketsByStudent[] = $bracket;
            }

            $dataset2 = ($gradeBracketsByStudent);
            $context .= "Variable is composed of Students' Grade in a Board Subject";
        break;
        
        case 'PerformanceRating':
            $gradeBrackets = ["<50%", "50%-70%", "70%-80%", "80%-90%", ">90%"];
            $dataset1 = ($gradeBrackets);
            $scoresByBatch = getBatchRating($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_1);
            
            $gradeBracketsByStudent = [];
            // Loop through each grade and apply the categorization function
            foreach ($scoresByBatch as $grade) {
                // Call the new function to get the bracket for the current grade
                $bracket = getGradeBracket($grade);
                
                // Store the result
                $gradeBracketsByStudent[] = $bracket;
            }

            $dataset2 = ($gradeBracketsByStudent);
            $context .= "Variable is composed of Students' Performance Rating";
        break;
        
        case 'SimExam':
            $gradeBrackets = ["<50%", "50%-70%", "70%-80%", "80%-90%", ">90%"];
            $dataset1 = ($gradeBrackets);
            $scoresByBatch = getBatchSimulation($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_1);
            
            $gradeBracketsByStudent = [];
            // Loop through each grade and apply the categorization function
            foreach ($scoresByBatch as $grade) {
                // Call the new function to get the bracket for the current grade
                $bracket = getGradeBracket($grade);
                
                // Store the result
                $gradeBracketsByStudent[] = $bracket;
            }

            $dataset2 = ($gradeBracketsByStudent);
            $context .= "Variable is composed of Students' Scores in Percentage in a Simulation Exam";
        break;
        
        case 'Attendance':
            $gradeBrackets = ["<50%", "50%-70%", "70%-80%", "80%-90%", ">90%"];
            $dataset1 = ($gradeBrackets);
            $scoresByBatch = getBatchAttendance($con, $studentNumbers, $studentNumbersAcad, $studentMap);
            
            $gradeBracketsByStudent = [];
            // Loop through each grade and apply the categorization function
            foreach ($scoresByBatch as $grade) {
                // Call the new function to get the bracket for the current grade
                $bracket = getGradeBracket($grade);
                
                // Store the result
                $gradeBracketsByStudent[] = $bracket;
            }

            $dataset2 = ($gradeBracketsByStudent);
            $context .= "Variable is composed of Students' Review Classes Attendance in Percentage";
        break;
            
        case 'ReviewCenter':
            $dataset1 = getBatchCenter($con, $studentNumbers, $studentNumbersAcad, $studentMap);
            $dataset2 = getBatchCenter($con, $studentNumbers, $studentNumbersAcad, $studentMap);
            $context .= "Variable is composed of Students' Attended Review Center";
        break;
            
        case 'MockScores':
            $gradeBrackets = ["<50%", "50%-70%", "70%-80%", "80%-90%", ">90%"];
            $dataset1 = ($gradeBrackets);
            $scoresByBatch = getBatchMockScores($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_1);
            
            $gradeBracketsByStudent = [];
            // Loop through each grade and apply the categorization function
            foreach ($scoresByBatch as $grade) {
                // Call the new function to get the bracket for the current grade
                $bracket = getGradeBracket($grade);
                
                // Store the result
                $gradeBracketsByStudent[] = $bracket;
            }

            $dataset2 = ($gradeBracketsByStudent);
            $context .= "Variable is composed of Students' Scores in Percentage in a Mock Board Exam's Subject";
        break;
        
        case 'LicensureResult':
            $dataset1 = (["PASSED", "FAILED"]);
            $dataset2 = getBatchResult($con, $studentNumbers, $studentNumbersAcad, $studentMap);
            $context .= "Variable is composed of Students' Licensure Exam Results";
        break;
    }

    $categories = (array_unique($dataset1));  // unique values, re-indexed

if (isset($_POST['action']) && $_POST['action'] === "getCategories") {
    echo "<h4>Expected Frequencies (%)</h4>";
    // Return HTML inputs for expected frequencies
    foreach ($categories as $cat) {
        echo "<div class='form-group'>
                <label>$cat:</label>
                <input type='number' name='expected[$cat]' placeholder='%' required>
              </div>";
    }
    exit;
} else if (isset($_POST['action']) && $_POST['action'] === "calculate") {
    $dataset = array_values($dataset2);  // unique values, re-indexed
    $expectedPercents = $_POST['expected'];
    //echo json_encode([$expectedPercents]);

    // Observed counts
    $observedCounts = getObservedFrequencies(array_values($categories), $dataset);

    // Convert expected % → counts
    $total = count($dataset);
    $expectedCounts = [];
    foreach ($categories as $cat) {
        $expectedCounts[$cat] = round($total * ($expectedPercents[$cat] / 100));
    }

        $tool = "Chi-Square Goodness of Fit";
        $result = chiSquareGOF(array_values($observedCounts), array_values($expectedCounts));

        //$categories = array_keys($result['observed']);

        // Series 1: Observed Counts
        $observed_points = [];
        foreach ($result['observed'] as $count) {
            $observed_points[] = ['y' => $count];
        }

        // Series 2: Expected Counts
        $expected_points = [];
        foreach ($result['expected'] as $count) {
            $expected_points[] = ['y' => $count];
        }

        $chart_data_gof = [
            'type' => 'GOF',
            'x_categories' => $categories, // Category names for the X-axis labels
            'data_series' => [
                ['name' => 'Observed', 'dataPoints' => $observed_points],
                ['name' => 'Expected', 'dataPoints' => $expected_points]
            ],
            'chi2' => $result['chi2'],
            'df' => $result['df'],
            'significance' => $result['significance'],
            'x_label' => 'Category', 
            'y_label' => 'Count (Observed vs. Expected)'
        ];

            $htmlDisplay = "<h3>Chi-Square Goodness of Fit Results</h3>";
            $htmlDisplay .= "<table class='report-table'>";

            // --- Header Row ---
            $htmlDisplay .= "<tr><th>Category</th>";
            // Use the keys from the observed counts for the categories
            foreach ($categories as $catKey) {
                $htmlDisplay .= "<th>$catKey</th>";
            }
            $htmlDisplay .= "<th>Total</th></tr>";

            // --- Data Row: Observed Counts (O) ---
            $htmlDisplay .= "<tr><td><b>Observed (O)</b></td>";
            foreach ($result['observed'] as $obs) {
                $htmlDisplay .= "<td>$obs</td>";
            }
            $htmlDisplay .= "<td><b>{$result['n']}</b></td></tr>";

            // --- Data Row: Expected Counts (E) ---
            $htmlDisplay .= "<tr><td><b>Expected (E)</b></td>";
            foreach ($result['expected'] as $catKey => $exp) {
                // We assume the keys align perfectly
                $htmlDisplay .= "<td>" . number_format($exp, 2) . "</td>";
            }
            $htmlDisplay .= "<td><b>" . number_format(array_sum($result['expected']), 2) . "</b></td></tr>";


            // --- Data Row: Chi-Square Contribution [ (O-E)^2 / E ] ---
            $htmlDisplay .= "<tr><td><b>&#967;^2 Contribution</b></td>";
            $totalChi2Check = 0; // Use this variable to verify the total Chi-Square sum
            foreach ($result['observed'] as $catKey => $obs) {
                $exp = $result['expected'][$catKey];
                $cellChi2 = ($exp > 0) ? pow($obs - $exp, 2) / $exp : 0;
                $totalChi2Check += $cellChi2;
                $htmlDisplay .= "<td>[" . number_format($cellChi2, 2) . "]</td>";
            }
            $htmlDisplay .= "<td><b>" . number_format($totalChi2Check, 3) . "</b></td></tr>";


            $htmlDisplay .= "</table>";

                // Summary metrics
                $htmlDisplay .= "<br><table class='report-table'>";
                $htmlDisplay .= "<tr><th>Metric</th><th>Value</th></tr>";
                $htmlDisplay .= "<tr><td>Total Chi-Square</td><td>" . number_format($result['chi2'], 3) . "</td></tr>";
                $htmlDisplay .= "<tr><td>Degrees of Freedom</td><td>{$result['df']}</td></tr>";
                $htmlDisplay .= "<tr><td>Critical Value</td><td>{$result['crit_value']}</td></tr>";
                $htmlDisplay .= "<tr><td>Sample Size (N)</td><td>{$result['n']}</td></tr>";
                $htmlDisplay .= "<tr><td>Significance</td></td><td>{$result['significance']}</td></tr>";
                $htmlDisplay .= "</table>";

    echo json_encode([
        'success' => $result['success'] ?? true,
        'error' => $result['error'] ?? '',
        'htmlDisplay' => $htmlDisplay,
        "observed" => $observedCounts,
        "expected" => $expectedCounts,
        "dataset" => $dataset,
        "categories" => $categories,
        'consolidatedData' => $chart_data_gof,
        'students' => $students,
    ], JSON_NUMERIC_CHECK);
}
?>