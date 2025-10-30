<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";
include 'populate_filter.php';

// Decode options outside the conditional block
$decodedOptions = json_decode($jsonOptions, true);
    $level = $_SESSION['level'] ?? null;
    $userId = $_SESSION['id'] ?? null;
    
    $filter_academic_year = $_SESSION['filter_academic_year'] ?? '';
    $filter_college       = $_SESSION['filter_college'] ?? '';
    $filter_program       = $_SESSION['filter_program'] ?? '';
    $filter_semester      = $_SESSION['filter_semester'] ?? '';
    $filter_year_level    = $_SESSION['filter_year_level'] ?? '';
    $filter_section       = $_SESSION['filter_section'] ?? '';
    $filter_year_batch    = $_SESSION['filter_year_batch'] ?? '';
    $filter_board_batch   = $_SESSION['filter_board_batch'] ?? '';
    $filter_type          = $_SESSION['filter_type'] ?? '';

if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK && isset($_POST['mode'])) {
    $csvFile = $_FILES['file']['tmp_name'];
    $mode = $_POST['mode']; // "student_info" or "student_section"

    if (($handle = fopen($csvFile, "r")) !== FALSE) {
        // Skip header
        fgetcsv($handle);

        if ($mode === "student_info") {
    /**************************************
     * MODE 1: Insert/Update Student Info
     **************************************/

    // Re-defining the fields list here for clarity and use in the logic
    $update_fields = [
        'student_fname', 'student_mname', 'student_lname', 'student_suffix', 'student_college',
        'student_program', 'student_birthdate', 'student_sex', 'student_socioeconomic',
        'student_living', 'student_address_number', 'student_address_street', 'student_address_barangay',
        'student_address_city', 'student_address_province', 'student_address_postal',
        'student_work', 'student_scholarship', 'student_language', 'student_last_school',
    ];
    // Include is_active in the fields to select from the OLD data for comparison
    $fields_to_select = implode(', ', array_merge(['student_number', 'is_active'], $update_fields));

    // Prepare SQL statement to fetch OLD data for comparison (must be outside the loop)
    // *** MODIFICATION 1: Removed AND is_active = 1 to fetch even inactive records ***
    $get_old_data_stmt = $con->prepare("SELECT {$fields_to_select} FROM student_info WHERE student_number = ?");

    // Audit statement (Assuming $userId is available from session)
    $auditStmt = $con->prepare("INSERT INTO student_add_audit (student_number, added_by, added_at, remarks, location) VALUES (?, ?, NOW(), ?, ?)");
    $userId = $_SESSION['user_id'] ?? 0; // Ensure you have a valid user ID here

    // *** MODIFICATION 2: Removed AND is_active = 1 to count student regardless of status ***
    $checkStmt = $con->prepare("SELECT COUNT(*) FROM student_info WHERE student_number = :student_number");

    $insertStmt = $con->prepare("
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

    $updateStmt = $con->prepare("
        UPDATE student_info SET 
            student_fname = :student_fname,
            student_mname = :student_mname,
            student_lname = :student_lname,
            student_suffix = :student_suffix,
            student_college = :student_college,
            student_program = :student_program,
            student_birthdate = :student_birthdate,
            student_sex = :student_sex,
            student_socioeconomic = :student_socioeconomic,
            student_living = :student_living,
            student_address_number = :student_address_number,
            student_address_street = :student_address_street,
            student_address_barangay = :student_address_barangay,
            student_address_city = :student_address_city,
            student_address_province = :student_address_province,
            student_address_postal = :student_address_postal,
            student_work = :student_work,
            student_scholarship = :student_scholarship,
            student_language = :student_language,
            student_last_school = :student_last_school,
            date_created = :date_created,
            is_active = 1
        WHERE student_number = :student_number 
    ");

    // --- Process CSV rows ---
    $inserted = 0;
    $updated = 0;
    $reactivated = 0; // New counter for reactivated records
    $skipped = 0;
    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
        // ... (College and Program Mapping code remains the same) ...
        // --- Map College ---
        $college_id = 0;
        foreach ($decodedOptions['collegeOptions'] as $collegeOption) {
            if ($collegeOption['name'] == strtoupper(trim($row[5]))) {
                $college_id = $collegeOption['id'];
                break;
            }
        }

        // Map program ID to name
        $program_id = 0;
        foreach ($decodedOptions['programOptions'] as $col_id => $programs) {
            foreach ($programs as $program) {
                if ($program['name'] == strtoupper(trim($row[6]))) {
                    $program_id = $program['id'];
                    break 2;
                }
            }
        }

        // --- Permission checks (remains the same) ---
        $userLevel = $_SESSION['level'] ?? null;
        $userProgram = $_SESSION['program'] ?? null;
        $userCollege = $_SESSION['college'] ?? null;

        // If level 3, only allow rows that map to the user's program
        if ($userLevel === 3 && !empty($userProgram)) {
            if ($program_id == 0 || $program_id != $userProgram) {
                $skipped++;
                continue; // skip this row
            }
        }

        // If level 2, only allow rows that map to the user's college
        if (($userLevel === 2 || $userLevel === 1) && !empty($userCollege)) {
            if ($college_id == 0 || $college_id != $userCollege) {
                $skipped++;
                continue; // skip this row
            }
        }

        // Map arrangement ID
        $arrangement_id = 1;
        foreach ($decodedOptions['arrangementOptions'] as $arrangementOption) {
            if ($arrangementOption['name'] == strtoupper(trim($row[10]))) {
                $arrangement_id = $arrangementOption['id'];
                break;
            }
        }

        // Map language ID
        $language_id = 1;
        foreach ($decodedOptions['languageOptions'] as $languageOption) {
            if ($languageOption['name'] == strtoupper(trim($row[19]))) {
                $language_id = $languageOption['id'];
                break;
            }
        }

        $birthdate = date("Y-m-d", strtotime($row[7])); // format conversion


        // --- Prepare New Data Parameters (CSV data) ---
        $params = [
            ':student_number'       => strtoupper(trim($row[0])),
            ':student_fname'        => strtoupper(trim($row[1])),
            ':student_mname'        => strtoupper(trim($row[2])),
            ':student_lname'        => strtoupper(trim($row[3])),
            ':student_suffix'       => strtoupper(trim($row[4])),
            ':student_college'      => $college_id,
            ':student_program'      => $program_id,
            ':student_birthdate'    => $birthdate,
            ':student_sex'          => strtoupper(trim($row[8])),
            ':student_socioeconomic'=> strtoupper(trim($row[9])),
            ':student_living'       => $arrangement_id,
            ':student_address_number'=> strtoupper(trim($row[11])),
            ':student_address_street'=> strtoupper(trim($row[12])),
            ':student_address_barangay'=> strtoupper(trim($row[13])),
            ':student_address_city' => strtoupper(trim($row[14])),
            ':student_address_province'=> strtoupper(trim($row[15])),
            ':student_address_postal'=> strtoupper(trim($row[16])),
            ':student_work' => strtoupper(trim($row[17])),
            ':student_scholarship'  => strtoupper(trim($row[18])),
            ':student_language'     => $language_id,
            ':student_last_school'  => strtoupper(trim($row[20])),
            ':date_created'         => date('Y-m-d H:i:s')
        ];

        // Check if student exists (regardless of is_active status)
        $checkStmt->execute([':student_number' => $params[':student_number']]);
        $exists = $checkStmt->fetchColumn();
        $student_number = $params[':student_number'];


        if ($exists) {
            // --- EXISTING: FETCH OLD DATA & COMPARE FOR AUDIT ---
            $get_old_data_stmt->execute([$student_number]);
            $old_data = $get_old_data_stmt->fetch(PDO::FETCH_ASSOC);

            $changes_log = [];
            $was_inactive = (isset($old_data['is_active']) && $old_data['is_active'] == 0);

            // Compare each field to log changes
            foreach ($update_fields as $field) {
                // New value is from the $params array (removing the colon)
                $new_value = $params[":{$field}"]; 
                // Old value is from the fetched $old_data array
                $old_value = $old_data[$field] ?? ''; 
                
                if ($new_value != $old_value) {
                    // Log the field, old value, and new value
                    $changes_log[] = "{$field}: '{$old_value}' -> '{$new_value}'";
                }
            }
            
            // Log reactivation change if it happened
            if ($was_inactive) {
                $changes_log[] = "is_active: '0' -> '1' (Reactivated)";
            }
            
            // Only update and log if actual changes were found (including is_active change)
            if (!empty($changes_log)) {
                $updateStmt->execute($params);
                $updated++;
                
                // Track reactivation
                if ($was_inactive) {
                    $reactivated++;
                }

                // Audit Log for UPDATE/REACTIVATION
                $audit_remarks = "CSV Update: " . implode(" | ", $changes_log);
                $auditStmt->execute([$student_number, $userId, $audit_remarks, 'CSV_MASTERLIST']);

            } else {
                // No changes found, but student exists
                $updated++; // Still count as 'updated' if no changes were made? Or should be 'ignored'? 
                            // Keeping it 'updated' as it was in the original logic.
            }

        } else {
            // --- NEW STUDENT: INSERT ---
            $insertStmt->execute($params);
            $inserted++;
            
            // Audit Log for INSERT
            $audit_remarks = "CSV Insert: New student record.";
            $auditStmt->execute([$student_number, $userId, $audit_remarks, 'CSV_MASTERLIST']);
        }
    }
    // Updated output message
    echo "Student info imported. Inserted: $inserted; Updated: $updated (Reactivated: $reactivated); Skipped: $skipped";
} else if ($mode === "student_section") {
    /**************************************
     * MODE 2: Insert into Student Section
     **************************************/
    $checkInfoStmt = $con->prepare("
        SELECT student_number 
        FROM student_info 
        WHERE student_number = :student_number 
          AND is_active = 1 
          AND student_college = :student_college 
          AND student_program = :student_program
          AND is_active = 1
        LIMIT 1
    ");
            
    if ($_SESSION['filter_type'] == 'section') {
        $checkSectionStmt = $con->prepare("
            SELECT is_active 
            FROM student_section 
            WHERE student_number = :student_number
            AND section = :section
            AND program_id = :program_id
            AND year_level = :year_level
            AND semester = :semester
            AND academic_year = :academic_year
        ");

        $updateSectionStmt = $con->prepare("
            UPDATE student_section set is_active = 1
            WHERE student_number = :student_number AND is_active = 0 AND semester = :semester AND year_level = :year_level
            AND academic_year = :academic_year AND program_id = :program_id AND section = :section LIMIT 1
            ");

        $insertSectionStmt = $con->prepare("
            INSERT INTO student_section (
                student_number, section, program_id, year_level, semester, academic_year
            ) VALUES (
                :student_number, :section, :program_id, :year_level, :semester, :academic_year
            )
        ");

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $studentNumber = trim($row[0]);

            // ✅ Step 1: Check if student exists in student_info
            $checkInfoStmt->execute([
                ':student_number' => $studentNumber,
                ':student_college' => $filter_college,
                ':student_program' => $filter_program
            ]);

            if ($checkInfoStmt->rowCount() > 0) {
                // ✅ Step 2: Check if already in this section
                $checkSectionStmt->execute([
                    ':student_number' => $studentNumber,
                    ':section'        => $filter_section,
                    ':year_level'  => $filter_year_level,
                    ':program_id'       => $filter_program,
                    ':semester'       => $filter_semester,
                    ':academic_year'  => $filter_academic_year
                ]);

                $existsInSection = $checkSectionStmt->fetchColumn();

                if (!$existsInSection) {
                    // ✅ Step 3: Insert only if not already in section
                    $insertSectionStmt->execute([
                        ':student_number' => $studentNumber,
                        ':section'        => $filter_section,
                        ':year_level'  => $filter_year_level,
                        ':program_id'       => $filter_program,
                        ':semester'       => $filter_semester,
                        ':academic_year'  => $filter_academic_year
                    ]);
                    
                    // Audit: insert rows into student_delete_audit table
                    $auditStmt = $con->prepare("INSERT INTO student_add_audit (student_number, added_by, added_at, remarks, location) VALUES (?, ?, NOW(), ?, ?)");
                    // Build a safe location string (avoid PHP backticks which execute shell commands)
                    if ($filter_type == 'section') {
                        $locPart = 'SECTION ' . ($filter_section !== '' ? $filter_section : 'UNKNOWN');
                        $remarks = "$filter_academic_year - $filter_semester Semester - Year $filter_year_level";
                    } else if ($filter_type == 'batch') {
                        $locPart = 'BATCH ' . ($filter_year_batch !== '' ? $filter_year_batch : 'UNKNOWN') . '-' . ($filter_board_batch !== '' ? $filter_board_batch : 'UNKNOWN');
                        $remarks = "$filter_year_batch - $filter_board_batch";
                    } else {
                        $locPart = 'UNKNOWN';
                    }

                    $auditStmt->execute([$studentNumber, $userId, $remarks, $locPart]);
                } else if ($existsInSection == 0) {
                    // ✅ Step 3: Insert only if not already in section
                    $updateSectionStmt->execute([
                        ':student_number' => $studentNumber,
                        ':section'        => $filter_section,
                        ':year_level'  => $filter_year_level,
                        ':program_id'       => $filter_program,
                        ':semester'       => $filter_semester,
                        ':academic_year'  => $filter_academic_year
                    ]);
                    
                    // Audit: insert rows into student_delete_audit table
                    $auditStmt = $con->prepare("INSERT INTO student_add_audit (student_number, added_by, added_at, remarks, location) VALUES (?, ?, NOW(), ?, ?)");
                    // Build a safe location string (avoid PHP backticks which execute shell commands)
                    if ($filter_type == 'section') {
                        $locPart = 'SECTION ' . ($filter_section !== '' ? $filter_section : 'UNKNOWN');
                        $remarks = "$filter_academic_year - $filter_semester Semester - Year $filter_year_level";
                    } else if ($filter_type == 'batch') {
                        $locPart = 'BATCH ' . ($filter_year_batch !== '' ? $filter_year_batch : 'UNKNOWN') . '-' . ($filter_board_batch !== '' ? $filter_board_batch : 'UNKNOWN');
                        $remarks = "$filter_year_batch - $filter_board_batch";
                    } else {
                        $locPart = 'UNKNOWN';
                    }

                    $auditStmt->execute([$studentNumber, $userId, $remarks, $locPart]);
                }
            }
        }
        echo "Student sections imported (skipping duplicates).";
    } else if ($_SESSION['filter_type'] == 'batch') {
        $checkBatchStmt = $con->prepare("
            SELECT is_active 
            FROM board_batch 
            WHERE student_number = :student_number
            AND program_id = :program_id
            AND year = :year
            AND batch_number = :batch_number
        ");

        $students = [];

        $updateBatchStmt = $con->prepare("
            UPDATE board_batch set is_active = 1 WHERE student_number = :student_number AND is_active = 0
            AND year = :year AND program_id = :program_id AND batch_number = :batch_number LIMIT 1
        ");

        $insertBatchStmt = $con->prepare("
            INSERT INTO board_batch (
                student_number, year, program_id, batch_number
            ) VALUES (
                :student_number, :year, :program_id, :batch_number
            )
        ");

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $studentNumber = trim($row[0]);

            // ✅ Step 1: Check if student exists in student_info
            $checkInfoStmt->execute([
                ':student_number' => $studentNumber,
                ':student_college' => $filter_college,
                ':student_program' => $filter_program
            ]);
            
                    $students[] = $studentNumber;

            if ($checkInfoStmt->rowCount() > 0) {
                // ✅ Step 2: Check if already in this batch
                $checkBatchStmt->execute([
                    ':student_number' => $studentNumber,
                    ':year'           => $filter_year_batch,
                    ':program_id'       => $filter_program,
                    ':batch_number'   => $filter_board_batch
                ]);

                $existsInBatch = $checkBatchStmt->fetchColumn();

                if (!$existsInBatch) {
                    // ✅ Step 3: Insert only if not already in batch
                    $insertBatchStmt->execute([
                        ':student_number' => $studentNumber,
                        ':year'           => $filter_year_batch,
                        ':program_id'       => $filter_program,
                        ':batch_number'   => $filter_board_batch
                    ]);

                    
                    // Audit: insert rows into student_delete_audit table
                    $auditStmt = $con->prepare("INSERT INTO student_add_audit (student_number, added_by, added_at, remarks, location) VALUES (?, ?, NOW(), ?, ?)");
                    // Build a safe location string (avoid PHP backticks which execute shell commands)
                    if ($filter_type == 'section') {
                        $locPart = 'SECTION ' . ($filter_section !== '' ? $filter_section : 'UNKNOWN');
                        $remarks = "$filter_academic_year - $filter_semester Semester - Year $filter_year_level";
                    } else if ($filter_type == 'batch') {
                        $locPart = 'BATCH ' . ($filter_year_batch !== '' ? $filter_year_batch : 'UNKNOWN') . '-' . ($filter_board_batch !== '' ? $filter_board_batch : 'UNKNOWN');
                        $remarks = "$filter_year_batch - $filter_board_batch";
                    } else {
                        $locPart = 'UNKNOWN';
                    }

                    $auditStmt->execute([$studentNumber, $userId, $remarks, $locPart]);
                } else if ($existsInBatch == 0) {
                    // ✅ Step 3: Insert only if not already in batch
                    $updateBatchStmt->execute([
                        ':student_number' => $studentNumber,
                        ':year'           => $filter_year_batch,
                        ':program_id'       => $filter_program,
                        ':batch_number'   => $filter_board_batch
                    ]);

                    
                    // Audit: insert rows into student_delete_audit table
                    $auditStmt = $con->prepare("INSERT INTO student_add_audit (student_number, added_by, added_at, remarks, location) VALUES (?, ?, NOW(), ?, ?)");
                    // Build a safe location string (avoid PHP backticks which execute shell commands)
                    if ($filter_type == 'section') {
                        $locPart = 'SECTION ' . ($filter_section !== '' ? $filter_section : 'UNKNOWN');
                        $remarks = "$filter_academic_year - $filter_semester Semester - Year $filter_year_level";
                    } else if ($filter_type == 'batch') {
                        $locPart = 'BATCH ' . ($filter_year_batch !== '' ? $filter_year_batch : 'UNKNOWN') . '-' . ($filter_board_batch !== '' ? $filter_board_batch : 'UNKNOWN');
                        $remarks = "$filter_year_batch - $filter_board_batch";
                    } else {
                        $locPart = 'UNKNOWN';
                    }

                    $auditStmt->execute([$studentNumber, $userId, $remarks, $locPart]);
                }
            }
        }
        foreach ($students as $student) {
        echo `Board batches imported ($student).`;
        }
}



        fclose($handle);

    } else {
        echo "Error opening uploaded CSV file.";
    }
} else {
    echo "No file uploaded or upload error.";
}
}
?>
