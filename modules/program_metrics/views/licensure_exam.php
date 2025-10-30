<?php 
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";
require_once PROJECT_PATH . "/access_check.php";
require_once PROJECT_PATH . "/functions.php";

// Check if user's college/program is hidden
checkUserAccess($con);

$_SESSION['filter_metric'] = 'LicensureResult';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BRIDGE</title>
    <!-- Correct jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Correct DataTables JS CDN -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <!-- Correct DataTables CSS CDN 
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" />-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="modules/program_metrics/css/program_metrics_tables.css">
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

        <!-- NEW content wrapper to prevent overlap -->
        <div class="content">
            <div class="container-wrapper">
                <div class="container">
                    <h2>Licensure Exam Results</h2>
                    <!-- Active Filters Display -->
                    <div id="activeFiltersDisplay" class="mb-4"></div>

                    <!-- Table for displaying academic information -->
                    <div class="dataTables_wrapper">
                        <div class="table-wrapper">
                            <table id="myTable" class="display nowrap">
                                <thead>
                                    <tr class="top">
                                        <?php if ($_SESSION['level'] != 0) {?>
                                        <th class="select-column hidden">Select</th>
                                        <?php } ?>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>First Attempt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        <?php if ($_SESSION['level'] != 0) { ?>
                            <p class="note"> Note: Click on the Student ID to edit the student information.</p>
                        <?php }?>
                        </div> <!--- Table wrapper -->
                    </div> <!--- DataTables wrapper -->
                    <?php if ($_SESSION['level'] != 0) { ?>
                    <div class="button-container">
                        <button class="button" onclick="openAddStudentModal()" title="Click here to add a student.">Add Student</button>
                        <!--button class="button" id="deleteBtn" title="Click here to remove student/s.">Remove Student</button-->
                    <?php }?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Modal -->
    <div id="filterModal" class="modal">
        <div class="modal-content">
            <h2>Filter Students</h2>
            <div class="form-container">
                <div class="form-group">
                    <label for="filterCollege" class="form-label">College:</label>
                    <select name="filterCollege" id="filterCollege" class="form-select" onchange="populateFilterPrograms()">
                        <option value="none">Select College</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="filterProgram" class="form-label">Program:</label>
                    <select name="filterProgram" id="filterProgram" class="form-select" onchange="populateBatch()">
                        <option value="none">Select Program</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="Year" class="form-label">Year:</label>
                    <select name="year" id="Year" class="form-select" onchange="populateBatch()">
                        <option value="none">Select Year</option>
                        <option value="2026">2026</option>
                        <option value="2027">2027</option>
                        <option value="2028">2028</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="boardBatch" class="form-label">Board Exam Batch:</label>
                    <select name="board_batch" id="boardBatch" class="form-select">
                        <option value="none">Select Batch</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                    </select>
                </div>
            </div>

            <div class="modal-buttons">               
                <button onclick="closeFilterModal()" class="btn-clear">Cancel</button>
                <button onclick="applyFilters()">Apply Filters</button>
            </div>
        </div>
    </div>

    <!-- Metrics Modal -->
    <div id="metricsModal" class="modal">
        <div class="modal-content">
            <h2>Change Program Metric</h2>
            <div class="form-group">
                <label for="metricSelect" disabled selected>Select Metric:</label>
                <select id="metricSelect" onchange="handleMetricChange()">
                    <option value="">Select</option>
                    <option value="ReviewCenter">Review Center</option>
                    <option value="MockScores">Mock Exam Scores</option>
                    <option value="LicensureResult">Licensure Exam Results</option>
                </select>
            </div>

            <div class="modal-buttons">               
                <button class="btn-clear" onclick="cancelMetricsModal()">Cancel</button>      
                <button onclick="goToFilterModal()">Change</button>         
            </div> 
        </div>
    </div>

    <!-- Add Student Modal -->
    <div id="addStudentModal" class="modal">
        <div class="modal-content">
            <h2>Add Student Licensure Exam Result</h2>
            
            <!-- Initial Options -->
            <div id="initialOptions" class="options-container">
                <button id="importFileBtn" class="option-btn">
                    <i class="bi bi-file-earmark-excel"></i>
                    Import File
                </button>
                <button id="manualEntryBtn" class="option-btn">
                    <i class="bi bi-person-plus"></i>
                    Manual Entry
                </button>
            </div>

            <!-- Import File Section -->
            <div id="importFileSection" class="import-section" style="display: none;">
                <div class="upload-container">
                    <div class="upload-area" id="dropZone">
                        <i class="bi bi-cloud-upload"></i>
                        <p>Drag and drop your Excel file here</p>
                        <p>or</p>
                        <input type="file" id="fileInput" accept=".xlsx, .xls" style="display: none;">
                        <button class="browse-btn" onclick="document.getElementById('fileInput').click()">Browse Files</button>
                    </div>
                </div>
                <div class="modal-buttons">
                    <button type="button" id="cancelImport" class="button btn-clear">Cancel</button>
                </div>
            </div>

            <!-- Find Student ID Section (Manual Entry) -->
            <div id="findStudentSection" class="form-group full-width" style="display: none;">
                <label for="findstudentId">Find Student ID:</label>
                <input type="text" id="findstudentId" name="findstudentId" class="studentID" placeholder="Enter Student ID to check" required onkeypress="allowOnlyNumbers(event)">
                <p id="checkStudentResult" style="color: red; display: none;"></p>
                <div class="modal-buttons">
                    <button type="button" id="cancelEdit" class="button btn-clear">Cancel</button>
                    <button id="checkStudentBtn" type="button" data-href="edit-licensure-exam-results">Check<div class="loader" id="loader" style="display: none;"></div></button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="core/logout_inform.js"></script>
    <script src="core/get_pending_count.js"></script>
    <script src="modules/program_metrics/js/program_metrics_tables_edit_add.js"></script>
    <script>
            window.userSession = {
            level: <?php echo json_encode($_SESSION['level'] ?? ''); ?>,
            college: <?php echo json_encode($_SESSION['college'] ?? ''); ?>,
            program: <?php echo json_encode($_SESSION['program'] ?? ''); ?>,
            filter_college: <?php echo json_encode($_SESSION['filter_college'] ?? ''); ?>,
            filter_program: <?php echo json_encode($_SESSION['filter_program'] ?? ''); ?>,
            filter_year_batch: <?php echo json_encode($_SESSION['filter_year_batch'] ?? ''); ?>,
            filter_board_batch: <?php echo json_encode($_SESSION['filter_board_batch'] ?? ''); ?>,
            filter_metric: <?php echo json_encode($_SESSION['filter_metric'] ?? ''); ?>
            };

            document.addEventListener('DOMContentLoaded', function () {
            
                const formData = new FormData();
                    formData.append('college', <?php echo json_encode($_SESSION['filter_college'] ?? ''); ?>);
                    formData.append('program', <?php echo json_encode($_SESSION['filter_program'] ?? ''); ?>);
                    formData.append('yearBatch', <?php echo json_encode($_SESSION['filter_year_batch'] ?? ''); ?>);
                    formData.append('boardBatch', <?php echo json_encode($_SESSION['filter_board_batch'] ?? ''); ?>);
                    
                    fetch('modules/program_metrics/processes/filter_table_exam.php', {
                        method: "POST",
                        body: formData
                    })
                    .then(res => res.text()) // we expect HTML rows here
                    .then(data => {
                        // Update rows using DataTables API to prevent header/body misalignment
                        const table = window.studentInfoTable || $('#myTable').DataTable();
                        table.clear();
                        const $rows = $(data).filter('tr');
                        $rows.each(function () { table.row.add(this); });
                        table.draw(false);
                        table.columns.adjust().responsive.recalc();
                    })
                    .catch(err => {
                        console.error("Error loading table:", err);
                    });
                });
        </script>
</body>
</html>