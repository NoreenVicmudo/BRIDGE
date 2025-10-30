<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";

header('Content-Type: application/json');

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $metric = $_POST['metric'] ?? '';
        $response = ['isValid' => true, 'message' => ''];

        switch ($metric) {
            case 'BoardSubjects':
                $program_id = trim($_POST['program_id']);
                $subject_name = trim($_POST['subject_name']);
                $subject_id = trim($_POST['subject_id']);

                // Check for duplicate: SAME PROGRAM && SAME SUBJECT
                $checkStmt = $con->prepare("
                    SELECT COUNT(*) FROM board_subjects 
                    WHERE program_id = :program_id 
                    AND LOWER(subject_name) = LOWER(:subject_name) 
                    AND is_active = 1
                    AND subject_id != :subject_id
                ");
                $checkStmt->execute([
                    ':program_id' => $program_id,
                    ':subject_name' => $subject_name,
                    ':subject_id' => $subject_id === "AddBoardSubject" ? -1 : $subject_id
                ]);
                $count = $checkStmt->fetchColumn();

                if ($count > 0) {
                    $response['isValid'] = false;
                    $response['message'] = "⚠️ This board subject already exists in the selected program. Please choose a different name or edit the existing one.";
                }
                break;

            case 'GeneralSubjects':
                $program_id = trim($_POST['program_id']);
                $subject_name = trim($_POST['subject_name']);
                $subject_id = trim($_POST['subject_id']);

                // Check for duplicate: SAME SUBJECT NAME && PROGRAM
                $checkStmt = $con->prepare("
                    SELECT COUNT(*) FROM general_subjects 
                    WHERE program_id = :program_id 
                    AND LOWER(general_subject_name) = LOWER(:subject_name) 
                    AND is_active = 1
                    AND general_subject_id != :subject_id
                ");
                $checkStmt->execute([
                    ':program_id' => $program_id,
                    ':subject_name' => $subject_name,
                    ':subject_id' => $subject_id === "AddSubject" ? -1 : $subject_id
                ]);
                $count = $checkStmt->fetchColumn();

                if ($count > 0) {
                    $response['isValid'] = false;
                    $response['message'] = "⚠️ This general subject already exists in the selected program. Please choose a different name or edit the existing one.";
                }
                break;

            case 'TypeOfRating':
                $program_id = trim($_POST['program_id']);
                $category_name = trim($_POST['category_name']);
                $category_id = trim($_POST['category_id']);

                // Check for duplicate: SAME CATEGORY && SAME PROGRAM
                $checkStmt = $con->prepare("
                    SELECT COUNT(*) FROM rating_category 
                    WHERE program_id = :program_id 
                    AND LOWER(category_name) = LOWER(:category_name) 
                    AND is_active = 1
                    AND category_id != :category_id
                ");
                $checkStmt->execute([
                    ':program_id' => $program_id,
                    ':category_name' => $category_name,
                    ':category_id' => $category_id === "AddRating" ? -1 : $category_id
                ]);
                $count = $checkStmt->fetchColumn();

                if ($count > 0) {
                    $response['isValid'] = false;
                    $response['message'] = "⚠️ This rating category already exists in the selected program. Please choose a different name or edit the existing one.";
                }
                break;

            case 'TypeOfSimulation':
                $program_id = trim($_POST['program_id']);
                $simulation_name = trim($_POST['simulation_name']);
                $simulation_id = trim($_POST['simulation_id']);

                // Check for duplicate: SAME SIMULATION NAME && PROGRAM
                $checkStmt = $con->prepare("
                    SELECT COUNT(*) FROM simulation_exams 
                    WHERE program_id = :program_id 
                    AND LOWER(simulation_name) = LOWER(:simulation_name) 
                    AND is_active = 1
                    AND simulation_id != :simulation_id
                ");
                $checkStmt->execute([
                    ':program_id' => $program_id,
                    ':simulation_name' => $simulation_name,
                    ':simulation_id' => $simulation_id === "AddSimulation" ? -1 : $simulation_id
                ]);
                $count = $checkStmt->fetchColumn();

                if ($count > 0) {
                    $response['isValid'] = false;
                    $response['message'] = "⚠️ This simulation exam already exists in the selected program. Please choose a different name or edit the existing one.";
                }
                break;

            case 'College':
                $college_name = trim($_POST['college_name']);
                $college_id = trim($_POST['college_id']);

                // Check for duplicate: SAME COLLEGE NAME
                $checkStmt = $con->prepare("
                    SELECT COUNT(*) FROM colleges 
                    WHERE LOWER(name) = LOWER(:college_name) 
                    AND is_active = 1
                    AND college_id != :college_id
                ");
                $checkStmt->execute([
                    ':college_name' => $college_name,
                    ':college_id' => $college_id === "AddCollege" ? -1 : $college_id
                ]);
                $count = $checkStmt->fetchColumn();

                if ($count > 0) {
                    $response['isValid'] = false;
                    $response['message'] = "⚠️ A college with this name already exists. Please choose a different name or edit the existing one.";
                }
                break;

            case 'Program':
                $program_name = trim($_POST['program_name']);
                $college_id = trim($_POST['college_id']);
                $program_id = trim($_POST['program_id']);

                // Check for duplicate: SAME PROGRAM && SAME COLLEGE
                // If adding new program (AddProgram), check if any program with same name exists in this college
                // If editing existing program, check if any OTHER program with same name exists in this college
                if ($program_id === "AddProgram") {
                    $checkStmt = $con->prepare("
                        SELECT COUNT(*) FROM programs 
                        WHERE college_id = :college_id 
                        AND LOWER(name) = LOWER(:program_name) 
                        AND is_active = 1
                    ");
                    $checkStmt->execute([
                        ':college_id' => $college_id,
                        ':program_name' => $program_name
                    ]);
                } else {
                    $checkStmt = $con->prepare("
                        SELECT COUNT(*) FROM programs 
                        WHERE college_id = :college_id 
                        AND LOWER(name) = LOWER(:program_name) 
                        AND is_active = 1
                        AND program_id != :program_id
                    ");
                    $checkStmt->execute([
                        ':college_id' => $college_id,
                        ':program_name' => $program_name,
                        ':program_id' => $program_id
                    ]);
                }
                $count = $checkStmt->fetchColumn();

                if ($count > 0) {
                    $response['isValid'] = false;
                    $response['message'] = "⚠️ A program with this name already exists in the selected college. Please choose a different name or edit the existing one.";
                }
                break;

            case 'CurrentLivingArrangement':
                $arrangement_name = trim($_POST['arrangement_name']);
                $arrangement_id = trim($_POST['arrangement_id']);

                // Check for duplicate: SAME ARRANGEMENT NAME
                $checkStmt = $con->prepare("
                    SELECT COUNT(*) FROM living_arrangement 
                    WHERE LOWER(arrangement_name) = LOWER(:arrangement_name) 
                    AND is_active = 1
                    AND arrangement_id != :arrangement_id
                ");
                $checkStmt->execute([
                    ':arrangement_name' => $arrangement_name,
                    ':arrangement_id' => $arrangement_id === "AddLivingArrangement" ? -1 : $arrangement_id
                ]);
                $count = $checkStmt->fetchColumn();

                if ($count > 0) {
                    $response['isValid'] = false;
                    $response['message'] = "⚠️ This living arrangement already exists. Please choose a different name or edit the existing one.";
                }
                break;

            case 'LanguageSpoken':
                $language_name = trim($_POST['language_name']);
                $language_id = trim($_POST['language_id']);

                // Check for duplicate: SAME LANGUAGE NAME
                $checkStmt = $con->prepare("
                    SELECT COUNT(*) FROM language_spoken 
                    WHERE LOWER(language_name) = LOWER(:language_name) 
                    AND is_active = 1
                    AND language_id != :language_id
                ");
                $checkStmt->execute([
                    ':language_name' => $language_name,
                    ':language_id' => $language_id === "AddLanguage" ? -1 : $language_id
                ]);
                $count = $checkStmt->fetchColumn();

                if ($count > 0) {
                    $response['isValid'] = false;
                    $response['message'] = "⚠️ This language already exists. Please choose a different name or edit the existing one.";
                }
                break;

            case 'MockSubjects':
                $program_id = trim($_POST['program_id']);
                $subject_name = trim($_POST['subject_name']);
                $subject_id = trim($_POST['subject_id']);

                // Check for duplicate: SAME MOCK SUBJECT NAME && PROGRAM
                $checkStmt = $con->prepare("
                    SELECT COUNT(*) FROM mock_subjects 
                    WHERE program_id = :program_id 
                    AND LOWER(mock_subject_name) = LOWER(:subject_name) 
                    AND is_active = 1
                    AND mock_subject_id != :subject_id
                ");
                $checkStmt->execute([
                    ':program_id' => $program_id,
                    ':subject_name' => $subject_name,
                    ':subject_id' => $subject_id === "AddMockSubject" ? -1 : $subject_id
                ]);
                $count = $checkStmt->fetchColumn();

                if ($count > 0) {
                    $response['isValid'] = false;
                    $response['message'] = "⚠️ This mock subject already exists in the selected program. Please choose a different name or edit the existing one.";
                }
                break;

            default:
                $response['isValid'] = false;
                $response['message'] = "Invalid metric";
                break;
        }

        echo json_encode($response);
    } else {
        echo json_encode(['isValid' => false, 'message' => 'Invalid request method']);
    }
} catch (PDOException $e) {
    echo json_encode(['isValid' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['isValid' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$con = null;
?>
