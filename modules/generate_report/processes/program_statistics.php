<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";
require_once __DIR__ . "/functions.php";
require_once PROJECT_PATH . "/functions.php";
  
// Capture the JSON output produced by populate_filter.php safely
ob_start();
@include __DIR__ . '/../../../populate_filter.php';
$jsonOutput = ob_get_clean();
// Try to extract the JSON object from output (strip PHP notices or HTML)
$firstBrace = strpos($jsonOutput, '{');
$lastBrace = strrpos($jsonOutput, '}');
if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
    $jsonClean = substr($jsonOutput, $firstBrace, $lastBrace - $firstBrace + 1);
} else {
    $jsonClean = $jsonOutput; // fallback
}
$decodedOptions = json_decode($jsonClean, true);

// Normalize programs list: some populate_filter outputs programOptions grouped by college
$programsList = [];
if (!empty($decodedOptions['programs']) && is_array($decodedOptions['programs'])) {
    $programsList = $decodedOptions['programs'];
} elseif (!empty($decodedOptions['programOptions']) && is_array($decodedOptions['programOptions'])) {
    foreach ($decodedOptions['programOptions'] as $collegeId => $progs) {
        foreach ($progs as $p) {
            $programsList[] = [
                'program_id' => $p['id'],
                'program_name' => $p['name'],
                'college_id' => $collegeId
            ];
        }
    }
}

// Set up filter variables from POST data
$filter_year_batch  = $_POST['yearBatch'] ?? '';
$metric             = $_POST['field0'] ?? '';

// Store filter values in session
$_SESSION['filter_year_batch'] = $filter_year_batch;
// Determine selected program (expecting session to have filter_program)
$selectedProgram = $_SESSION['filter_program'] ?? '';

// Retrieve students for the selected program and year (getBatch honors session-level restrictions)
$studentsList = getBatch($con, 'none', $selectedProgram, $filter_year_batch, 'none');

// Build handy structures
$studentNumbersAcad = array_column($studentsList, 'student_number'); // academic/student numbers
$studentBatchIds = array_column($studentsList, 'batch_id'); // board_batch ids (may duplicate)
$studentLNames = array_column($studentsList, 'student_lname');

// Map each student_number -> program id (we'll use this for averaging per program)
$studentToProgram = [];
foreach ($studentsList as $st) {
    $studentToProgram[$st['student_number']] = $selectedProgram;
}

// Map each student_number -> batch_id (used by many helpers)
$studentMapBatch = [];
foreach ($studentsList as $st) {
    $studentMapBatch[$st['student_number']] = $st['batch_id'];
}

// Unique batch ids list for helper functions that expect batch IDs
$studentNumbers = array_values(array_unique($studentBatchIds));

// board batch filter (optional POST field)
$filter_board_batch = $_POST['boardBatch'] ?? '';

// determine college for selected program (prefer normalized $programsList)
$college = '';
if (!empty($selectedProgram) && !empty($programsList) && is_array($programsList)) {
    foreach ($programsList as $p) {
        if (isset($p['program_id']) && $p['program_id'] == $selectedProgram) {
            $college = $p['college_id'] ?? '';
            break;
        }
    }
}
// Note: $college is not referenced later in this script currently. We compute it
// defensively in case helper functions or future code need the selected program's
// college_id. If you want to remove unused variables entirely, this block can
// be removed safely.

$context = "Data Set is composed of ";

// We'll compute program-wise averages for every program in decodedOptions['programs']
$programAverages = [];
// debugging: store per-program student-level values
$programStudentValues = [];
// additional debug info
$debugInfo = [];

