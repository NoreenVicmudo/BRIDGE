<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";

header('Content-Type: application/json');

function parseBytes(string $sizeStr): int {
    $unit = strtolower(substr($sizeStr, -1));
    $value = (int) $sizeStr;
    if ($unit === 'g') return $value * 1024 * 1024 * 1024;
    if ($unit === 'm') return $value * 1024 * 1024;
    if ($unit === 'k') return $value * 1024;
    return (int) $sizeStr;
}

function validateUploadedImage(array $file, PDO $pdo, int $policyLimitBytes = 2097152): array {
    if (!isset($file) || !isset($file['error'])) {
        return [false, "No file uploaded."];
    }

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

    if (!is_uploaded_file($file['tmp_name']) || !file_exists($file['tmp_name'])) {
        return [false, "Uploaded file missing or not readable."];
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (strpos($mime, 'image/') !== 0) {
            return [false, "Uploaded file is not a valid image."];
        }
    } else {
        $mime = $imageInfo['mime'] ?? '';
        if (stripos($mime, 'image/') !== 0) {
            return [false, "Uploaded file is not a valid image."];
        }
    }

    // Only accept JPG and PNG formats
    $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!in_array(strtolower($mime), $allowedMimes, true)) {
        return [false, "Only JPG and PNG image files are allowed."];
    }

    $size = (int)$file['size'];
    if ($size > $policyLimitBytes) {
        return [false, "The image file is too large. Please upload a smaller one (max 2MB)."];
    }

    $uploadMax = parseBytes(ini_get('upload_max_filesize'));
    $postMax = parseBytes(ini_get('post_max_size'));
    $effectivePhpLimit = min($uploadMax ?: PHP_INT_MAX, $postMax ?: PHP_INT_MAX);
    if ($effectivePhpLimit !== PHP_INT_MAX && $size > $effectivePhpLimit) {
        return [false, "The image exceeds server upload limits."];
    }

    return [true, "OK"];
}

