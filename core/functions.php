<?php
// Define mapping function for headers
function parseGwaHeader($header) {
    // 1. New pattern to detect request for overall average GWA
    if (strtoupper($header) === 'ALL_GWA') {
        // Return a special identifier for the calling function (getBatchGWA)
        // to signal that the overall average is needed.
        return ['ALL', 'ALL'];
    }

    // 2. Original pattern for specific semester GWA
    // Expected formats: 1Y_1S, 2Y_2S, 3Y_1S, etc.
    if (preg_match('/^(\d)Y_(\d)S$/i', $header, $matches)) {
        $year_level = (int)$matches[1];
        // Note: The logic for semester mapping should match what is stored in your DB
        $semester   = $matches[2] == 1 ? "1ST" : "2ND"; 
        return [$year_level, $semester];
    }
    
    return null; // Invalid header
}

// Reusable sidebar function
function renderSidebar() {
    ob_start();
    ?>
    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <a href="home" class="sidebar-logo-link same-page">
            <img src="Pictures/white_logo.png" alt="MCU Logo" class="logo img-fluid">
        </a>
        <ul>
            <li><a href="student-information-filter" class="next-page"><i class="bi bi-person"></i> Student Information</a></li>
            <li><a href="academic-profile-filter" class="next-page"><i class="bi bi-journal-text"></i> Academic Profile</a></li>
            <li><a href="program-metrics-filter" class="next-page"><i class="bi bi-bar-chart-line"></i> Program Metrics</a></li>
            <li><a href="generate-report-filter" class="next-page"><i class="bi bi-file-earmark-text"></i> Generate Report</a></li>
            <li><a href="student-information-entry" class="next-page"><i class="bi bi-plus-circle"></i> Additional Entry</a></li>
            <?php if (isset($_SESSION['level']) && $_SESSION['level'] == 0): ?>
                <li><a href="transaction-logs" class="next-page"><i class="bi bi-journal-bookmark"></i> Transaction Logs</a></li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle"><i class="bi bi-person-gear"><span id="accountBadge" class="badge bg-danger"></span></i> User Management</a>
                    <ul class="dropdown-menu">
                        <li><a href="users" class="next-page"><i class="bi bi-tools"></i> User List </a></li>
                        <li><a href="approvals" class="next-page"><i class="bi bi-person-lock"><span id="approvalBadgeMenu" class="badge bg-danger"></span></i> Approval Request</a></li>
                    </ul>
                </li>
            <?php endif; ?>
            <div class="sidebar-profile">
                <div class="profile-toggle" id="profileToggle">
                    <img src="<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? 'Pictures/default.jpg'); ?>" alt="Profile" class="profile-img">
                    <div class="profile-info">
                        <div class="profile-text">
                            <span class="profile-name"><?php echo $_SESSION['username'] ?></span>
                            <span class="profile-position">
                                <?php 
                                    if ($_SESSION['level'] == 0) {
                                        echo "Admin";
                                    } else if ($_SESSION['level'] == 1) {
                                        echo "Dean";
                                    } else if ($_SESSION['level'] == 2) {
                                        echo "Administrative Assistant";
                                    } else if ($_SESSION['level'] == 3) {
                                        echo "Program Head";
                                    } 
                                ?>
                            </span>
                        </div>
                        <i class="bi bi-caret-up"></i>
                    </div>
                </div>
                <ul class="profile-dropdown">
                    <li><a href="settings" class="next-page"><i class="bi bi-gear"></i> Settings</a></li>
                    <li><a href="core/logout" class="next-page"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}
?>