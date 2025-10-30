<?php
require_once __DIR__ . "/../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";

try {
    // Check if form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $form_type = $_POST['form_type'] ?? '';

    $level = $_SESSION['level'] ?? null;
    $userId = $_SESSION['user_id'] ?? null;

    $filter_academic_year = $_SESSION['filter_academic_year'] ?? '';
    $filter_college       = $_SESSION['filter_college'] ?? '';
    $filter_program       = $_SESSION['filter_program'] ?? '';
    $filter_semester      = $_SESSION['filter_semester'] ?? '';
    $filter_year_level    = $_SESSION['filter_year_level'] ?? '';
    $filter_section       = $_SESSION['filter_section'] ?? '';
    $filter_year_batch    = $_SESSION['filter_year_batch'] ?? '';
    $filter_board_batch   = $_SESSION['filter_board_batch'] ?? '';
    $filter_type          = $_SESSION['filter_type'] ?? '';

    switch ($form_type) {
    case 'add_student_info':
        // Retrieve and trim form data
        $student_number = trim($_POST['studentId']);
        $student_fname = strtoupper(trim($_POST['fname']));
        $student_mname = strtoupper(trim($_POST['mname']));
        $student_lname = strtoupper(trim($_POST['lname']));
        $student_suffix = strtoupper(trim($_POST['Suffix']));
        $student_college = trim($_POST['filterCollege'] ?? $_SESSION['college'] ?? '');
        $student_program = trim($_POST['filterProgram'] ?? $_SESSION['program'] ?? '');
        $student_birthdate = trim($_POST['birthdate']);
        $student_sex = strtoupper(trim($_POST['sex']));
        $student_socioeconomic = trim($_POST['socioeconomicStatus']);
        $student_living = trim($_POST['livingArrangement']);
        $student_address_number = strtoupper(trim($_POST['houseNo']));
        $student_address_street = strtoupper(trim($_POST['street']));
        $student_address_barangay = strtoupper(trim($_POST['barangay']));
        $student_address_city = strtoupper(trim($_POST['city']));
        $student_address_province = strtoupper(trim($_POST['state']));
        $student_address_postal = strtoupper(trim($_POST['postalCode']));
        $student_work = strtoupper(trim($_POST['workStatus']));
        $student_scholarship = strtoupper(trim($_POST['scholarship']));
        $student_language = trim($_POST['language']);
        $student_last_school = strtoupper(trim($_POST['lastSchool']));
        $date_created = date('Y-m-d H:i:s');

        // Prepare and execute the SQL statement
        $stmt = $con->prepare("
            INSERT INTO student_info (
                student_number, student_fname, student_mname, student_lname, student_suffix, student_college, 
                student_program, student_birthdate, student_sex, student_socioeconomic, 
                student_living, student_address_number, student_address_street, student_address_barangay, student_address_city, 
                student_address_province, student_address_postal, student_work, student_scholarship, student_language, 
                student_last_school, date_created
            ) VALUES (
                :student_number, :student_fname, :student_mname, :student_lname, :student_suffix, :student_college, 
                :student_program, :student_birthdate, :student_sex, :student_socioeconomic, 
                :student_living, :student_address_number, :student_address_street, :student_address_barangay, :student_address_city, 
                :student_address_province, :student_address_postal, :student_work, :student_scholarship, :student_language, 
                :student_last_school, :date_created
            )
        ");

        // Bind parameters
        $stmt->bindParam(':student_number', $student_number, PDO::PARAM_STR);
        $stmt->bindParam(':student_fname', $student_fname, PDO::PARAM_STR);
        $stmt->bindParam(':student_mname', $student_mname, PDO::PARAM_STR);
        $stmt->bindParam(':student_lname', $student_lname, PDO::PARAM_STR);
        $stmt->bindParam(':student_suffix', $student_suffix, PDO::PARAM_STR);
        $stmt->bindParam(':student_college', $student_college, PDO::PARAM_STR);
        $stmt->bindParam(':student_program', $student_program, PDO::PARAM_STR);
        $stmt->bindParam(':student_birthdate', $student_birthdate, PDO::PARAM_STR);
        $stmt->bindParam(':student_sex', $student_sex, PDO::PARAM_STR);
        $stmt->bindParam(':student_socioeconomic', $student_socioeconomic, PDO::PARAM_STR);
        $stmt->bindParam(':student_living', $student_living, PDO::PARAM_STR);
        $stmt->bindParam(':student_address_number', $student_address_number, PDO::PARAM_STR);
        $stmt->bindParam(':student_address_street', $student_address_street, PDO::PARAM_STR);
        $stmt->bindParam(':student_address_barangay', $student_address_barangay, PDO::PARAM_STR);
        $stmt->bindParam(':student_address_city', $student_address_city, PDO::PARAM_STR);
        $stmt->bindParam(':student_address_province', $student_address_province, PDO::PARAM_STR);
        $stmt->bindParam(':student_address_postal', $student_address_postal, PDO::PARAM_STR);
        $stmt->bindParam(':student_work', $student_work, PDO::PARAM_STR);
        $stmt->bindParam(':student_scholarship', $student_scholarship, PDO::PARAM_STR);
        $stmt->bindParam(':student_language', $student_language, PDO::PARAM_STR);
        $stmt->bindParam(':student_last_school', $student_last_school, PDO::PARAM_STR);
        $stmt->bindParam(':date_created', $date_created, PDO::PARAM_STR);

        // Execute the statement
        if ($stmt->execute()) {
            
            if($_SESSION['filter_type'] == 'section'){
                $stmt = $con->prepare("
                INSERT INTO student_section (
                    student_number, section, year_level, semester, academic_year
                ) VALUES (
                    :student_number, :section, :year_level, :semester, :academic_year 
                )
            ");

            // Bind parameters
                $stmt->bindParam(':student_number', $student_number, PDO::PARAM_STR);
                $stmt->bindParam(':academic_year', $filter_academic_year, PDO::PARAM_STR);
                $stmt->bindParam(':semester',  $filter_semester, PDO::PARAM_STR);
                $stmt->bindParam(':year_level', $filter_year_level, PDO::PARAM_STR);
                $stmt->bindParam(':section', $filter_section, PDO::PARAM_STR);
                $stmt->execute();

            } else if ($_SESSION['filter_type'] == 'batch'){
                $stmt = $con->prepare("
                INSERT INTO board_batch (
                    student_number, year, batch_number
                ) VALUES (
                    :student_number, :year, :batch_number
                )
            ");

            // Bind parameters
                $stmt->bindParam(':student_number', $student_number, PDO::PARAM_STR);
                $stmt->bindParam(':year', $_SESSION['filter_year_batch'], PDO::PARAM_STR);
                $stmt->bindParam(':batch_number',  $_SESSION['filter_board_batch'], PDO::PARAM_STR);
                $stmt->execute();
            }

                // Audit: insert rows into student_delete_audit table
                $auditStmt = $con->prepare("INSERT INTO student_add_audit (student_number, added_by, added_at, remarks, location) VALUES (?, ?, NOW(), ?, ?)");
                $audit_reason = $reason; // default single reason
                $auditStmt->execute([$student_number, $userId, 'New student. Added to MASTERLIST.', $filter_type == 'section' ? `SECTION $filter_section` : `BATCH $filter_year_batch-$filter_board_batch`]);

            // Redirect to student_info.php
            header("Location: student-information");
            exit();
        } else {
            echo "Error submitting student information.";
        }
    break;
    
    case 'add_student_info_masterlist':
        // Retrieve and trim form data
        $student_number = trim($_POST['studentId']);
        $student_fname = strtoupper(trim($_POST['fname']));
        $student_mname = strtoupper(trim($_POST['mname']));
        $student_lname = strtoupper(trim($_POST['lname']));
        $student_suffix = strtoupper(trim($_POST['Suffix']));
        $student_college = trim($_POST['filterCollege'] ?? $_SESSION['college'] ?? '');
        $student_program = trim($_POST['filterProgram'] ?? $_SESSION['program'] ?? '');
        $student_birthdate = trim($_POST['birthdate']);
        $student_sex = strtoupper(trim($_POST['sex']));
        $student_socioeconomic = trim($_POST['socioeconomicStatus']);
        $student_living = trim($_POST['livingArrangement']);
        $student_address_number = strtoupper(trim($_POST['houseNo']));
        $student_address_street = strtoupper(trim($_POST['street']));
        $student_address_barangay = strtoupper(trim($_POST['barangay']));
        $student_address_city = strtoupper(trim($_POST['city']));
        $student_address_province = strtoupper(trim($_POST['state']));
        $student_address_postal = strtoupper(trim($_POST['postalCode']));
        $student_work = strtoupper(trim($_POST['workStatus']));
        $student_scholarship = strtoupper(trim($_POST['scholarship']));
        $student_language = trim($_POST['language']);
        $student_last_school = strtoupper(trim($_POST['lastSchool']));
        $date_created = date('Y-m-d H:i:s');

        // Prepare and execute the SQL statement
        $stmt = $con->prepare("
            INSERT INTO student_info (
                student_number, student_fname, student_mname, student_lname, student_suffix, student_college, 
                student_program, student_birthdate, student_sex, student_socioeconomic, 
                student_living, student_address_number, student_address_street, student_address_barangay, student_address_city, 
                student_address_province, student_address_postal, student_work, student_scholarship, student_language, 
                student_last_school, date_created
            ) VALUES (
                :student_number, :student_fname, :student_mname, :student_lname, :student_suffix, :student_college, 
                :student_program, :student_birthdate, :student_sex, :student_socioeconomic, 
                :student_living, :student_address_number, :student_address_street, :student_address_barangay, :student_address_city, 
                :student_address_province, :student_address_postal, :student_work, :student_scholarship, :student_language, 
                :student_last_school, :date_created
            )
        ");

        // Bind parameters
        $stmt->bindParam(':student_number', $student_number, PDO::PARAM_STR);
        $stmt->bindParam(':student_fname', $student_fname, PDO::PARAM_STR);
        $stmt->bindParam(':student_mname', $student_mname, PDO::PARAM_STR);
        $stmt->bindParam(':student_lname', $student_lname, PDO::PARAM_STR);
        $stmt->bindParam(':student_suffix', $student_suffix, PDO::PARAM_STR);
        $stmt->bindParam(':student_college', $student_college, PDO::PARAM_STR);
        $stmt->bindParam(':student_program', $student_program, PDO::PARAM_STR);
        $stmt->bindParam(':student_birthdate', $student_birthdate, PDO::PARAM_STR);
        $stmt->bindParam(':student_sex', $student_sex, PDO::PARAM_STR);
        $stmt->bindParam(':student_socioeconomic', $student_socioeconomic, PDO::PARAM_STR);
        $stmt->bindParam(':student_living', $student_living, PDO::PARAM_STR);
        $stmt->bindParam(':student_address_number', $student_address_number, PDO::PARAM_STR);
        $stmt->bindParam(':student_address_street', $student_address_street, PDO::PARAM_STR);
        $stmt->bindParam(':student_address_barangay', $student_address_barangay, PDO::PARAM_STR);
        $stmt->bindParam(':student_address_city', $student_address_city, PDO::PARAM_STR);
        $stmt->bindParam(':student_address_province', $student_address_province, PDO::PARAM_STR);
        $stmt->bindParam(':student_address_postal', $student_address_postal, PDO::PARAM_STR);
        $stmt->bindParam(':student_work', $student_work, PDO::PARAM_STR);
        $stmt->bindParam(':student_scholarship', $student_scholarship, PDO::PARAM_STR);
        $stmt->bindParam(':student_language', $student_language, PDO::PARAM_STR);
        $stmt->bindParam(':student_last_school', $student_last_school, PDO::PARAM_STR);
        $stmt->bindParam(':date_created', $date_created, PDO::PARAM_STR);

        // Execute the statement
        if ($stmt->execute()) {
            // Audit: insert rows into student_delete_audit table
            $auditStmt = $con->prepare("INSERT INTO student_add_audit (student_number, added_by, added_at, remarks, location) VALUES (?, ?, NOW(), ?, ?)");
            $audit_reason = $reason; // default single reason
            $auditStmt->execute([$student_number, $userId, 'New student.', 'MASTERLIST']);

            // Redirect to student_info.php
            header("Location: masterlist");
            exit();
        } else {
            echo "Error submitting student information.";
        }
    break;

    //UPDATE UPDATE UPDATE UPDATE UPDATE UPDATE UPDATE UPDATE UPDATE UPDATE UPDATE UPDATE UPDATE UPDATE UPDATE 

    case 'update_student_info':
        $student_id = $_POST['studentId'] ?? null;
        $academic_year = $_POST['academic_year'] ?? null;

        if (!$student_id) {
            die("Missing student ID.");
        }

        // --- 1. Fetch Old Data ---
        // Select all fields we might update to get the 'Old Value'
        $fields_to_select = implode(', ', [
            'student_number', 'student_fname', 'student_mname', 'student_lname', 'student_suffix', 'student_college',
            'student_program', 'student_birthdate', 'student_sex', 'student_socioeconomic', 'student_living',
            'student_address_number', 'student_address_street', 'student_address_barangay', 'student_address_city', 'student_address_province', 'student_address_postal',
            'student_work', 'student_scholarship', 'student_language', 'student_last_school'
        ]);

        $old_data_sql = "SELECT {$fields_to_select} FROM student_info WHERE student_id = ?";
        $old_data_stmt = $con->prepare($old_data_sql);
        $old_data_stmt->execute([$student_id]);
        $old_data = $old_data_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$old_data) {
            die("Student not found.");
        }

        // --- 2. Prepare New Data and Log Changes ---
        $finalData = [];
        $updated_fields_log = [];

        // The list of fields remains the same for processing POST data
        $fields = array_keys($old_data); // Use the fields fetched from the database

        foreach ($fields as $field) {
            if (isset($_POST[$field]) && $_POST[$field] !== '') {
                $new_value = strtoupper(trim($_POST[$field]));
                $old_value = strtoupper(trim($old_data[$field])); // Clean the old value for comparison

                // Only update and log if the value has actually changed
                if ($new_value !== $old_value) {
                    $finalData[$field] = $new_value;

                    // Log the field name, old value, and new value
                    $updated_fields_log[] = "{$field}: '{$old_value}' -> '{$new_value}'";
                }
            }
        }

        if (empty($finalData)) {
            die("No data changed or no data to update.");
        }

        // --- 3. Prepare and Execute UPDATE Statement ---
        $setClause = implode(", ", array_map(function($field) { return "$field = ?"; }, array_keys($finalData)));
        $values = array_values($finalData);
        $values[] = $student_id; // For the WHERE clause

        $sql = "UPDATE student_info SET $setClause WHERE student_id = ?";
        $stmt = $con->prepare($sql);

        if($stmt->execute($values)){
            // --- 4. Prepare and Execute Audit Log ---
            // Create a string listing all updated fields with old/new values
            $remarks_updated_fields = "Update: " . implode(" | ", $updated_fields_log);

            // Get student_number for audit (using the old value since it might be what's needed for lookup)
            $student_number_for_audit = $old_data['student_number'] ?? $student_id;

            $auditStmt = $con->prepare("INSERT INTO student_update_audit (student_number, updated_by, updated_at, remarks, location) VALUES (?, ?, NOW(), ?, ?)");

            // The remarks now contain the old and new data
            $auditStmt->execute([$student_number_for_audit, $userId, $remarks_updated_fields, $filter_type == 'section' ? `SECTION $filter_section` : `BATCH $filter_year_batch-$filter_board_batch`]);

        } else {
            echo "Error submitting student information.";
        }

        header("Location: edit-student-information?id=" . urlencode($student_id));
        break;

    case 'update_student_info_masterlist':
         $student_id = $_POST['studentId'] ?? null;
        $academic_year = $_POST['academic_year'] ?? null;

        if (!$student_id) {
            die("Missing student ID.");
        }

        // --- 1. Fetch Old Data ---
        // Select all fields we might update to get the 'Old Value'
        $fields_to_select = implode(', ', [
            'student_number', 'student_fname', 'student_mname', 'student_lname', 'student_suffix', 'student_college',
            'student_program', 'student_birthdate', 'student_sex', 'student_socioeconomic', 'student_living',
            'student_address_number', 'student_address_street', 'student_address_barangay', 'student_address_city', 'student_address_province', 'student_address_postal',
            'student_work', 'student_scholarship', 'student_language', 'student_last_school'
        ]);

        $old_data_sql = "SELECT {$fields_to_select} FROM student_info WHERE student_id = ?";
        $old_data_stmt = $con->prepare($old_data_sql);
        $old_data_stmt->execute([$student_id]);
        $old_data = $old_data_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$old_data) {
            die("Student not found.");
        }

        // --- 2. Prepare New Data and Log Changes ---
        $finalData = [];
        $updated_fields_log = [];

        // The list of fields remains the same for processing POST data
        $fields = array_keys($old_data); // Use the fields fetched from the database

        foreach ($fields as $field) {
            if (isset($_POST[$field]) && $_POST[$field] !== '') {
                $new_value = strtoupper(trim($_POST[$field]));
                $old_value = strtoupper(trim($old_data[$field])); // Clean the old value for comparison

                // Only update and log if the value has actually changed
                if ($new_value !== $old_value) {
                    $finalData[$field] = $new_value;

                    // Log the field name, old value, and new value
                    $updated_fields_log[] = "{$field}: '{$old_value}' -> '{$new_value}'";
                }
            }
        }

        if (empty($finalData)) {
            die("No data changed or no data to update.");
        }

        // --- 3. Prepare and Execute UPDATE Statement ---
        $setClause = implode(", ", array_map(function($field) { return "$field = ?"; }, array_keys($finalData)));
        $values = array_values($finalData);
        $values[] = $student_id; // For the WHERE clause

        $sql = "UPDATE student_info SET $setClause WHERE student_id = ?";
        $stmt = $con->prepare($sql);

        if($stmt->execute($values)){
            // --- 4. Prepare and Execute Audit Log ---
            // Create a string listing all updated fields with old/new values
            $remarks_updated_fields = "Update: " . implode(" | ", $updated_fields_log);

            // Get student_number for audit (using the old value since it might be what's needed for lookup)
            $student_number_for_audit = $old_data['student_number'] ?? $student_id;

            $auditStmt = $con->prepare("INSERT INTO student_update_audit (student_number, updated_by, updated_at, remarks, location) VALUES (?, ?, NOW(), ?, ?)");

            // The remarks now contain the old and new data
            $auditStmt->execute([$student_number_for_audit, $userId, $remarks_updated_fields, 'MASTERLIST']);

        } else {
            echo "Error submitting student information.";
        }
        header("Location: edit-student-masterlist?id=" . urlencode($student_id));
        break;

        default:
            echo "Invalid form submission.";
            echo $form_type;
            break;
}       
}
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Close connection
$con = null;
?>