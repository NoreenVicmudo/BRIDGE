<?php
session_start();
require_once __DIR__ . "/../../../core/config.php"; 
require_once PROJECT_PATH . "/j_conn.php";

header('Content-Type: application/json');

$user_id = $_SESSION['id'] ?? null;
if (!$user_id) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

function parseBytes(string $sizeStr): int {
    $unit = strtolower(substr($sizeStr, -1));
    $value = (int) $sizeStr;
    if ($unit === 'g') return $value * 1024 * 1024 * 1024;
    if ($unit === 'm') return $value * 1024 * 1024;
    if ($unit === 'k') return $value * 1024;
    return (int) $sizeStr;
}

function get_mysql_max_allowed_packet(PDO $pdo): int {
    try {
        // Use SELECT @@max_allowed_packet which returns an integer
        $row = $pdo->query("SELECT @@max_allowed_packet as m")->fetch(PDO::FETCH_ASSOC);
        return isset($row['m']) ? (int)$row['m'] : 0;
    } catch (Exception $e) {
        return 0; // if we can't fetch, return 0 and ignore later
    }
}

function validateUploadedImage(array $file, PDO $pdo, int $policyLimitBytes = 2097152): array {
    // returns [bool $ok, string $message]
    if (!isset($file) || !isset($file['error'])) {
        return [false, "No file uploaded."];
    }

    // handle PHP upload errors first
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return [false, "The uploaded file exceeds the maximum allowed size by the server."];
        case UPLOAD_ERR_PARTIAL:
            return [false, "File was only partially uploaded."];
        case UPLOAD_ERR_NO_FILE:
            return [false, "No file was uploaded."];
        default:
            return [false, "File upload error (code: {$file['error']})."];
    }

    // check actual file existence and readable
    if (!is_uploaded_file($file['tmp_name']) || !file_exists($file['tmp_name'])) {
        return [false, "Uploaded file missing or not readable."];
    }

    // check it's an image
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        // fallback to finfo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (strpos($mime, 'image/') !== 0) {
            return [false, "Uploaded file is not a valid image."];
        }
    } else {
        // optionally restrict to common types
        $mime = $imageInfo['mime'] ?? '';
        if (stripos($mime, 'image/') !== 0) {
            return [false, "Uploaded file is not a valid image."];
        }
    }

    // size checks: app policy, PHP config, and MySQL limit
    $size = (int)$file['size'];

    // Check application policy (2MB default)
    if ($size > $policyLimitBytes) {
        return [false, "The image file is too large. Please upload a smaller one."];
    }

    // check php.ini limits
    $uploadMax = parseBytes(ini_get('upload_max_filesize'));
    $postMax = parseBytes(ini_get('post_max_size'));
    $effectivePhpLimit = min($uploadMax ?: PHP_INT_MAX, $postMax ?: PHP_INT_MAX);
    if ($effectivePhpLimit !== PHP_INT_MAX && $size > $effectivePhpLimit) {
        return [false, "The image exceeds server upload limits (upload_max_filesize/post_max_size)."];
    }

    // check MySQL max_allowed_packet (if available)
    $mysqlLimit = get_mysql_max_allowed_packet($pdo);
    if ($mysqlLimit > 0) {
        // reserve a small overhead (1KB) for SQL/headers
        $safety = 1024;
        if ($size > ($mysqlLimit - $safety)) {
            return [false, "This photo is too large for the system to save. Please upload a smaller one."];
        }
    }

    return [true, "OK"];
}

try {
    // --- VALIDATE ONLY branch ---
    if (isset($_POST['validate_only']) && $_POST['validate_only'] == "1") {
        try {
            if (!isset($_FILES['profile_pic'])) {
                throw new Exception("No file uploaded");
            }
            list($ok, $msg) = validateUploadedImage($_FILES['profile_pic'], $con, 2 * 1024 * 1024);
            if (!$ok) throw new Exception($msg);
            echo json_encode(["success" => true]);
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
        }
        exit;
    }

    // --- PROCESS SAVE branch (re-validate here too) ---
    // username
    $username = $_POST['username'] ?? '';
    
    // Validate username length (3-30 characters)
    if (strlen($username) < 3) {
        throw new Exception("Username must be at least 3 characters long.");
    }
    if (strlen($username) > 30) {
        throw new Exception("Username cannot exceed 30 characters.");
    }
    
    // Validate username pattern (letters, numbers, underscore, dash only)
    if (!preg_match('/^[a-zA-Z0-9_-]{3,30}$/', $username)) {
        throw new Exception("Username can only contain letters, numbers, underscore, and dash.");
    }
    
    // profile picture (optional)
    $profilePicData = null;
    $profilePicBase64 = null;

    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Validate again BEFORE trying to save to DB
        list($ok, $msg) = validateUploadedImage($_FILES['profile_pic'], $con, 2 * 1024 * 1024);
        if (!$ok) {
            throw new Exception($msg);
        }

        $profilePicData = file_get_contents($_FILES['profile_pic']['tmp_name']);
        if ($profilePicData === false) {
            throw new Exception("Could not read uploaded file.");
        }
        // build base64 preview (preserve actual mime if possible)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['profile_pic']['tmp_name']);
        $profilePicBase64 = 'data:' . ($mime ?: 'image/jpeg') . ';base64,' . base64_encode($profilePicData);
    }

    // --- detect change reason ---
    $reason = null;
    // Get old data first
    $stmtOld = $con->prepare("SELECT user_username, user_profile_pic FROM user_account WHERE user_id = :id");
    $stmtOld->execute([":id" => $user_id]);
    $old = $stmtOld->fetch(PDO::FETCH_ASSOC);

    if ($old) {
        if ($username !== $old['user_username']) {
            $reason = "username";
        }
        if ($profilePicData !== null) {
            $reason = "profile_picture";
        }
    }

    // build query
    $sql = "UPDATE user_account 
            SET user_username = :username";

    if ($profilePicData !== null) {
        $sql .= ", user_profile_pic = :profile_pic";
    }

    if ($reason !== null) {
        $sql .= ", update_reason = :reason, last_updated_at = NOW()";
    }

    $sql .= " WHERE user_id = :id";

    $stmt = $con->prepare($sql);
    $stmt->bindParam(":username", $username, PDO::PARAM_STR);
    $stmt->bindParam(":id", $user_id, PDO::PARAM_INT);

    if ($profilePicData !== null) {
        // Use PDO::PARAM_LOB, sending the binary content directly
        $stmt->bindParam(":profile_pic", $profilePicData, PDO::PARAM_LOB);
    }
    if ($reason !== null) {
        $stmt->bindParam(":reason", $reason, PDO::PARAM_STR);
    }

    $stmt->execute();

    // This is what makes the page "sync" after reload
    $_SESSION['username'] = $username;
    if ($profilePicData !== null) {
        $_SESSION['profile_pic'] = $profilePicBase64;
    }

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    error_log("Update profile failed: " . $e->getMessage());

    $friendlyMsg = "Something went wrong while updating your profile. Please try again.";

    if (stripos($e->getMessage(), 'max_allowed_packet') !== false
        || stripos($e->getMessage(), 'too large') !== false
        || stripos($e->getMessage(), 'exceeds') !== false
    ) {
        $friendlyMsg = "The image file is too large. Please upload a smaller photo.";
    } else {
        // return the actual message for clearer UX for validation errors
        $friendlyMsg = $e->getMessage();
    }

    echo json_encode([
        "success" => false,
        "message" => $friendlyMsg
    ]);
}
