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
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="modules/academic_profile/css/academic_profile.css">
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
                        <form id="filterForm" action="modules/academic_profile/processes/apply_filter.php" method="POST">
                            <div id="activeFilters" class="form-container">
                                <h2>Academic Metrics Filter Students</h2>
                                <div class="form-group">
                                    <label for="displayAcademicYear" class="form-label">Academic Year</label>
                                    <select name="academic_year" id="displayAcademicYear" class="form-select">
                                        <option value="none">Select</option>
                                        <option value="2022-2023">2022-2023</option>
                                        <option value="2023-2024">2023-2024</option>
                                        <option value="2024-2025">2024-2025</option>
                                        <option value="2025-2026">2025-2026</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="displayCollege" class="form-label">College</label>
                                    <select name="college" id="displayCollege" class="form-select" onchange="populatePrograms()">
                                        <option value="none">Select</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="displayProgram">Program</label>
                                    <select name="program" id="displayProgram" class="form-select" onchange="populateYears()">
                                        <option value="none">Select</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="displayYearLevel" class="form-label">Year Level</label>
                                    <select name="year_level" id="displayYearLevel" class="form-select" onchange="populateSections()">
                                        <option value="none">Select</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="displaySemester" class="form-label">Semester</label>
                                    <select name="semester" id="displaySemester" class="form-select" onchange="resetSectionAndMaybePopulate()">
                                        <option value="none">Select</option>
                                        <option value="1ST">1st Semester</option>
                                        <option value="2ND">2nd Semester</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="displaySection" class="form-label">Section</label>
                                    <select name="section" id="displaySection" class="form-select">
                                        <option value="none">Select</option>
                                    </select>
                                </div>

                                <div class="buttons">
                                    <button type="button" class="btn btn-secondary btn-clear" onclick="clearFilters()">Clear</button>
                                    <button type="button" class="btn btn-primary" id="filterStudentsBtn">Filter Students</button>
                                    <!--button type="submit" class="btn btn-primary">Filter Students</button-->
                                </div>

                            </div> <!--- Active filters container -->
                    </div> <!--- Form container -->
                </div> <!--- Container wrapper -->
            </div> <!--- Content wrapper -->
        </div> <!--- Main wrapper -->

         <!-- Select Metrics Modal -->
        <div id="metricsModal" class="modal">
            <div class="modal-content">
                <h2>Select Academic Metrics</h2>
                <div class="form-group">
                    <label for="metricSelect">Select Metric:</label>
                    <select name="metric" id="metricSelect" onchange="handleMetricChange()">
                        <option value="" disabled selected>Select</option>
                        <option value="GWA">GWA</option>
                        <option value="BoardGrades">Grades in Board Subjects</option>
                        <option value="Retakes">Back Subjects/Retakes</option>
                        <option value="PerformanceRating">Performance Rating</option>
                        <option value="SimExam">Simulation Exam Results</option>
                        <option value="Attendance">Attendance in Review Classes</option>
                        <option value="Recognition">Academic Recognition</option>
                    </select>
                </div>
                        </form>

                <div class="modal-buttons">
                    <button onclick="cancelMetricsModal()" class="btn-clear">Cancel</button>     
                    <button onclick="goToFilterModal()">View</button>                             
                </div> 
            </div>
        </div>
        
        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="core/logout_inform.js"></script>
        <script src="core/get_pending_count.js"></script>
        <script src="core/session_warning.js"></script>
        <script src="modules/academic_profile/js/academic_profile_filter.js"></script>
        <script>
            window.userSession = {
            level: <?php echo json_encode($_SESSION['level']); ?>,
            college: <?php echo json_encode($_SESSION['college']); ?>,
            program: <?php echo json_encode($_SESSION['program']); ?>
            };
        </script>
    </body>
</html>