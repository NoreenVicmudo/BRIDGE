<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";

try {
    // Check if form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $metric = $_POST['metric'] ?? '';
    
    // Access control - only deans (1), assistants (2), and program heads (3) can access academic profile
    $userLevel = $_SESSION['level'] ?? null;
    if (!in_array($userLevel, [1, 2, 3])) {
        echo "Error: Access denied. Only deans, assistants, and program heads can modify academic profile data.";
        exit;
    }

    switch ($metric) {
    case 'BoardSubjects': 
        $program_id = trim($_POST['program_id']);
        $subject_id = trim($_POST['subject_id']);
        $subject_name = strtoupper(trim($_POST['subject_name']));
        $is_hidden = ($_POST['is_hidden'] ?? '0') === '1' ? 0 : 1; // 0 = hidden, 1 = visible
        $date_created = date('Y-m-d H:i:s');

        // Prepare and execute the SQL statement
        $insertStmt = $con->prepare("
            INSERT INTO board_subjects (
                program_id, subject_name, is_active, date_created
            ) VALUES (
                :program_id, :subject_name, :is_active, :date_created
            )
        ");

        $updateStmt = $con->prepare("
            UPDATE board_subjects SET
                program_id = :program_id,
                subject_name = :subject_name,
                is_active = :is_active,
                date_created = :date_created
            WHERE subject_id = :subject_id
        ");

        $params = [
            ':program_id' => $program_id,
            ':subject_id' => $subject_id,
            ':subject_name' => $subject_name,
            ':is_active' => $is_hidden,
            ':date_created' => $date_created
        ];

        // Check if this is an "Add" operation or an update operation
        if ($subject_id === "AddBoardSubject") {
            // This is a new board subject - always INSERT
            if ($insertStmt->execute([':program_id' => $params[':program_id'], ':subject_name' => $params[':subject_name'], ':is_active' => $params[':is_active'], ':date_created' => $params[':date_created']])) {
                echo "Adding new board subject was successful!";
            } else {
                echo "Error adding new board subject.";
            }
        } else {
            // This is an existing board subject - check if it exists and UPDATE
            $checkStmt = $con->prepare("SELECT COUNT(*) FROM board_subjects WHERE subject_id = :subject_id");
            $checkStmt->execute([':subject_id' => $subject_id]);
            $exists = $checkStmt->fetchColumn();

            if ($exists) {
                if ($updateStmt->execute($params)) {
                    echo "Updating the board subject was successful!";
                } else {
                    echo "Error updating the board subject.";
                }
            } else {
                echo "Error: Board subject with ID " . $subject_id . " not found or is inactive.";
            }
        }
    break;
    // Additional Entry: GENERAL SUBJECTS (insert/update)
    // Accepts: program_id, general_subject_id (existing or "AddSubject"), general_subject_name
    // Behavior: If id exists and active -> update; else -> insert
    case 'GeneralSubjects': 
        $program_id = trim($_POST['program_id']);
        $general_subject_id = trim($_POST['general_subject_id']);
        $general_subject_name = strtoupper(trim($_POST['general_subject_name']));
        $is_hidden = ($_POST['is_hidden'] ?? '0') === '1' ? 0 : 1; // 0 = hidden, 1 = visible
        $date_created = date('Y-m-d H:i:s');

        $insertStmt = $con->prepare("
            INSERT INTO general_subjects (
                program_id, general_subject_name, is_active, date_created
            ) VALUES (
                :program_id, :general_subject_name, :is_active, :date_created
            )
        ");

        $updateStmt = $con->prepare("
            UPDATE general_subjects SET
                program_id = :program_id,
                general_subject_name = :general_subject_name,
                is_active = :is_active,
                date_created = :date_created
            WHERE general_subject_id = :general_subject_id
        ");

        $params = [
            ':program_id' => $program_id,
            ':general_subject_id' => $general_subject_id,
            ':general_subject_name' => $general_subject_name,
            ':is_active' => $is_hidden,
            ':date_created' => $date_created
        ];

        // Check if this is an "Add" operation or an update operation
        if ($general_subject_id === "AddSubject") {
            // This is a new general subject - always INSERT
            if ($insertStmt->execute([':program_id' => $params[':program_id'], ':general_subject_name' => $params[':general_subject_name'], ':is_active' => $params[':is_active'], ':date_created' => $params[':date_created']])) {
                echo "Adding new general subject was successful!";
            } else {
                echo "Error adding new general subject.";
            }
        } else {
            // This is an existing general subject - check if it exists and UPDATE
            $checkStmt = $con->prepare("SELECT COUNT(*) FROM general_subjects WHERE general_subject_id = :general_subject_id");
            $checkStmt->execute([':general_subject_id' => $general_subject_id]);
            $exists = $checkStmt->fetchColumn();

            if ($exists) {
                if ($updateStmt->execute($params)) {
                    echo "Updating the general subject was successful!";
                } else {
                    echo "Error updating the general subject.";
                }
            } else {
                echo "Error: General subject with ID " . $general_subject_id . " not found or is inactive.";
            }
        }
    break;

    // Additional Entry: TYPE OF RATING (insert/update)
    // Accepts: program_id, category_id (existing or "AddRating"), category_name
    // Behavior: If id exists and active -> update; else -> insert
    case 'TypeOfRating': 
        $program_id = trim($_POST['program_id']);
        $category_id = trim($_POST['category_id']);
        $category_name = strtoupper(trim($_POST['category_name']));
        $is_hidden = ($_POST['is_hidden'] ?? '0') === '1' ? 0 : 1; // 0 = hidden, 1 = visible
        $date_created = date('Y-m-d H:i:s');

        $insertStmt = $con->prepare("
            INSERT INTO rating_category (
                program_id, category_name, is_active, date_created
            ) VALUES (
                :program_id, :category_name, :is_active, :date_created
            )
        ");

        $updateStmt = $con->prepare("
            UPDATE rating_category SET
                program_id = :program_id,
                category_name = :category_name,
                is_active = :is_active,
                date_created = :date_created
            WHERE category_id = :category_id
        ");

        $params = [
            ':program_id' => $program_id,
            ':category_id' => $category_id,
            ':category_name' => $category_name,
            ':is_active' => $is_hidden,
            ':date_created' => $date_created
        ];

        // Check if this is an "Add" operation or an update operation
        if ($category_id === "AddRating") {
            // This is a new rating category - always INSERT
            if ($insertStmt->execute([':program_id' => $params[':program_id'], ':category_name' => $params[':category_name'], ':is_active' => $params[':is_active'], ':date_created' => $params[':date_created']])) {
                echo "Adding new rating category was successful!";
            } else {
                echo "Error adding new rating category.";
            }
        } else {
            // This is an existing rating category - check if it exists and UPDATE
            $checkStmt = $con->prepare("SELECT COUNT(*) FROM rating_category WHERE category_id = :category_id");
            $checkStmt->execute([':category_id' => $category_id]);
            $exists = $checkStmt->fetchColumn();

            if ($exists) {
                if ($updateStmt->execute($params)) {
                    echo "Updating the rating category was successful!";
                } else {
                    echo "Error updating the rating category.";
                }
            } else {
                echo "Error: Rating category with ID " . $category_id . " not found or is inactive.";
            }
        }
    break;

    case 'TypeOfSimulation': // case for Type of Simulation (same logic with board subjects)
        $program_id = trim($_POST['program_id']);
        $simulation_id = trim($_POST['subject_id']); // Using subject_id field for simulation_id
        $simulation_name = strtoupper(trim($_POST['subject_name'])); // Using subject_name field for simulation_name
        $is_hidden = ($_POST['is_hidden'] ?? '0') === '1' ? 0 : 1; // 0 = hidden, 1 = visible
        $date_created = date('Y-m-d H:i:s');

        // Prepare and execute the SQL statement
        $insertStmt = $con->prepare("
            INSERT INTO simulation_exams (
                program_id, simulation_name, is_active, date_created
            ) VALUES (
                :program_id, :simulation_name, :is_active, :date_created
            )
        ");

        $updateStmt = $con->prepare("
            UPDATE simulation_exams SET
                program_id = :program_id,
                simulation_name = :simulation_name,
                is_active = :is_active,
                date_created = :date_created
            WHERE simulation_id = :simulation_id
        ");

        $params = [
            ':program_id' => $program_id,
            ':simulation_id' => $simulation_id,
            ':simulation_name' => $simulation_name,
            ':is_active' => $is_hidden,
            ':date_created' => $date_created
        ];

        // Check if this is an "Add" operation or an update operation
        if ($simulation_id === "AddSimulation") {
            // This is a new simulation exam - always INSERT
            if ($insertStmt->execute([':program_id' => $params[':program_id'], ':simulation_name' => $params[':simulation_name'], ':is_active' => $params[':is_active'], ':date_created' => $params[':date_created']])) {
                echo "Adding new simulation exam was successful!";
            } else {
                echo "Error adding new simulation exam.";
            }
        } else {
            // This is an existing simulation exam - check if it exists and UPDATE
            $checkStmt = $con->prepare("SELECT COUNT(*) FROM simulation_exams WHERE simulation_id = :simulation_id");
            $checkStmt->execute([':simulation_id' => $simulation_id]);
            $exists = $checkStmt->fetchColumn();

            if ($exists) {
                if ($updateStmt->execute($params)) {
                    echo "Updating the simulation exam was successful!";
                } else {
                    echo "Error updating the simulation exam.";
                }
            } else {
                echo "Error: Simulation exam with ID " . $simulation_id . " not found or is inactive.";
            }
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