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
    <!-- Correct jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Correct DataTables JS CDN -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <!-- Correct DataTables CSS CDN 
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" />-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="modules/student_info/css/update_student_info.css">
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

        <!-- Modal for validation before saving changes -->
        <div class="modal" id="validationModal">
            <div class="modal-content">
                <h2>Add Student</h2>
                <p>Are you sure you want to save?</p>
                <div class="modal-buttons">
                    <button class="button btn-clear" id="cancelSave" type="button">Cancel</button>
                    <!-- This will trigger the actual redirect -->
                    <button class="button" data-href="student-information" id="confirmSave" type="button">
                        Confirm
                        <div class="loader" id="loader"></div>
                    </button>                  
                </div>
            </div>
        </div>
       
        <div class="content">
            <div class="container-wrapper">
                <div class="container">
                    <h2>Student Information</h2>
                    <div class="form-container">
                        <form id="viewStudentForm" action="/bridge/modules/student_info/processes/submit_student_info.php" method="POST">
                        <input type="hidden" name="form_type" value="add_student_info">
                            <div class="form-group full-width">
                                <label for="studentId">Student ID:</label>
                                <input type="text" id="studentId" name="studentId" placeholder="Student ID"  readonly required>
                            </div>

                            <div class="form-group">
                                <label>Full Name:</label>
                                <div class="row-group row-group-name">
                                    <input type="text" id="lname" name="lname" placeholder="Last Name" required>
                                    <input type="text" id="fname" name="fname" placeholder="First Name" required>
                                    <input type="text" id="mname" name="mname" placeholder="Middle Name" required>
                                    <input type="text" id="Suffix" name="Suffix" placeholder="Suffix" class="smaller-input">
                                </div>
                            </div>

                            <div class="form-group row-group">
                                <div>
                                    <label for="filterCollege">College:</label>
                                    <select name="filterCollege" id="filterCollege" onchange="populateFilterPrograms()" required>
                                        <option value=""  selected>Select College</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="filterProgram">Program:</label>
                                    <select name="filterProgram" id="filterProgram" onchange="populateFilterYears()" required>
                                        <option value="" disabled selected>Select Program</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row-group">
                                <div>
                                    <label for="birthdate">Birthdate:</label>
                                    <input type="date" id="birthdate" name="birthdate" required>
                                </div>
                                <div>
                                    <label for="sex">Sex:</label>
                                    <select id="sex" name="sex" required>
                                        <option value="" disabled selected>Select Sex</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row-group">
                                <div>
                                    <label for="socioeconomicStatus">Socioeconomic Status (PHP):</label>
                                    <input type="number" id="socioeconomicStatus" name="socioeconomicStatus" placeholder="Amount in PHP" required>
                                </div>
                                <div>
                                    <label for="livingArrangement">Living Arrangement:</label>
                                    <select id="livingArrangement" name="livingArrangement" required>
                                        <option value="" disabled selected>Select Living Arrangement</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Address:</label>
                                <div class="row-group row-group-address">
                                    <input type="text" id="houseNo" name="houseNo" placeholder="House No." required>
                                    <input type="text" id="street" name="street" placeholder="Street" required>
                                    <input type="text" id="barangay" name="barangay" placeholder="Barangay" required>
                                </div>
                                <div class="row-group row-group-address">
                                    <input type="text" id="city" name="city" placeholder="City" required>
                                    <input type="text" id="state" name="state" placeholder="Province" required>
                                    <input type="text" id="postalCode" name="postalCode" placeholder="ZIP Code" required>
                                </div>
                            </div>

                            <div class="form-group row-group">
                                <div>
                                    <label for="workStatus">Work Status:</label>
                                    <select id="workStatus" name="workStatus" required>
                                        <option value="" disabled selected>Select Work Status</option>
                                        <option value="Full-time">Full-time</option>
                                        <option value="Part-time">Part-time</option>
                                        <option value="Not-Working">Not Working</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="scholarship">Scholarship Status:</label>
                                    <select id="scholarship" name="scholarship" required>
                                        <option value="" disabled selected>Select Scholarship Status</option>
                                        <option value="Internal">MCU-Funded</option>
                                        <option value="External">External</option>
                                        <option value="None">None</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row-group">                              
                                <div>
                                    <label for="language">Language Spoken At Home:</label>
                                    <select id="language" name="language" required>
                                        <option value="" disabled selected>Select Language</option>
                                    </select>                                  
                                </div>
                                <div>
                                    <label for="lastSchool">Last School Attended (SHS):</label>
                                    <select id="lastSchool" name="lastSchool" required>
                                        <option value="" disabled selected>Select School Type</option>
                                        <option value="Private">Private</option>
                                        <option value="Public">Public</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group full-width">
                            <!-- Hidden textbox for "Others" -->
                                <div id="otherLanguageContainer" style="display: none;">
                                    <label for="otherLanguage">If others, please specify:</label>
                                    <input type="text" id="otherLanguage" name="otherLanguage" placeholder="Enter other language" />
                                </div>
                            </div>
                        </form>
                        <div class="button-container">
                            <button class="button btn-clear back-page" data-href="student-information" title="Go back to student info.">Back</button>
                            <button type="button" class="button" id="saveButton" title="Click to save the student's information.">Add Student</button>
                        </div>
                    </div>                   
                </div> <!--- Container -->
            </div> <!--- Container wrapper -->
        </div> <!--- Content wrapper -->

    </div> <!--- Main wrapper -->
    <script src="core/logout_inform.js"></script>
    <script src="core/get_pending_count.js"></script>
    <script src="core/session_warning.js"></script>
    <script src="modules/student_info/js/student_info.js"></script>
    <script>
        window.userSession = {
        level: <?php echo json_encode($_SESSION['level']); ?>,
        college: <?php echo json_encode($_SESSION['college']); ?>,
        program: <?php echo json_encode($_SESSION['program']); ?>
        };
    </script>
    <script>
            document.addEventListener('DOMContentLoaded', function() {
            // Wait for languageOptions to be loaded by the fetch in academic_profile_tables_edit_add.js
            // If languageOptions is loaded asynchronously, you may need to wait for it
            if (typeof languageOptions !== "undefined" && Object.keys(languageOptions).length > 0) {
                populateLanguages();
            } else {
                // If languageOptions is not yet loaded, poll until it is
                let tries = 0;
                const interval = setInterval(function() {
                    if (typeof languageOptions !== "undefined" && Object.keys(languageOptions).length > 0) {
                        populateLanguages();
                        clearInterval(interval);
                    }
                    tries++;
                    if (tries > 20) clearInterval(interval); // Stop after 2 seconds
                }, 100);
                
            // Wait for arrangementOptions to be loaded by the fetch in academic_profile_tables_edit_add.js
            // If arrangementOptions is loaded asynchronously, you may need to wait for it
            if (typeof arrangementOptions !== "undefined" && Object.keys(arrangementOptions).length > 0) {
                populateArrangements();
            } else {
                // If arrangementOptions is not yet loaded, poll until it is
                let tries = 0;
                const interval = setInterval(function() {
                    if (typeof arrangementOptions !== "undefined" && Object.keys(arrangementOptions).length > 0) {
                        populateArrangements();
                        clearInterval(interval);
                    }
                    tries++;
                    if (tries > 20) clearInterval(interval); // Stop after 2 seconds
                }, 100);
            }
            }
        });
    </script>
</body>
</html>