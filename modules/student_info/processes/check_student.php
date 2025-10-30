<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";

if(isset($_POST['studentNumber'])){
    $studentNumber = $_POST['studentNumber'];
    $mode = $_POST['mode'];
    $level = $_SESSION['level'] ?? null;
    $userId = $_SESSION['id'] ?? null;

    if($mode == "section") {
        $filter_academic_year = $_SESSION['filter_academic_year'] ?? '';
        $filter_college       = $_SESSION['filter_college'] ?? '';
        $filter_program       = $_SESSION['filter_program'] ?? '';
        $filter_semester      = $_SESSION['filter_semester'] ?? '';
        $filter_year_level    = $_SESSION['filter_year_level'] ?? '';
        $filter_section       = $_SESSION['filter_section'] ?? '';
        $filter_year_batch    = $_SESSION['filter_year_batch'] ?? '';
        $filter_board_batch   = $_SESSION['filter_board_batch'] ?? '';
        $filter_type          = $_SESSION['filter_type'] ?? '';

        $stmt = $con->prepare("SELECT student_number FROM student_info WHERE student_number = :student_number AND is_active = 1 AND student_college = :student_college AND student_program = :student_program LIMIT 1");
            $stmt->bindParam(':student_number', $studentNumber, PDO::PARAM_STR);
            $stmt->bindParam(':student_college', $filter_college, PDO::PARAM_STR);
            $stmt->bindParam(':student_program',  $filter_program, PDO::PARAM_STR);
            $stmt->execute();

        if($stmt->rowCount() > 0){
            if($filter_type == 'section'){
                $checkStmt = $con->prepare("SELECT COUNT(*) FROM student_section WHERE student_number = :student_number AND is_active = 0 AND semester = :semester AND year_level = :year_level AND academic_year = :academic_year AND program_id = :program_id AND section = :section LIMIT 1");
                // Bind parameters
                $checkStmt->bindParam(':student_number', $studentNumber, PDO::PARAM_STR);
                $checkStmt->bindParam(':academic_year', $filter_academic_year, PDO::PARAM_STR);
                $checkStmt->bindParam(':semester',  $filter_semester, PDO::PARAM_STR);
                $checkStmt->bindParam(':year_level', $filter_year_level, PDO::PARAM_STR);
                $checkStmt->bindParam(':program_id', $filter_program, PDO::PARAM_STR);
                $checkStmt->bindParam(':section', $filter_section, PDO::PARAM_STR);
                $checkStmt->execute();
                $exists = $checkStmt->fetchColumn();
                if($exists){
                    $updateStmt = $con->prepare("
                    UPDATE student_section set is_active = 1 WHERE student_number = :student_number AND is_active = 0 AND semester = :semester AND year_level = :year_level AND academic_year = :academic_year AND program_id = :program_id AND section = :section LIMIT 1
                    ");
                    // Bind parameters
                    $updateStmt->bindParam(':student_number', $studentNumber, PDO::PARAM_STR);
                    $updateStmt->bindParam(':academic_year', $filter_academic_year, PDO::PARAM_STR);
                    $updateStmt->bindParam(':semester',  $filter_semester, PDO::PARAM_STR);
                    $updateStmt->bindParam(':year_level', $filter_year_level, PDO::PARAM_STR);
                    $updateStmt->bindParam(':program_id', $filter_program, PDO::PARAM_STR);
                    $updateStmt->bindParam(':section', $filter_section, PDO::PARAM_STR);
                    $updateStmt->execute();
                } else {
                    $insertStmt = $con->prepare("
                    INSERT INTO student_section (
                        student_number, section, year_level, program_id, semester, academic_year
                    ) VALUES (
                        :student_number, :section, :year_level, :program_id, :semester, :academic_year 
                    )
                    ");
                    // Bind parameters
                    $insertStmt->bindParam(':student_number', $studentNumber, PDO::PARAM_STR);
                    $insertStmt->bindParam(':academic_year', $filter_academic_year, PDO::PARAM_STR);
                    $insertStmt->bindParam(':semester',  $filter_semester, PDO::PARAM_STR);
                    $insertStmt->bindParam(':year_level', $filter_year_level, PDO::PARAM_STR);
                    $insertStmt->bindParam(':program_id', $filter_program, PDO::PARAM_STR);
                    $insertStmt->bindParam(':section', $filter_section, PDO::PARAM_STR);
                    $insertStmt->execute();
                }

            } else if ($filter_type == 'batch'){
                $checkStmt = $con->prepare("SELECT COUNT(*) FROM student_section WHERE student_number = :student_number AND is_active = 0 AND semester = :semester AND year_level = :year_level AND academic_year = :academic_year AND program_id = :program_id AND section = :section LIMIT 1");
                // Bind parameters
                $checkStmt->bindParam(':student_number', $studentNumber, PDO::PARAM_STR);
                $checkStmt->bindParam(':academic_year', $filter_academic_year, PDO::PARAM_STR);
                $checkStmt->bindParam(':semester',  $filter_semester, PDO::PARAM_STR);
                $checkStmt->bindParam(':year_level', $filter_year_level, PDO::PARAM_STR);
                $checkStmt->bindParam(':program_id', $filter_program, PDO::PARAM_STR);
                $checkStmt->bindParam(':section', $filter_section, PDO::PARAM_STR);
                $checkStmt->execute();
                $exists = $checkStmt->fetchColumn();
                if($exists){
                    $updateStmt = $con->prepare("
                    UPDATE board_batch set is_active = 1 WHERE student_number = :student_number AND is_active = 0 AND year = :year AND program_id = :program_id AND batch_number = :batch_number LIMIT 1
                    ");
                    // Bind parameters
                    $updateStmt->bindParam(':student_number', $studentNumber, PDO::PARAM_STR);
                    $updateStmt->bindParam(':year', $filter_year_batch, PDO::PARAM_STR);
                    $updateStmt->bindParam(':program_id', $filter_program, PDO::PARAM_STR);
                    $updateStmt->bindParam(':batch_number', $filter_board_batch, PDO::PARAM_STR);
                    $updateStmt->execute();
                } else {
                    $insertStmt = $con->prepare("
                    INSERT INTO board_batch (
                        student_number, year, program_id, batch_number
                    ) VALUES (
                        :student_number, :year, :program_id, :batch_number
                    )
                    ");
                    // Bind parameters
                    $insertStmt->bindParam(':student_number', $studentNumber, PDO::PARAM_STR);
                    $insertStmt->bindParam(':year', $filter_year_batch, PDO::PARAM_STR);
                    $insertStmt->bindParam(':program_id', $filter_program, PDO::PARAM_STR);
                    $insertStmt->bindParam(':batch_number',  $filter_board_batch, PDO::PARAM_STR);
                    $insertStmt->execute();
                }
            }
                    // Audit: insert rows into student_delete_audit table
                    $auditStmt = $con->prepare("INSERT INTO student_add_audit (student_number, added_by, added_at, remarks, location) VALUES (?, ?, NOW(), ?, ?)");
                    // Build a safe location string (avoid PHP backticks which execute shell commands)
                    if ($filter_type == 'section') {
                        $locPart = 'SECTION ' . ($filter_section !== '' ? $filter_section : 'UNKNOWN');
                        $remarks = "$$filter_academic_year - $filter_semester Semester - Year $filter_year_level";
                    } else if ($filter_type == 'batch') {
                        $locPart = 'BATCH ' . ($filter_year_batch !== '' ? $filter_year_batch : 'UNKNOWN') . '-' . ($filter_board_batch !== '' ? $filter_board_batch : 'UNKNOWN');
                        $remarks = "$filter_year_batch - $filter_board_batch";
                    } else {
                        $locPart = 'UNKNOWN';
                    }


                $auditStmt->execute([$studentNumber, $userId, $remarks, $locPart]);

                echo json_encode(["status" => "exists"]);
        } else {
            echo json_encode(["status" => "not_exists"]);
        }
    } else if ($mode == "masterlist") {
        $filter_college       = $_SESSION['filter_college'] ?? '';
        $filter_program       = $_SESSION['filter_program'] ?? '';

        $stmt = $con->prepare("SELECT is_active FROM student_info WHERE student_number = :student_number AND student_college = :student_college AND student_program = :student_program LIMIT 1");
            $stmt->bindParam(':student_number', $studentNumber, PDO::PARAM_STR);
            $stmt->bindParam(':student_college', $filter_college, PDO::PARAM_STR);
            $stmt->bindParam(':student_program',  $filter_program, PDO::PARAM_STR);
            $stmt->execute();

        if($stmt->rowCount() > 0){
                $checkStmt = $con->prepare("SELECT COUNT(*) FROM student_info WHERE student_number = :student_number AND student_college = :student_college AND student_program = :student_program AND is_active = 0 LIMIT 1");
                // Bind parameters
                $checkStmt->bindParam(':student_number', $studentNumber, PDO::PARAM_STR);
                $checkStmt->bindParam(':student_college', $filter_college, PDO::PARAM_STR);
                $checkStmt->bindParam(':student_program',  $filter_program, PDO::PARAM_STR);
                $checkStmt->execute();
                $exists = $checkStmt->fetchColumn();
                if($exists){
                    $updateStmt = $con->prepare("
                    UPDATE student_info set is_active = 1 WHERE student_number = :student_number AND student_college = :student_college AND student_program = :student_program AND is_active = 0
                    ");
                    // Bind parameters
                    $updateStmt->bindParam(':student_number', $studentNumber, PDO::PARAM_STR);
                    $updateStmt->bindParam(':student_college', $filter_college, PDO::PARAM_STR);
                    $updateStmt->bindParam(':student_program',  $filter_program, PDO::PARAM_STR);
                    $updateStmt->execute();
                }
                    // Audit: insert rows into student_delete_audit table
                    $auditStmt = $con->prepare("INSERT INTO student_add_audit (student_number, added_by, added_at, remarks, location) VALUES (?, ?, NOW(), ?, ?)");
                    // Build a safe location string (avoid PHP backticks which execute shell commands)
                        $remarks = "RESTORED RECORD";
                        $locPart = 'MASTERLIST';


                $auditStmt->execute([$studentNumber, $userId, $remarks, $locPart]);

                echo json_encode(["status" => "exists"]);
        } else {
            echo json_encode(["status" => "not_exists"]);
        }
    }
}
?>