try {
    // Check access - only admins (0), deans (1), and administrative assistants (2) can access
    $userLevel = $_SESSION['level'] ?? null;
    if (!in_array($userLevel, [0, 1, 2])) {
        echo json_encode(["success" => false, "message" => "Access denied. Only admins, deans, and administrative assistants can modify document formats."]);
        exit;
    }

    // Validation only branch
    if (isset($_POST['validate_only']) && $_POST['validate_only'] == "1") {
        if (!isset($_FILES['logo'])) {
            echo json_encode(["success" => false, "message" => "No file uploaded"]);
            exit;
        }
        list($ok, $msg) = validateUploadedImage($_FILES['logo'], $con, 2 * 1024 * 1024);
        if (!$ok) {
            echo json_encode(["success" => false, "message" => $msg]);
            exit;
        }
        echo json_encode(["success" => true]);
        exit;
    }

    $collegeId = $_POST['college_id'] ?? null;
    $color = $_POST['color'] ?? null;
    $email = $_POST['email'] ?? null;
    $removeLogo = isset($_POST['remove_logo']) && $_POST['remove_logo'] == "1";

    if (!$collegeId) {
        echo json_encode(["success" => false, "message" => "College ID is required"]);
        exit;
    }

    // Check if dean/assistant is accessing their own college
    if (in_array($userLevel, [1, 2])) {
        $userCollege = $_SESSION['college'] ?? null;
        if ($userCollege != $collegeId) {
            echo json_encode(["success" => false, "message" => "Access denied. You can only modify your own college's document format."]);
            exit;
        }
    }

    // Validate color (hex format)
    if ($color && !preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
        echo json_encode(["success" => false, "message" => "Invalid color format. Please use hex format (e.g., #5c297c)."]);
        exit;
    }

    // Validate email format if provided
    if ($email && !empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success" => false, "message" => "Invalid email format."]);
        exit;
    }

    // Handle logo upload - save to file system
    $logoPath = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        list($ok, $msg) = validateUploadedImage($_FILES['logo'], $con, 2 * 1024 * 1024);
        if (!$ok) {
            echo json_encode(["success" => false, "message" => $msg]);
            exit;
        }

        // Create upload directory if it doesn't exist
        // PROJECT_PATH is in core/, so we need to go up one level to bridge root
        $uploadDir = dirname(PROJECT_PATH) . '/uploads/college_logo/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                echo json_encode(["success" => false, "message" => "Could not create upload directory."]);
                exit;
            }
        }

        // Generate unique filename: college_{id}_{timestamp}_{random}.ext
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['logo']['tmp_name']);
        $ext = '';
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $ext = 'jpg';
                break;
            case 'image/png':
                $ext = 'png';
                break;
            default:
                echo json_encode(["success" => false, "message" => "Only JPG and PNG image files are allowed."]);
                exit;
        }
        
        // Generate unique filename with timestamp and random string to prevent collisions
        $uniqueId = uniqid('', true);
        $filename = 'college_' . $collegeId . '_' . time() . '_' . str_replace('.', '', $uniqueId) . '.' . $ext;
        $filepath = $uploadDir . $filename;

        // Delete old logo if exists
        $stmtOld = $con->prepare("SELECT logo_path FROM colleges WHERE college_id = ?");
        $stmtOld->execute([$collegeId]);
        $old = $stmtOld->fetch(PDO::FETCH_ASSOC);
        if ($old && $old['logo_path'] && file_exists(dirname(PROJECT_PATH) . '/' . $old['logo_path'])) {
            @unlink(dirname(PROJECT_PATH) . '/' . $old['logo_path']);
        }

        // Move uploaded file
        if (!move_uploaded_file($_FILES['logo']['tmp_name'], $filepath)) {
            echo json_encode(["success" => false, "message" => "Could not save uploaded file."]);
            exit;
        }

        // Store relative path (from project root)
        $logoPath = 'uploads/college_logo/' . $filename;
    } elseif ($removeLogo) {
        // Delete old logo file if removing
        $stmtOld = $con->prepare("SELECT logo_path FROM colleges WHERE college_id = ?");
        $stmtOld->execute([$collegeId]);
        $old = $stmtOld->fetch(PDO::FETCH_ASSOC);
        if ($old && $old['logo_path'] && file_exists(dirname(PROJECT_PATH) . '/' . $old['logo_path'])) {
            @unlink(dirname(PROJECT_PATH) . '/' . $old['logo_path']);
        }
    }

    // Build update query
    $updates = [];
    $params = [];
    $paramTypes = [];

    if ($logoPath !== null) {
        $updates[] = "logo_path = ?";
        $params[] = $logoPath;
        $paramTypes[] = PDO::PARAM_STR;
    } elseif ($removeLogo) {
        $updates[] = "logo_path = NULL";
    }

    if ($color !== null) {
        $updates[] = "brand_color = ?";
        $params[] = $color;
        $paramTypes[] = PDO::PARAM_STR;
    }

    if ($email !== null) {
        $updates[] = "college_email = ?";
        $emailValue = empty($email) ? null : $email;
        $params[] = $emailValue;
        $paramTypes[] = PDO::PARAM_STR;
    }

    if (empty($updates)) {
        echo json_encode(["success" => false, "message" => "No changes to save."]);
        exit;
    }

    // Add WHERE clause parameter
    $params[] = $collegeId;
    $paramTypes[] = PDO::PARAM_INT;

    $sql = "UPDATE colleges SET " . implode(", ", $updates) . " WHERE college_id = ?";
    $stmt = $con->prepare($sql);

    // Bind parameters dynamically
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindParam($i + 1, $params[$i], $paramTypes[$i]);
    }

    $stmt->execute();

    echo json_encode(["success" => true, "message" => "Document format saved successfully."]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error saving document format: " . $e->getMessage()]);
}
?>

