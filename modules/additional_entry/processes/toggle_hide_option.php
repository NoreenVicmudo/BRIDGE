<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";

try {
    // Check if form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $option_type = $_POST['option_type'] ?? '';
        $option_id = trim($_POST['option_id']);
        $is_hidden = isset($_POST['is_hidden']) ? 1 : 0; // 1 = hidden, 0 = visible
        $date_updated = date('Y-m-d H:i:s');

        // Validate input
        if (empty($option_type) || empty($option_id)) {
            echo "Error: Missing required parameters.";
            exit;
        }

        // Map option types to their respective tables and columns
        $table_mappings = [
            'college' => [
                'table' => 'colleges',
                'id_column' => 'college_id',
                'name_column' => 'name'
            ],
            'program' => [
                'table' => 'programs', 
                'id_column' => 'program_id',
                'name_column' => 'name'
            ],
            'living_arrangement' => [
                'table' => 'living_arrangement',
                'id_column' => 'arrangement_id', 
                'name_column' => 'arrangement_name'
            ],
            'language' => [
                'table' => 'language_spoken',
                'id_column' => 'language_id',
                'name_column' => 'language_name'
            ],
            'board_subject' => [
                'table' => 'board_subjects',
                'id_column' => 'subject_id',
                'name_column' => 'subject_name'
            ],
            'general_subject' => [
                'table' => 'general_subjects',
                'id_column' => 'general_subject_id',
                'name_column' => 'general_subject_name'
            ],
            'rating_category' => [
                'table' => 'rating_category',
                'id_column' => 'category_id',
                'name_column' => 'category_name'
            ],
            'simulation_exam' => [
                'table' => 'simulation_exams',
                'id_column' => 'simulation_id',
                'name_column' => 'simulation_name'
            ],
            'mock_subject' => [
                'table' => 'mock_subjects',
                'id_column' => 'mock_subject_id',
                'name_column' => 'mock_subject_name'
            ]
        ];

        // Check if option type is valid
        if (!array_key_exists($option_type, $table_mappings)) {
            echo "Error: Invalid option type.";
            exit;
        }

        $table_info = $table_mappings[$option_type];
        $table_name = $table_info['table'];
        $id_column = $table_info['id_column'];
        $name_column = $table_info['name_column'];

        // First, get the current name for the response
        $nameStmt = $con->prepare("SELECT {$name_column} FROM {$table_name} WHERE {$id_column} = :option_id");
        $nameStmt->execute([':option_id' => $option_id]);
        $current_name = $nameStmt->fetchColumn();

        if (!$current_name) {
            echo "Error: Option not found.";
            exit;
        }

        // Update the is_active status
        $updateStmt = $con->prepare("
            UPDATE {$table_name} 
            SET is_active = :is_active, date_created = :date_updated
            WHERE {$id_column} = :option_id
        ");

        $params = [
            ':is_active' => $is_hidden ? 0 : 1, // 0 = hidden, 1 = visible
            ':date_updated' => $date_updated,
            ':option_id' => $option_id
        ];

        if ($updateStmt->execute($params)) {
            $status = $is_hidden ? 'hidden' : 'shown';
            echo "Successfully {$status} {$option_type}: '" . htmlspecialchars($current_name) . "' (ID: {$option_id}).";
            
            // If hiding a college, also hide all programs under that college
            if ($option_type === 'college' && $is_hidden) {
                $hideProgramsStmt = $con->prepare("
                    UPDATE programs 
                    SET is_active = 0, date_created = :date_updated
                    WHERE college_id = :college_id
                ");
                $hideProgramsStmt->execute([
                    ':date_updated' => $date_updated,
                    ':college_id' => $option_id
                ]);
                echo " Also hidden all programs under this college.";
            }
            // If showing a college, also show all programs under that college
            else if ($option_type === 'college' && !$is_hidden) {
                $showProgramsStmt = $con->prepare("
                    UPDATE programs 
                    SET is_active = 1, date_created = :date_updated
                    WHERE college_id = :college_id
                ");
                $showProgramsStmt->execute([
                    ':date_updated' => $date_updated,
                    ':college_id' => $option_id
                ]);
                echo " Also shown all programs under this college.";
            }
        } else {
            echo "Error: Failed to update {$option_type} status.";
        }
    }
} catch (PDOException $e) {
    echo "Error: Database connection failed. Please contact the administrator. Technical details: " . htmlspecialchars($e->getMessage());
}

// Close connection
$con = null;
?>
