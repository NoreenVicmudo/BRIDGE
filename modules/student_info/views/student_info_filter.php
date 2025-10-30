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
        <link rel="stylesheet" href="modules/student_info/css/student_info.css">
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
                        <form id="filterForm" action="modules/student_info/processes/apply_filter.php" method="POST">
                            <div id="activeFilters" class="form-container">
                                <h2>Student Information Filter Students</h2>
                                
                                <!-- Toggle Button -->
                                <div class="d-flex justify-content-end mb-3">
                                    <button id="toggleFilter" class="filter-toggle-btn" type="button">
                                        <i class="bi bi-arrow-left-right"></i><span>Switch to Batch Filter</span>
                                    </button>
                                </div>

                                <!-- =================== SECTION FILTER (default visible) =================== -->
                                <div id="sectionFilter">
                                    <div class="form-group">
                                        <label for="displayAcademicYear" class="form-label">Academic Year</label>
                                        <select name="academic_year" id="displayAcademicYear" class="form-select" onchange="populateSections()">
                                            <option value="none" disabled selected>Select</option>
                                            <option value="2022-2023">2022-2023</option>
                                            <option value="2023-2024">2023-2024</option>
                                            <option value="2024-2025">2024-2025</option>
                                            <option value="2025-2026">2025-2026</option>
                                        </select>
                                    </div>                                  

                                    <div class="form-group">
                                        <label for="displayCollege" class="form-label">College</label>
                                        <select name="college" id="displayCollege" class="form-select" onchange="populatePrograms()">
                                            <option value="none" disabled selected>Select</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="displayProgram">Program</label>
                                        <select name="program" id="displayProgram" class="form-select" onchange="populateYears()">
                                            <option value="none" disabled selected>Select</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="displayYearLevel" class="form-label">Year Level</label>
                                        <select name="year_level" id="displayYearLevel" class="form-select" onchange="populateSections()">
                                            <option value="none" disabled selected>Select</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="displaySemester" class="form-label">Semester</label>
                                        <select name="semester" id="displaySemester" class="form-select" onchange="populateSections()">
                                            <option value="none" disabled selected>Select</option>
                                            <option value="1ST">1st Semester</option>
                                            <option value="2ND">2nd Semester</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="displaySection" class="form-label">Section</label>
                                        <select name="section" id="displaySection" class="form-select">
                                            <option value="none" disabled selected>Select</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- =================== BATCH FILTER (hidden by default) =================== -->
                                <div id="batchFilter" style="display:none;">
                                    <div class="form-group">
                                        <label for="batchCollege" class="form-label">College</label>
                                        <select name="batch_college" id="batchCollege" class="form-select" onchange="populateProgramsBatch()">
                                            <option value="none" disabled selected>Select</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="batchProgram" class="form-label">Program</label>
                                        <select name="batch_program" id="batchProgram" class="form-select">
                                            <option value="none" disabled selected>Select</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="batchYear" class="form-label">Year</label>
                                        <select name="year" id="Year" class="form-select">
                                            <option value="none" disabled selected>Select</option>
                                            <option value="2026">2026</option>
                                            <option value="2027">2027</option>
                                            <option value="2028">2028</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="boardBatch" class="form-label">Board Exam Batch</label>
                                        <select name="board_batch" id="boardBatch" class="form-select">
                                            <option value="none" disabled selected>Select</option>
                                            <option value="1">1</option>
                                            <option value="2">2</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Buttons by https://www.youtube.com/watch?v=VCLxJd1d84s -->
                                <div class="buttons">
                                    <a type="button" href="masterlist" class="btn btn-secondary next-page">View Masterlist</a>
                                    <button type="button" class="btn btn-clear" onclick="clearFilters()">Clear</button>
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
        <script src="modules/student_info/js/student_info_filter.js"></script>
        <script src="core/session_warning.js"></script>
        <script>
            window.userSession = {
            level: <?php echo json_encode($_SESSION['level']); ?>,
            college: <?php echo json_encode($_SESSION['college']); ?>,
            program: <?php echo json_encode($_SESSION['program']); ?>
            };
        </script>
    </body>
</html>