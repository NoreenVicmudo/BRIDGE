<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";
require_once __DIR__ . "/functions.php";
header('Content-Type: application/json');

function getBatch($con, $college, $program, $filter_year_batch, $filter_board_batch): array {

    //echo json_encode([$college, $program, $filter_year_batch, $filter_board_batch]);

    $query = "SELECT
            si.student_id,
            bb.batch_id,
            si.student_number,
            si.student_fname,
            si.student_mname,
            si.student_lname,
            si.student_suffix,
            si.student_sex
        FROM
            student_info AS si
        LEFT JOIN
            board_batch AS bb ON si.student_number = bb.student_number
        WHERE
            si.is_active = 1  AND bb.is_active = 1";

    $params = [];

    // Append filters based on user input and session level
    if (!empty($filter_year_batch) && $filter_year_batch !== 'none') {
        $query .= " AND bb.year = ?";
        $params[] = $filter_year_batch;
    }

    // College filter logic is now based on session level
    if ($_SESSION['level'] == 3) {
        // Level 2 users are restricted to their assigned college
        $query .= " AND si.student_college = ?";
        $params[] = $_SESSION['college'];
    } else if (($_SESSION['level'] == 1 || $_SESSION['level'] == 2) && !empty($college) && $college !== 'none') {
        // Level 1 users can filter within their assigned college
        $query .= " AND si.student_college = ?";
        $params[] = $college;
    } else if ($_SESSION['level'] == 0 && !empty($college) && $college !== 'none') {
        // Level 0 users can filter any college
        $query .= " AND si.student_college = ?";
        $params[] = $college;
    }

    // Program filter logic is also based on session level
    if ($_SESSION['level'] == 3) {
        // Level 2 users are restricted to their assigned program
        $query .= " AND bb.program_id = ? AND si.student_program = ?";
        $params[] = $_SESSION['program'];
        $params[] = $_SESSION['program'];
    } else if (($_SESSION['level'] == 1 || $_SESSION['level'] == 2) && !empty($program) && $program !== 'none') {
        // Level 1 users can filter any program within their college
        $query .= " AND bb.program_id = ? AND si.student_program = ?";
        $params[] = $program;
        $params[] = $program;
    } else if ($_SESSION['level'] == 0 && !empty($program) && $program !== 'none') {
        // Level 0 users can filter any program
        $query .= " AND bb.program_id = ? AND si.student_program = ?";
        $params[] = $program;
        $params[] = $program;
    }

    if (!empty($filter_board_batch) && $filter_board_batch !== 'none') {
        $query .= " AND bb.batch_number = ?";
        $params[] = $filter_board_batch;
    }

        $query .= " ORDER BY si.student_number";

        
        $studentStmt = $con->prepare($query);
        $studentStmt->execute($params);
        $students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

    return $students;
}

function getBatchRange($con, $college, $program, $yearStart, $yearEnd, $filter_board_batch): array {
    $query = "SELECT
            si.student_id,
            bb.batch_id,
            si.student_number,
            si.student_fname,
            si.student_mname,
            si.student_lname,
            si.student_suffix,
            bb.year
        FROM
            student_info AS si
        LEFT JOIN
            board_batch AS bb ON si.student_number = bb.student_number
        WHERE
            si.is_active = 1  AND bb.is_active = 1";

    $params = [];

    // Year range filter
    if (!empty($yearStart) && !empty($yearEnd)) {
        $query .= " AND bb.year BETWEEN ? AND ?";
        $params[] = $yearStart;
        $params[] = $yearEnd;
    }

    // College filter
    if ($_SESSION['level'] == 3) {
        $query .= " AND si.student_college = ?";
        $params[] = $_SESSION['college'];
    } else if (($_SESSION['level'] == 1 || $_SESSION['level'] == 2) && !empty($college) && $college !== 'none') {
        $query .= " AND si.student_college = ?";
        $params[] = $college;
    } else if ($_SESSION['level'] == 0 && !empty($college) && $college !== 'none') {
        $query .= " AND si.student_college = ?";
        $params[] = $college;
    }

    // Program filter
    if ($_SESSION['level'] == 3) {
        $query .= " AND bb.program_id = ? AND si.student_program = ?";
        $params[] = $_SESSION['program'];
        $params[] = $_SESSION['program'];
    } else if (($_SESSION['level'] == 1 || $_SESSION['level'] == 2) && !empty($program) && $program !== 'none') {
        $query .= " AND bb.program_id = ? AND si.student_program = ?";
        $params[] = $program;
        $params[] = $program;
    } else if ($_SESSION['level'] == 0 && !empty($program) && $program !== 'none') {
        $query .= " AND bb.program_id = ? AND si.student_program = ?";
        $params[] = $program;
        $params[] = $program;
    }

    // Optional board batch
    if (!empty($filter_board_batch) && $filter_board_batch !== 'none') {
        $query .= " AND bb.batch_number = ?";
        $params[] = $filter_board_batch;
    }

    $query .= " ORDER BY bb.year, si.student_number";

    $studentStmt = $con->prepare($query);
    $studentStmt->execute($params);
    $students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

    return $students;
}

