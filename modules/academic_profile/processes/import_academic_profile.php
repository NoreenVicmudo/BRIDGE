<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";
require_once PROJECT_PATH . "/functions.php";

header('Content-Type: application/json');

$response = [
    'success'  => false,
    'inserted' => 0,
    'updated'  => 0,
    'skipped'  => 0,
    'processed'=> 0,
    'errors'   => []
];

try {
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $csvFile = $_FILES['file']['tmp_name'];

        if (($handle = fopen($csvFile, "r")) !== FALSE) {
            $form_type = $_SESSION['filter_metric'] ?? '';

            switch ($form_type) {
                case 'GWA':
                    // ✅ Preload valid students
                    $stmt = $con->prepare("
                        SELECT student_number 
                        FROM student_info 
                        WHERE student_program = :program AND is_active = 1
                    ");
                    $stmt->execute([':program' => $_SESSION['filter_program']]);
                    $students = array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN), true);

                    // ✅ Preload existing GWA records
                    $stmt = $con->prepare("SELECT student_number, year_level, semester FROM student_gwa");
                    $stmt->execute();
                    $existingKeys = [];
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $existingKeys["{$row['student_number']}-{$row['year_level']}-{$row['semester']}"] = true;
                    }

                    // ✅ Read CSV
                    $header = fgetcsv($handle); 
                    $bulkData = [];

                    while (($row = fgetcsv($handle)) !== FALSE) {
                        $student_number = strtoupper(trim($row[0]));

                        if (!isset($students[$student_number])) {
                            $response['skipped']++;
                            $response['errors'][] = "Student not found: $student_number";
                            continue;
                        }

                        // Loop through each semester column
                        for ($i = 1; $i < count($header); $i++) {
                            $gwa = $row[$i] ?? null;

                            $map = parseGwaHeader($header[$i]);
                            if (!$map) {
                                $response['skipped']++;
                                $response['errors'][] = "Invalid column: {$header[$i]}";
                                continue;
                            }

                            list($year_level, $semester) = $map;

                            if ($gwa === '' || $gwa === null) {
                                $response['skipped']++;
                                $response['errors'][] = "Missing GWA for student {$student_number} ({$year_level}Y Sem {$semester})";
                                continue;
                            }

                            $key = "{$student_number}-{$year_level}-{$semester}";

                            if (isset($existingKeys[$key])) {
                                $response['updated']++;
                            } else {
                                $response['inserted']++;
                                $existingKeys[$key] = true; // prevent double-counting in same import
                            }

                            $bulkData[] = [
                                $student_number,
                                $year_level,
                                $semester,
                                $gwa
                            ];
                        }
                    }
                    fclose($handle);

                    // ✅ Run bulk upserts
                    if ($bulkData) {
                        $con->beginTransaction();

                        $chunkSize = 500; // adjust depending on CSV size
                        for ($i = 0; $i < count($bulkData); $i += $chunkSize) {
                            $chunk = array_slice($bulkData, $i, $chunkSize);

                            $placeholders = [];
                            $params = [];
                            foreach ($chunk as $row) {
                                $placeholders[] = "(?, ?, ?, ?)";
                                $params = array_merge($params, $row);
                            }

                            $sql = "
                                INSERT INTO student_gwa (student_number, year_level, semester, gwa)
                                VALUES " . implode(",", $placeholders) . "
                                ON DUPLICATE KEY UPDATE gwa = VALUES(gwa)
                            ";

                            $stmt = $con->prepare($sql);
                            $stmt->execute($params);
                        }

                        $con->commit();
                    }

                    $response['success'] = true;
                break;
                
                case 'BoardGrades':
                    //Preload all students in program
                    $stmt = $con->prepare("
                        SELECT student_number 
                        FROM student_info 
                        WHERE student_program = :program AND is_active = 1
                    ");
                    $stmt->execute([':program' => $_SESSION['filter_program']]);
                    $students = array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN), true);

                    //Preload all subjects for program
                    $stmt = $con->prepare("
                        SELECT UPPER(TRIM(subject_name)) AS subject_name, subject_id 
                        FROM board_subjects 
                        WHERE program_id = :program
                    ");
                    $stmt->execute([':program' => $_SESSION['filter_program']]);
                    $subjects = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // ["MATH" => 1, "SCIENCE" => 2]

                    //Preload all existing student-subject grades (for precise counts)
                    $stmt = $con->prepare("
                        SELECT student_number, subject_id 
                        FROM student_board_subjects_grades
                    ");
                    $stmt->execute();
                    $existing = [];
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $existing[$row['student_number'] . '|' . $row['subject_id']] = true;
                    }

                    //Read header (first row → subject names)
                    $header = fgetcsv($handle);
                    $studentNumberCol = 0;
                    $subjectNames = array_slice($header, 1);

                    $bulkData = [];

                    // ✅ Process each student row
                    while (($row = fgetcsv($handle)) !== FALSE) {
                        $student_number = strtoupper(trim($row[$studentNumberCol]));

                        // Skip if student not in preload
                        if (!isset($students[$student_number])) {
                            $response['skipped']++;
                            $response['errors'][] = "Student not found: $student_number";
                            continue;
                        }

                        // Loop through each subject column
                        foreach ($subjectNames as $i => $subject_name) {
                            $grade = $row[$i + 1] ?? '';

                            if ($grade < 0){
                                $grade = 0;
                            }

                            if ($grade === '' || $grade === null) {
                                $response['skipped']++;
                                continue; // skip empty grade cells
                            }

                            $subjectKey = strtoupper(trim($subject_name));
                            $subject_id = $subjects[$subjectKey] ?? null;

                            if (!$subject_id) {
                                $response['skipped']++;
                                $response['errors'][] = "Subject not found: $subject_name";
                                continue;
                            }

                            $key = $student_number . '|' . $subject_id;
                            if (isset($existing[$key])) {
                                $response['updated']++;
                            } else {
                                $response['inserted']++;
                            }

                            // Collect data for bulk insert
                            $bulkData[] = [
                                'sid'   => $student_number,
                                'subid' => $subject_id,
                                'grade' => $grade
                            ];
                        }
                    }

                    fclose($handle);

                    // ✅ Bulk insert with ON DUPLICATE KEY UPDATE
                    if (!empty($bulkData)) {
                        $values = [];
                        $params = [];
                        foreach ($bulkData as $d) {
                            $values[] = "(?, ?, ?)";
                            $params[] = $d['sid'];
                            $params[] = $d['subid'];
                            $params[] = $d['grade'];
                        }

                        $sql = "
                            INSERT INTO student_board_subjects_grades (student_number, subject_id, subject_grade)
                            VALUES " . implode(',', $values) . "
                            ON DUPLICATE KEY UPDATE subject_grade = VALUES(subject_grade)
                        ";

                        $stmt = $con->prepare($sql);
                        $stmt->execute($params);
                    }

                    $response['success'] = true;
                break;

                case 'Retakes':
                    //Preload all students in program
                    $stmt = $con->prepare("
                        SELECT student_number 
                        FROM student_info 
                        WHERE student_program = :program AND is_active = 1
                    ");
                    $stmt->execute([':program' => $_SESSION['filter_program']]);
                    $students = array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN), true);

                    //Preload all subjects for program
                    $stmt = $con->prepare("
                        SELECT UPPER(TRIM(general_subject_name)) AS general_subject_name, general_subject_id 
                        FROM general_subjects 
                        WHERE program_id = :program
                    ");
                    $stmt->execute([':program' => $_SESSION['filter_program']]);
                    $subjects = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // ["MATH" => 1, "SCIENCE" => 2]

                    //Preload all existing student-subject grades (for precise counts)
                    $stmt = $con->prepare("
                        SELECT student_number, general_subject_id 
                        FROM student_back_subjects
                    ");
                    $stmt->execute();
                    $existing = [];
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $existing[$row['student_number'] . '|' . $row['general_subject_id']] = true;
                    }

                    //Read header (first row → subject names)
                    $header = fgetcsv($handle);
                    $studentNumberCol = 0;
                    $subjectNames = array_slice($header, 1);

                    $bulkData = [];

                    // ✅ Process each student row
                    while (($row = fgetcsv($handle)) !== FALSE) {
                        $student_number = strtoupper(trim($row[$studentNumberCol]));

                        // Skip if student not in preload
                        if (!isset($students[$student_number])) {
                            $response['skipped']++;
                            $response['errors'][] = "Student not found: $student_number";
                            continue;
                        }

                        // Loop through each subject column
                        foreach ($subjectNames as $i => $subject_name) {
                            $retakes = $row[$i + 1] ?? '';

                            if ($retakes < 0){
                                $retakes = 0;
                            }

                            if ($retakes === '' || $retakes === null) {
                                $response['skipped']++;
                                continue; // skip empty grade cells
                            }

                            $subjectKey = strtoupper(trim($subject_name));
                            $subject_id = $subjects[$subjectKey] ?? null;

                            if (!$subject_id) {
                                $response['skipped']++;
                                $response['errors'][] = "Subject not found: $subject_name";
                                continue;
                            }

                            $key = $student_number . '|' . $subject_id;
                            if (isset($existing[$key])) {
                                $response['updated']++;
                            } else {
                                $response['inserted']++;
                            }

                            // Collect data for bulk insert
                            $bulkData[] = [
                                'sid'   => $student_number,
                                'subid' => $subject_id,
                                'retakes' => $retakes
                            ];
                        }
                    }

                    fclose($handle);

                    // ✅ Bulk insert with ON DUPLICATE KEY UPDATE
                    if (!empty($bulkData)) {
                        $values = [];
                        $params = [];
                        foreach ($bulkData as $d) {
                            $values[] = "(?, ?, ?)";
                            $params[] = $d['sid'];
                            $params[] = $d['subid'];
                            $params[] = $d['retakes'];
                        }

                        $sql = "
                            INSERT INTO student_back_subjects (student_number, general_subject_id, terms_repeated)
                            VALUES " . implode(',', $values) . "
                            ON DUPLICATE KEY UPDATE terms_repeated = VALUES(terms_repeated)
                        ";

                        $stmt = $con->prepare($sql);
                        $stmt->execute($params);
                    }

                    $response['success'] = true;
                break;
                
                case 'PerformanceRating':
                    // Preload all students in program
                    $stmt = $con->prepare("
                        SELECT student_number 
                        FROM student_info 
                        WHERE student_program = :program AND is_active = 1
                    ");
                    $stmt->execute([':program' => $_SESSION['filter_program']]);
                    $students = array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN), true);

                    // Preload all subjects for program
                    $stmt = $con->prepare("
                        SELECT UPPER(TRIM(category_name)) AS category_name, category_id 
                        FROM rating_category 
                        WHERE program_id = :program
                    ");
                    $stmt->execute([':program' => $_SESSION['filter_program']]);
                    $categories = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // ["MATH" => 1, "SCIENCE" => 2]

                    // Preload all existing student-subject grades (for precise counts)
                    $stmt = $con->prepare("
                        SELECT student_number, category_id 
                        FROM student_performance_rating
                    ");
                    $stmt->execute();
                    $existing = [];
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $existing[$row['student_number'] . '|' . $row['category_id']] = true;
                    }

                    // Read header (first row → subject names)
                    $header = fgetcsv($handle);
                    $studentNumberCol = 0;
                    $categoryNames = array_slice($header, 1);

                    $bulkData = [];

                    // Process each student row
                    while (($row = fgetcsv($handle)) !== FALSE) {
                        $student_number = strtoupper(trim($row[$studentNumberCol]));

                        // Skip if student not in preload
                        if (!isset($students[$student_number])) {
                            $response['skipped']++;
                            $response['errors'][] = "Student not found: $student_number";
                            continue;
                        }

                        // Loop through each subject column
                        foreach ($categoryNames as $i => $category_name) {
                            $grade = $row[$i + 1] ?? '';

                            if ($grade < 0){
                                $grade = 0;
                            }

                            if ($grade === '' || $grade === null) {
                                $response['skipped']++;
                                continue; // skip empty grade cells
                            }

                            $categoryKey = strtoupper(trim($category_name));
                            $category_id = $categories[$categoryKey] ?? null;

                            if (!$category_id) {
                                $response['skipped']++;
                                $response['errors'][] = "Subject not found: $category_name";
                                continue;
                            }

                            $key = $student_number . '|' . $category_id;
                            if (isset($existing[$key])) {
                                $response['updated']++;
                            } else {
                                $response['inserted']++;
                            }

                            // Collect data for bulk insert
                            $bulkData[] = [
                                'sid'   => $student_number,
                                'subid' => $category_id,
                                'grade' => $grade
                            ];
                        }
                    }

                    fclose($handle);

                    // ✅ Bulk insert with ON DUPLICATE KEY UPDATE
                    if (!empty($bulkData)) {
                        $values = [];
                        $params = [];
                        foreach ($bulkData as $d) {
                            $values[] = "(?, ?, ?)";
                            $params[] = $d['sid'];
                            $params[] = $d['subid'];
                            $params[] = $d['grade'];
                        }

                        $sql = "
                            INSERT INTO student_performance_rating (student_number, category_id, rating)
                            VALUES " . implode(',', $values) . "
                            ON DUPLICATE KEY UPDATE rating = VALUES(rating)
                        ";

                        $stmt = $con->prepare($sql);
                        $stmt->execute($params);
                    }

                    $response['success'] = true;
                break;
                
                case 'SimExam':
                    // Preload all students in program
                    $stmt = $con->prepare("
                        SELECT student_number 
                        FROM student_info 
                        WHERE student_program = :program AND is_active = 1
                    ");
                    $stmt->execute([':program' => $_SESSION['filter_program']]);
                    $students = array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN), true);

                    // Preload all subjects for program
                    $stmt = $con->prepare("
                        SELECT UPPER(TRIM(simulation_name)) AS simulation_name, simulation_id 
                        FROM simulation_exams 
                        WHERE program_id = :program
                    ");
                    $stmt->execute([':program' => $_SESSION['filter_program']]);
                    $categories = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // ["MATH" => 1, "SCIENCE" => 2]

                    // Preload all existing student-subject grades (for precise counts)
                    $stmt = $con->prepare("
                        SELECT student_number, simulation_id 
                        FROM student_simulation_exam
                    ");
                    $stmt->execute();
                    $existing = [];
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $existing[$row['student_number'] . '|' . $row['simulation_id']] = true;
                    }

                    // Read header (first row → subject names)
                    $header = fgetcsv($handle);
                    $studentNumberCol = 0;
                    $categoryNames = array_slice($header, 1);

                    $bulkData = [];

                    // Process each student row
                    while (($row = fgetcsv($handle)) !== FALSE) {
                        $student_number = strtoupper(trim($row[$studentNumberCol]));

                        // Skip if student not in preload
                        if (!isset($students[$student_number])) {
                            $response['skipped']++;
                            $response['errors'][] = "Student not found: $student_number";
                            continue;
                        }

                        // Loop through each subject column
                        foreach ($categoryNames as $i => $category_name) {
                            $cell = $row[$i + 1] ?? '';
                            $parts = explode('/', $cell);
                            $grade = trim($parts[0] ?? '');
                            $total = trim($parts[1] ?? '');

                            if ($total > 1000){
                                $total = 1000;
                            } else if ($total < 0){
                                $total = 0;
                            }

                            if ($grade > $total){
                                $grade = $total;
                            } else if ($grade < 0){
                                $grade = 0;
                            }

                            if ($grade === '' || $grade === null) {
                                $response['skipped']++;
                                continue; // skip empty grade cells
                            }

                            $categoryKey = strtoupper(trim($category_name));
                            $category_id = $categories[$categoryKey] ?? null;

                            if (!$category_id) {
                                $response['skipped']++;
                                $response['errors'][] = "Subject not found: $category_name";
                                continue;
                            }

                            $key = $student_number . '|' . $category_id;
                            if (isset($existing[$key])) {
                                $response['updated']++;
                            } else {
                                $response['inserted']++;
                            }

                            // Collect data for bulk insert
                            $bulkData[] = [
                                'sid'   => $student_number,
                                'subid' => $category_id,
                                'grade' => $grade,
                                'total' => $total
                            ];
                        }
                    }

                    fclose($handle);

                    // ✅ Bulk insert with ON DUPLICATE KEY UPDATE
                    if (!empty($bulkData)) {
                        $values = [];
                        $params = [];
                        foreach ($bulkData as $d) {
                            $values[] = "(?, ?, ?, ?)";
                            $params[] = $d['sid'];
                            $params[] = $d['subid'];
                            $params[] = $d['grade'];
                            $params[] = $d['total'];
                        }

                        $sql = "
                            INSERT INTO student_simulation_exam (student_number, simulation_id, student_score, total_score)
                            VALUES " . implode(',', $values) . "
                            ON DUPLICATE KEY UPDATE student_score = VALUES(student_score), total_score = VALUES(total_score)
                        ";

                        $stmt = $con->prepare($sql);
                        $stmt->execute($params);
                    }

                    $response['success'] = true;
                break;
                
                case 'Attendance':
                    // Preload all students in program
                    $stmt = $con->prepare("
                        SELECT student_number 
                        FROM student_info 
                        WHERE student_program = :program AND is_active = 1
                    ");
                    $stmt->execute([':program' => $_SESSION['filter_program']]);
                    $students = array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN), true);

                    // Preload existing attendance records (to count insert/update)
                    $stmt = $con->prepare("SELECT student_number FROM student_attendance_reviews");
                    $stmt->execute();
                    $existing = array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN), true);

                    // Read header (CSV must match DB field names)
                    $header = fgetcsv($handle);
                    $studentNumberCol = array_search('student_number', $header);
                    $attendedCol      = array_search('sessions_attended', $header);
                    $totalCol         = array_search('sessions_total', $header);

                    $bulkData = [];

                    while (($row = fgetcsv($handle)) !== FALSE) {
                        $student_number   = strtoupper(trim($row[$studentNumberCol] ?? ''));
                        $sessionsAttended = (int)($row[$attendedCol] ?? 0);
                        $sessionsTotal    = (int)($row[$totalCol] ?? 0);

                        // Skip invalid students
                        if (!isset($students[$student_number])) {
                            $response['skipped']++;
                            $response['errors'][] = "Student not found: $student_number";
                            continue;
                        }

                        // Validate sessions
                        if ($sessionsTotal < 0) $sessionsTotal = 0;
                        if ($sessionsAttended < 0) $sessionsAttended = 0;
                        if ($sessionsAttended > $sessionsTotal) $sessionsAttended = $sessionsTotal;

                        // Count insert/update
                        if (isset($existing[$student_number])) {
                            $response['updated']++;
                        } else {
                            $response['inserted']++;
                        }

                        // Collect for bulk insert
                        $bulkData[] = [
                            'sid'     => $student_number,
                            'attended'=> $sessionsAttended,
                            'total'   => $sessionsTotal
                        ];
                    }

                    fclose($handle);

                    // Bulk UPSERT
                    if (!empty($bulkData)) {
                        $values = [];
                        $params = [];
                        foreach ($bulkData as $d) {
                            $values[] = "(?, ?, ?)";
                            $params[] = $d['sid'];
                            $params[] = $d['attended'];
                            $params[] = $d['total'];
                        }

                        $sql = "
                            INSERT INTO student_attendance_reviews 
                                (student_number, sessions_attended, sessions_total)
                            VALUES " . implode(',', $values) . "
                            ON DUPLICATE KEY UPDATE 
                                sessions_attended = VALUES(sessions_attended),
                                sessions_total    = VALUES(sessions_total)
                        ";

                        $stmt = $con->prepare($sql);
                        $stmt->execute($params);
                    }

                    $response['success'] = true;
                break;
                
                case 'Recognition':
                    // Preload all students in program
                    $stmt = $con->prepare("
                        SELECT student_number 
                        FROM student_info 
                        WHERE student_program = :program AND is_active = 1
                    ");
                    $stmt->execute([':program' => $_SESSION['filter_program']]);
                    $students = array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN), true);

                    // Preload existing attendance records (to count insert/update)
                    $stmt = $con->prepare("SELECT student_number FROM student_academic_recognition WHERE award_id = 2");
                    $stmt->execute();
                    $existing = array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN), true);

                    // Read header (CSV must match DB field names)
                    $header = fgetcsv($handle);
                    $studentNumberCol = array_search('student_number', $header);
                    $awardCol      = array_search('deans_lister', $header);

                    $bulkData = [];

                    while (($row = fgetcsv($handle)) !== FALSE) {
                        $student_number   = strtoupper(trim($row[$studentNumberCol] ?? ''));
                        $awardCount = (int)($row[$awardCol] ?? 0);

                        // Skip invalid students
                        if (!isset($students[$student_number])) {
                            $response['skipped']++;
                            $response['errors'][] = "Student not found: $student_number";
                            continue;
                        }

                        // Validate sessions
                        if ($awardCount < 0) $awardCount = 0;

                        // Count insert/update
                        if (isset($existing[$student_number])) {
                            $response['updated']++;
                        } else {
                            $response['inserted']++;
                        }

                        // Collect for bulk insert
                        $bulkData[] = [
                            'sid'     => $student_number,
                            'awards'  => $awardCount
                        ];
                    }

                    fclose($handle);

                    // Bulk UPSERT
                    if (!empty($bulkData)) {
                        $values = [];
                        $params = [];
                        foreach ($bulkData as $d) {
                            $values[] = "(?, ?, ?)";
                            $params[] = $d['sid'];
                            $params[] = 2;
                            $params[] = $d['awards'];
                        }

                        $sql = "
                            INSERT INTO student_academic_recognition 
                                (student_number, award_id, award_count)
                            VALUES " . implode(',', $values) . "
                            ON DUPLICATE KEY UPDATE 
                                award_count = VALUES(award_count)
                        ";

                        $stmt = $con->prepare($sql);
                        $stmt->execute($params);
                    }

                    $response['success'] = true;
                break;
            }
        }
    } else {
        $response['errors'][] = "No file uploaded or upload error.";
    }
} catch (PDOException $e) {
    $response['errors'][] = $e->getMessage();
}

// Return JSON
echo json_encode($response);
?>