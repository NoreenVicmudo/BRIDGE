<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";
require_once PROJECT_PATH . "/access_check.php";
require_once PROJECT_PATH . "/functions.php";

// Check if user's college/program is hidden
checkUserAccess($con);

// Check if user has access to document format entry
// Only admins (0), deans (1), and administrative assistants (2) can access
if (!isset($_SESSION['level']) || !in_array($_SESSION['level'], [0, 1, 2])) {
    header("Location: student-information-entry");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>BRIDGE</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <!-- Correct jQuery CDN -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- Correct DataTables JS CDN -->
        <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
        <!-- Correct DataTables CSS CDN 
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" />-->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="modules/additional_entry/css/data_entry.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.js"></script>
        <!-- Bootstrap 5.3.3 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="shortcut icon" href="Pictures/favicon.ico" type="image/x-icon">
    </head>
    <body>
        <div class="main-wrapper">
        <?php echo renderSidebar(); ?>

            <!-- Sidebar Toggle Button -->
            <button class="toggle-btn" onclick="toggleSidebar()">
                <i id="toggleIcon" class="bi bi-chevron-double-left"></i>
            </button>

            <div class="sidebar-overlay"></div>

            <header>
                <a href="home">
                <img src="Pictures/white_logo.png" alt="MCU Logo" class="logo img-fluid">
                </a>
            </header>

            <!-- CONTENT -->
            <div class="content">
                <div class="container-wrapper">
                    <div class="container">
                        <h2>Additional Entry</h2>
                        <?php 
                        $currentPage = basename($_SERVER['PHP_SELF'], '.php');
                        // Map actual file names to URL paths for navigation
                        if ($currentPage == 'student_info_data_entry') {
                            $currentPage = 'student-information-entry';
                        } elseif ($currentPage == 'academic_profile_data_entry') {
                            $currentPage = 'academic-profile-entry';
                        } elseif ($currentPage == 'program_metrics_data_entry') {
                            $currentPage = 'program-metrics-entry';
                        } elseif ($currentPage == 'document_format_entry') {
                            $currentPage = 'document-design';
                        }
                        ?>
                        <h4 class="mb-3">
                        <nav class="nav menu-bar">
                            <a class="nav-link <?php if($currentPage=='student-information-entry') echo 'active'; ?>" 
                            href="student-information-entry">
                            <span class="d-none d-md-inline">Student Information</span>
                            <i class="bi bi-person d-inline d-md-none"></i>
                            </a>
                            <?php if (isset($_SESSION['level']) && in_array($_SESSION['level'], [1, 2, 3])): ?>
                            <a class="nav-link <?php if($currentPage=='academic-profile-entry') echo 'active'; ?>" 
                            href="academic-profile-entry">
                            <span class="d-none d-md-inline">Academic Profile</span>
                            <i class="bi bi-journal-text d-inline d-md-none"></i>
                            </a>
                            <a class="nav-link <?php if($currentPage=='program-metrics-entry') echo 'active'; ?>" 
                            href="program-metrics-entry">
                            <span class="d-none d-md-inline">Program Metrics</span>
                            <i class="bi bi-bar-chart d-inline d-md-none"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (isset($_SESSION['level']) && in_array($_SESSION['level'], [0, 1, 2])): ?>
                            <a class="nav-link <?php if($currentPage=='document-design') echo 'active'; ?>" 
                            href="document-design">
                            <span class="d-none d-md-inline">Document Format</span>
                            <i class="bi bi-file-earmark-text d-inline d-md-none"></i>
                            </a>
                            <?php endif; ?>
                            <span class="underline"></span>
                        </nav>
                        </h4>

                        <!-- Dynamic content -->
                        <div id="tab-content">
                            <h4 id="tab-title"></h4>
                            <div class="entry-content">
                                <div class="form-group">
                                    <label for="metricSelect">Select College:</label>
                                    <select id="metricSelect">
                                        <option value="" disabled selected>Select</option>                                    
                                    </select>
                                </div>

                                <!-- Document Format Inputs (shown when college is selected) -->
                                <div id="documentFormatInputs" style="display: none;">
                                    <!-- Logo Upload -->
                                    <div class="form-group logo-upload-group">
                                        <label for="logoUpload">College Logo:</label>
                                        <div class="logo-upload-container">
                                            <div class="logo-preview-wrapper">
                                                <img id="logoPreview" src="assets/img/blank.png" alt="Logo Preview" class="logo-preview">
                                                <div class="logo-upload-overlay">
                                                    <i class="bi bi-camera-fill"></i>
                                                    <span>Click to upload</span>
                                                </div>
                                            </div>
                                            <input type="file" id="logoUpload" accept="image/jpeg,image/jpg,image/png" style="display: none;">
                                            <button type="button" class="btn-remove-logo" id="removeLogoBtn" style="display: none;">
                                                <i class="bi bi-x-circle"></i> Remove Logo
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Color Picker -->
                                    <div class="form-group color-input-group">
                                        <label for="colorPicker">Brand Color (Hex Code):</label>
                                        <div class="color-input-container">
                                            <input type="color" id="colorPicker" value="#5c297c">
                                            <input type="text" id="colorHexInput" placeholder="#5c297c" maxlength="7" pattern="^#[0-9A-Fa-f]{6}$">
                                        </div>
                                        <small class="color-hint">Choose a color or enter a 6-digit hex code (e.g., #5c297c)</small>
                                    </div>

                                    <!-- Email Input -->
                                    <div class="form-group email-input-group">
                                        <label for="collegeEmail">College Email:</label>
                                        <div class="email-input-container">
                                            <i class="bi bi-envelope email-icon"></i>
                                            <input type="email" id="collegeEmail" placeholder="college@mcu.edu.ph">
                                        </div>
                                    </div>
                                </div>
                               
                                <div class="entry-buttons" style="display: none;">               
                                    <button class="button" onclick="openValidationModal();" title="Save Information" disabled>Save</button>                        
                                </div> 
                            </div> <!--- Modal content -->
                        </div>
                            
                    </div> <!--- Container -->
                </div> <!--- Container wrapper -->
            </div> <!--- Content wrapper -->
        </div> <!--- Main wrapper -->

        <!-- Modal for validation before saving changes -->
        <div class="modal" id="validationModal">
            <div class="modal-content">
                <h2>Update Document Format</h2>
                <p>Are you sure you want to save?</p>
                <div class="modal-buttons">
                 <button class="button btn-clear" id="cancelSave" type="button">Cancel</button>
                <button class="button" id="confirmSave" type="button">
                    Confirm
                    <div class="loader" id="loader"></div>
                </button>                
                </div>
            </div>
        </div>
        
        <!-- Bootstrap JS -->
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
            <script src="core/logout_inform.js"></script>
            <script src="core/get_pending_count.js"></script>
            <script src="core/session_warning.js"></script>
            <script src="modules/additional_entry/js/general_additional_entry.js"></script>
            <script src="modules/additional_entry/js/document_format.js"></script>
            <script>
                window.userSession = {
                    level: <?php echo json_encode($_SESSION['level']); ?>,
                    college: <?php echo json_encode($_SESSION['college']); ?>,
                    program: <?php echo json_encode($_SESSION['program']); ?>
                };
            </script>     
    </body>
</html>