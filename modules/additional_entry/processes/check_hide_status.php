<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";

try {
    // Check if form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $option_type = $_POST['option_type'] ?? '';
        $option_id = trim($_POST['option_id']);

        // Validate input
        if (empty($option_type) || empty($option_id)) {
            echo json_encode(['error' => 'Missing required parameters']);
            exit;
        }

        // Map option types to their respective tables and columns
        $table_mappings = [
            'college' => [
                'table' => 'colleges',
                'id_column' => 'college_id'
            ],
            'program' => [
                'table' => 'programs', 
                'id_column' => 'program_id'
            ],
            'living_arrangement' => [
                'table' => 'living_arrangement',
                'id_column' => 'arrangement_id'
            ],
            'language' => [
                'table' => 'language_spoken',
                'id_column' => 'language_id'
            ],
            'board_subject' => [
                'table' => 'board_subjects',
                'id_column' => 'subject_id'
            ],
            'general_subject' => [
                'table' => 'general_subjects',
                'id_column' => 'general_subject_id'
            ],
            'rating_category' => [
                'table' => 'rating_category',
                'id_column' => 'category_id'
            ],
            'simulation_exam' => [
                'table' => 'simulation_exams',
                'id_column' => 'simulation_id'
            ],
            'mock_subject' => [
                'table' => 'mock_subjects',
                'id_column' => 'mock_subject_id'
            ]
        ];

        // Check if option type is valid
        if (!array_key_exists($option_type, $table_mappings)) {
            echo json_encode(['error' => 'Invalid option type']);
            exit;
        }

        $table_info = $table_mappings[$option_type];
        $table_name = $table_info['table'];
        $id_column = $table_info['id_column'];

        // Get the is_active status
        $stmt = $con->prepare("SELECT is_active FROM {$table_name} WHERE {$id_column} = :option_id");
        $stmt->execute([':option_id' => $option_id]);
        $is_active = $stmt->fetchColumn();

        if ($is_active === false) {
            echo json_encode(['error' => 'Option not found']);
            exit;
        }

        // Return the is_active status
        echo json_encode(['is_active' => (int)$is_active]);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
}

// Close connection
$con = null;
?>
