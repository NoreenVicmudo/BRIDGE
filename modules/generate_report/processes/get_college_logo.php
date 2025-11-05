<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";

header('Content-Type: image/png'); // Default to PNG, can be adjusted based on actual image type
header('Cache-Control: public, max-age=3600'); // Cache for 1 hour

try {
    $collegeId = $_GET['college_id'] ?? null;
    
    if (!$collegeId) {
        // Return a transparent 1x1 PNG if no college ID
        header('Content-Type: image/png');
        echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        exit;
    }

    // Fetch logo path from database
    $stmt = $con->prepare("SELECT logo_path FROM colleges WHERE college_id = ? AND is_active = 1");
    $stmt->execute([$collegeId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && !empty($row['logo_path'])) {
        // PROJECT_PATH is in core/, so we need the parent directory (bridge) for file path
        $filePath = dirname(PROJECT_PATH) . '/' . $row['logo_path'];
        if (file_exists($filePath)) {
            // Detect MIME type from file
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($filePath);
            
            // Set appropriate content type
            if (in_array($mimeType, ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp'])) {
                header('Content-Type: ' . $mimeType);
            }
            
            // Output file
            readfile($filePath);
            exit;
        }
    }
    
    // Return transparent 1x1 PNG if no logo exists
    header('Content-Type: image/png');
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
} catch (Exception $e) {
    // On error, return transparent 1x1 PNG
    header('Content-Type: image/png');
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
}
?>

