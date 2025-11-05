<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";
require_once PROJECT_PATH . "/access_check.php";
require_once PROJECT_PATH . "/functions.php";

// Check if user's college/program is hidden
checkUserAccess($con);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>BRIDGE</title>
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="modules/generate_report/css/generate_report_filter.css">
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
        

            <!-- FILTER STUDENTS FORM -->
            <div class="content d-flex align-items-center justify-content-center" style="min-height: 100vh;">
                <div class="container-wrapper">
                    <div class="container" style="max-width: 700px;">
                        <form id="filterForm" action="modules/generate_report/processes/apply_filter.php" method="POST">
                            <div id="activeFilters" class="form-container">
                                <h2 id="headTitle">Report Generation<br>Batch Reports</h2>
                                <?php if (isset($_SESSION['level']) && $_SESSION['level'] == 0): ?>
                                <!-- Toggle Button -->
                                <div class="d-flex justify-content-end mb-3">
                                    <button id="toggleFilter" class="filter-toggle-btn" type="button">
                                        <i class="bi bi-arrow-left-right"></i><span>Switch to Programs Statistics</span>
                                    </button>
                                </div>
                                <?php endif; ?>
                                <!-- =================== BATCH FILTER (Default) =================== -->
                                <div id="batchFilter">
                                    <div class="form-group">
                                        <label for="displayCollege" class="form-label">College</label>
                                        <select name="college" id="displayCollege" class="form-select" onchange="populatePrograms()">
                                            <option value="none" disabled selected>Select</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="displayProgram" class="form-label">Program</label>
                                        <select name="program" id="displayProgram" class="form-select" onchange="populateYearStart()">
                                            <option value="none" disabled selected>Select</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="batchYear" class="form-label">Year Start</label>
                                        <select name="yearStart" id="YearStart" class="form-select" onchange="populateYearEnd()">
                                            <option value="none" disabled selected>Select</option>
                                            <option value="2026">2026</option>
                                            <option value="2027">2027</option>
                                            <option value="2028">2028</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="batchYear" class="form-label">Year End</label>
                                        <select name="yearEnd" id="YearEnd" class="form-select">
                                            <option value="none" disabled selected>Select</option>
                                            <option value="2026">2026</option>
                                            <option value="2027">2027</option>
                                            <option value="2028">2028</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- =================== ALL PROGRAMS STATISTICS (Hidden by default) =================== -->
                                <div id="yearFilter" style="display:none;">
                                    <div class="form-group">
                                        <label for="yearFilterField" class="form-label">Year</label>
                                        <select name="yearFilterField" id="yearFilterField" class="form-select">
                                            <option value="none" disabled selected>Select</option>
                                            <option value="2026">2026</option>
                                            <option value="2027">2027</option>
                                            <option value="2028">2028</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="buttons">
                                    <button type="button" class="btn btn-secondary btn-clear" onclick="clearFilters()">Clear</button>
                                    <button type="submit" class="btn btn-primary">Filter Students</button>
                                </div>

                            </div> <!--- Active filters container -->
                        </form>
                    </div> <!--- Form container -->
                </div> <!--- Container wrapper -->
            </div> <!--- Content wrapper -->
        </div> <!--- Main wrapper -->
        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="core/logout_inform.js"></script>
        <script src="core/get_pending_count.js"></script>
        <script src="core/session_warning.js"></script>
        <script src="modules/generate_report/js/generate_report_filter.js"></script>
        <script>
            window.userSession = {
            level: <?php echo json_encode($_SESSION['level'] ?? ''); ?>,
            college: <?php echo json_encode($_SESSION['college'] ?? ''); ?>,
            program: <?php echo json_encode($_SESSION['program'] ?? ''); ?>
            };
    </script>
    </body>
</html>