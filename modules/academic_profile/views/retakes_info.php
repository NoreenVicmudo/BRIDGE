<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";
require_once PROJECT_PATH . "/access_check.php";
require_once PROJECT_PATH . "/functions.php";

// Check if user's college/program is hidden
checkUserAccess($con);

$_SESSION['filter_metric'] = 'Retakes';

$subjectStmt = $con->prepare("
    SELECT general_subject_id, general_subject_name
    FROM general_subjects
    WHERE program_id = :program_id
");
$subjectStmt->execute(['program_id' => $_SESSION['filter_program']]);
$subjects = $subjectStmt->fetchAll(PDO::FETCH_KEY_PAIR);
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
    <link rel="stylesheet" href="modules/academic_profile/css/academic_metrics.css">
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
                    <h2>Back Subjects / Retakes</h2>
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
                                        <?php foreach ($subjects as $subjectName) {
                                            echo "<th>$subjectName</th>";
                                        } ?>
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
                    <?php }?>
                    </div>
                </div> <!--Container-->
            </div> <!-- Container wrapper-->
        </div> <!-- Content wrapper-->
    </div> <!-- Main wrapper-->

    <!-- Filter Modal -->
    <div id="filterModal" class="modal">
        <div class="modal-content">
            <h2>Filter Students</h2>
            <div class="form-container">
                <div class="form-group">
                    <label for="filterAcademicYear">A.Y.:</label>
                    <select id="filterAcademicYear" onchange="populateFilterSections()">
                        <option value="" disabled selected>Select</option>
                        <option>2022-2023</option>
                        <option>2023-2024</option>
                        <option>2024-2025</option>
                        <option>2025-2026</option>
                    </select>
                </div>

                

                <div class="form-group">
                    <label for="filterCollege">College:</label>
                    <select id="filterCollege" onchange="populateFilterPrograms()">
                        <option value="" disabled selected>Select</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="filterProgram">Program:</label>
                    <select id="filterProgram" onchange="populateFilterYears()">
                        <option value="" disabled selected>Select</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="filterYearLevel">Year Level:</label>
                    <select id="filterYearLevel" onchange="populateFilterSections()">
                        <option value="" disabled selected>Select</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="filterSemester">Semester:</label>
                    <select id="filterSemester" onchange="populateFilterSections()">
                        <option value="" disabled selected>Select</option>
                        <option value="1ST">1ST SEMESTER</option>
                        <option value="2ND">2ND SEMESTER</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="filterSection">Section:</label>
                    <select id="filterSection">
                        <option value="" disabled selected>Select</option>
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
            <h2>Change Academic Metric</h2>
            <div class="form-group">
                <label for="metricSelect">Select Metric:</label>
                <select id="metricSelect" onchange="handleMetricChange()">
                    <option value="" disabled selected>Select Metric:</option>
                    <option value="GWA">GWA</option>
                    <option value="BoardGrades">Grades in Board Subjects</option>
                    <option value="Retakes">Back Subjects/Retakes</option>
                    <option value="PerformanceRating">Performance Rating</option>
                    <option value="SimExam">Simulation Exam Results</option>
                    <option value="Attendance">Attendance in Review Classes</option>
                    <option value="Recognition">Academic Recognition</option>
                </select>
            </div>

            <div class="modal-buttons">              
                <button onclick="cancelMetricsModal()" class="back-page btn-clear">Cancel</button>        
                <button onclick="goToFilterModal()">Change</button>       
            </div> 
        </div>
    </div>

    <!-- Add Student Modal -->
    <div id="addStudentModal" class="modal">
        <div class="modal-content">
            <h2>Add Student Back Subjects / Retakes</h2>
            
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
                <input type="text" id="findstudentId" name="findstudentId" class="studentID" placeholder="Enter Student ID to check student's existence." required onkeypress="allowOnlyNumbers(event)">
                <p id="checkStudentResult" style="color: red; display: none;"></p>
                <div class="modal-buttons">
                    <button type="button" id="cancelEdit" class="button btn-clear">Cancel</button>
                    <button id="checkStudentBtn" type="button" data-href="edit-back-subjects-retakes">Check<div class="loader" id="loader" style="display: none;"></div></button>               
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="core/logout_inform.js"></script>
    <script src="core/get_pending_count.js"></script>
    <script src="modules/academic_profile/js/academic_profile_tables_edit_add.js"></script>
    <script src="core/session_warning.js"></script>
    <script>
            window.userSession = {
            level: <?php echo json_encode($_SESSION['level']); ?>,
            college: <?php echo json_encode($_SESSION['college']); ?>,
            program: <?php echo json_encode($_SESSION['program']); ?>,
            filter_academic_year: <?php echo json_encode($_SESSION['filter_academic_year']); ?>,
            filter_college: <?php echo json_encode($_SESSION['filter_college']); ?>,
            filter_program: <?php echo json_encode($_SESSION['filter_program']); ?>,
            filter_year_level: <?php echo json_encode($_SESSION['filter_year_level']); ?>,
            filter_section: <?php echo json_encode($_SESSION['filter_section']); ?>,
            filter_semester: <?php echo json_encode($_SESSION['filter_semester']); ?>,
            filter_metric: <?php echo json_encode($_SESSION['filter_metric']); ?>
            };

            document.addEventListener('DOMContentLoaded', function () {
            
                const formData = new FormData();
                    formData.append('academic_year', <?php echo json_encode($_SESSION['filter_academic_year']); ?>);
                    formData.append('college', <?php echo json_encode($_SESSION['filter_college']); ?>);
                    formData.append('program', <?php echo json_encode($_SESSION['filter_program']); ?>);
                    formData.append('semester', <?php echo json_encode($_SESSION['filter_semester']); ?>);
                    formData.append('year_level', <?php echo json_encode($_SESSION['filter_year_level']); ?>);
                    formData.append('section', <?php echo json_encode($_SESSION['filter_section']); ?>);

                    fetch('modules/academic_profile/processes/filter_table_retakes.php', {
                        method: "POST",
                        body: formData
                    })
                    .then(res => res.text()) // we expect HTML rows here
                    .then(data => {
                        console.log(data);
                        // Update rows using DataTables API to prevent header/body misalignment
                        const table = window.studentInfoTable || $('#myTable').DataTable();
                        table.clear();
                        const $rows = $(data);
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