function getBatchAge($con, $studentNumbers, $studentNumbersAcad, $studentMap): array {
        $ageByBatch = [];
        foreach ($studentNumbers as $batchId) {
            $ageByBatch[$batchId] = 0;
        }

        if (!empty($studentNumbersAcad)) {
            $in = str_repeat('?,', count($studentNumbersAcad) - 1) . '?';
            $ageStmt = $con->prepare("
                SELECT student_number, student_birthdate
                FROM student_info
                WHERE student_number IN ($in) AND is_active = 1
            ");
            // append subject ID at the end
            $ageStmt->execute($studentNumbersAcad);

            while ($row = $ageStmt->fetch(PDO::FETCH_ASSOC)) {
                $birthdate = new DateTime($row['student_birthdate']);
                $today = new DateTime();
                $age = $today->diff($birthdate)->y;

                $studentNo = $row['student_number'];
                $studentAge = $age;

                // Check if the student number exists in the map
                // Assuming $studentMap[$studentNo] is now an array of batch IDs
                if (isset($studentMap[$studentNo])) {
                    
                    // Retrieve all batch IDs associated with this student number
                    $batchIds = $studentMap[$studentNo];

                    // **Iterate over all associated batch IDs**
                    foreach ($batchIds as $batchId) {
                        // Check if this batch ID is one we are tracking (initialized earlier)
                        if (isset($ageByBatch[$batchId])) {
                            $ageByBatch[$batchId] = $studentAge;
                        }
                    }
                }
            }
            return $ageByBatch;

        } else {
            return [];
        }

}

function getBatchGender($con, $studentNumbers,array $studentNumbersAcad, $studentMap): array {
    $genderByBatch = [];
    // Initialize gender data for all unique batch IDs provided in $studentNumbers
    // assuming $studentNumbers contains the list of all relevant batch IDs
    foreach ($studentNumbers as $batchId) {
        // I'll keep 0 for now based on your original code.
        $genderByBatch[$batchId] = "UNSPECIFIED"; 
    }

    if (!empty($studentNumbersAcad)) {
        // Prepare IN clause for the SQL query
        $in = str_repeat('?,', count($studentNumbersAcad) - 1) . '?';
        $genderStmt = $con->prepare("
            SELECT student_number, student_sex
            FROM student_info
            WHERE student_number IN ($in) AND is_active = 1
        ");
        
        // Execute the statement
        $genderStmt->execute($studentNumbersAcad);

        while ($row = $genderStmt->fetch(PDO::FETCH_ASSOC)) {
            $studentNo = $row['student_number'];
            $studentGender = strtoupper(trim($row['student_sex']));

            // Check if the student number exists in the map
            // Assuming $studentMap[$studentNo] is now an array of batch IDs
            if (isset($studentMap[$studentNo])) {
                
                // Retrieve all batch IDs associated with this student number
                $batchIds = $studentMap[$studentNo];

                // **Iterate over all associated batch IDs**
                foreach ($batchIds as $batchId) {
                    // Check if this batch ID is one we are tracking (initialized earlier)
                    if (isset($genderByBatch[$batchId])) {
                        // Store the gender for this specific batch ID
                        // This ensures that the gender is saved for every batch
                        // the student is linked to.
                        $genderByBatch[$batchId] = $studentGender;
                    }
                }
            }
        }
        return $genderByBatch;

    } else {
        return [];
    }
}

function getBatchSocioeconomicStatus($con, $studentNumbers, $studentNumbersAcad, $studentMap): array {
    $statusStmt = $con->query('SELECT status, minimum, maximum FROM socioeconomic_status');
    $incomeBrackets = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

        // Initialize all batch IDs with score = 0
        $scoresByBatch = [];
        foreach ($studentNumbers as $batchId) {
            $scoresByBatch[$batchId] = 0;
        }

        if (!empty($studentNumbersAcad)) {
            $in = str_repeat('?,', count($studentNumbersAcad) - 1) . '?';
            $gradeStmt = $con->prepare("
                SELECT student_number, student_socioeconomic
                FROM student_info
                WHERE student_number IN ($in) AND is_active = 1
            ");
            // append subject ID at the end
            $gradeStmt->execute($studentNumbersAcad);

            while ($row = $gradeStmt->fetch(PDO::FETCH_ASSOC)) {
                $studentNo = $row['student_number'];
                $score = (float)$row['student_socioeconomic'];

            if (isset($studentMap[$studentNo])) {
                $batchIds = $studentMap[$studentNo];

                // **Iterate over all associated batch IDs**
                foreach ($batchIds as $batchId) {
                    // Check if this batch ID is one we are tracking (initialized earlier)
                    if (isset($scoresByBatch[$batchId])) {
                        $scoresByBatch[$batchId] = $score;
                    }
                }
            }
        }
            return $scoresByBatch;

        } else {
            return [];
        }
}

function getBatchArrangement($con, $studentNumbers, $studentNumbersAcad, $studentMap): array {
        $arrangementByBatch = [];
        foreach ($studentNumbers as $batchId) {
            $arrangementByBatch[$batchId] = 0;
        }

        if (!empty($studentNumbersAcad)) {
            $in = str_repeat('?,', count($studentNumbersAcad) - 1) . '?';
            $arrangementStmt = $con->prepare("
                SELECT student_number, student_living
                FROM student_info
                WHERE student_number IN ($in) AND is_active = 1
            ");
            // append subject ID at the end
            $arrangementStmt->execute($studentNumbersAcad);

            while ($row = $arrangementStmt->fetch(PDO::FETCH_ASSOC)) {
                $studentNo = $row['student_number'];
                $studentLiving = $row['student_living'];

                if (isset($studentMap[$studentNo])) {
                    $batchIds = $studentMap[$studentNo];

                    // **Iterate over all associated batch IDs**
                    foreach ($batchIds as $batchId) {
                        // Check if this batch ID is one we are tracking (initialized earlier)
                        if (isset($arrangementByBatch[$batchId])) {
                            $arrangementByBatch[$batchId] = $studentLiving;
                        }
                    }
                }
            }
            return $arrangementByBatch;

        } else {
            return [];
        }

}

function getBatchWork($con, $studentNumbers, $studentNumbersAcad, $studentMap): array {
        $workByBatch = [];
        foreach ($studentNumbers as $batchId) {
            $workByBatch[$batchId] = 0;
        }

        if (!empty($studentNumbersAcad)) {
            $in = str_repeat('?,', count($studentNumbersAcad) - 1) . '?';
            $workStmt = $con->prepare("
                SELECT student_number, student_work
                FROM student_info
                WHERE student_number IN ($in) AND is_active = 1
            ");
            // append subject ID at the end
            $workStmt->execute($studentNumbersAcad);

            while ($row = $workStmt->fetch(PDO::FETCH_ASSOC)) {
                $studentNo = $row['student_number'];
                $studentWork = strtoupper(trim($row['student_work']));

                if (isset($studentMap[$studentNo])) {
                    $batchIds = $studentMap[$studentNo];

                    // **Iterate over all associated batch IDs**
                    foreach ($batchIds as $batchId) {
                        // Check if this batch ID is one we are tracking (initialized earlier)
                        if (isset($workByBatch[$batchId])) {
                            $workByBatch[$batchId] = $studentWork;
                        }
                    }
                }
            }
            return $workByBatch;

        } else {
            return [];
        }

}

function getBatchScholarship($con, $studentNumbers, $studentNumbersAcad, $studentMap): array {
        $scholarshipByBatch = [];
        foreach ($studentNumbers as $batchId) {
            $scholarshipByBatch[$batchId] = 0;
        }

        if (!empty($studentNumbersAcad)) {
            $in = str_repeat('?,', count($studentNumbersAcad) - 1) . '?';
            $scholarshipStmt = $con->prepare("
                SELECT student_number, student_scholarship
                FROM student_info
                WHERE student_number IN ($in) AND is_active = 1
            ");
            // append subject ID at the end
            $scholarshipStmt->execute($studentNumbersAcad);

            while ($row = $scholarshipStmt->fetch(PDO::FETCH_ASSOC)) {
                $studentNo = $row['student_number'];
                $studentScholarship = strtoupper(trim($row['student_scholarship']));

                if (isset($studentMap[$studentNo])) {
                    $batchIds = $studentMap[$studentNo];

                    // **Iterate over all associated batch IDs**
                    foreach ($batchIds as $batchId) {
                        // Check if this batch ID is one we are tracking (initialized earlier)
                        if (isset($scholarshipByBatch[$batchId])) {
                            $scholarshipByBatch[$batchId] = $studentScholarship;
                        }
                    }
                }
            }
            return $scholarshipByBatch;

        } else {
            return [];
        }

}

function getBatchLanguage($con, $studentNumbers, $studentNumbersAcad, $studentMap): array {
        $languageByBatch = [];
        foreach ($studentNumbers as $batchId) {
            $languageByBatch[$batchId] = 0;
        }

        if (!empty($studentNumbersAcad)) {
            $in = str_repeat('?,', count($studentNumbersAcad) - 1) . '?';
            $languageStmt = $con->prepare("
                SELECT student_number, student_language
                FROM student_info
                WHERE student_number IN ($in) AND is_active = 1
            ");
            // append subject ID at the end
            $languageStmt->execute($studentNumbersAcad);

            while ($row = $languageStmt->fetch(PDO::FETCH_ASSOC)) {
                $studentNo = $row['student_number'];
                $studentLanguage = $row['student_language'];

                if (isset($studentMap[$studentNo])) {
                    $batchIds = $studentMap[$studentNo];

                    // **Iterate over all associated batch IDs**
                    foreach ($batchIds as $batchId) {
                        // Check if this batch ID is one we are tracking (initialized earlier)
                        if (isset($languageByBatch[$batchId])) {
                            $languageByBatch[$batchId] = $studentLanguage;
                        }
                    }
                }
            }
            return $languageByBatch;

        } else {
            return [];
        }
}

function getBatchYear($con, $studentNumbers, $studentNumbersAcad, $studentMap): array {
    $yearByBatch = [];
    foreach ($studentNumbers as $batchId) {
        $yearByBatch[$batchId] = 0;
    }

    // If no academic student numbers provided, return empty
    if (empty($studentNumbers)) return [];

    // We'll query the board_batch table for student_number -> year mapping
    $in = str_repeat('?,', count($studentNumbers) - 1) . '?';
    $stmt = $con->prepare("SELECT batch_id, year FROM board_batch WHERE batch_id IN ($in) AND is_active = 1");
    $stmt->execute($studentNumbers);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $year = (int)$row['year'];
        $yearByBatch[$row['batch_id']] = $year;
    }
    return $yearByBatch;
}

function getBatchSchool($con, $studentNumbers, $studentNumbersAcad, $studentMap): array {
        $schoolByBatch = [];
        foreach ($studentNumbers as $batchId) {
            $schoolByBatch[$batchId] = 0;
        }

        if (!empty($studentNumbersAcad)) {
            $in = str_repeat('?,', count($studentNumbersAcad) - 1) . '?';
            $schoolStmt = $con->prepare("
                SELECT student_number, student_last_school
                FROM student_info
                WHERE student_number IN ($in) AND is_active = 1
            ");
            // append subject ID at the end
            $schoolStmt->execute($studentNumbersAcad);

            while ($row = $schoolStmt->fetch(PDO::FETCH_ASSOC)) {
                $studentNo = $row['student_number'];
                $studentSchool = strtoupper(trim($row['student_last_school']));

                if (isset($studentMap[$studentNo])) {
                    $batchIds = $studentMap[$studentNo];

                    // **Iterate over all associated batch IDs**
                    foreach ($batchIds as $batchId) {
                        // Check if this batch ID is one we are tracking (initialized earlier)
                        if (isset($schoolByBatch[$batchId])) {
                            $schoolByBatch[$batchId] = $studentSchool;
                        }
                    }
                }
            }
            return $schoolByBatch;

        } else {
            return [];
        }
}

function getBatchGWA($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric): array {
    // Initialize all batch IDs with score = 0
    $scoresByBatch = [];
    foreach ($studentNumbers as $batchId) {
        $scoresByBatch[$batchId] = 0;
    }

    $map = parseGwaHeader($sub_metric);
    list($year_level, $semester) = $map;

    // Check if the overall average is requested
    $average_all = (strtoupper($year_level) === 'ALL' || strtoupper($semester) === 'ALL');

    if (!empty($studentNumbersAcad)) {
        $in = str_repeat('?,', count($studentNumbersAcad) - 1) . '?';

        if ($average_all) {
            // **Query for ALL GWA and calculate the average per student**
            $gradeStmt = $con->prepare("
                SELECT student_number, AVG(gwa) as gwa_average
                FROM student_gwa
                WHERE student_number IN ($in)
                GROUP BY student_number
            ");
            // Execute with only student numbers
            $gradeStmt->execute($studentNumbersAcad);

        } else {
            // **Original Query for a specific year_level and semester**
            $gradeStmt = $con->prepare("
                SELECT student_number, gwa
                FROM student_gwa
                WHERE student_number IN ($in) AND year_level = ? AND semester = ?
            ");
            // Execute with student numbers, year_level, and semester
            $gradeStmt->execute(array_merge($studentNumbersAcad, [$year_level, $semester]));
        }

        while ($row = $gradeStmt->fetch(PDO::FETCH_ASSOC)) {
            $studentNo = $row['student_number'];
            // Use 'gwa_average' if averaging, otherwise use 'gwa'
            $score = (float)($average_all ? $row['gwa_average'] : $row['gwa']);

            if (isset($studentMap[$studentNo])) {
                $batchIds = $studentMap[$studentNo];

                // Iterate over all associated batch IDs
                foreach ($batchIds as $batchId) {
                    // Check if this batch ID is one we are tracking (initialized earlier)
                    if (isset($scoresByBatch[$batchId])) {
                        $scoresByBatch[$batchId] = $score;
                    }
                }
            }
        }
        return $scoresByBatch;

    } else {
        return [];
    }
}

function getBatchBoardGrades($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric): array {
    // Initialize all batch IDs with score = 0
        $scoresByBatch = [];
        foreach ($studentNumbers as $batchId) {
            $scoresByBatch[$batchId] = 0;
        }

        if (!empty($studentNumbersAcad)) {
            $in = str_repeat('?,', count($studentNumbersAcad) - 1) . '?';
            $gradeStmt = $con->prepare("
                SELECT student_number, subject_grade
                FROM student_board_subjects_grades
                WHERE student_number IN ($in) AND subject_id = ?
            ");
            // append subject ID at the end
            $gradeStmt->execute(array_merge($studentNumbersAcad, [$sub_metric]));

            while ($row = $gradeStmt->fetch(PDO::FETCH_ASSOC)) {
                $studentNo = $row['student_number'];
                $score = (float)$row['subject_grade'];

                if (isset($studentMap[$studentNo])) {
                    $batchIds = $studentMap[$studentNo];

                    // **Iterate over all associated batch IDs**
                    foreach ($batchIds as $batchId) {
                        // Check if this batch ID is one we are tracking (initialized earlier)
                        if (isset($scoresByBatch[$batchId])) {
                            $scoresByBatch[$batchId] = $score;
                        }
                    }
                }
            }
            return $scoresByBatch;

        } else {
            return [];
        }
}

function getBatchRetakes($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric): array {
    // Initialize all batch IDs with score = 0
        $retakesByBatch = [];
        foreach ($studentNumbers as $batchId) {
            $retakesByBatch[$batchId] = 0;
        }

        if (!empty($studentNumbersAcad)) {
            $in = str_repeat('?,', count($studentNumbersAcad) - 1) . '?';
            $retakeStmt = $con->prepare("
                SELECT student_number, terms_repeated
                FROM student_back_subjects
                WHERE student_number IN ($in) AND general_subject_id = ?
            ");
            // append subject ID at the end
            $retakeStmt->execute(array_merge($studentNumbersAcad, [$sub_metric]));

            while ($row = $retakeStmt->fetch(PDO::FETCH_ASSOC)) {
                $studentNo = $row['student_number'];
                $retakes = (float)$row['terms_repeated'];

                if (isset($studentMap[$studentNo])) {
                    $batchIds = $studentMap[$studentNo];

                    // **Iterate over all associated batch IDs**
                    foreach ($batchIds as $batchId) {
                        // Check if this batch ID is one we are tracking (initialized earlier)
                        if (isset($retakesByBatch[$batchId])) {
                            $retakesByBatch[$batchId] = $retakes;
                        }
                    }
                }
            }
            return $retakesByBatch;

        } else {
            return [];
        }
}

function getBatchRating($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric): array {
    // Initialize all batch IDs with score = 0
        $scoresByBatch = [];
        foreach ($studentNumbers as $batchId) {
            $scoresByBatch[$batchId] = 0;
        }

        if (!empty($studentNumbersAcad)) {
            $in = str_repeat('?,', count($studentNumbersAcad) - 1) . '?';
            $gradeStmt = $con->prepare("
                SELECT student_number, rating
                FROM student_performance_rating
                WHERE student_number IN ($in) AND category_id = ?
            ");
            // append subject ID at the end
            $gradeStmt->execute(array_merge($studentNumbersAcad, [$sub_metric]));

            while ($row = $gradeStmt->fetch(PDO::FETCH_ASSOC)) {
                $studentNo = $row['student_number'];
                $score = (float)$row['rating'];

                if (isset($studentMap[$studentNo])) {
                    $batchIds = $studentMap[$studentNo];

                    // **Iterate over all associated batch IDs**
                    foreach ($batchIds as $batchId) {
                        // Check if this batch ID is one we are tracking (initialized earlier)
                        if (isset($scoresByBatch[$batchId])) {
                            $scoresByBatch[$batchId] = $score;
                        }
                    }
                }
            }
            return $scoresByBatch;

        } else {
            return [];
        }
}

function getBatchSimulation($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric): array {
    // Initialize all batch IDs with score = 0
        $scoresByBatch = [];
        foreach ($studentNumbers as $batchId) {
            $scoresByBatch[$batchId] = 0;
        }

        if (!empty($studentNumbersAcad)) {
            $in = str_repeat('?,', count($studentNumbersAcad) - 1) . '?';
            $gradeStmt = $con->prepare("
                SELECT student_number, student_score, total_score
                FROM student_simulation_exam
                WHERE student_number IN ($in) AND simulation_id = ?
            ");
            // append subject ID at the end
            $gradeStmt->execute(array_merge($studentNumbersAcad, [$sub_metric]));

            while ($row = $gradeStmt->fetch(PDO::FETCH_ASSOC)) {
                $studentNo = $row['student_number'];
                $score = (float)$row['student_score'];
                $total = (float)$row['total_score'];
                $final_score = $total > 0 ? ($score / $total) * 100 : 0;

                if (isset($studentMap[$studentNo])) {
                    $batchIds = $studentMap[$studentNo];

                    // **Iterate over all associated batch IDs**
                    foreach ($batchIds as $batchId) {
                        // Check if this batch ID is one we are tracking (initialized earlier)
                        if (isset($scoresByBatch[$batchId])) {
                            $scoresByBatch[$batchId] = $final_score;
                        }
                    }
                }
            }
            return $scoresByBatch;

        } else {
            return [];
        }
}

function getBatchAwards($con, $studentNumbers, $studentNumbersAcad, $studentMap): array {
    // Initialize all batch IDs with score = 0
        $scoresByBatch = [];
        foreach ($studentNumbers as $batchId) {
            $scoresByBatch[$batchId] = 0;
        }
        
        if (!empty($studentNumbersAcad)) {
            $in = str_repeat('?,', count($studentNumbersAcad) - 1) . '?';
            $gradeStmt = $con->prepare("
                SELECT student_number, award_count
                FROM student_academic_recognition
                WHERE student_number IN ($in) AND award_id = 2
            ");
            // append subject ID at the end
            $gradeStmt->execute($studentNumbersAcad);

            while ($row = $gradeStmt->fetch(PDO::FETCH_ASSOC)) {
                $studentNo = $row['student_number'];
                $awards = $row['award_count'];

                if (isset($studentMap[$studentNo])) {
                    $batchIds = $studentMap[$studentNo];

                    // **Iterate over all associated batch IDs**
                    foreach ($batchIds as $batchId) {
                        // Check if this batch ID is one we are tracking (initialized earlier)
                        if (isset($scoresByBatch[$batchId])) {
                            $scoresByBatch[$batchId] = $awards;
                        }
                    }
                }
            }
            return $scoresByBatch;

        } else {
            return [];
        }
}

function getBatchAttendance($con, $studentNumbers, $studentNumbersAcad, $studentMap): array {
    // Initialize all batch IDs with score = 0
        $scoresByBatch = [];
        foreach ($studentNumbers as $batchId) {
            $scoresByBatch[$batchId] = 0;
        }
        
        if (!empty($studentNumbersAcad)) {
            $in = str_repeat('?,', count($studentNumbersAcad) - 1) . '?';
            $gradeStmt = $con->prepare("
                SELECT student_number, sessions_attended, sessions_total
                FROM student_attendance_reviews
                WHERE student_number IN ($in)
            ");
            // append subject ID at the end
            $gradeStmt->execute($studentNumbersAcad);

            while ($row = $gradeStmt->fetch(PDO::FETCH_ASSOC)) {
                $studentNo = $row['student_number'];
                $score = (float)$row['sessions_attended'];
                $total = (float)$row['sessions_total'];
                $final_score = $total > 0 ? ($score / $total) * 100 : 0;

                if (isset($studentMap[$studentNo])) {
                    $batchIds = $studentMap[$studentNo];

                    // **Iterate over all associated batch IDs**
                    foreach ($batchIds as $batchId) {
                        // Check if this batch ID is one we are tracking (initialized earlier)
                        if (isset($scoresByBatch[$batchId])) {
                            $scoresByBatch[$batchId] = $final_score;
                        }
                    }
                }
            }
            return $scoresByBatch;

        } else {
            return [];
        }
}

function getBatchCenter($con, $studentNumbers, $studentNumbersAcad, $studentMap): array {
    // Initialize all batch IDs with score = 0
        $centerByBatch = [];
        foreach ($studentNumbers as $batchId) {
            $centerByBatch[$batchId] = 0;
        }

        if (!empty($studentNumbers)) {
            $in = str_repeat('?,', count($studentNumbers) - 1) . '?';
            $centerStmt = $con->prepare("
                SELECT batch_id, review_center
                FROM student_review_center
                WHERE batch_id IN ($in)
            ");
            // append subject ID at the end
            $centerStmt->execute($studentNumbers);

            while ($row = $centerStmt->fetch(PDO::FETCH_ASSOC)) {
                $studentResult = strtoupper(trim($row['review_center']));
                $centerByBatch[$row['batch_id']] = $studentResult;
            }
            return $centerByBatch;

        } else {
            return [];
        }
}

function getBatchMockScores($con, $studentNumbers, $studentNumbersAcad, $studentMap, $sub_metric): array {
    // Initialize all batch IDs with score = 0
        $scoresByBatch = [];
        foreach ($studentNumbers as $batchId) {
            $scoresByBatch[$batchId] = 0;
        }

        if (!empty($studentNumbers)) {
            $in = str_repeat('?,', count($studentNumbers) - 1) . '?';
            $gradeStmt = $con->prepare("
                SELECT batch_id, student_score, total_score
                FROM student_mock_board_scores
                WHERE batch_id IN ($in) AND mock_subject_id = ?
            ");
            // append subject ID at the end
            $gradeStmt->execute(array_merge($studentNumbers, [$sub_metric]));

            while ($row = $gradeStmt->fetch(PDO::FETCH_ASSOC)) {
                $score = (float)$row['student_score'];
                $total = (float)$row['total_score'];
                // store as raw score (or percentage if you prefer)

                $final_score = $total > 0 ? ($score / $total) * 100 : 0;
                $scoresByBatch[$row['batch_id']] = $final_score;
            }
            return $scoresByBatch;

        } else {
            return [];
        }
}

function getBatchResult($con, $studentNumbers, $studentNumbersAcad, $studentMap): array {
    // Initialize all batch IDs with score = 0
        $resultByBatch = [];
        foreach ($studentNumbers as $batchId) {
            $resultByBatch[$batchId] = 0;
        }

        if (!empty($studentNumbers)) {
            $in = str_repeat('?,', count($studentNumbers) - 1) . '?';
            $resultStmt = $con->prepare("
                SELECT batch_id, exam_result
                FROM student_licensure_exam
                WHERE batch_id IN ($in)
            ");
            // append subject ID at the end
            $resultStmt->execute($studentNumbers);

            while ($row = $resultStmt->fetch(PDO::FETCH_ASSOC)) {
                $studentResult = strtoupper(trim($row['exam_result']));
                $resultByBatch[$row['batch_id']] = $studentResult;
            }
            return $resultByBatch;

        } else {
            return [];
        }
}

function getBatchAttemptsCumulative($con, $college, $program, $yearStart, $yearEnd, $filter_board_batch): array {
    $base_query = "
            WITH attempts_per_interval AS ( -- Renamed CTE for clarity
                SELECT
                    bb.year,
                    bb.batch_number,
                    bb.batch_id,
                    si.student_number,
                    CONCAT(si.student_fname, ' ', si.student_lname) AS student_name,
                    COUNT(sl.exam_id) AS attempts_in_interval
                FROM student_info AS si
                INNER JOIN board_batch AS bb 
                    ON si.student_number = bb.student_number
                LEFT JOIN student_licensure_exam AS sl 
                    ON bb.batch_id = sl.batch_id
                WHERE si.is_active = 1
                AND bb.is_active = 1
        ";
        
        $params = [];

        // Apply all filtering logic to the base query
        // NOTE: Year range filter for the CUMULATIVE count is best applied later 
        // to ensure attempts from prior years are included in the running total.

        // College filter logic
        if (isset($_SESSION['level']) && $_SESSION['level'] == 2) {
            $base_query .= " AND si.student_college = ?";
            $params[] = $_SESSION['college'];
        } else if (isset($_SESSION['level']) && ($_SESSION['level'] == 1 || $_SESSION['level'] == 0) && !empty($college) && $college !== 'none') {
            $base_query .= " AND si.student_college = ?";
            $params[] = $college;
        }

        // Program filter
        if (isset($_SESSION['level']) && $_SESSION['level'] == 3) {
            $base_query .= " AND bb.program_id = ? AND si.student_program = ?";
            $params[] = $_SESSION['program'];
            $params[] = $_SESSION['program'];
        } else if (isset($_SESSION['level']) && ($_SESSION['level'] == 1 || $_SESSION['level'] == 2 || $_SESSION['level'] == 0) && !empty($program) && $program !== 'none') {
            $base_query .= " AND bb.program_id = ? AND si.student_program = ?";
            $params[] = $program;
            $params[] = $program;
        }

        // Group by Year, Batch Number, and Student to get attempts per interval
        $base_query .= "
                GROUP BY bb.year, bb.batch_number, si.student_number, si.student_fname, si.student_lname
            )
        ";

    // 2. Wrap the base query with the SUM() OVER() window function to calculate the running total.
    
    $base_query .= "SELECT
                batch_id,
                SUM(attempts_in_interval) OVER (
                    PARTITION BY student_number
                    ORDER BY year, batch_number -- **CRITICAL CHANGE: Added batch_number to ORDER BY**
                ) AS cumulative_attempt_count
            FROM attempts_per_interval";

        // Year range filter
        if (!empty($yearStart) && !empty($yearEnd)) {
            $base_query .= " WHERE year BETWEEN ? AND ?";
            $params[] = $yearStart;
            $params[] = $yearEnd;
        }

        // Optional board batch
        if (!empty($filter_board_batch) && $filter_board_batch !== 'none') {
            $base_query .= " AND batch_number = ?";
            $params[] = $filter_board_batch;
        }

    $query = $base_query . " ORDER BY year, student_number;";

        $stmt = $con->prepare($query);
        
        // Execute the statement with all collected parameters
        $stmt->execute($params);
        
        $dataset = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $batch_id = $row['batch_id'];
            $dataset[$batch_id] = (float)$row['cumulative_attempt_count'];
        }
        return $dataset;
}

function getGradeBracket(float $percentage): string {
    // Ensure the percentage is within a sensible 0-100 range, though the logic below handles limits.
    // We'll use a series of if-elseif-else statements for clear range checking.

    if ($percentage > 90.0) {
        return ">90%";
    } elseif ($percentage > 80.0 && $percentage <= 90.0) {
        // Technically, the upper bound is captured by the first condition, 
        // but including the check ensures clarity for the range 80.0 to 90.0 (inclusive).
        return "80%-90%";
    } elseif ($percentage > 70.0 && $percentage <= 80.0) {
        return "70%-80%";
    } elseif ($percentage > 50.0 && $percentage <= 70.0) {
        return "50%-70%";
    } elseif ($percentage <= 50.0 && $percentage >= 0.0) {
        // Assuming percentages are non-negative.
        return "<50%";
    } else {
        // For values outside the expected 0-100 range (e.g., negative).
        return "INVALID_GRADE";
    }
}

function getExpectedFrequency($categories, $expectedPercents) {
    $categories = array_keys($_POST['expected']);
    $expectedPercents = $_POST['expected'];

    // Observed counts
    global $studentResults;
    $observedCounts = getObservedFrequencies($categories, $studentResults);

    // Convert expected % → counts
    $total = count($studentResults);
    $expectedCounts = [];
    foreach ($categories as $cat) {
        $expectedCounts[$cat] = round($total * ($expectedPercents[$cat] / 100));
    }

    // Build results table
    echo "<h4>Frequencies</h4>
          <table border='1' cellpadding='6'>
            <tr><th>Category</th><th>Observed</th><th>Expected</th></tr>";
    foreach ($categories as $cat) {
        echo "<tr>
                <td>$cat</td>
                <td>{$observedCounts[$cat]}</td>
                <td>{$expectedCounts[$cat]}</td>
              </tr>";
    }
    echo "</table>";
    exit;
}

function getObservedFrequencies($categories, $studentResults) {
    $counts = array_fill_keys($categories, 0);
    foreach ($studentResults as $res) {
        if (isset($counts[$res])) {
            $counts[$res]++;
        }
    }
    return $counts;
}

function descriptiveStats(array $dataset): array {
    $count = count($dataset);
    if ($count === 0) return [
        'success' => false,
        'error' => 'Dataset is empty'
    ];

    // Sort the array for median calculation
    sort($dataset);
    
    $mean = array_sum($dataset) / $count;
    $min = min($dataset);
    $max = max($dataset);

    // Median calculation
    $middleIndex = floor($count / 2);
    $median = ($count % 2 == 0) 
        ? ($dataset[$middleIndex - 1] + $dataset[$middleIndex]) / 2 
        : $dataset[$middleIndex];

    // Calculate sum of squared differences for variance
    $sumOfSquares = 0.0;
    foreach ($dataset as $s) {
        $sumOfSquares += pow($s - $mean, 2);
    }
    
    // Corrected variance and standard deviation for a sample
    // Handle the case where count == 1 to avoid division by zero
    if ($count === 1) {
        $sampleVariance = 0.0;
        $sampleStdDev = 0.0;
    } else {
        $sampleVariance = $sumOfSquares / ($count - 1);
        $sampleStdDev = sqrt($sampleVariance);
    }

    return [
        'success' => true,
        'count' => $count,
        'mean' => round($mean, 4),
        'median' => $median,
        'min' => $min,
        'max' => $max,
        'stdDev' => round($sampleStdDev, 4),
        'variance' => round($sampleVariance, 4)
    ];
}

function linearRegression(array $x_data, array $y_data, array $group_name): array
{
    // Validate input data
    $n = count($x_data);
    if ($n !== count($y_data) || $n < 2) {
        return [
        'success' => false,
        'error' => 'Dataset is empty'
    ];
    }

    // Calculate the means of x and y
    $sum_x = array_sum($x_data); // Added for summary output
    $sum_y = array_sum($y_data); // Added for summary output
    $x_mean = $sum_x / $n;
    $y_mean = $sum_y / $n;

    // Variables for Slope and Pearson r
    $numerator_b1_r = 0.0; // Sum of Products (SP) = Σ(X - Mx)(Y - My)
    $denominator_b1 = 0.0; // Sum of Squares for X (SSx) = Σ(X - Mx)²
    $sum_y_diff_squared = 0.0; // Sum of Squares for Y (SSy) = Σ(Y - My)²
    $table_data = [];
    
    // First loop: Calculate all deviation components (SP, SSx, SSy)
    for ($i = 0; $i < $n; $i++) {
        $x_diff = $x_data[$i] - $x_mean;
        $y_diff = $y_data[$i] - $y_mean;
        
        $product_of_diffs = $x_diff * $y_diff;
        $x_diff_squared = pow($x_diff, 2);
        $y_diff_squared = pow($y_diff, 2);
        
        $numerator_b1_r += $product_of_diffs;
        $denominator_b1 += $x_diff_squared;
        $sum_y_diff_squared += $y_diff_squared;
        
        // Store intermediate steps for the table output
        $table_data[] = [
            'group' => $group_name[$i] ?? '',
            'x' => $x_data[$i],
            'y' => $y_data[$i],
            'x_minus_mean' => $x_diff,
            'y_minus_mean' => $y_diff,
            'x_diff_squared' => $x_diff_squared,
            'y_diff_squared' => $y_diff_squared,
            'product_of_diffs' => $product_of_diffs,
        ];
    }
    
    // Calculate the slope (b or b1)
    $b1 = ($denominator_b1 <= 0.5) ? 0 : $numerator_b1_r / $denominator_b1;

    // Calculate the y-intercept (a or b0)
    $b0 = $y_mean - $b1 * $x_mean;

    // Calculate the Pearson r value and R-squared
    $r_denominator = sqrt($denominator_b1) * sqrt($sum_y_diff_squared);
    $r_value = ($r_denominator == 0) ? 0 : $numerator_b1_r / $r_denominator;
    $r_squared = pow($r_value, 2);

    // Final result array
    return [
        'success' => true,
        'slope' => $b1,
        'intercept' => $b0,
        'r_value' => $r_value,
        'r_squared' => $r_squared,
        'n' => $n,
        'sum_x' => $sum_x,                     // New: Sum X
        'sum_y' => $sum_y,                     // New: Sum Y
        'x_mean' => $x_mean,
        'y_mean' => $y_mean,
        'sum_x_diff_squared' => $denominator_b1, // SSx (Sum of Squares for X)
        'sum_y_diff_squared' => $sum_y_diff_squared, // SSy (Sum of Squares for Y)
        'sum_product_of_diffs' => $numerator_b1_r, // SP (Sum of Products)
        'table_data' => $table_data,            // New: Detailed calculation table data
    ];
}

function chiSquareTOI(array $arr1, array $arr2): array 
{
    $n = min(count($arr1), count($arr2));
    $contingency = [];
    $rowTotals = [];
    $colTotals = [];
    $grandTotal = 0;

    // Build contingency table
    for ($i = 0; $i < $n; $i++) {
        $v1 = $arr1[$i];
        $v2 = $arr2[$i];
        if ($v1 === '' || $v2 === '') continue;

        if (!isset($contingency[$v1])) $contingency[$v1] = [];
        if (!isset($contingency[$v1][$v2])) $contingency[$v1][$v2] = 0;
        $contingency[$v1][$v2]++;

        $rowTotals[$v1] = ($rowTotals[$v1] ?? 0) + 1;
        $colTotals[$v2] = ($colTotals[$v2] ?? 0) + 1;
        $grandTotal++;
    }

    // Calculate Chi-Square
    $chi2 = 0.0;
    $expected = [];
    foreach ($contingency as $rowKey => $cols) {
        foreach ($colTotals as $colKey => $colTotal) {
            $observed = $cols[$colKey] ?? 0;
            $exp = ($rowTotals[$rowKey] * $colTotal) / $grandTotal;
            $expected[$rowKey][$colKey] = $exp;
            if ($exp > 0) {
                $chi2 += pow($observed - $exp, 2) / $exp;
            }
        }
    }

    $df = (count($rowTotals) - 1) * (count($colTotals) - 1);

    $sig = check_significance_chi($chi2, $df);

    if ($sig['success'] == true){
        return [
            "chi2" => $chi2,
            "df" => $df,
            "crit_value" => $sig['crit_value'],
            "significance" => $sig['significance'],
            "observed" => $contingency,
            "expected" => $expected,
            "rowTotals" => $rowTotals,
            "colTotals" => $colTotals,
            "n" => $grandTotal
        ];
    } else {
        return ["success" => $sig['success'],
                "error" => $sig['error']];
    }
}

function chiSquareGOF(array $observedCounts, array $expectedCounts): array 
{
    $chi2 = 0.0;
    
    // The Grand Total (N) is the sum of the observed counts
    $grandTotal = array_sum($observedCounts);
    
    // --- 1. Calculate Chi-Square (χ²) ---
    
    // Ensure we iterate over all categories present in the observed data
    foreach ($observedCounts as $catKey => $observed) {
        
        // Retrieve Expected Count (E). It must exist for a valid test.
        // Use a tiny non-zero value if E is missing or exactly 0 for safety.
        $exp = $expectedCounts[$catKey] ?? 0.00000001; 
        
        // Calculate the Chi-Square contribution: (O - E)^2 / E
        if ($exp > 0) {
            $chi2 += pow($observed - $exp, 2) / $exp;
        }
        
        // Note: Check the assumption E >= 5 in a production environment
    }

    // --- 2. Calculate Degrees of Freedom (df) ---
    // df = Number of Categories (k) - 1
    $df = count($observedCounts) - 1;

    // --- 3. Determine Significance (using your helper function) ---
    // This assumes check_significance is defined and handles the critical value lookup
    $sig = check_significance_chi($chi2, $df);

    if ($sig['success'] == true){
    // --- 4. Return All Relevant Calculated Values ---
        return [
            "chi2" => round($chi2, 4),
            "df" => $df,
            "crit_value" => $sig['crit_value'],
            "significance" => $sig['significance'],
            "observed" => $observedCounts,
            "expected" => $expectedCounts,
            "n" => $grandTotal
        ];
    } else {
        return ["success" => $sig['success'],
                "error" => $sig['error']];
    }
}

function chi_square_critical_values() : array {
    // Critical Values for alpha = 0.05, corresponding to df = 1 through 15
    return [
        3.841,   // df = 1
        5.991,   // df = 2
        7.815,   // df = 3
        9.488,   // df = 4
        11.070,  // df = 5
        12.592,  // df = 6
        14.067,  // df = 7
        15.507,  // df = 8
        16.919,  // df = 9
        18.307,  // df = 10
        19.675,  // df = 11
        21.026,  // df = 12
        22.362,  // df = 13
        23.685,  // df = 14
        24.996,   // df = 15
        26.296,   // df = 16
        27.587,   // df = 17
        28.869,   // df = 18
        30.144,   // df = 19
        31.410,  // df = 20
        32.671,  // df = 21
        33.924,  // df = 22
        35.172,  // df = 23
        36.415,  // df = 24
        37.652,  // df = 25
        38.885,  // df = 26
        40.113,  // df = 27
        41.337,  // df = 28
        42.557,  // df = 29
        43.773   // df = 30
    ];
}

function t_test_critical_values() : array {
    // Critical Values for alpha = 0.05, corresponding to df = 1 through 15
    return [
        1 => 12.706, 2 => 4.303, 3 => 3.182, 4 => 2.776, 5 => 2.571,
        6 => 2.447, 7 => 2.365, 8 => 2.306, 9 => 2.262, 10 => 2.228,
        11 => 2.201, 12 => 2.179, 13 => 2.160, 14 => 2.145, 15 => 2.131,
        16 => 2.120, 17 => 2.110, 18 => 2.101, 19 => 2.093, 20 => 2.086,
        25 => 2.060, 30 => 2.042, 40 => 2.021, 60 => 2.000, 120 => 1.980
    ];
}

function check_significance_chi($chi2_statistic, $df) : array {
    $critical_values = chi_square_critical_values();
    
    // Check if df is within the range (1 to 15)
    if ($df < 1 || $df > count($critical_values)) {
        return [
            "success" => false,
            "error" => "Cannot test significance: Degrees of Freedom (df) must be between 1 and " . count($critical_values)
        ];
    }
    
    // The critical value is at index (df - 1)
    $critical_value = $critical_values[$df - 1];
    
    // Perform the test
    if ($chi2_statistic > $critical_value) {
        // If the statistic is greater than the critical value, it falls in the rejection region.
        return [
            "success" => true,
            "crit_value" => $critical_value,
            "significance" =>"Result is **Statistically Significant** (p < 0.05). Reject Null Hypothesis."
        ];
    } else {
        // Fail to reject the Null Hypothesis.
        return [
            "success" => true,
            "crit_value" => $critical_value,
            "significance" => "Result is **Not Statistically Significant** (p > 0.05). Fail to Reject Null Hypothesis."
        ];
    }
}

function check_significance_t_test($tTest_statistic, $df) : array {
    $critical_values = t_test_critical_values();

    if (!is_int($df) || $df < 1) {
        return [
            "success" => false,
            "error" => "Cannot test significance: Degrees of Freedom (df) must be between 1 and " . count($critical_values)
        ];
    }

    // Try exact match first, otherwise find nearest smaller df available
    if (isset($critical_values[$df])) {
        $critical_value = $critical_values[$df];
    } else {
        // Find the largest key less than or equal to df
        $keys = array_keys($critical_values);
        rsort($keys);
        $found = null;
        foreach ($keys as $k) {
            if ($k <= $df) { $found = $k; break; }
        }
        $critical_value = $found ? $critical_values[$found] : null;
    }

    if ($critical_value === null) {
        return [
            'success' => true,
            'crit_value' => null,
            'significance' => 'Critical t-value not available for df=' . $df
        ];
    }

    if ($tTest_statistic > $critical_value) {
        return [
            'success' => true,
            'crit_value' => $critical_value,
            'significance' => 'Result is Statistically Significant (p < 0.05). Reject Null Hypothesis.'
        ];
    }

    return [
        'success' => true,
        'crit_value' => $critical_value,
        'significance' => 'Result is Not Statistically Significant (p > 0.05). Fail to Reject Null Hypothesis.'
    ];
}

function pearsonR(array $x_data, array $y_data, array $group_name): ?array
{
    $n = count($x_data);
    if ($n !== count($y_data) || $n < 2) {
        return [];
    }

    // Means
    $x_mean = array_sum($x_data) / $n;
    $y_mean = array_sum($y_data) / $n;

    $numerator = 0.0;
    $denominator_x = 0.0;
    $denominator_y = 0.0;
    $table_data = [];

    for ($i = 0; $i < $n; $i++) {
        $x_diff = $x_data[$i] - $x_mean;
        $y_diff = $y_data[$i] - $y_mean;

        $product_of_diffs = $x_diff * $y_diff;
        $x_diff_squared = pow($x_diff, 2);
        $y_diff_squared = pow($y_diff, 2);

        $table_data[] = [
            'group' => $group_name[$i] ?? '',
            'x' => $x_data[$i],
            'y' => $y_data[$i],
            'x_minus_mean' => $x_diff,
            'y_minus_mean' => $y_diff,
            'x_diff_squared' => $x_diff_squared,
            'y_diff_squared' => $y_diff_squared,
            'product_of_diffs' => $product_of_diffs,
        ];

        $numerator += $product_of_diffs;
        $denominator_x += $x_diff_squared;
        $denominator_y += $y_diff_squared;
    }

    $denominator = sqrt($denominator_x) * sqrt($denominator_y);
    $r_value = ($denominator == 0) ? 0 : $numerator / $denominator;

    $sig = check_significance_pearson($r_value, $n);

    if ($sig['success'] == true){
        return [
            'r_value' => $r_value,
            'x_mean' => $x_mean,
            'y_mean' => $y_mean,
            'table_data' => $table_data,
            'sum_product_of_diffs' => $numerator,
            'sum_x_diff_squared' => $denominator_x,
            'sum_y_diff_squared' => $denominator_y,
            'r_squared' => pow($r_value, 2),
            'n' => $n,
            'crit_value' => $sig['crit_value'],
            'significance' => $sig['message']
        ];
    } else {
        return ["success" => $sig['success'],
                "error" => $sig['error']];
    }
}

function check_significance_pearson(float $r_calculated, int $n): array {
    $critical_values_table = pearson_critical_values();
    $abs_r = abs($r_calculated);
    $alpha = 0.05;
    $df = $n - 2;

    // 1. Look up the critical value
    if (!isset($critical_values_table[$df]) || !isset($critical_values_table[$df][$alpha])) {
        // Fallback for degrees of freedom (df) not directly in the table
        return [
            'success' => false,
            'error' => "Critical value not found for df=$df and alpha=$alpha. Please check your table."
        ];
    }

    $critical_r = $critical_values_table[$df][$alpha];

    // 2. Compare the calculated 'r' to the critical value
    $is_significant = $abs_r >= $critical_r;

    // 3. Formulate the result message
    $status = $is_significant ? 'statistically significant' : 'not statistically significant';
    $conclusion = $is_significant ? 'Reject the Null Hypothesis (H₀: ρ = 0).' : 'Fail to Reject the Null Hypothesis (H₀: ρ = 0).';

    $message = "The Pearson 'r' ($r_calculated) is \"$status\" at alpha = $alpha (two-tailed) with df = $df.\n";
    $message .= "Critical value: $critical_r, Absolute 'r': $abs_r.\n";
    $message .= "Conclusion: $conclusion";

    return [
        'success' => true,
        'is_significant' => $is_significant ?? 0,
        'crit_value' => $critical_r ?? 0,
        'message' => $message ?? 0
    ];
}

function pearson_critical_values(){
    return [
    1 => [0.05 => 0.997],
    2 => [0.05 => 0.950],
    3 => [0.05 => 0.878],
    4 => [0.05 => 0.811],
    5 => [0.05 => 0.754],
    6 => [0.05 => 0.707],
    7 => [0.05 => 0.666],
    8 => [0.05 => 0.632],
    9 => [0.05 => 0.602],
    10 => [0.05 => 0.576],
    11 => [0.05 => 0.553],
    12 => [0.05 => 0.532],
    13 => [0.05 => 0.514],
    14 => [0.05 => 0.497],
    15 => [0.05 => 0.482],
    16 => [0.05 => 0.468],
    17 => [0.05 => 0.456],
    18 => [0.05 => 0.444],
    19 => [0.05 => 0.433],
    20 => [0.05 => 0.423],
    21 => [0.05 => 0.413],
    22 => [0.05 => 0.404],
    23 => [0.05 => 0.396],
    24 => [0.05 => 0.388],
    25 => [0.05 => 0.381],
    26 => [0.05 => 0.374],
    27 => [0.05 => 0.367],
    28 => [0.05 => 0.361],
    29 => [0.05 => 0.355],
    30 => [0.05 => 0.349],
    35 => [0.05 => 0.325],
    40 => [0.05 => 0.304],
    45 => [0.05 => 0.288],
    50 => [0.05 => 0.273],
    55 => [0.05 => 0.261],
    60 => [0.05 => 0.250],
    65 => [0.05 => 0.240],
    70 => [0.05 => 0.232],
    75 => [0.05 => 0.224],
    80 => [0.05 => 0.217],
    85 => [0.05 => 0.211],
    90 => [0.05 => 0.205],
    95 => [0.05 => 0.199],
    100 => [0.05 => 0.195],
];
}

function independent_t_test(array $group1, array $group2): array {
    $n1 = count($group1);
    $n2 = count($group2);

    if ($n1 < 2 || $n2 < 2) {
        return [
            "success" => false,
            "error" => "Each group must have at least 2 samples."
        ];
    }

    // Means
    $mean1 = array_sum($group1) / $n1;
    $mean2 = array_sum($group2) / $n2;

    // Deviations + SS for group 1
    $dev1 = [];
    $sq_dev1 = [];
    $SS1 = 0;
    foreach ($group1 as $x) {
        $d = $x - $mean1;
        $sq = pow($d, 2);
        $dev1[] = $d;
        $sq_dev1[] = $sq;
        $SS1 += $sq;
    }

    // Deviations + SS for group 2
    $dev2 = [];
    $sq_dev2 = [];
    $SS2 = 0;
    foreach ($group2 as $x) {
        $d = $x - $mean2;
        $sq = pow($d, 2);
        $dev2[] = $d;
        $sq_dev2[] = $sq;
        $SS2 += $sq;
    }

    // Variances
    $var1 = $SS1 / ($n1 - 1);
    $var2 = $SS2 / ($n2 - 1);

    // --- Pooled variance ---
    $sp2 = (($SS1 + $SS2) / ($n1 + $n2 - 2));

    // Standard error
    $se = sqrt($sp2 * (1/$n1 + 1/$n2));

    // t statistic
    $t = ($mean1 - $mean2) / $se;

    // Degrees of freedom
    $df = $n1 + $n2 - 2;

    $sig = check_significance_t_test($t, $df);

    if ($sig['success'] == true){
        return [
            // Data for full table
            "group1" => $group1,
            "group2" => $group2,
            "dev1" => $dev1,
            "sq_dev1" => $sq_dev1,
            "dev2" => $dev2,
            "sq_dev2" => $sq_dev2,
            "mean1" => $mean1,
            "mean2" => $mean2,
            "SS1" => $SS1,
            "SS2" => $SS2,
            "var1" => $var1,
            "var2" => $var2,
            "sp2" => $sp2,
            "se" => $se,
            "t_value" => $t,
            "df" => $df,
            "crit_value" => $sig['crit_value'],
            "significance" => $sig['significance']
        ];
    } else {
        return ["success" => $sig['success'],
                "error" => $sig['error']];
    }
}


function dependent_t_test($dataset1, $dataset2) {
    $n = count($dataset1);

    // 1. Basic validation
    if ($n < 2) {
        return [
            "success" => false,
            "error" => "Datasets must have at least two paired observations."
        ];
    }
    if ($n !== count($dataset2)) {
        return [
            "success" => false,
            "error" => "Both datasets must have the same number of elements."
        ];
    }

    // Step 2: Calculate Differences (d_i = dataset1_i - dataset2_i)
    $differences = [];
    for ($i = 0; $i < $n; $i++) {
        $differences[] = $dataset1[$i] - $dataset2[$i];
    }

    // Step 3: Mean of each dataset (for reporting)
    $mean1 = array_sum($dataset1) / $n;
    $mean2 = array_sum($dataset2) / $n;

    // Step 4: Mean of differences (d_bar)
    $meanDiff = array_sum($differences) / $n;

    // Step 5: Standard deviation of differences (s_d)
    // Formula: sqrt( sum((d - d_bar)^2) / (n - 1) )
    $deviations = [];
    $sqDevs = [];
    foreach ($differences as $d) {
        $dev = $d - $meanDiff;
        $deviations[] = $dev;
        $sqDevs[] = pow($dev, 2);
    }

    // Sum of squared deviations
    $SS = array_sum($sqDevs);

    // Variance of differences
    $variance = $SS / ($n - 1);

    // Handle case where n=1 (though caught by $n < 2 check, good practice)
    $sdDiff = ($n > 1) ? sqrt($variance) : NAN;

    // Step 6: Standard Error of the Mean Difference (SE)
    // Formula: s_d / sqrt(n)
    $se = ($sdDiff !== NAN && $n > 0) ? $sdDiff / sqrt($n) : NAN;

    // Step 7: t-value
    // Formula: d_bar / SE
    $t = ($se > 0) ? $meanDiff / $se : NAN;

    // Step 8: Degrees of freedom
    $df = $n - 1;

    $sig = check_significance_t_test($t, $df);

    // Note on p-value: PHP standard library does not include the t-distribution CDF
    // You must use the returned 't_value' and 'df' with a t-distribution table or statistical software 
    // to find the exact p-value.

    if ($sig['success'] == true){
        return [
            "n" => $n,
            "differences" => $differences,
            "mean_var1" => $mean1,
            "mean_var2" => $mean2,
            "mean_difference" => $meanDiff,
            "deviations" => $deviations,
            "sq_devs" => $sqDevs,
            "SS" => $SS,
            "variance" => $variance,
            "sd_difference" => $sdDiff,
            "se" => $se,
            "t_value" => $t,
            "df" => $df,
            "crit_value" => $sig['crit_value'],
            "significance" => $sig['significance']
        ];
    } else {
        return ["success" => $sig['success'],
                "error" => $sig['error']];
    }
}

function averagePerBatch(array $rawData, array $studentMap): array {
    $batchScores = [];

    foreach ($rawData as $studentNo => $values) {
        if (count($values) === 0) continue;

        // average this student's values
        $studentAvg = array_sum($values) / count($values);

        if (isset($studentMap[$studentNo])) {
            $batchId = $studentMap[$studentNo];
            $batchScores[$batchId][] = $studentAvg;
        }
    }

    // finalize per batch
    foreach ($batchScores as $batchId => $studentAvgs) {
        $batchScores[$batchId] = array_sum($studentAvgs) / count($studentAvgs);
    }

    return $batchScores;
}

function getMetricAverage(array $values): float {
    $values = array_filter($values, fn($v) => is_numeric($v)); // only numeric
    if (empty($values)) return 0;
    return array_sum($values) / count($values);
}
?>