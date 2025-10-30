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
                case 'ReviewCenter':
                    try {
                        // Start transaction
                        $con->beginTransaction();

                        // Preload all students in batch (map student_number → batch_id)
                        $stmt = $con->prepare("
                            SELECT student_number, batch_id
                            FROM board_batch 
                            WHERE year = :year 
                            AND program_id = :program 
                            AND batch_number = :batch 
                            AND is_active = 1
                        ");
                        $stmt->execute([
                            ':program' => $_SESSION['filter_program'],
                            ':year'    => $_SESSION['filter_year_batch'],
                            ':batch'   => $_SESSION['filter_board_batch']
                        ]);
                        $students = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); 
                        // Example: [ "2023-0001" => 55, "2023-0002" => 56 ]

                        // Preload existing review center data
                        $stmt = $con->prepare("SELECT batch_id, review_center FROM student_review_center");
                        $stmt->execute();
                        $existingCenters = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

                        // Read header (CSV must contain: student_number, review_center)
                        $header = fgetcsv($handle);
                        $studentNumberCol = array_search('student_number', $header);
                        $centerCol        = array_search('review_center', $header);

                        $bulkData  = [];
                        $auditLogs = [];

                        while (($row = fgetcsv($handle)) !== FALSE) {
                            $student_number = strtoupper(trim($row[$studentNumberCol] ?? ''));
                            $reviewCenter   = strtoupper(trim($row[$centerCol] ?? ''));

                            // 🔑 Map student_number → batch_id
                            $batch_id = $students[$student_number] ?? null;

                            if (!$batch_id) {
                                $response['skipped']++;
                                $response['errors'][] = "Student not found or not in batch: $student_number";
                                continue;
                            }

                            $oldValue = $existingCenters[$batch_id] ?? null;

                            if ($oldValue === null) {
                                // ➕ New insert
                                $response['inserted']++;
                                $remarks = "Inserted: $reviewCenter";
                            } elseif ($oldValue !== $reviewCenter) {
                                // 🔄 Updated
                                $response['updated']++;
                                $remarks = "Update: {Review Center: '$oldValue' → '$reviewCenter'}";
                            } else {
                                // No change, skip
                                continue;
                            }

                            // Collect for bulk upsert
                            $bulkData[] = [
                                'bid'    => $batch_id,
                                'center' => $reviewCenter
                            ];

                            // Collect audit log
                            $student_number_for_audit = "$student_number (Batch: {$_SESSION['filter_year_batch']} - {$_SESSION['filter_board_batch']})";
                            $auditLogs[] = [
                                $student_number_for_audit,
                                $_SESSION['id'],
                                $remarks,
                                'REVIEW CENTER'
                            ];
                        }

                        fclose($handle);

                        // ✅ Perform bulk upsert
                        if (!empty($bulkData)) {
                            $values = [];
                            $params = [];
                            foreach ($bulkData as $d) {
                                $values[] = "(?, ?)";
                                $params[] = $d['bid'];     // batch_id
                                $params[] = $d['center'];  // review_center
                            }

                            $sql = "
                                INSERT INTO student_review_center (batch_id, review_center)
                                VALUES " . implode(',', $values) . "
                                ON DUPLICATE KEY UPDATE 
                                    review_center = VALUES(review_center)
                            ";
                            $stmt = $con->prepare($sql);
                            $stmt->execute($params);
                        }

                        // ✅ Perform bulk insert for audit logs
                        if (!empty($auditLogs)) {
                            $values = [];
                            $params = [];
                            foreach ($auditLogs as $log) {
                                $values[] = "(?, ?, NOW(), ?, ?)";
                                $params = array_merge($params, $log);
                            }

                            $sql = "
                                INSERT INTO student_program_audit 
                                    (student_number, updated_by, updated_at, remarks, location)
                                VALUES " . implode(',', $values);

                            $stmt = $con->prepare($sql);
                            $stmt->execute($params);
                        }

                        // ✅ Commit all changes
                        $con->commit();

                        $response['success'] = true;

                    } catch (Exception $e) {
                        // Roll back all if something goes wrong
                        $con->rollBack();
                        $response['success'] = false;
                        $response['error'] = $e->getMessage();
                    }

                    break;

                case 'MockScores':
                    // ✅ Preload all students in batch (map student_number → batch_id)
                    $stmt = $con->prepare("
                        SELECT student_number, batch_id
                        FROM board_batch 
                        WHERE year = :year 
                        AND program_id = :program 
                        AND batch_number = :batch 
                        AND is_active = 1
                    ");
                    $stmt->execute([
                        ':program' => $_SESSION['filter_program'],
                        ':year'    => $_SESSION['filter_year_batch'],
                        ':batch'   => $_SESSION['filter_board_batch']
                    ]);
                    $students = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); 
                    // Example: [ "2023-0001" => 55, "2023-0002" => 56 ]

                    //Preload all subjects for program
                    $stmt = $con->prepare("
                        SELECT UPPER(TRIM(mock_subject_name)) AS mock_subject_name, mock_subject_id 
                        FROM mock_subjects 
                        WHERE program_id = :program
                    ");
                    $stmt->execute([':program' => $_SESSION['filter_program']]);
                    $subjects = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // ["MATH" => 1, "SCIENCE" => 2]

                    // ✅ Preload existing review center records
                    $stmt = $con->prepare("
                        SELECT batch_id, mock_subject_id
                        FROM student_mock_board_scores
                    ");
                    $stmt->execute();
                    $existing = [];
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $existing[$row['batch_id'] . '|' . $row['mock_subject_id']] = true;
                    }

                    //Read header (first row → subject names)
                    $header = fgetcsv($handle);
                    $studentNumberCol = 0;
                    $subjectNames = array_slice($header, 1);

                    $bulkData = [];

                    // ✅ Process each student row
                    while (($row = fgetcsv($handle)) !== FALSE) {
                        $student_number = strtoupper(trim($row[$studentNumberCol]));

                        // 🔑 Map student_number → batch_id
                        $batch_id = $students[$student_number] ?? null;

                        // Skip if student not in preload
                        if (!isset($batch_id)) {
                            $response['skipped']++;
                            $response['errors'][] = "Student not found: $student_number";
                            continue;
                        }

                        // Loop through each subject column
                        foreach ($subjectNames as $i => $subject_name) {
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

                            $subjectKey = strtoupper(trim($subject_name));
                            $subject_id = $subjects[$subjectKey] ?? null;

                            if (!$subject_id) {
                                $response['skipped']++;
                                $response['errors'][] = "Subject not found: $subject_name";
                                continue;
                            }

                            $key = $batch_id . '|' . $subject_id;
                            if (isset($existing[$key])) {
                                $response['updated']++;
                            } else {
                                $response['inserted']++;
                            }

                            // Collect data for bulk insert
                            $bulkData[] = [
                                'sid'   => $batch_id,
                                'subid' => $subject_id,
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
                            INSERT INTO student_mock_board_scores (batch_id, mock_subject_id, student_score, total_score)
                            VALUES " . implode(',', $values) . "
                            ON DUPLICATE KEY UPDATE
                                student_score = VALUES(student_score),
                                total_score   = VALUES(total_score)
                        ";

                        $stmt = $con->prepare($sql);
                        $stmt->execute($params);
                    }

                    $response['success'] = true;
                break;
                
                case 'LicensureResult':
                    // ✅ Preload all students in batch (map student_number → batch_id)
                    $stmt = $con->prepare("
                        SELECT student_number, batch_id
                        FROM board_batch 
                        WHERE year = :year 
                        AND program_id = :program 
                        AND batch_number = :batch 
                        AND is_active = 1
                    ");
                    $stmt->execute([
                        ':program' => $_SESSION['filter_program'],
                        ':year'    => $_SESSION['filter_year_batch'],
                        ':batch'   => $_SESSION['filter_board_batch']
                    ]);
                    $students = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); 
                    // Example: [ "2023-0001" => 55, "2023-0002" => 56 ]

                    // ✅ Preload existing review center records
                    $stmt = $con->prepare("SELECT batch_id FROM student_licensure_exam");
                    $stmt->execute();
                    $existing = array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN), true);

                    // ✅ Read header (CSV must contain: student_number, review_center)
                    $header = fgetcsv($handle);
                    $studentNumberCol = array_search('student_number', $header);
                    $dateCol        = array_search('date_taken', $header);
                    $resultCol        = array_search('exam_result', $header);

                    $bulkData = [];

                    while (($row = fgetcsv($handle)) !== FALSE) {
                        $student_number = strtoupper(trim($row[$studentNumberCol] ?? ''));
                        $dateTaken = date("Y-m-d", strtotime($row[$dateCol] ?? '')); // format conversion
                        $val = strtoupper(trim($row[$resultCol] ?? ''));
                        if ($val === "PASSED" || $val === "FAILED") {
                            $examResult = $val;
                        } else {
                            $examResult = ''; // or null, depending on what you want
                        }

                        // 🔑 Map student_number → batch_id
                        $batch_id = $students[$student_number] ?? null;

                        if (!$batch_id) {
                            $response['skipped']++;
                            $response['errors'][] = "Student not found or not in batch: $student_number";
                            continue;
                        }

                        // Count insert/update by batch_id
                        if (isset($existing[$batch_id])) {
                            $response['updated']++;
                        } else {
                            $response['inserted']++;
                        }

                        // Collect for bulk insert
                        $bulkData[] = [
                            'bid'    => $batch_id,
                            'date' => $dateTaken,
                            'result' => $examResult
                        ];
                    }

                    fclose($handle);

                    // ✅ Bulk UPSERT using batch_id as key
                    if (!empty($bulkData)) {
                        $values = [];
                        $params = [];
                        foreach ($bulkData as $d) {
                            $values[] = "(?, ?, ?)";
                            $params[] = $d['bid'];     // batch_id
                            $params[] = $d['date'];  // review_center
                            $params[] = $d['result'];  // review_center
                        }

                        $sql = "
                            INSERT INTO student_licensure_exam (batch_id, exam_date_taken, exam_result)
                            VALUES " . implode(',', $values) . "
                            ON DUPLICATE KEY UPDATE 
                                exam_date_taken = VALUES(exam_date_taken),
                                exam_result = VALUES(exam_result)
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