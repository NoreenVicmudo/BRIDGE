<?php
/**
 * Access Check Helper Functions
 * Provides functions to check if user's college/program is hidden
 */

/**
 * Check if user's college is hidden and redirect if necessary
 * @param PDO $con Database connection
 * @param string $redirectPath Path to redirect to (relative to root)
 * @return bool True if access is allowed, false if redirected
 */
function checkCollegeAccess($con, $redirectPath = '../access-restricted') {
    // Only check for deans, assistants, and program heads
    if (!in_array($_SESSION['level'], [1, 2, 3]) || empty($_SESSION['college'])) {
        return true; // No restrictions for these users
    }
    
    $checkCollegeStmt = $con->prepare("SELECT is_active FROM colleges WHERE college_id = ?");
    $checkCollegeStmt->execute([$_SESSION['college']]);
    $collegeStatus = $checkCollegeStmt->fetchColumn();
    
    if ($collegeStatus == 0) {
        // Capture current URL for redirect back
        $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
        $encodedUrl = urlencode($currentUrl);
        header("Location: ../{$redirectPath}?reason=college_hidden&return_url={$encodedUrl}");
        exit;
    }
    
    return true;
}

/**
 * Check if user's program is hidden and redirect if necessary
 * @param PDO $con Database connection
 * @param string $redirectPath Path to redirect to (relative to root)
 * @return bool True if access is allowed, false if redirected
 */
function checkProgramAccess($con, $redirectPath = '../access-restricted') {
    // Only check for program heads
    if ($_SESSION['level'] != 3 || empty($_SESSION['program'])) {
        return true; // No restrictions for these users
    }
    
    $checkProgramStmt = $con->prepare("SELECT is_active FROM programs WHERE program_id = ?");
    $checkProgramStmt->execute([$_SESSION['program']]);
    $programStatus = $checkProgramStmt->fetchColumn();
    
    if ($programStatus == 0) {
        // Capture current URL for redirect back
        $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
        $encodedUrl = urlencode($currentUrl);
        header("Location: ../{$redirectPath}?reason=program_hidden&return_url={$encodedUrl}");
        exit;
    }
    
    return true;
}

/**
 * Check both college and program access
 * @param PDO $con Database connection
 * @param string $redirectPath Path to redirect to (relative to root)
 * @return bool True if access is allowed, false if redirected
 */
function checkUserAccess($con, $redirectPath = '../access-restricted') {
    return checkCollegeAccess($con, $redirectPath) && checkProgramAccess($con, $redirectPath);
}
?>
