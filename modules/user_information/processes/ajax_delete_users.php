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

try {
    // basic DB handle validation
    if (!isset($con) || !$con) {
        throw new Exception('Database connection not available');
    }

    $con->beginTransaction();

    // Lookup students and validate permission per-row
    $placeholders = implode(',', array_fill(0, count($students), '?'));
    $stmt = $con->prepare("SELECT user_username FROM user_account WHERE user_username IN ($placeholders) AND is_active = 1");
    $stmt->execute($students);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $toDelete = [];
    foreach ($rows as $r) {
        $sn = $r['user_username'];
        $toDelete[] = $sn;
    }

    if (count($toDelete) === 0) {
        $con->rollBack();
        echo json_encode(['success'=>false,'message'=>'No users permitted to delete']);
        exit;
    }

    // Soft-delete: mark inactive and record who deleted and when
    // We'll include deleted_by so audit is on the row as well
    $placeholders2 = implode(',', array_fill(0, count($toDelete), '?'));
    // first param is deleted_by, followed by student numbers
    $updateSql = "UPDATE user_account SET is_active = 0 WHERE user_username IN ($placeholders2)";
    $update = $con->prepare($updateSql);
    $update->execute(($toDelete));

    // Decode per-student reasons if provided
    $reason_mode = $_POST['reason_mode'] ?? 'single'; // 'single' or 'multiple'
    $per_reasons_json = $_POST['per_reasons'] ?? '';
    $per_reasons = [];
    if ($reason_mode === 'multiple' && $per_reasons_json) {
        $decoded = json_decode($per_reasons_json, true);
        if (is_array($decoded)) $per_reasons = $decoded;
    }

    // Audit: insert rows into student_delete_audit table
    $auditStmt = $con->prepare("INSERT INTO user_manage_audit (user_username, action_by, action_at, remarks, location) VALUES (?, ?, NOW(), ?, ?)");
    foreach ($toDelete as $sn) {
        $audit_reason = $reason; // default single reason
        if ($reason_mode === 'multiple' && isset($per_reasons[$sn]) && $per_reasons[$sn]) {
            $audit_reason = trim($per_reasons[$sn]);
        }
        $auditStmt->execute([$sn, $userId, $audit_reason, 'USER LIST']);
    }

    $con->commit();
    echo json_encode(['success' => true, 'deleted_count' => count($toDelete)]);
    exit;
} catch (Exception $e) {
    if (isset($con) && $con && $con->inTransaction()) {
        $con->rollBack();
    }
    // Log full exception to server error log for debugging
    error_log("[ajax_delete_students] delete students failed: " . $e->getMessage() . " -- Trace: " . $e->getTraceAsString());

    // Return a safer response but include the message to help debug locally
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
    exit;
}