foreach ($programsList as $prog) {
    $progId = $prog['program_id'];
    $progName = $prog['program_name'] ?? $progId;

    // get students for this program and the selected year/batch
    $progStudents = getBatch($con, 'none', $progId, $filter_year_batch, 'none');
    if (empty($progStudents)) {
        $programAverages[$progId] = null;
        continue;
    }

    // Prepare student lists and mappings for this program
    $progStudentNumbersAcad = array_column($progStudents, 'student_number');
    $progStudentBatchIds = array_column($progStudents, 'batch_id');
    $progStudentMapBatch = [];
    foreach ($progStudents as $s) { $progStudentMapBatch[$s['student_number']] = $s['batch_id']; }
    $progStudentBatchUnique = array_values(array_unique($progStudentBatchIds));

    // collect per-student numeric scores for this program
    // $studentValuesAssoc[student_number] = value , $studentValues = numeric list
    $studentValuesAssoc = [];
    $studentValues = [];

    switch ($metric) {
        case 'age':
            $ageByBatch = getBatchAge($con, $progStudentBatchUnique, $progStudentNumbersAcad, $progStudentMapBatch);
            foreach ($progStudents as $st) {
                $sn = $st['student_number']; $bid = $st['batch_id'];
                if (isset($ageByBatch[$bid])) $studentValues[] = (float)$ageByBatch[$bid];
            }
            $context = "Data Set is composed of Students' Age";
            $label = "Age (average)";
            break;

        case 'socioeconomicStatus':
            $statusByBatch = getBatchSocioeconomicStatus($con, $progStudentBatchUnique, $progStudentNumbersAcad, $progStudentMapBatch);
            foreach ($progStudents as $st) {
                $bid = $st['batch_id']; if (isset($statusByBatch[$bid])) $studentValues[] = (float)$statusByBatch[$bid];
            }
            $context = "Data Set is composed of Students' Socioeconomic Status in Philippine Peso";
            $label = "Socioeconomic Status (average)";
            break;

        case 'GWA':
            // Aggregate all GWA rows for each student (ignore sub-metric selection)
            if (!empty($progStudentNumbersAcad)) {
                $in = str_repeat('?,', count($progStudentNumbersAcad) - 1) . '?';
                $stmt = $con->prepare("SELECT student_number, gwa FROM student_gwa WHERE student_number IN ($in)");
                $stmt->execute($progStudentNumbersAcad);
                $gwas = [];
                while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $gwas[$r['student_number']][] = (float)$r['gwa'];
                }
                foreach ($progStudents as $st) {
                    $sn = $st['student_number'];
                    if (!empty($gwas[$sn])) {
                        $val = array_sum($gwas[$sn]) / count($gwas[$sn]);
                        $studentValuesAssoc[$sn] = $val;
                        $studentValues[] = $val;
                    }
                }
            }
            $context = "Data Set is composed of Student's GWA (all available years/semesters)";
            $label = "GWA (average)";
            break;

        case 'BoardGrades':
            // Aggregate all board subject grades for each student (ignore sub-metric selection)
            if (!empty($progStudentNumbersAcad)) {
                $in = str_repeat('?,', count($progStudentNumbersAcad) - 1) . '?';
                $stmt = $con->prepare("SELECT student_number, subject_grade FROM student_board_subjects_grades WHERE student_number IN ($in)");
                $stmt->execute($progStudentNumbersAcad);
                $grades = [];
                while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $grades[$r['student_number']][] = (float)$r['subject_grade'];
                }
                foreach ($progStudents as $st) {
                    $sn = $st['student_number'];
                    if (!empty($grades[$sn])) {
                        $val = array_sum($grades[$sn]) / count($grades[$sn]);
                        $studentValuesAssoc[$sn] = $val;
                        $studentValues[] = $val;
                    }
                }
            }
            $context = "Data Set is composed of Student's Board Grades (all subjects)";
            $label = "Board Subject Grades (average grades)";
            break;

        case 'Retakes':
            // Aggregate all retake rows per student (ignore sub-metric selection)
            if (!empty($progStudentNumbersAcad)) {
                $in = str_repeat('?,', count($progStudentNumbersAcad) - 1) . '?';
                $stmt = $con->prepare("SELECT student_number, terms_repeated FROM student_back_subjects WHERE student_number IN ($in)");
                $stmt->execute($progStudentNumbersAcad);
                $retakes = [];
                while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $retakes[$r['student_number']][] = (float)$r['terms_repeated'];
                }

                foreach ($progStudents as $st) {
                    $sn = $st['student_number'];
                    if (!empty($retakes[$sn])) {
                        $val = array_sum($retakes[$sn]) / count($retakes[$sn]);
                        $studentValuesAssoc[$sn] = $val;
                        $studentValues[] = $val;
                    }
                }
            }
            $context = "Data Set is composed of Student's Amount of Retakes (all records)";
            $label = "Retakes (average)";
            break;

        case 'PerformanceRating':
            // Aggregate all performance rating entries per student
            if (!empty($progStudentNumbersAcad)) {
                $in = str_repeat('?,', count($progStudentNumbersAcad) - 1) . '?';
                $stmt = $con->prepare("SELECT student_number, rating FROM student_performance_rating WHERE student_number IN ($in)");
                $stmt->execute($progStudentNumbersAcad);
                $ratings = [];
                while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $ratings[$r['student_number']][] = (float)$r['rating'];
                }
                foreach ($progStudents as $st) {
                    $sn = $st['student_number'];
                    if (!empty($ratings[$sn])) {
                        $val = array_sum($ratings[$sn]) / count($ratings[$sn]);
                        $studentValuesAssoc[$sn] = $val;
                        $studentValues[] = $val;
                    }
                }
            }
            $context = "Data Set is composed of Student's Performance Ratings (all categories)";
            $label = "Performance Rating (average)";
            break;

        case 'SimExam':
            // Aggregate all simulation exam rows per student and compute percentage per entry
            if (!empty($progStudentNumbersAcad)) {
                $in = str_repeat('?,', count($progStudentNumbersAcad) - 1) . '?';
                $stmt = $con->prepare("SELECT student_number, student_score, total_score FROM student_simulation_exam WHERE student_number IN ($in)");
                $stmt->execute($progStudentNumbersAcad);
                $simmap = [];
                while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $total = (float)$r['total_score'];
                    $pct = $total > 0 ? ((float)$r['student_score'] / $total) * 100 : 0;
                    $simmap[$r['student_number']][] = $pct;
                }
                foreach ($progStudents as $st) {
                    $sn = $st['student_number'];
                    if (!empty($simmap[$sn])) {
                        $val = array_sum($simmap[$sn]) / count($simmap[$sn]);
                        $studentValuesAssoc[$sn] = $val;
                        $studentValues[] = $val;
                    }
                }
            }
            $context = "Data Set is composed of Student's Simulation Exam scores (all simulations)";
            $label = "Simulation Exam Performance (average)";
            break;

        case 'Attendance':
            $attByBatch = getBatchAttendance($con, $progStudentBatchUnique, $progStudentNumbersAcad, $progStudentMapBatch);
            foreach ($progStudents as $st) {
                $bid = $st['batch_id'];
                if (isset($attByBatch[$bid])) {
                    $val = (float)$attByBatch[$bid];
                    $studentValuesAssoc[$st['student_number']] = $val;
                    $studentValues[] = $val;
                }
            }
            $context = "Data Set is composed of Student's Review Classes Attendance in Percentage";
            $label = "Review Sessions Attendance (average)";
            break;

        case 'MockScores':
            // Aggregate mock scores by batch_id (this table stores batch-level rows), then map to students
            if (!empty($progStudentBatchUnique)) {
                $in = str_repeat('?,', count($progStudentBatchUnique) - 1) . '?';
                $stmt = $con->prepare("SELECT batch_id, student_score, total_score FROM student_mock_board_scores WHERE batch_id IN ($in)");
                $stmt->execute($progStudentBatchUnique);
                $mockmap = [];
                while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $total = (float)$r['total_score'];
                    $pct = $total > 0 ? ((float)$r['student_score'] / $total) * 100 : 0;
                    $mockmap[$r['batch_id']][] = $pct;
                }
                foreach ($progStudents as $st) {
                    $bid = $st['batch_id'];
                    if (!empty($mockmap[$bid])) {
                        $val = array_sum($mockmap[$bid]) / count($mockmap[$bid]);
                        $studentValuesAssoc[$st['student_number']] = $val;
                        $studentValues[] = $val;
                    }
                }
            }
            $context = "Data Set is composed of Student's Mock Exam scores (all mock subjects)";
            $label = "Mock Scores by Subject (average)";
            break;

        case 'TakeAttempt':
            // Use existing helper: returns flat array of attempts per student for given program
            $attempts = getBatchAttempts($con, $prog['college_id'] ?? null, $progId, $filter_year_batch, $filter_board_batch ?? null);
            foreach ($attempts as $k => $a) { $studentValuesAssoc[$k] = (float)$a; $studentValues[] = (float)$a; }
            $context = "Data Set is composed of Student's Attempts";
            $label = "Board Exam Attempts (average)";
            break;

        default:
            // unsupported metric for program-averages yet
            break;
    }

    // store per-program student values for debugging
    $programStudentValues[$progId] = $studentValuesAssoc;

    // compute means: present-only (students with data) and including-missing
    $totalStudents = count($progStudents);
    $presentCount = count($studentValues);
    $sumPresent = array_sum($studentValues);
    $mean_present = $presentCount > 0 ? ($sumPresent / $presentCount) : null;
    // including-missing treats missing student values as 0 and divides by total students
    $mean_including_missing = $totalStudents > 0 ? ($sumPresent / $totalStudents) : null;

    // populate debug info for this program (now includes both means)
    $debugInfo[$progId] = [
        'student_count' => $totalStudents,
        'student_values_count' => $presentCount,
        'student_values_assoc_count' => count($studentValuesAssoc),
        'mean_present' => $mean_present,
        'mean_including_missing' => $mean_including_missing,
        'sample_student_values' => array_slice($studentValuesAssoc, 0, 5, true)
    ];

    // by request: include everyone in the program when computing program average
    // so we export the mean that divides by total students (missing -> 0)
    $programAverages[$progId] = $mean_including_missing;
}

