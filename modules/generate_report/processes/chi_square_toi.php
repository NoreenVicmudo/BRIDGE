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
        $dataset1 = getBatchGender($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $context .= "1st Category is composed of Students' Gender";
    break;

    case 'socioeconomicStatus':
        $statusStmt = $con->query('SELECT status, minimum, maximum FROM socioeconomic_status');
        $incomeBrackets = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
        $statusByBatch = getBatchSocioeconomicStatus($con, $studentNumbers, $studentNumbersAcad, $studentMap);

        $dataset1 = [];
        foreach ($statusByBatch as $pesoValue) {
            $matched = "UNCLASSIFIED"; // default if no bracket matches
            foreach ($incomeBrackets as $bracket) {
                if ($pesoValue >= $bracket['minimum'] && $pesoValue <= $bracket['maximum']) {
                    $matched = $bracket['status'];
                    break; // stop once we found the right bracket
                }
            }
            $dataset1[] = $matched;
        }

        $context .= "1st Category is composed of Students' Socioeconomic Status";
    break;
    
    case 'livingArrangement':
        $statusStmt = $con->query('SELECT arrangement_id, arrangement_name FROM living_arrangement');
        $arrangementBrackets = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
        $statusByBatch = getBatchArrangement($con, $studentNumbers, $studentNumbersAcad, $studentMap);

        $dataset1 = [];
        foreach ($statusByBatch as $id) {
            $matched = "UNCLASSIFIED"; // default if no bracket matches
            foreach ($arrangementBrackets as $arrangement) {
                if ($id == $arrangement['arrangement_id']) {
                    $matched = $arrangement['arrangement_name'];
                    break; // stop once we found the right bracket
                }
            }
            $dataset1[] = $matched;
        }

        $context .= "1st Category is composed of Students' Living Arrangement";
    break;

    case 'workStatus':
        $dataset1 = getBatchWork($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $context .= "1st Category is composed of Students' Work Status";
    break;
    
    case 'scholarship':
        $dataset1 = getBatchScholarship($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $context .= "1st Category is composed of Students' Scholarship Status";
    break;
    
    case 'language':
        $languageStmt = $con->query('SELECT language_id, language_name FROM language_spoken');
        $languageBrackets = $languageStmt->fetchAll(PDO::FETCH_ASSOC);
        $languageByBatch = getBatchLanguage($con, $studentNumbers, $studentNumbersAcad, $studentMap);

        $dataset1 = [];
        foreach ($languageByBatch as $id) {
            $matched = "UNCLASSIFIED"; // default if no bracket matches
            foreach ($languageBrackets as $language) {
                if ($id == $language['language_id']) {
                    $matched = $language['language_name'];
                    break; // stop once we found the right bracket
                }
            }
            $dataset1[] = $matched;
        }

        $context .= "1st Category is composed of Students' Language Spoken at Home";
    break;

    case 'lastSchool':
        $dataset1 = getBatchSchool($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $context .= "1st Category is composed of Students' Last School Attended";
    break;
    
    case 'BoardGrades':
        $gradeBrackets = ["<50%", "50%-70%", "70%-80%", "80%-90%", ">90%"];
        $scoresByBatch = getBatchBoardGrades($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_1);
        
        $gradeBracketsByStudent = [];
        // Loop through each grade and apply the categorization function
        foreach ($scoresByBatch as $grade) {
            // Call the new function to get the bracket for the current grade
            $bracket = getGradeBracket($grade);
            
            // Store the result
            $gradeBracketsByStudent[] = $bracket;
        }

        $dataset1 = array_values($gradeBracketsByStudent);
        $context .= "1st Data Set is composed of Student's Grades in a Board Subject";
    break;
    
    case 'PerformanceRating':
        $scoresByBatch = getBatchRating($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_1);
        
        $gradeBracketsByStudent = [];
        // Loop through each grade and apply the categorization function
        foreach ($scoresByBatch as $grade) {
            // Call the new function to get the bracket for the current grade
            $bracket = getGradeBracket($grade);
            
            // Store the result
            $gradeBracketsByStudent[] = $bracket;
        }

        $dataset1 = array_values($gradeBracketsByStudent);
        $context .= "1st Data Set is composed of Student's Performance Rating";
    break;
    
    case 'SimExam':
        $scoresByBatch = getBatchSimulation($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_1);
        
        $gradeBracketsByStudent = [];
        // Loop through each grade and apply the categorization function
        foreach ($scoresByBatch as $grade) {
            // Call the new function to get the bracket for the current grade
            $bracket = getGradeBracket($grade);
            
            // Store the result
            $gradeBracketsByStudent[] = $bracket;
        }

        $dataset1 = array_values($gradeBracketsByStudent);
        $context .= "1st Data Set is composed of Student's Scores in Percentage in a Simulation Exam";
    break;
    
    case 'Attendance':
        $scoresByBatch = getBatchAttendance($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        
        $gradeBracketsByStudent = [];
        // Loop through each grade and apply the categorization function
        foreach ($scoresByBatch as $grade) {
            // Call the new function to get the bracket for the current grade
            $bracket = getGradeBracket($grade);
            
            // Store the result
            $gradeBracketsByStudent[] = $bracket;
        }

        $dataset1 = array_values($gradeBracketsByStudent);
        $context .= "1st Data Set is composed of Student's Review Classes Attendance in Percentage";
    break;
        
    case 'ReviewCenter':
        $dataset1 = getBatchCenter($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $context .= "1st Data Set is composed of Student's Attended Review Center";
    break;
        
    case 'MockScores':
        $scoresByBatch = getBatchMockScores($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_1);
        
        $gradeBracketsByStudent = [];
        // Loop through each grade and apply the categorization function
        foreach ($scoresByBatch as $grade) {
            // Call the new function to get the bracket for the current grade
            $bracket = getGradeBracket($grade);
            
            // Store the result
            $gradeBracketsByStudent[] = $bracket;
        }

        $dataset1 = array_values($gradeBracketsByStudent);
        $context .= "1st Data Set is composed of Student's Scores in Percentage in a Mock Board Exam's Subject";
    break;
    
    case 'LicensureResult':
        $dataset1 = getBatchResult($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $context .= "1st Data Set is composed of Student's Licensure Exam Results";
    break;
}

switch ($metric2) {
    case 'gender':
        $dataset2 = getBatchGender($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $context .= " and the 2nd Category is composed of Students' Gender";
    break;

    case 'socioeconomicStatus':
        $statusStmt = $con->query('SELECT status, minimum, maximum FROM socioeconomic_status');
        $incomeBrackets = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
        $statusByBatch = getBatchSocioeconomicStatus($con, $studentNumbers, $studentNumbersAcad, $studentMap);

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

        $context .= "1st Category is composed of Students' Socioeconomic Status";
    break;
    
    case 'livingArrangement':
        $statusStmt = $con->query('SELECT arrangement_id, arrangement_name FROM living_arrangement');
        $arrangementBrackets = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
        $statusByBatch = getBatchArrangement($con, $studentNumbers, $studentNumbersAcad, $studentMap);

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

        $context .= "and the 2nd Category is composed of Students' Living Arrangement";
    break;
    
    case 'workStatus':
        $dataset2 = getBatchWork($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $context .= " and the 2nd Category is composed of Students' Work Status";
    break;
    
    case 'scholarship':
        $dataset2 = getBatchScholarship($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $context .= " and the 2nd Category is composed of Students' Scholarship Status";
    break;
    
    case 'language':
        $languageStmt = $con->query('SELECT language_id, language_name FROM language_spoken');
        $languageBrackets = $languageStmt->fetchAll(PDO::FETCH_ASSOC);
        $languageByBatch = getBatchLanguage($con, $studentNumbers, $studentNumbersAcad, $studentMap);

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

        $context .= " and the 2nd Category is composed of Students' Language Spoken at Home";
    break;

    case 'lastSchool':
        $dataset2 = getBatchSchool($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $context .= " and the 2nd Category is composed of Students' Last School Attended";
    break;
    
    case 'BoardGrades':
        $scoresByBatch = getBatchBoardGrades($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_2);
        
        $gradeBracketsByStudent = [];
        // Loop through each grade and apply the categorization function
        foreach ($scoresByBatch as $grade) {
            // Call the new function to get the bracket for the current grade
            $bracket = getGradeBracket($grade);
            
            // Store the result
            $gradeBracketsByStudent[] = $bracket;
        }

        $dataset2 = array_values($gradeBracketsByStudent);
        $context .= " and the 2nd Data Set is composed of Student's Grades in a Board Subject";
    break;
    
    case 'PerformanceRating':
        $scoresByBatch = getBatchRating($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_2);
        
        $gradeBracketsByStudent = [];
        // Loop through each grade and apply the categorization function
        foreach ($scoresByBatch as $grade) {
            // Call the new function to get the bracket for the current grade
            $bracket = getGradeBracket($grade);
            
            // Store the result
            $gradeBracketsByStudent[] = $bracket;
        }

        $dataset2 = array_values($gradeBracketsByStudent);
        $context .= " and the 2nd Data Set is composed of Student's Performance Rating";
    break;
    
    case 'SimExam':
        $scoresByBatch = getBatchSimulation($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_2);
        
        $gradeBracketsByStudent = [];
        // Loop through each grade and apply the categorization function
        foreach ($scoresByBatch as $grade) {
            // Call the new function to get the bracket for the current grade
            $bracket = getGradeBracket($grade);
            
            // Store the result
            $gradeBracketsByStudent[] = $bracket;
        }

        $dataset2 = array_values($gradeBracketsByStudent);
        $context .= " and the 2nd Data Set is composed of Student's Scores in Percentage in a Simulation Exam";
    break;
    
    case 'Attendance':
        $scoresByBatch = getBatchAttendance($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        
        $gradeBracketsByStudent = [];
        // Loop through each grade and apply the categorization function
        foreach ($scoresByBatch as $grade) {
            // Call the new function to get the bracket for the current grade
            $bracket = getGradeBracket($grade);
            
            // Store the result
            $gradeBracketsByStudent[] = $bracket;
        }

        $dataset2 = array_values($gradeBracketsByStudent);
        $context .= " and the 2nd Data Set is composed of Student's Review Classes Attendance in Percentage";
    break;
        
    case 'ReviewCenter':
        $dataset2 = getBatchCenter($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $context .= "1st Data Set is composed of Student's Attended Review Center";
    break;
        
    case 'MockScores':
        $scoresByBatch = getBatchMockScores($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric_2);
        
        $gradeBracketsByStudent = [];
        // Loop through each grade and apply the categorization function
        foreach ($scoresByBatch as $grade) {
            // Call the new function to get the bracket for the current grade
            $bracket = getGradeBracket($grade);
            
            // Store the result
            $gradeBracketsByStudent[] = $bracket;
        }

        $dataset2 = array_values($gradeBracketsByStudent);
        $context .= " and the 2nd Data Set is composed of Student's Scores in Percentage in a Mock Board Exam's Subject";
    break;
    
    case 'LicensureResult':
        $dataset2 = getBatchResult($con, $studentNumbers, $studentNumbersAcad, $studentMap);
        $context .= "1st Data Set is composed of Student's Licensure Exam Results";
    break;
}

        $tool = "Chi-Square Test of Independence";
        $result = chiSquareTOI(array_values($dataset1), array_values($dataset2));

        $rowKeys = array_keys($result['rowTotals']);
        $colKeys = array_keys($result['colTotals']);
        $data_series = [];

        foreach ($colKeys as $colKey) {
            $series = [
                'name' => $colKey, // e.g., 'Scholarship Status: Yes'
                'dataPoints' => []
            ];
            
            foreach ($rowKeys as $rowKey) {
                $observedCount = $result['observed'][$rowKey][$colKey] ?? 0;
                $series['dataPoints'][] = [
                    'label' => $rowKey,  // ← FEMALE / MALE
                    'y' => $observedCount
                ];
            }
            $data_series[] = $series;
        }

        $chart_data_toi = [
            'type' => 'TOI',
            'x_categories' => $rowKeys, // Categories for the X-axis labels
            'data_series' => $data_series,
            'chi2' => $result['chi2'],
            'df' => $result['df'],
            'significance' => $result['significance'],
            // Add variable names here if available
            'x_label' => 'Variable 1 Category', 
            'y_label' => 'Variable 2 Counts'
        ];

                $htmlDisplay =  "<h3>Chi-Square Results</h3>";
                $htmlDisplay .= "<table class='report-table'>";

                // Header row
                $htmlDisplay .= "<tr><th>Results</th>";
                foreach ($result['colTotals'] as $colKey => $val) {
                    $htmlDisplay .= "<th>$colKey</th>";
                }
                $htmlDisplay .= "<th>Row Totals</th></tr>";

                // Rows with observed, expected, and chi² contribution
                foreach ($result['observed'] as $rowKey => $cols) {
                    $htmlDisplay .= "<tr><td><b>$rowKey</b></td>";
                    foreach ($result['colTotals'] as $colKey => $val) {
                        $obs = $cols[$colKey] ?? 0;
                        $exp = $result['expected'][$rowKey][$colKey];
                        $cellChi2 = ($exp > 0) ? pow($obs - $exp, 2) / $exp : 0;

                        $htmlDisplay .= "<td>
                                $obs<br>
                                (" . number_format($exp, 2) . ")<br>
                                [" . number_format($cellChi2, 2) . "]
                            </td>";
                    }
                    $htmlDisplay .= "<td><b>{$result['rowTotals'][$rowKey]}</b></td></tr>";
                }

                // Column totals
                $htmlDisplay .= "<tr><td><b>Column Totals</b></td>";
                foreach ($result['colTotals'] as $colKey => $val) {
                    $htmlDisplay .= "<td><b>$val</b></td>";
                }
                $htmlDisplay .= "<td><b>{$result['n']} (Grand Total)</b></td></tr>";

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
        'consolidatedData' => $chart_data_toi,
        "dataset1" => $dataset1,
        "dataset2" => $dataset2,
        'students' => $students,
    ], JSON_NUMERIC_CHECK);

?>