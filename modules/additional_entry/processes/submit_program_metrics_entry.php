<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";

try {
    // Check if form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $metric = $_POST['metric'] ?? '';
    
    // Access control - only deans (1), assistants (2), and program heads (3) can access program metrics
    $userLevel = $_SESSION['level'] ?? null;
    if (!in_array($userLevel, [1, 2, 3])) {
        echo "Error: Access denied. Only deans, assistants, and program heads can modify program metrics data.";
        exit;
    }

    switch ($metric) {
    case 'MockSubjects': // case for Mock Subjects (same logic with board subjects)
        $program_id = trim($_POST['program_id']);
        $mock_subject_id = trim($_POST['subject_id']); // Using subject_id field for mock_subject_id
        $mock_subject_name = strtoupper(trim($_POST['subject_name'])); // Using subject_name field for mock_subject_name
        $is_hidden = ($_POST['is_hidden'] ?? '0') === '1' ? 0 : 1; // 0 = hidden, 1 = visible
        $date_created = date('Y-m-d H:i:s');

        // Prepare and execute the SQL statement
        $insertStmt = $con->prepare("
            INSERT INTO mock_subjects (
                program_id, mock_subject_name, is_active, date_created
            ) VALUES (
                :program_id, :mock_subject_name, :is_active, :date_created
            )
        ");

        $updateStmt = $con->prepare("
            UPDATE mock_subjects SET
                program_id = :program_id,
                mock_subject_name = :mock_subject_name,
                is_active = :is_active,
                date_created = :date_created
            WHERE mock_subject_id = :mock_subject_id
        ");

        $params = [
            ':program_id' => $program_id,
            ':mock_subject_id' => $mock_subject_id,
            ':mock_subject_name' => $mock_subject_name,
            ':is_active' => $is_hidden,
            ':date_created' => $date_created
        ];

        // Check if this is an "Add" operation or an update operation
        if ($mock_subject_id === "AddMockSubject") {
            // This is a new mock subject - always INSERT
            if ($insertStmt->execute([':program_id' => $params[':program_id'], ':mock_subject_name' => $params[':mock_subject_name'], ':is_active' => $params[':is_active'], ':date_created' => $params[':date_created']])) {
                echo "Adding new mock subject was successful!";
            } else {
                echo "Error adding new mock subject.";
            }
        } else {
            // This is an existing mock subject - check if it exists and UPDATE
            $checkStmt = $con->prepare("SELECT COUNT(*) FROM mock_subjects WHERE mock_subject_id = :mock_subject_id");
            $checkStmt->execute([':mock_subject_id' => $mock_subject_id]);
            $exists = $checkStmt->fetchColumn();

            if ($exists) {
                if ($updateStmt->execute($params)) {
                    echo "Updating the mock subject was successful!";
                } else {
                    echo "Error updating the mock subject.";
                }
            } else {
                echo "Error: Mock subject with ID " . $mock_subject_id . " not found or is inactive.";
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