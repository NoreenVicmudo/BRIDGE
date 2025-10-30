<?php
    require_once __DIR__ . "/../core/config.php";
    require_once PROJECT_PATH . "/j_conn.php";
    require_once PROJECT_PATH . "/auth.php";

    $level = $_SESSION['level'] ?? '';
    $college = $_SESSION['college'] ?? '';
    $program = $_SESSION['program'] ?? '';
    $reason = $_GET['reason'] ?? '';
    $returnUrl = $_GET['return_url'] ?? '';

    // Determine the specific reason and message
    $message = '';
    $title = 'Access Temporarily Restricted';
    $icon = '⚠️';

    switch($reason) {
        case 'college_hidden':
            $message = "Your college has been temporarily disabled by the administrator.";
            $title = "College Access Restricted";
            $icon = '🏫';
            break;
        case 'program_hidden':
            $message = "Your program has been temporarily disabled by the administrator.";
            $title = "Program Access Restricted";
            $icon = '📚';
            break;
        case 'no_permissions':
            $message = "You don't have the necessary permissions to access this area.";
            $title = "Access Denied";
            $icon = '🔒';
            break;
        default:
            $message = "Your access has been temporarily restricted.";
            $title = "Access Restricted";
            $icon = '🚫';
    }

    // Get user info for personalized message
    $userName = 'User';
    if (isset($_SESSION['firstname']) && isset($_SESSION['lastname'])) {
        $userName = $_SESSION['firstname'] . ' ' . $_SESSION['lastname'];
    } elseif (isset($_SESSION['username'])) {
        $userName = $_SESSION['username'];
    }
    $userLevel = '';
    switch($level) {
        case 1: $userLevel = 'Dean'; break;
        case 2: $userLevel = 'AdministrativeAssistant'; break;
        case 3: $userLevel = 'Program Head'; break;
        default: $userLevel = 'User';
    }

    // Get college and program names for display
    $collegeName = 'Not assigned';
    $programName = 'Not assigned';

    if (!empty($college)) {
        $collegeStmt = $con->prepare("SELECT name FROM colleges WHERE college_id = ?");
        $collegeStmt->execute([$college]);
        $collegeName = $collegeStmt->fetchColumn() ?: 'Unknown College';
    }

    if (!empty($program)) {
        $programStmt = $con->prepare("SELECT name FROM programs WHERE program_id = ?");
        $programStmt->execute([$program]);
        $programName = $programStmt->fetchColumn() ?: 'Unknown Program';
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $title; ?> - BRDGE</title>
        
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <!-- NProgress -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.js"></script>
        <link rel="stylesheet" href="public/css/access.css">
        <link rel="shortcut icon" href="Pictures/favicon.ico" type="image/x-icon">
    </head>
    <body>
        <div class="access-container">
            <div class="access-card">
                <!-- Header Section -->
                <div class="access-header">
                    <div class="access-icon"><?php echo $icon; ?></div>
                    <h1 class="access-title"><?php echo $title; ?></h1>
                    <p class="access-subtitle">BRIDGE</p>
                </div>
                
                <!-- Content Section -->
                <div class="access-content">
                    <!-- Message -->
                    <div class="access-message">
                        Hello <strong><?php echo htmlspecialchars($userName); ?></strong>,<br><br>
                        <?php echo $message; ?> This is a temporary restriction that has been put in place by the system administrator.
                    </div>
                    
                    <!-- User Information Card -->
                    <div class="user-info-card">
                        <h4><i class="bi bi-person-circle me-2"></i>Your Account Information</h4>
                        <p><strong>Role:</strong> <?php echo $userLevel; ?></p>
                        <p><strong>College:</strong> <?php echo htmlspecialchars($collegeName); ?></p>
                        <?php if ($level == 3): // Only show program for Program Heads ?>
                        <p><strong>Program:</strong> <?php echo htmlspecialchars($programName); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Actions Section -->
                    <div class="actions-section">
                        <h4><i class="bi bi-list-check me-2"></i>What you can do:</h4>
                        <div class="action-item">
                            <i class="bi bi-telephone action-icon"></i>
                            <span>Contact the system administrator to resolve this issue</span>
                        </div>
                        <div class="action-item">
                            <i class="bi bi-gear action-icon"></i>
                            <span>Access Settings to manage your account preferences</span>
                        </div>
                        <div class="action-item">
                            <i class="bi bi-arrow-clockwise action-icon"></i>
                            <span>Try accessing the page again to check if restrictions have been lifted</span>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4 col-sm-6">
                            <a href="settings" class="btn btn-custom btn-primary-custom w-100 back-page">
                                <i class="bi bi-gear"></i> Go to Settings
                            </a>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <a href="home" class="btn btn-custom btn-secondary-custom w-100 back-page">
                                <i class="bi bi-house"></i> Home Page
                            </a>
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <?php if (!empty($returnUrl)): ?>
                                <a href="<?php echo htmlspecialchars($returnUrl); ?>" class="btn btn-custom btn-success-custom w-100">
                                    <i class="bi bi-arrow-clockwise"></i> Try Again
                                </a>
                            <?php else: ?>
                                <a href="javascript:location.reload()" class="btn btn-custom btn-success-custom w-100">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh Page
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Contact Information Card -->
                    <div class="contact-info-card">
                        <h5><i class="bi bi-question-circle me-2"></i>Need Help?</h5>
                        <p>If you believe this is an error, please contact your system administrator.</p>                  
                    </div>
                </div>
            </div>
        </div>

        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="public/js/general_public.js"></script>
        <script src="core/logout_inform.js"></script>
        <script src="core/session_warning.js"></script>
    </body>
</html>
