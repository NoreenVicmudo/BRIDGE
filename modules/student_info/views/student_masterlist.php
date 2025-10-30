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
        <!-- Correct jQuery CDN -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- Correct DataTables JS CDN -->
        <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
        <!-- Correct DataTables CSS CDN 
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" />-->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="modules/student_info/css/student_infolist.css">
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

            <!-- SELECTION BASED ON GRADES -->
            <div class="content">
                <div class="container-wrapper">
                <div class="container">
                    <h2>Student Masterlist</h2>
                        <!-- Active Filters Display>
                        <div id="activeFiltersDisplay" class="mb-4"></div-->

                        <!-- Table for displaying student information -->
                    <div class="dataTables_wrapper">
                        <div class="table-wrapper">
                            <table id="myTable" class="display nowrap">
                                <thead>
                                    <tr class="top">
                                        <?php if ($_SESSION['level'] != 0) {?>
                                        <th class="select-column hidden">Select</th>
                                        <?php } ?>
                                        <th>Student ID</th>
                                        <th>Student Name</th> <!-- Last Name, First Name, Middle Initial -->
                                        <th>College</th>
                                        <th>Program</th>
                                        <th>Age</th>
                                        <th>Sex</th>
                                        <th>Socioeconomic Status</th>
                                        <th>Permanent Address</th> <!-- Unit Number, House Number, Street Name, Barangay, City, State/Province, Zip Code -->
                                        <th>Current Living Arrangement</th>
                                        <th>Work Status</th>
                                        <th>Scholarship/Grant</th>
                                        <th>Language Spoken at Home</th>
                                        <th>Last School Attended (Senior High School)</th>
                                    </tr>
                                </thead>
                                <tbody id="studentTableBody">                                       
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
                            <button class="button" id="deleteBtn" title="Click here to remove student/s.">Remove Student</button>
                        </div>
                    <?php }?>
                </div> <!--- Container -->
                </div> <!--- Container wrapper -->
        </div> <!--- Content wrapper -->
        </div> <!--- Main wrapper -->

        <!-- Enhanced Delete Modal -->
        <div id="enhancedDeleteModal" class="modal">
        <div class="modal-content">
            <h2>Remove Student</h2>
            <p>You are about to delete the selected student record(s). Please provide a reason for this action.</p>
            <div style="margin-bottom: 12px; display: flex; align-items: center; gap: 24px;">
            <label style="font-weight: normal; display: flex; align-items: center; gap: 4px; margin-bottom: 0;">
                <input type="radio" name="deleteMode" value="single" checked style="margin-right: 4px;">
                Use one reason for all students
            </label>
            <label style="font-weight: normal; display: flex; align-items: center; gap: 4px; margin-bottom: 0;">
                <input type="radio" name="deleteMode" value="multiple" style="margin-right: 4px;">
                Use separate reason per student
            </label>
            </div>
            <!-- Single Reason Section -->
            <div id="singleReasonSection">
            <div id="singleStudentList" style="margin-bottom: 10px;"></div>
            <label for="singleReasonSelect" style="font-weight: normal;">Select a reason:</label>
            <select id="singleReasonSelect" class="custom-combobox">
                <option value="">Select</option>
                <option value="Incorrect or Incomplete Entry">Incorrect or Incomplete Entry</option>
                <option value="Transferred">The student transferred</option>
                <option value="Withdrawn">The student withdrawn or dropped out</option>
                <option value="Other">Other (please specify)</option>
            </select>
            <textarea id="singleOtherReason" style="display:none; margin-top:8px; width:100%; min-height:40px;" placeholder="Please specify the reason"></textarea>
            </div>
            <!-- Multiple Reason Section -->
            <div id="multipleReasonSection" style="display:none; max-height: 250px; overflow-y: auto; margin-top: 10px;"></div>
            <div class="modal-buttons" style="margin-top: 20px;">         
            <button id="cancelDeleteWithReason" class="button cancel-delete btn-clear">Cancel</button>
            <button id="confirmDeleteWithReason" class="button delete-confirm">Confirm and Delete</button>
            </div>
        </div>
        </div>

        <!-- Add Student Modal -->
        <div id="addStudentModal" class="modal">
            <div class="modal-content">
                <h2>Add New Student</h2>
                
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
                            <input type="file" id="fileInput" accept=".xlsx, .xls, .csv" style="display: none;">
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
                        <button id="checkStudentBtn" type="button" class="button">Check<div class="loader" id="loader" style="display: none;"></div></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirm Add Student Modal (shown when ID not found) -->
        <div id="confirmAddStudentModal" class="modal">
            <div class="modal-content">
                <h2>Add Student?</h2>
                <p>Student with this ID does not exist. Do you want to proceed to add the student?</p>
                <div id="confirmAddStudentOptions" class="options-container">
                    <button id="cancelAddStudent" class="option-btn btn-clear">
                        Cancel
                    </button>
                    <button id="proceedAddStudent" class="option-btn">
                        Proceed
                    </button>
                </div>
            </div>
        </div>

        
        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="core/logout_inform.js"></script>
        <script src="core/get_pending_count.js"></script>
        <script src="core/session_warning.js"></script>
        <script src="modules/student_info/js/student_masterlist.js"></script>
        <script>
            window.userSession = {
            level: <?php echo json_encode($_SESSION['level']); ?>,
            college: <?php echo json_encode($_SESSION['college']); ?>,
            program: <?php echo json_encode($_SESSION['program']); ?>
            };


            document.addEventListener('DOMContentLoaded', function () {
            
                const formData = new FormData();
                    formData.append('college', <?php echo json_encode($_SESSION['college']); ?>);
                    formData.append('program', <?php echo json_encode($_SESSION['program']); ?>);

                    fetch("modules/student_info/processes/filter_table_masterlist.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(res => res.text()) // we expect HTML rows here
                    .then(data => {
                        console.log(data);
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