// Prepare dataset: keep all programs in consolidatedData (use null when missing)
// but compute statistics only on non-null program averages.
$dataset_for_stats = [];
$consolidatedData = [];
foreach ($programsList as $p) {
    $pid = $p['program_id'];
    $pname = $p['program_name'] ?? $pid;
    $avg = $programAverages[$pid] ?? 0;
    if ($avg !== null) {
        $dataset_for_stats[] = (float)$avg;
        $consolidatedData[] = ['label' => $pname, 'y' => (float)$avg];
    } else {
        // include program with null value so charts/tables can show it
        $consolidatedData[] = ['label' => $pname, 'y' => null];
    }
}

// set context if empty
if (empty($context)) $context = 'Program averages for selected metric';

// Now compute stats on the program averages dataset
$tool = "Descriptive Statistics";
// compute stats only on programs that have numeric averages
$stats = descriptiveStats($dataset_for_stats);
//$summary = generateReport($stats, $context, $tool, $groq);

        

// Build an HTML table that shows every program and its average (blank when missing)
$htmlDisplay = '
                                <h3>Data Set</h3>
                                <table class="report-table">
                                    <thead>
                                        <tr><th>Program</th><th style="text-align:center;">' . $label . '</th></tr>
                                    </thead>
                                    <tbody>
                            ';

                                // show in the same order as programsList
                                foreach ($consolidatedData as $row) {
                                        $pname = htmlspecialchars($row['label']);
                                        $val = is_null($row['y']) ? '0' : number_format((float)$row['y'], 4);
                                        $htmlDisplay .= "<tr><td>{$pname}</td><td style=\"text-align:center;\">{$val}</td></tr>";
                                }

