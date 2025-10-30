<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";

try {
    // Check if form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $form_type = $_POST['form_type'] ?? '';
    $userId = $_SESSION['id'] ?? null;
    $student_number = trim($_POST['studentId']) ?? null;
    $filter_batch_year = $_SESSION['filter_year_batch'] ?? null;
    $filter_batch_number = $_SESSION['filter_board_batch'] ?? null;

    switch ($form_type) {
    case 'review_center': // REVIEW CENTER ADD, REVIEW CENTER ADD, REVIEW CENTER ADD, REVIEW CENTER ADD, REVIEW CENTER ADD, REVIEW CENTER ADD
        // Retrieve and trim form data
        $batch_id = trim($_POST['batchId']);
        $review_center = strtoupper(trim($_POST['reviewCenter']));
        $date_created = date('Y-m-d H:i:s');

        // Prepare and execute the SQL statement
        $insertStmt = $con->prepare("
            INSERT INTO student_review_center (
                batch_id, review_center, date_created
            ) VALUES (
                :batch_id, :review_center, :date_created
            )
        ");

        $updateStmt = $con->prepare("
            UPDATE student_review_center SET
                review_center = :review_center,
                date_created = :date_created
            WHERE batch_id = :batch_id
        ");

        $params = [
            ':batch_id' => $batch_id,
            ':review_center' => $review_center,
            ':date_created' => $date_created
        ];

        $checkStmt = $con->prepare("SELECT review_center FROM student_review_center WHERE batch_id = :batch_id");
        
        $checkStmt->execute([':batch_id' => $params[':batch_id']]);
        $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($exists) {
            if ($updateStmt->execute($params)) {
                // --- 4. Prepare and Execute Audit Log ---
                // Create a string listing all updated fields with old/new values
                $remarks_updated_fields = "Update: {Review Center: '" . $exists['review_center'] . "' to '" . $review_center . "'}";
                $student_number_for_audit = "$student_number (Batch: $filter_batch_year - $filter_batch_number)";
                $auditStmt = $con->prepare("INSERT INTO student_program_audit (student_number, updated_by, updated_at, remarks, location) VALUES (?, ?, NOW(), ?, ?)");

                // The remarks now contain the old and new data
                $auditStmt->execute([$student_number_for_audit, $userId, $remarks_updated_fields, 'REVIEW CENTER']);

                
                // Redirect to student_info.php
                header("Location: /bridge/edit-student-review-center?studentId=" . $batch_id);
                exit();
            } else {
                echo "Error submitting student information.";
            }
        } else {
            if ($insertStmt->execute($params)) {
                // --- 4. Prepare and Execute Audit Log ---
                // Create a string listing all updated fields with old/new values
                $remarks = "Inserted: " . $review_center;
                $auditStmt = $con->prepare("INSERT INTO student_program_audit (student_number, updated_by, updated_at, remarks, location) VALUES (?, ?, NOW(), ?, ?)");
                
                $student_number_for_audit = "$student_number (Batch: $filter_batch_year - $filter_batch_number)";
                $auditStmt->execute([$student_number_for_audit, $userId, $remarks, 'REVIEW CENTER']);

                
                // Redirect to student_info.php
                header("Location: /bridge/edit-student-review-center?studentId=" . $batch_id);
                exit();
            } else {
                echo "Error updating student information.";
            }
        }
    break;

    case 'mock_scores': // MOCK SUBJECT ADD GRADES, MOCK SUBJECT ADD GRADES, MOCK SUBJECT ADD GRADES, MOCK SUBJECT ADD GRADES, MOCK SUBJECT ADD GRADES, 
        // Retrieve and trim form data
        $batch_id = trim($_POST['batchId']);
        $mock_subject = trim($_POST['mockSubject']);
        $student_score = floatval(trim($_POST['studentScore']));
        $total_score = floatval(trim($_POST['totalScore']));
        if ($total_score < 0){
            $total_score = 0;
        }
        if ($student_score > $total_score){
            $student_score = $total_score;
        } else if ($student_score < 0){
            $student_score = 0;
        }
        $date_created = date('Y-m-d H:i:s');

        

        $subjectStmt = $con->prepare("
            SELECT mock_subject_id, mock_subject_name
            FROM mock_subjects
            WHERE program_id = :program_id
        ");
        $subjectStmt->execute(['program_id' => $_SESSION['filter_program']]);
        $subjects = $subjectStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Prepare and execute the SQL statement
        $insertStmt = $con->prepare("
            INSERT INTO student_mock_board_scores (
                batch_id, mock_subject_id, student_score, total_score, date_created
            ) VALUES (
                :batch_id, :mock_subject_id, :student_score, :total_score, :date_created
            )
        ");

        $updateStmt = $con->prepare("
            UPDATE student_mock_board_scores SET
                student_score = :student_score,
                total_score = :total_score,
                date_created = :date_created
            WHERE batch_id = :batch_id AND mock_subject_id = :mock_subject_id
        ");

        $params = [
            ':batch_id' => $batch_id,
            ':mock_subject_id' => $mock_subject,
            ':student_score' => $student_score,
            ':total_score' => $total_score,
            ':date_created' => $date_created
        ];

        $checkStmt = $con->prepare("SELECT student_score, total_score FROM student_mock_board_scores WHERE batch_id = :batch_id AND mock_subject_id = :mock_subject_id");
        
        $checkStmt->execute([':batch_id' => $params[':batch_id'], ':mock_subject_id' => $params[':mock_subject_id']]);
        $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!empty($subjects)) {
            foreach ($subjects as $subject_id => $subject_name) {
                if ($subject_id == $mock_subject) {
                    $selected_subject_name = $subject_name;
                    break;
                }
            }
        }

        if ($exists) {
            if ($updateStmt->execute($params)) {
                // --- 4. Prepare and Execute Audit Log ---
                // Create a string listing all updated fields with old/new values
                $remarks_updated_fields = "Update: {" . $selected_subject_name . ": '" . $exists['student_score'] . "/" . $exists['total_score'] . "' to '$student_score/$total_score'}";
                $student_number_for_audit = "$student_number (Batch: $filter_batch_year - $filter_batch_number)";
                $auditStmt = $con->prepare("INSERT INTO student_program_audit (student_number, updated_by, updated_at, remarks, location) VALUES (?, ?, NOW(), ?, ?)");

                // The remarks now contain the old and new data
                $auditStmt->execute([$student_number_for_audit, $userId, $remarks_updated_fields, 'REVIEW CENTER']);

                
                // Redirect to student_info.php
                header("Location: /bridge/edit-mock-board-scores?studentId=" . $batch_id);
                exit();
            } else {
                echo "Error submitting student information.";
            }
        } else {
            if ($insertStmt->execute($params)) {
                // --- 4. Prepare and Execute Audit Log ---
                // Create a string listing all updated fields with old/new values
                $remarks = "Inserted to $selected_subject_name: $student_score/$total_score";
                $auditStmt = $con->prepare("INSERT INTO student_program_audit (student_number, updated_by, updated_at, remarks, location) VALUES (?, ?, NOW(), ?, ?)");
                
                $student_number_for_audit = "$student_number (Batch: $filter_batch_year - $filter_batch_number)";
                $auditStmt->execute([$student_number_for_audit, $userId, $remarks, 'MOCK SCORES']);

                // Redirect to student_info.php
                
                // Redirect to student_info.php
                header("Location: /bridge/edit-mock-board-scores?studentId=" . $batch_id);
                exit();
            } else {
                echo "Error updating student information.";
            }
        }
    break;

    case 'exam_result': // REVIEW CENTER ADD, REVIEW CENTER ADD, REVIEW CENTER ADD, REVIEW CENTER ADD, REVIEW CENTER ADD, REVIEW CENTER ADD
        // Retrieve and trim form data
        $batch_id = trim($_POST['batchId']);
        $exam_result = strtoupper(trim($_POST['examResult']));
        $exam_date_taken = $_POST['dateTaken'];
        $new_data = ['exam_result' => $exam_result, 'exam_date_taken' => $exam_date_taken]; 
        $date_created = date('Y-m-d H:i:s');

        // Prepare and execute the SQL statement
        $insertStmt = $con->prepare("
            INSERT INTO student_licensure_exam (
                batch_id, exam_result, exam_date_taken, date_created
            ) VALUES (
                :batch_id, :exam_result, :exam_date_taken, :date_created
            )
        ");

        $updateStmt = $con->prepare("
            UPDATE student_licensure_exam SET
                exam_result = :exam_result,
                exam_date_taken = :exam_date_taken,
                date_created = :date_created
            WHERE batch_id = :batch_id
        ");

        $params = [
            ':batch_id' => $batch_id,
            ':exam_result' => $exam_result,
            ':exam_date_taken' => $exam_date_taken,
            ':date_created' => $date_created
        ];

        $checkStmt = $con->prepare("SELECT exam_result, exam_date_taken FROM student_licensure_exam WHERE batch_id = :batch_id");
        
        $checkStmt->execute([':batch_id' => $params[':batch_id']]);
        $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($exists) {
            if ($updateStmt->execute($params)) {        
                $finalData = [];
                $updated_fields_log = [];

                // The list of fields remains the same for processing POST data
                $fields = array_keys($exists); // Use the fields fetched from the database

                foreach ($fields as $field) {
                        $new_value = $new_data[$field]; // Clean the new value from POST data
                        $old_value = strtoupper(trim($exists[$field])); // Clean the old value for comparison

                        // Only update and log if the value has actually changed
                        if ($new_value !== $old_value) {
                            $finalData[$field] = $new_value;

                            // Log the field name, old value, and new value
                            $updated_fields_log[] = "{$field}: '{$old_value}' -> '{$new_value}'";
                        }
                }

                // Create a string listing all updated fields with old/new values
                $remarks_updated_fields = "Update: " . implode(" | ", $updated_fields_log);
                $student_number_for_audit = "$student_number (Batch: $filter_batch_year - $filter_batch_number)";
                $auditStmt = $con->prepare("INSERT INTO student_program_audit (student_number, updated_by, updated_at, remarks, location) VALUES (?, ?, NOW(), ?, ?)");
                $auditStmt->execute([$student_number_for_audit, $userId, $remarks_updated_fields, 'LICENSURE EXAM']);

                // Redirect to student_info.php
                header("Location: /bridge/edit-licensure-exam-results?studentId=" . $batch_id);
                exit();
            } else {
                echo "Error submitting student information.";
            }
        } else {
            if ($insertStmt->execute($params)) {
                // --- 4. Prepare and Execute Audit Log ---
                // Create a string listing all updated fields with old/new values
                $remarks = "Inserted: " . $exam_result . " taken on " . $exam_date_taken;
                $auditStmt = $con->prepare("INSERT INTO student_program_audit (student_number, updated_by, updated_at, remarks, location) VALUES (?, ?, NOW(), ?, ?)");
                $student_number_for_audit = "$student_number (Batch: $filter_batch_year - $filter_batch_number)";
                $auditStmt->execute([$student_number_for_audit, $userId, $remarks, 'LICENSURE EXAM']);

                // Redirect to student_info.php
                header("Location: /bridge/edit-licensure-exam-results?studentId=" . $batch_id);
                exit();
            } else {
                echo "Error updating student information.";
            }
        }
    break;
}       
}
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Close connection
$con = null;
?>