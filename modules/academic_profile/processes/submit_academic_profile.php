<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";

try {
    // Check if form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $form_type = $_POST['form_type'] ?? '';

    switch ($form_type) {
        case 'gwa': // BOARD SUBJECT ADD GRADES, BOARD SUBJECT ADD GRADES, BOARD SUBJECT ADD GRADES, BOARD SUBJECT ADD GRADES, BOARD SUBJECT ADD GRADES, 
        // Retrieve and trim form data
        $student_number = trim($_POST['studentId']);
        $year_level = trim($_POST['yearLevel']);
        $semester = trim($_POST['semester']);
        $gwa = floatval(trim($_POST['gwa']));
        if ($gwa > 5){
            $gwa = 5;
        } else if ($subject_grade < 0){
            $gwa = 0;
        }
        $date_created = date('Y-m-d H:i:s');

        // Prepare and execute the SQL statement
        $insertStmt = $con->prepare("
            INSERT INTO student_gwa (
                student_number, year_level, semester, gwa, date_created
            ) VALUES (
                :student_number, :year_level, :semester, :gwa, :date_created
            )
        ");

        $updateStmt = $con->prepare("
            UPDATE student_gwa SET
                gwa = :gwa,
                date_created = :date_created
            WHERE student_number = :student_number AND year_level = :year_level AND semester = :semester
        ");

        $params = [
            ':student_number' => $student_number,
            ':year_level' => $year_level,
            ':semester' => $semester,
            ':gwa' => $gwa,
            ':date_created' => $date_created
        ];

        $checkStmt = $con->prepare("SELECT COUNT(*) FROM student_gwa WHERE student_number = :student_number AND year_level = :year_level AND semester = :semester");
        
        $checkStmt->execute([':student_number' => $params[':student_number'], ':year_level' => $params[':year_level'], ':semester' => $params[':semester']]);
        $exists = $checkStmt->fetchColumn();

        if ($exists) {
            if ($updateStmt->execute($params)) {
                // Redirect to student_info.php
                header("Location: /bridge/edit-general-weighted-average?studentId=" . $student_number);
                exit();
            } else {
                echo "Error submitting student information.";
            }
        } else {
            if ($insertStmt->execute($params)) {
                // Redirect to student_info.php
                header("Location: /bridge/edit-general-weighted-average?studentId=" . $student_number);
                exit();
            } else {
                echo "Error updating student information.";
            }
        }
    break;

    case 'board_subject': // BOARD SUBJECT ADD GRADES, BOARD SUBJECT ADD GRADES, BOARD SUBJECT ADD GRADES, BOARD SUBJECT ADD GRADES, BOARD SUBJECT ADD GRADES, 
        // Retrieve and trim form data
        $student_number = trim($_POST['studentId']);
        $subject_code = trim($_POST['subjectCode']);
        $subject_grade = floatval(trim($_POST['subject_grade']));
        if ($subject_grade > 100){
            $subject_grade = 100;
        } else if ($subject_grade < 0){
            $subject_grade = 0;
        }
        $date_created = date('Y-m-d H:i:s');

        // Prepare and execute the SQL statement
        $insertStmt = $con->prepare("
            INSERT INTO student_board_subjects_grades (
                student_number, subject_id, subject_grade, date_created
            ) VALUES (
                :student_number, :subject_id, :subject_grade, :date_created
            )
        ");

        $updateStmt = $con->prepare("
            UPDATE student_board_subjects_grades SET
                subject_grade = :subject_grade,
                date_created = :date_created
            WHERE student_number = :student_number AND subject_id = :subject_id
        ");

        $params = [
            ':student_number' => $student_number,
            ':subject_id' => $subject_code,
            ':subject_grade' => $subject_grade,
            ':date_created' => $date_created
        ];

        $checkStmt = $con->prepare("SELECT COUNT(*) FROM student_board_subjects_grades WHERE student_number = :student_number AND subject_id = :subject_id");
        
        $checkStmt->execute([':student_number' => $params[':student_number'], ':subject_id' => $params[':subject_id']]);
        $exists = $checkStmt->fetchColumn();

        if ($exists) {
            if ($updateStmt->execute($params)) {
                // Redirect to student_info.php
                header("Location: /bridge/edit-board-subject-grades?studentId=" . $student_number);
                exit();
            } else {
                echo "Error submitting student information.";
            }
        } else {
            if ($insertStmt->execute($params)) {
                // Redirect to student_info.php
                header("Location: /bridge/edit-board-subject-grades?studentId=" . $student_number);
                exit();
            } else {
                echo "Error updating student information.";
            }
        }
    break;
    
    case 'retakes': // RETAKES ADD RETAKES, RETAKES ADD RETAKES, RETAKES ADD RETAKES, RETAKES ADD RETAKES, RETAKES ADD RETAKES, 
        // Retrieve and trim form data
        $student_number = trim($_POST['studentId']);
        $subject_code = trim($_POST['subjectCode']);
        $retakes = floatval(trim($_POST['retakes']));
        if ($retakes < 0){
            $retakes = 0;
        }
        $date_created = date('Y-m-d H:i:s');

        // Prepare and execute the SQL statement
        $insertStmt = $con->prepare("
            INSERT INTO student_back_subjects (
                student_number, general_subject_id, terms_repeated, date_created
            ) VALUES (
                :student_number, :general_subject_id, :terms_repeated, :date_created
            )
        ");

        $updateStmt = $con->prepare("
            UPDATE student_back_subjects SET
                terms_repeated = :terms_repeated,
                date_created = :date_created
            WHERE student_number = :student_number AND general_subject_id = :general_subject_id
        ");

        $params = [
            ':student_number' => $student_number,
            ':general_subject_id' => $subject_code,
            ':terms_repeated' => $retakes,
            ':date_created' => $date_created
        ];

        $checkStmt = $con->prepare("SELECT COUNT(*) FROM student_back_subjects WHERE student_number = :student_number AND general_subject_id = :general_subject_id");
        
        $checkStmt->execute([':student_number' => $params[':student_number'], ':general_subject_id' => $params[':general_subject_id']]);
        $exists = $checkStmt->fetchColumn();

        if ($exists) {
            if ($updateStmt->execute($params)) {
                // Redirect to student_info.php
                header("Location: /bridge/edit-back-subjects-retakes?studentId=" . $student_number);
                exit();
            } else {
                echo "Error submitting student information.";
            }
        } else {
            if ($insertStmt->execute($params)) {
                // Redirect to student_info.php
                header("Location: /bridge/edit-back-subjects-retakes?studentId=" . $student_number);
                exit();
            } else {
                echo "Error updating student information.";
            }
        }
    break;

    case 'performance_rating': // PERFORMANCE RATING ADD RATING, PERFORMANCE RATING ADD RATING, PERFORMANCE RATING ADD RATING, PERFORMANCE RATING ADD RATING, PERFORMANCE RATING ADD RATING, 
        // Retrieve and trim form data
        $student_number = trim($_POST['studentId']);
        $rating_category = trim($_POST['ratingCategory']);
        $rating = floatval(trim($_POST['rating']));
        if ($rating > 100){
            $rating = 100;
        } else if ($rating < 0){
            $rating = 0;
        }
        $date_created = date('Y-m-d H:i:s');

        // Prepare and execute the SQL statement
        $insertStmt = $con->prepare("
            INSERT INTO student_performance_rating (
                student_number, category_id, rating, date_created
            ) VALUES (
                :student_number, :category_id, :rating, :date_created
            )
        ");

        $updateStmt = $con->prepare("
            UPDATE student_performance_rating SET
                rating = :rating,
                date_created = :date_created
            WHERE student_number = :student_number AND category_id = :category_id
        ");

        $params = [
            ':student_number' => $student_number,
            ':category_id' => $rating_category,
            ':rating' => $rating,
            ':date_created' => $date_created
        ];

        $checkStmt = $con->prepare("SELECT COUNT(*) FROM student_performance_rating WHERE student_number = :student_number AND category_id = :category_id");
        
        $checkStmt->execute([':student_number' => $params[':student_number'], ':category_id' => $params[':category_id']]);
        $exists = $checkStmt->fetchColumn();

        if ($exists) {
            if ($updateStmt->execute($params)) {
                // Redirect to student_info.php
                header("Location: /bridge/edit-performance-rating?studentId=" . $student_number);
                exit();
            } else {
                echo "Error submitting student information.";
            }
        } else {
            if ($insertStmt->execute($params)) {
                // Redirect to student_info.php
                header("Location: /bridge/edit-performance-rating?studentId=" . $student_number);
                exit();
            } else {
                echo "Error updating student information.";
            }
        }
    break;

    case 'simulation_exam': // PERFORMANCE RATING ADD RATING, PERFORMANCE RATING ADD RATING, PERFORMANCE RATING ADD RATING, PERFORMANCE RATING ADD RATING, PERFORMANCE RATING ADD RATING, 
        // Retrieve and trim form data
        $student_number = trim($_POST['studentId']);
        $simulation_exam = trim($_POST['simulationExam']);
        $student_score = floatval(trim($_POST['studentScore']));
        $total_score = floatval(trim($_POST['totalScore']));
        if ($total_score > 1000){
            $total_score = 1000;
        } else if ($total_score < 0){
            $total_score = 0;
        }

        if ($student_score > $total_score){
            $student_score = $total_score;
        } else if ($student_score < 0){
            $student_score = 0;
        }
        $date_created = date('Y-m-d H:i:s');

        // Prepare and execute the SQL statement
        $insertStmt = $con->prepare("
            INSERT INTO student_simulation_exam (
                student_number, simulation_id, student_score, total_score, date_created
            ) VALUES (
                :student_number, :simulation_id, :student_score, :total_score, :date_created
            )
        ");

        $updateStmt = $con->prepare("
            UPDATE student_simulation_exam SET
                student_score = :student_score,
                total_score = :total_score,
                date_created = :date_created
            WHERE student_number = :student_number AND simulation_id = :simulation_id
        ");

        $params = [
            ':student_number' => $student_number,
            ':simulation_id' => $simulation_exam,
            ':student_score' => $student_score,
            ':total_score' => $total_score,
            ':date_created' => $date_created
        ];

        $checkStmt = $con->prepare("SELECT COUNT(*) FROM student_simulation_exam WHERE student_number = :student_number AND simulation_id = :simulation_id");
        
        $checkStmt->execute([':student_number' => $params[':student_number'], ':simulation_id' => $params[':simulation_id']]);
        $exists = $checkStmt->fetchColumn();

        if ($exists) {
            if ($updateStmt->execute($params)) {
                // Redirect to student_info.php
                header("Location: /bridge/edit-simulation-exam-results?studentId=" . $student_number);
                exit();
            } else {
                echo "Error submitting student information.";
            }
        } else {
            if ($insertStmt->execute($params)) {
                // Redirect to student_info.php
                header("Location: /bridge/edit-simulation-exam-results?studentId=" . $student_number);
                exit();
            } else {
                echo "Error updating student information.";
            }
        }
    break;

    case 'review_attendance': // PERFORMANCE RATING ADD RATING, PERFORMANCE RATING ADD RATING, PERFORMANCE RATING ADD RATING, PERFORMANCE RATING ADD RATING, PERFORMANCE RATING ADD RATING, 
        // Retrieve and trim form data
        $student_number = trim($_POST['studentId']);
        $sessions_attended = floatval(trim($_POST['sessionsAttended']));
        $sessions_total = floatval(trim($_POST['sessionsTotal']));
        if ($sessions_total > 1000){
            $sessions_total = 1000;
        } else if ($sessions_total < 0){
            $sessions_total = 0;
        }

        if ($sessions_attended > $sessions_total){
            $sessions_attended = $sessions_total;
        } else if ($sessions_attended < 0){
            $sessions_attended = 0;
        }
        $date_created = date('Y-m-d H:i:s');

        // Prepare and execute the SQL statement
        $insertStmt = $con->prepare("
            INSERT INTO student_attendance_reviews (
                student_number, sessions_attended, sessions_total, date_created
            ) VALUES (
                :student_number, :sessions_attended, :sessions_total, :date_created
            )
        ");

        $updateStmt = $con->prepare("
            UPDATE student_attendance_reviews SET
                sessions_attended = :sessions_attended,
                sessions_total = :sessions_total,
                date_created = :date_created
            WHERE student_number = :student_number
        ");

        $params = [
            ':student_number' => $student_number,
            ':sessions_attended' => $sessions_attended,
            ':sessions_total' => $sessions_total,
            ':date_created' => $date_created
        ];

        $checkStmt = $con->prepare("SELECT COUNT(*) FROM student_attendance_reviews WHERE student_number = :student_number");
        
        $checkStmt->execute([':student_number' => $params[':student_number']]);
        $exists = $checkStmt->fetchColumn();

        if ($exists) {
            if ($updateStmt->execute($params)) {
                // Redirect to student_info.php
                header("Location: /bridge/edit-review-classes-attendance?studentId=" . $student_number);
                exit();
            } else {
                echo "Error submitting student information.";
            }
        } else {
            if ($insertStmt->execute($params)) {
                // Redirect to student_info.php
                header("Location: /bridge/edit-review-classes-attendance?studentId=" . $student_number);
                exit();
            } else {
                echo "Error updating student information.";
            }
        }
    break;

    case 'awards': // BOARD SUBJECT ADD GRADES, BOARD SUBJECT ADD GRADES, BOARD SUBJECT ADD GRADES, BOARD SUBJECT ADD GRADES, BOARD SUBJECT ADD GRADES, 
        // Retrieve and trim form data
        $student_number = trim($_POST['studentId']);
        $award_count = (int)trim($_POST['awardCount'] ?? 0);
        $date_created = date('Y-m-d H:i:s');

        // Prepare and execute the SQL statement
        $insertStmt = $con->prepare("
            INSERT INTO student_academic_recognition (
                student_number, award_count, date_created
            ) VALUES (
                :student_number, :award_count, :date_created
            )
        ");

        $updateStmt = $con->prepare("
            UPDATE student_academic_recognition SET
                award_count = :award_count
            WHERE student_number = :student_number
        ");

        $params = [
            ':student_number' => $student_number,
            ':award_count' => $award_count,
            ':date_created' => $date_created
        ];

        $checkStmt = $con->prepare("SELECT COUNT(*) FROM student_academic_recognition WHERE student_number = :student_number");
        
        $checkStmt->execute([':student_number' => $params[':student_number']]);
        $exists = $checkStmt->fetchColumn();

        if ($exists) {
            if ($updateStmt->execute([':award_count' => $params[':award_count'], ':student_number' => $params[':student_number']])) {
                // Redirect to student_info.php
                header("Location: /bridge/edit-academic-recognition?studentId=" . $student_number);
                exit();
            } else {
                echo "Error submitting student information.";
            }
        } else {
            if ($insertStmt->execute($params)) {
                // Redirect to student_info.php
                header("Location: /bridge/edit-academic-recognition?studentId=" . $student_number);
                exit();
            } else {
                echo "Error updating student information.";
            }
        }
    break;

    //UPDATE UPDATE UPDATE UPDATE UPDATE UPDATE UPDATE UPDATE UPDATE UPDATE UPDATE UPDATE UPDATE UPDATE UPDATE 

    case 'edit_awards':
        // The form passes student number via the input named 'studentId'
        $student_number = trim($_POST['studentId'] ?? '');
        // The form passes the new count via the input named 'awardCount'
        $award_count = (int)($_POST['awardCount'] ?? 0);

        if (empty($student_number)) {
            // Handle error if student ID is missing
            die("Error: Student number is required for update.");
        }

        // --- UPSERT LOGIC ---
        // 1. Check if a recognition record already exists for the student_number
        $checkSql = "SELECT student_number FROM student_academic_recognition WHERE student_number = ?";
        $checkStmt = $con->prepare($checkSql);
        $checkStmt->execute([$student_number]);
        $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        $success = false;

        if ($exists) {
            // 2. If record exists, UPDATE the award_count
            $updateSql = "UPDATE student_academic_recognition SET award_count = ? WHERE student_number = ?";
            $updateStmt = $con->prepare($updateSql);
            $success = $updateStmt->execute([$award_count, $student_number]);
        } else {
            // 3. If record does not exist, INSERT a new record
            $insertSql = "INSERT INTO student_academic_recognition (student_number, award_count) VALUES (?, ?)";
            $insertStmt = $con->prepare($insertSql);
            $success = $insertStmt->execute([$student_number, $award_count]);
        }

        if ($success) {
            // Success: Redirect back to the student list page
            header("Location: /bridge/edit-academic-recognition?studentId=" . $student_number);
            exit();
        } else {
            // Failure
            echo "Error processing student academic recognition (Update/Insert failed).";
        }
        break;

                default:
            echo "Invalid form submission.";
            break;
}       
}
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Close connection
$con = null;
?>