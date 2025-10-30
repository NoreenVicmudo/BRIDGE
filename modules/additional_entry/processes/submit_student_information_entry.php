<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";

try {
    // Check if form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Ensure session is started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    $metric = $_POST['metric'] ?? '';
    
    // Debug: Log the received metric and user level
    error_log("Received metric: '" . $metric . "' (length: " . strlen($metric) . ")");
    error_log("User level: " . ($_SESSION['level'] ?? 'NOT SET'));
    error_log("Session ID: " . session_id());
    error_log("Session data: " . print_r($_SESSION, true));
    error_log("All POST data: " . print_r($_POST, true));
    
    // Access control based on user level
    $userLevel = isset($_SESSION['level']) ? (int)$_SESSION['level'] : null;
    
    // Check if user is logged in at all
    if (!isset($_SESSION['level']) || $_SESSION['level'] === null) {
        error_log("No user level found in session - user may not be logged in");
        echo "Error: You must be logged in to perform this action.";
        exit;
    }
    
    // Check access permissions for each metric
    // Normalize metric to handle case variations - handle special cases
    $normalizedMetric = $metric;
    if (strtolower($metric) === 'socioeconomicstatus') {
        $normalizedMetric = 'SocioeconomicStatus';
    } elseif (strtolower($metric) === 'college') {
        $normalizedMetric = 'College';
    } elseif (strtolower($metric) === 'program') {
        $normalizedMetric = 'Program';
    } elseif (strtolower($metric) === 'currentlivingarrangement') {
        $normalizedMetric = 'CurrentLivingArrangement';
    } elseif (strtolower($metric) === 'languagespoken') {
        $normalizedMetric = 'LanguageSpoken';
    }
    error_log("Normalized metric: '" . $normalizedMetric . "'");
    
    switch ($normalizedMetric) {
        case 'SocioeconomicStatus':
        case 'College':
            // Only admins (level 0) can access these
            error_log("Access check - User level: " . $userLevel . " (type: " . gettype($userLevel) . ")");
            if ($userLevel !== 0) {
                error_log("Access denied - User level " . $userLevel . " is not admin (0)");
                echo "Error: Access denied. Only administrators can modify " . strtolower($metric) . ". Your level: " . $userLevel;
                exit;
            }
            error_log("Access granted for admin user");
            break;
        case 'Program':
            // Only admins (0) and deans (1) can access
            if (!in_array($userLevel, [0, 1])) {
                echo "Error: Access denied. Only administrators and deans can modify programs.";
                exit;
            }
            break;
        case 'CurrentLivingArrangement':
        case 'LanguageSpoken':
            // Only deans (1), assistants (2), and program heads (3) can access
            if (!in_array($userLevel, [1, 2, 3])) {
                echo "Error: Access denied. Only deans, assistants, and program heads can modify " . strtolower($metric) . ".";
                exit;
            }
            break;
        default:
            error_log("Invalid metric specified: '" . $metric . "' (normalized: '" . $normalizedMetric . "')");
            echo "Error: Invalid metric specified. Received: " . htmlspecialchars($metric);
            exit;
    }

    switch ($normalizedMetric) {
    case 'SocioeconomicStatus': //task dea: Socioeconomic status insertion/updating
        // Debug: Log received data
        error_log("Socioeconomic Status - Received POST data:");
        error_log("rich_min: " . ($_POST['rich_min'] ?? 'NOT SET'));
        error_log("highIncome_min: " . ($_POST['highIncome_min'] ?? 'NOT SET'));
        error_log("highIncome_max: " . ($_POST['highIncome_max'] ?? 'NOT SET'));
        error_log("upperMiddle_min: " . ($_POST['upperMiddle_min'] ?? 'NOT SET'));
        error_log("upperMiddle_max: " . ($_POST['upperMiddle_max'] ?? 'NOT SET'));
        error_log("middleClass_min: " . ($_POST['middleClass_min'] ?? 'NOT SET'));
        error_log("middleClass_max: " . ($_POST['middleClass_max'] ?? 'NOT SET'));
        error_log("lowerMiddle_min: " . ($_POST['lowerMiddle_min'] ?? 'NOT SET'));
        error_log("lowerMiddle_max: " . ($_POST['lowerMiddle_max'] ?? 'NOT SET'));
        error_log("lowIncome_min: " . ($_POST['lowIncome_min'] ?? 'NOT SET'));
        error_log("lowIncome_max: " . ($_POST['lowIncome_max'] ?? 'NOT SET'));
        error_log("poor_max: " . ($_POST['poor_max'] ?? 'NOT SET'));
        
        // Retrieve and trim form data
        $rich_min = trim($_POST['rich_min']);
        $highIncome_min = trim($_POST['highIncome_min']);
        $highIncome_max = trim($_POST['highIncome_max']);
        $upperMiddle_min = trim($_POST['upperMiddle_min']);
        $upperMiddle_max = trim($_POST['upperMiddle_max']);
        $middleClass_min = trim($_POST['middleClass_min']);
        $middleClass_max = trim($_POST['middleClass_max']);
        $lowerMiddle_min = trim($_POST['lowerMiddle_min']);
        $lowerMiddle_max = trim($_POST['lowerMiddle_max']);
        $lowIncome_min = trim($_POST['lowIncome_min']);
        $lowIncome_max = trim($_POST['lowIncome_max']);
        $poor_max = trim($_POST['poor_max']);
        $date_created = date('Y-m-d H:i:s');

        // Input validation for socioeconomic status
        $income_ranges = [
            'rich_min' => $rich_min,
            'highIncome_min' => $highIncome_min,
            'highIncome_max' => $highIncome_max,
            'upperMiddle_min' => $upperMiddle_min,
            'upperMiddle_max' => $upperMiddle_max,
            'middleClass_min' => $middleClass_min,
            'middleClass_max' => $middleClass_max,
            'lowerMiddle_min' => $lowerMiddle_min,
            'lowerMiddle_max' => $lowerMiddle_max,
            'lowIncome_min' => $lowIncome_min,
            'lowIncome_max' => $lowIncome_max,
            'poor_max' => $poor_max
        ];

        foreach ($income_ranges as $field => $value) {
            if (empty($value)) {
                echo "Error: All income ranges must be filled. Missing value for " . str_replace('_', ' ', $field) . ".";
                exit;
            }
            if (!is_numeric($value) || $value < 0) {
                echo "Error: Income values must be positive numbers. Invalid value for " . str_replace('_', ' ', $field) . ": " . htmlspecialchars($value) . ".";
                exit;
            }
        }

        // Check if socioeconomic_status table exists and has data
        $tableCheck = $con->query("SHOW TABLES LIKE 'socioeconomic_status'");
        if ($tableCheck->rowCount() == 0) {
            echo "Error: socioeconomic_status table does not exist.";
            exit;
        }
        
        // Check current data in table
        $checkData = $con->query("SELECT * FROM socioeconomic_status");
        error_log("Current socioeconomic_status table data: " . print_r($checkData->fetchAll(PDO::FETCH_ASSOC), true));

        // Update each socioeconomic status
        $statuses = [
            ['status' => 'RICH', 'minimum' => $rich_min, 'maximum' => null],
            ['status' => 'HIGH INCOME', 'minimum' => $highIncome_min, 'maximum' => $highIncome_max],
            ['status' => 'UPPER MIDDLE', 'minimum' => $upperMiddle_min, 'maximum' => $upperMiddle_max],
            ['status' => 'MIDDLE CLASS', 'minimum' => $middleClass_min, 'maximum' => $middleClass_max],
            ['status' => 'LOWER MIDDLE', 'minimum' => $lowerMiddle_min, 'maximum' => $lowerMiddle_max],
            ['status' => 'LOW INCOME', 'minimum' => $lowIncome_min, 'maximum' => $lowIncome_max],
            ['status' => 'POOR', 'minimum' => null, 'maximum' => $poor_max]
        ];

        $updateStmt = $con->prepare("
            UPDATE socioeconomic_status SET
                minimum = :minimum,
                maximum = :maximum,
                date_created = :date_created
            WHERE status = :status
        ");

        $success = true;
        foreach ($statuses as $status) {
            // Check if the status exists in the table
            $checkStatus = $con->prepare("SELECT COUNT(*) FROM socioeconomic_status WHERE status = :status");
            $checkStatus->execute([':status' => $status['status']]);
            $statusExists = $checkStatus->fetchColumn();
            
            error_log("Status " . $status['status'] . " exists: " . ($statusExists > 0 ? 'YES' : 'NO'));
            
            if ($statusExists == 0) {
                // Insert new record if it doesn't exist
                $insertStmt = $con->prepare("
                    INSERT INTO socioeconomic_status (status, minimum, maximum, date_created) 
                    VALUES (:status, :minimum, :maximum, :date_created)
                ");
                $params = [
                    ':status' => $status['status'],
                    ':minimum' => $status['minimum'],
                    ':maximum' => $status['maximum'],
                    ':date_created' => $date_created
                ];
                
                if (!$insertStmt->execute($params)) {
                    error_log("Failed to insert status: " . $status['status'] . " - " . print_r($insertStmt->errorInfo(), true));
                    $success = false;
                    break;
                } else {
                    error_log("Successfully inserted status: " . $status['status']);
                }
            } else {
                // Update existing record
                $params = [
                    ':status' => $status['status'],
                    ':minimum' => $status['minimum'],
                    ':maximum' => $status['maximum'],
                    ':date_created' => $date_created
                ];
                
                // Debug: Log each update attempt
                error_log("Updating status: " . $status['status'] . " with min: " . $status['minimum'] . " max: " . $status['maximum']);
                
                if (!$updateStmt->execute($params)) {
                    error_log("Failed to update status: " . $status['status'] . " - " . print_r($updateStmt->errorInfo(), true));
                    $success = false;
                    break;
                } else {
                    error_log("Successfully updated status: " . $status['status']);
                }
            }
        }

        if ($success) {
            echo "Successfully updated all socioeconomic status ranges: Rich (₱" . number_format($rich_min) . "+), High Income (₱" . number_format($highIncome_min) . "-₱" . number_format($highIncome_max) . "), Upper Middle (₱" . number_format($upperMiddle_min) . "-₱" . number_format($upperMiddle_max) . "), Middle Class (₱" . number_format($middleClass_min) . "-₱" . number_format($middleClass_max) . "), Lower Middle (₱" . number_format($lowerMiddle_min) . "-₱" . number_format($lowerMiddle_max) . "), Low Income (₱" . number_format($lowIncome_min) . "-₱" . number_format($lowIncome_max) . "), Poor (₱" . number_format($poor_max) . " and below).";
        } else {
            echo "Error: Failed to update socioeconomic status ranges. Please check your input values and try again.";
        }
    break;

    case 'College': //task dea: College insertion/updating
        // Retrieve and trim form data
        $college_id = trim($_POST['language_id']); // Reusing language_id field name for consistency
        $college_name = strtoupper(trim($_POST['language_input_name'])); // Reusing language_input_name field name
        $is_hidden = ($_POST['is_hidden'] ?? '0') === '1' ? 0 : 1; // 0 = hidden, 1 = visible
        // Debug: Log the hide status
        error_log("College Hide Status - POST value: " . ($_POST['is_hidden'] ?? 'not set') . ", is_hidden: " . $is_hidden);
        $date_created = date('Y-m-d H:i:s');

        // Input validation for college
        if (empty($college_name)) {
            echo "Error: College name cannot be empty. Please enter a valid college name.";
            exit;
        }
        if (strlen($college_name) < 3) {
            echo "Error: College name must be at least 3 characters long. Current length: " . strlen($college_name) . " characters.";
            exit;
        }
        if (strlen($college_name) > 100) {
            echo "Error: College name cannot exceed 100 characters. Current length: " . strlen($college_name) . " characters.";
            exit;
        }

        // Prepare and execute the SQL statement
        $insertStmt = $con->prepare("
            INSERT INTO colleges (
                name, is_active, date_created
            ) VALUES (
                :college_name, :is_active, :date_created
            )
        ");

        $updateStmt = $con->prepare("
            UPDATE colleges SET
                name = :college_name,
                is_active = :is_active,
                date_created = :date_created
            WHERE college_id = :college_id
        ");

        $params = [
            ':college_id' => $college_id,
            ':college_name' => $college_name,
            ':is_active' => $is_hidden,
            ':date_created' => $date_created
        ];

        // Check if this is an "Add" operation or an update operation
        if ($college_id === "AddCollege") {
            // This is a new college - always INSERT
            if ($insertStmt->execute([':college_name' => $params[':college_name'], ':is_active' => $params[':is_active'], ':date_created' => $params[':date_created']])) {
                $new_id = $con->lastInsertId();
                echo "Successfully added new college: '" . htmlspecialchars($college_name) . "' (ID: " . $new_id . ").";
            } else {
                echo "Error: Failed to add new college '" . htmlspecialchars($college_name) . "'. Database error occurred.";
            }
        } else {
            // This is an existing college - check if it exists and UPDATE
            $checkStmt = $con->prepare("SELECT COUNT(*) FROM colleges WHERE college_id = :college_id");
            $checkStmt->execute([':college_id' => $college_id]);
            $exists = $checkStmt->fetchColumn();

            if ($exists) {
                if ($updateStmt->execute($params)) {
                    echo "Successfully updated college: '" . htmlspecialchars($college_name) . "' (ID: " . $college_id . ").";
                } else {
                    echo "Error: Failed to update college '" . htmlspecialchars($college_name) . "'. Database error occurred.";
                }
            } else {
                echo "Error: College with ID " . $college_id . " not found or is inactive.";
            }
        }
    break;

    case 'Program': //task dea: Program insertion/updating with college validation
        // Retrieve and trim form data
        $program_id = trim($_POST['language_id']); // Reusing language_id field name for consistency
        $program_name = strtoupper(trim($_POST['language_input_name'])); // Reusing language_input_name field name
        $college_id = trim($_POST['college_id']); // College selection from dropdown
        $is_hidden = ($_POST['is_hidden'] ?? '0') === '1' ? 0 : 1; // 0 = hidden, 1 = visible
        $date_created = date('Y-m-d H:i:s');

        // Input validation for program
        if (empty($program_name)) {
            echo "Error: Program name cannot be empty. Please enter a valid program name.";
            exit;
        }
        if (empty($college_id)) {
            echo "Error: College selection is required. Please select a college for this program.";
            exit;
        }
        if (strlen($program_name) < 3) {
            echo "Error: Program name must be at least 3 characters long. Current length: " . strlen($program_name) . " characters.";
            exit;
        }
        if (strlen($program_name) > 100) {
            echo "Error: Program name cannot exceed 100 characters. Current length: " . strlen($program_name) . " characters.";
            exit;
        }
        if (!is_numeric($college_id)) {
            echo "Error: Invalid college selection. Please select a valid college from the dropdown.";
            exit;
        }

        // Prepare and execute the SQL statement
        $insertStmt = $con->prepare("
            INSERT INTO programs (
                college_id, name, is_active, date_created
            ) VALUES (
                :college_id, :program_name, :is_active, :date_created
            )
        ");

        $updateStmt = $con->prepare("
            UPDATE programs SET
                college_id = :college_id,
                name = :program_name,
                is_active = :is_active,
                date_created = :date_created
            WHERE program_id = :program_id
        ");

        $params = [
            ':program_id' => $program_id,
            ':college_id' => $college_id,
            ':program_name' => $program_name,
            ':is_active' => $is_hidden,
            ':date_created' => $date_created
        ];

        // Check if this is an "Add" operation or an update operation
        if ($program_id === "AddProgram") {
            // This is a new program - always INSERT
            if ($insertStmt->execute([':college_id' => $params[':college_id'], ':program_name' => $params[':program_name'], ':is_active' => $params[':is_active'], ':date_created' => $params[':date_created']])) {
                $new_id = $con->lastInsertId();
                echo "Successfully added new program: '" . htmlspecialchars($program_name) . "' (ID: " . $new_id . ") under College ID " . $college_id . ".";
            } else {
                echo "Error: Failed to add new program '" . htmlspecialchars($program_name) . "'. Database error occurred.";
            }
        } else {
            // This is an existing program - check if it exists and UPDATE
            $checkStmt = $con->prepare("SELECT COUNT(*) FROM programs WHERE program_id = :program_id");
            $checkStmt->execute([':program_id' => $program_id]);
            $exists = $checkStmt->fetchColumn();

            if ($exists) {
                if ($updateStmt->execute($params)) {
                    echo "Successfully updated program: '" . htmlspecialchars($program_name) . "' (ID: " . $program_id . ") under College ID " . $college_id . ".";
                } else {
                    echo "Error: Failed to update program '" . htmlspecialchars($program_name) . "'. Database error occurred.";
                }
            } else {
                echo "Error: Program with ID " . $program_id . " not found or is inactive.";
            }
        }
    break;

    case 'CurrentLivingArrangement': //task dea: Current living arrangement insertion/updating
        // Retrieve and trim form data
        $arrangement_id = trim($_POST['language_id']); // Reusing language_id field name for consistency
        $arrangement_name = strtoupper(trim($_POST['language_input_name'])); // Reusing language_input_name field name
        $is_hidden = ($_POST['is_hidden'] ?? '0') === '1' ? 0 : 1; // 0 = hidden, 1 = visible
        $date_created = date('Y-m-d H:i:s');

        // Input validation for living arrangement
        if (empty($arrangement_name)) {
            echo "Error: Living arrangement name cannot be empty. Please enter a valid arrangement name.";
            exit;
        }
        if (strlen($arrangement_name) < 3) {
            echo "Error: Living arrangement name must be at least 3 characters long. Current length: " . strlen($arrangement_name) . " characters.";
            exit;
        }
        if (strlen($arrangement_name) > 20) {
            echo "Error: Living arrangement name cannot exceed 20 characters. Current length: " . strlen($arrangement_name) . " characters.";
            exit;
        }

        // Prepare and execute the SQL statement
        $insertStmt = $con->prepare("
            INSERT INTO living_arrangement (
                arrangement_name, is_active, date_created
            ) VALUES (
                :arrangement_name, :is_active, :date_created
            )
        ");

        $updateStmt = $con->prepare("
            UPDATE living_arrangement SET
                arrangement_name = :arrangement_name,
                is_active = :is_active,
                date_created = :date_created
            WHERE arrangement_id = :arrangement_id
        ");

        $params = [
            ':arrangement_id' => $arrangement_id,
            ':arrangement_name' => $arrangement_name,
            ':is_active' => $is_hidden,
            ':date_created' => $date_created
        ];

        // Check if this is an "Add" operation or an update operation
        if ($arrangement_id === "AddLivingArrangement") {
            // This is a new living arrangement - always INSERT
            if ($insertStmt->execute([':arrangement_name' => $params[':arrangement_name'], ':is_active' => $params[':is_active'], ':date_created' => $params[':date_created']])) {
                $new_id = $con->lastInsertId();
                echo "Successfully added new living arrangement: '" . htmlspecialchars($arrangement_name) . "' (ID: " . $new_id . ").";
            } else {
                echo "Error: Failed to add new living arrangement '" . htmlspecialchars($arrangement_name) . "'. Database error occurred.";
            }
        } else {
            // This is an existing living arrangement - check if it exists and UPDATE
            $checkStmt = $con->prepare("SELECT COUNT(*) FROM living_arrangement WHERE arrangement_id = :arrangement_id");
            $checkStmt->execute([':arrangement_id' => $arrangement_id]);
            $exists = $checkStmt->fetchColumn();

            if ($exists) {
                if ($updateStmt->execute($params)) {
                    echo "Successfully updated living arrangement: '" . htmlspecialchars($arrangement_name) . "' (ID: " . $arrangement_id . ").";
                } else {
                    echo "Error: Failed to update living arrangement '" . htmlspecialchars($arrangement_name) . "'. Database error occurred.";
                }
            } else {
                echo "Error: Living arrangement with ID " . $arrangement_id . " not found or is inactive.";
            }
        }
    break;

    case 'LanguageSpoken': //task dea: Language spoken (already working)
        // Retrieve and trim form data
        $language_id = trim($_POST['language_id']);
        $language_name = strtoupper(trim($_POST['language_input_name']));
        $is_hidden = ($_POST['is_hidden'] ?? '0') === '1' ? 0 : 1; // 0 = hidden, 1 = visible
        $date_created = date('Y-m-d H:i:s');

        // Input validation for language
        if (empty($language_name)) {
            echo "Error: Language name cannot be empty. Please enter a valid language name.";
            exit;
        }
        if (strlen($language_name) < 2) {
            echo "Error: Language name must be at least 2 characters long. Current length: " . strlen($language_name) . " characters.";
            exit;
        }
        if (strlen($language_name) > 20) {
            echo "Error: Language name cannot exceed 20 characters. Current length: " . strlen($language_name) . " characters.";
            exit;
        }

        // Prepare and execute the SQL statement
        $insertStmt = $con->prepare("
            INSERT INTO language_spoken (
                language_name, is_active, date_created
            ) VALUES (
                :language_name, :is_active, :date_created
            )
        ");

        $updateStmt = $con->prepare("
            UPDATE language_spoken SET
                language_name = :language_name,
                is_active = :is_active,
                date_created = :date_created
            WHERE language_id = :language_id
        ");

        $params = [
            ':language_id' => $language_id,
            ':language_name' => $language_name,
            ':is_active' => $is_hidden,
            ':date_created' => $date_created
        ];

        // Check if this is an "Add" operation or an update operation
        if ($language_id === "AddLanguage") {
            // This is a new language - always INSERT
            if ($insertStmt->execute([':language_name' => $params[':language_name'], ':is_active' => $params[':is_active'], ':date_created' => $params[':date_created']])) {
                $new_id = $con->lastInsertId();
                echo "Successfully added new language: '" . htmlspecialchars($language_name) . "' (ID: " . $new_id . ").";
            } else {
                echo "Error: Failed to add new language '" . htmlspecialchars($language_name) . "'. Database error occurred.";
            }
        } else {
            // This is an existing language - check if it exists and UPDATE
            $checkStmt = $con->prepare("SELECT COUNT(*) FROM language_spoken WHERE language_id = :language_id");
            $checkStmt->execute([':language_id' => $language_id]);
            $exists = $checkStmt->fetchColumn();

            if ($exists) {
                if ($updateStmt->execute($params)) {
                    echo "Successfully updated language: '" . htmlspecialchars($language_name) . "' (ID: " . $language_id . ").";
                } else {
                    echo "Error: Failed to update language '" . htmlspecialchars($language_name) . "'. Database error occurred.";
                }
            } else {
                echo "Error: Language with ID " . $language_id . " not found or is inactive.";
            }
        }
    break;

    
    default:
        echo "Error: Invalid form submission. Please select a valid metric from the dropdown.";
        break;
}       
}
} catch (PDOException $e) {
    echo "Error: Database connection failed. Please contact the administrator. Technical details: " . htmlspecialchars($e->getMessage());
}

// Close connection
$con = null;
?>