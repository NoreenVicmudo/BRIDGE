<?php
    require_once __DIR__ . "/../../../core/config.php";
    require_once PROJECT_PATH . '/j_conn.php';
    require_once PROJECT_PATH . '/auth.php'; // ensures session and login

    header('Content-Type: application/json');

    // ensure POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success'=>false,'message'=>'Invalid method']);
        exit;
    }

            $filter_type = $_SESSION['filter_type'] ?? 'section';
            $_SESSION['filter_type'] = $filter_type;

    // permission check: only allow admins or authorized users
    $level = $_SESSION['level'] ?? null;
    $userId = $_SESSION['id'] ?? null; // adjust to your session fields
    if ($level === null) {
        http_response_code(403);
        echo json_encode(['success'=>false,'message'=>'Not authenticated']);
        exit;
            }

            // read payload
            $studentsJson = $_POST['students'] ?? '[]';
            $students = json_decode($studentsJson, true);
            $reason = trim($_POST['reason'] ?? '');
            // normalize and sanitize student numbers (remove empty / whitespace-only values)
            $students = is_array($students) ? array_values(array_filter(array_map('trim', $students), function($v){ return $v !== '' && $v !== null; })) : [];
            if (!is_array($students) || count($students) === 0) {
                echo json_encode(['success'=>false,'message'=>'No students provided']);
                exit;
            }

            // Optional: enforce same program/college checks for non-admins
            // If your policy: level 3 can only delete students in their program, level 2 only in their college
            $userProgram = $_SESSION['program'] ?? null;
            $userCollege = $_SESSION['college'] ?? null;

            if ($filter_type == 'section') {
                // Set up filter variables from POST data
                $filter_year_batch  = '';
                $filter_board_batch = '';

                $academic_year = $_POST['academic_year'] ?? '';
                $college       = $_POST['college'] ?? '';
                $program       = $_POST['program'] ?? '';
                $semester      = $_POST['semester'] ?? $_SESSION['filter_semester'] ?? '';
                $year_level    = $_POST['year_level'] ?? '';
                $section       = $_POST['section'] ?? '';
            }
            else if ($filter_type == 'batch') {
                // Set up filter variables from POST data
                $academic_year = '';
                $semester      = '';
                $year_level    = '';
                $section       = '';

                $college            = $_POST['collegeBatch'] ?? '';
                $program            = $_POST['programBatch'] ?? '';
                $filter_year_batch  = $_POST['yearBatch'] ?? '';
                $filter_board_batch = $_POST['boardBatch'] ?? '';

            }

            try {
                // basic DB handle validation
                if (!isset($con) || !$con) {
                    throw new Exception('Database connection not available');
                }

                $con->beginTransaction();

                // Lookup students and validate permission per-row
                $placeholders = implode(',', array_fill(0, count($students), '?'));
    
                if ($filter_type == 'section') {
                    $stmt = $con->prepare("SELECT student_number, section, year_level, program_id, semester, academic_year FROM student_section WHERE student_number IN ($placeholders) AND section = ? AND year_level = ? AND program_id = ? AND semester = ? AND academic_year = ? AND is_active = 1");
                    $params = [$section, $year_level, $program, $semester, $academic_year];
                } else if ($filter_type == 'batch') {
                    $stmt = $con->prepare("SELECT student_number, year, program_id, batch_number FROM board_batch WHERE student_number IN ($placeholders) AND year = ? AND program_id = ? AND batch_number = ? AND is_active = 1");
                    $params = [$filter_year_batch, $program, $filter_board_batch];
                } else {
                    // default: search in student_section table
                    $stmt = $con->prepare("SELECT student_number, section, year_level, program_id, semester, academic_year FROM student_section WHERE student_number IN ($placeholders) AND is_active = 1");
                    $params = [];
                }
                // execute with student numbers first (placeholders for IN(...))
                $stmt->execute(array_merge($students, $params));
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $toDelete = [];
                $skipped = []; // student_number => reason
                $matchedStudents = array_column($rows, 'student_number');
                foreach ($rows as $r) {
                    $sn = $r['student_number'];
                    // permission filtering
                    if ($level == 3 && $userProgram && ($r['program_id'] ?? $r['student_program'] ?? '') != $userProgram) {
                        $skipped[$sn] = 'program_mismatch';
                        continue; // skip
                    }
                    /*if (($level == 2 || $level == 1) && $userCollege && ($r['program_college'] ?? $r['student_college'] ?? '') != $userCollege) {
                        $skipped[$sn] = 'college_mismatch';
                        continue; // skip
                    }*/
                    $toDelete[] = $sn;
                }

                if (count($toDelete) === 0) {
                    $con->rollBack();

                    // Build a helpful debug response (only returned when client requests debug=1)
                    $missing = array_values(array_diff($students, $matchedStudents));
                    $debugResp = [
                        'success' => false,
                        'message' => 'No students permitted to delete',
                        'received_count' => count($students),
                        'received_students' => array_values($students),
                        'matched_count' => count($matchedStudents),
                        'matched_students' => array_values($matchedStudents),
                        'missing_students' => $missing,
                        'skipped_count' => count($skipped),
                        'skipped_students' => $skipped,
                        'permitted_count' => count($toDelete),
                        'permitted_students' => $toDelete,
                        'session' => ['level' => $level, 'program' => $userProgram, 'college' => $userCollege],
                        'query_params' => [$students, $params],
                        'filter_type' => $filter_type,
                        'query' => $stmt->queryString,
                    ];

                    if (isset($_POST['debug']) && (string)$_POST['debug'] === '1') {
                        echo json_encode($debugResp);
                    } else {
                        // Log debug details to server error log for investigation
                        error_log('[ajax_remove_students] No students permitted to delete: ' . json_encode($debugResp));
                        echo json_encode(['success'=>false,'message'=>'No students permitted to delete', 'debug'=>$debugResp]);
                    }
                    exit;
                }

                // Soft-delete: mark inactive and record who deleted and when
                $placeholders2 = implode(',', array_fill(0, count($toDelete), '?'));
                // build update SQL and params based on filter type
                if ($filter_type == 'section') {
                    $updateSql = "UPDATE student_section SET is_active = 0 WHERE student_number IN ($placeholders2) AND section = ? AND year_level = ? AND program_id = ? AND semester = ? AND academic_year = ? AND is_active = 1";
                    $params = [$section, $year_level, $program, $semester, $academic_year];
                } else if ($filter_type == 'batch') {
                    $updateSql = "UPDATE board_batch SET is_active = 0 WHERE student_number IN ($placeholders2) AND year = ? AND program_id = ? AND batch_number = ? AND is_active = 1";
                    $params = [$filter_year_batch, $program, $filter_board_batch];
                } else {
                    $updateSql = "UPDATE student_section SET is_active = 0 WHERE student_number IN ($placeholders2) AND is_active = 1";
                    $params = [];
                }
                $update = $con->prepare($updateSql);
                $update->execute(array_merge($toDelete, $params));

                // Decode per-student reasons if provided
                $reason_mode = $_POST['reason_mode'] ?? 'single'; // 'single' or 'multiple'
                $per_reasons_json = $_POST['per_reasons'] ?? '';
                $per_reasons = [];
                if ($reason_mode === 'multiple' && $per_reasons_json) {
                    $decoded = json_decode($per_reasons_json, true);
                    if (is_array($decoded)) $per_reasons = $decoded;
                }

                // Audit: insert rows into student_delete_audit table
                $auditStmt = $con->prepare("INSERT INTO student_delete_audit (student_number, deleted_by, deleted_at, reason, location) VALUES (?, ?, NOW(), ?, ?)");

                // Build a safe location string (avoid PHP backticks which execute shell commands)
                if ($filter_type == 'section') {
                    $locPart = 'SECTION ' . ($section !== '' ? $section : 'UNKNOWN');
                } else if ($filter_type == 'batch') {
                    $locPart = 'BATCH ' . ($filter_year_batch !== '' ? $filter_year_batch : 'UNKNOWN') . '-' . ($filter_board_batch !== '' ? $filter_board_batch : 'UNKNOWN');
                } else {
                    $locPart = 'UNKNOWN';
                }

                foreach ($toDelete as $sn) {
                    $audit_reason = $reason; // default single reason
                    if ($reason_mode === 'multiple' && isset($per_reasons[$sn]) && $per_reasons[$sn]) {
                        $audit_reason = trim($per_reasons[$sn]);
                    }
                    // Ensure we never insert NULL into a NOT NULL column
                    $locationVal = $locPart ?: 'UNKNOWN';
                    $auditStmt->execute([$sn, $userId, $audit_reason, $locationVal]);
                }

                $con->commit();
                echo json_encode(['success' => true, 'deleted_count' => count($toDelete)]);
                exit;
            } catch (Exception $e) {
                if (isset($con) && $con && $con->inTransaction()) {
                    $con->rollBack();
                }
                // Log full exception to server error log for debugging
                error_log("[ajax_remove_students] delete students failed: " . $e->getMessage() . " -- Trace: " . $e->getTraceAsString());

                // Return a safer response but include the message to help debug locally
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Server error', 'filterType' => $filter_type, 'error' => $e->getMessage()]);
                exit;
            }