$htmlDisplay .= '
                </tbody>
                </table>

                  <h3>Descriptive Statistics</h3>
                  <table class="report-table">
                    <tr><th>Metric</th><th>Value</th></tr>
                    <tr><td>Count</td><td>' . $stats["count"] . '</td></tr>
                    <tr><td>Mean</td><td>' . $stats["mean"] . '</td></tr>
                    <tr><td>Median</td><td>' . $stats["median"] . '</td></tr>
                    <tr><td>Minimum</td><td>' . $stats["min"] . '</td></tr>
                    <tr><td>Maximum</td><td>' . $stats["max"] . '</td></tr>
                    <tr><td>Std. Deviation</td><td>' . $stats["stdDev"] . '</td></tr>
                    <tr><td>Variance</td><td>' . $stats["variance"] . '</td></tr>
                  </table>
                  <h3>Summary</h3>
                ';

    echo json_encode([
        'htmlDisplay' => $htmlDisplay,
        'consolidatedData' => $consolidatedData,
        'students' => $studentsList,
        'debug' => [
            'programAverages' => $programAverages,
            'programStudentValues' => $programStudentValues
        ,
            'info' => $debugInfo,
            'received' => [ 'metric' => $metric, 'yearBatch' => $filter_year_batch ]
        ]
    ], JSON_NUMERIC_CHECK)
            
?>