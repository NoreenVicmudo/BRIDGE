<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";

header('Content-Type: application/json');

try {
    $collegeId = $_GET['college_id'] ?? null;
    
    if (!$collegeId) {
        echo json_encode(["success" => false, "message" => "College ID is required"]);
        exit;
    }

    // Check access - only admins (0), deans (1), and administrative assistants (2) can access
    $userLevel = $_SESSION['level'] ?? null;
    if (!in_array($userLevel, [0, 1, 2])) {
        echo json_encode(["success" => false, "message" => "Access denied"]);
        exit;
    }

    // Check if dean/assistant is accessing their own college
    if (in_array($userLevel, [1, 2])) {
        $userCollege = $_SESSION['college'] ?? null;
        if ($userCollege != $collegeId) {
            echo json_encode(["success" => false, "message" => "Access denied"]);
            exit;
        }
    }

    // Fetch college format data
    $stmt = $con->prepare("SELECT logo_path, brand_color, college_email FROM colleges WHERE college_id = ?");
    $stmt->execute([$collegeId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $response = [
            "success" => true,
            "color" => $row['brand_color'] ?? "#5c297c",
            "email" => $row['college_email'] ?? null
        ];

        // Return logo path if exists and file exists on disk
        // PROJECT_PATH is in core/, so we need the parent directory (bridge) for file checks
        if (!empty($row['logo_path']) && file_exists(dirname(PROJECT_PATH) . '/' . $row['logo_path'])) {
            // Return the file path that can be used directly in img src
            // basename(dirname(PROJECT_PATH)) gives us "bridge"
            $response["logo_path"] = '/' . basename(dirname(PROJECT_PATH)) . '/' . $row['logo_path'];
        } else {
            $response["logo_path"] = null;
        }

        echo json_encode($response);
    } else {
        echo json_encode([
            "success" => true,
            "color" => "#5c297c",
            "email" => null,
            "logo_path" => null
        ]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error fetching college data: " . $e->getMessage()]);
}
?>

