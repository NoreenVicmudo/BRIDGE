<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";
require_once PROJECT_PATH . "/access_check.php";
require_once PROJECT_PATH . "/functions.php";

// Check if user's college/program is hidden
checkUserAccess($con);

if (isset($_GET['id'])) {
  // Load filter options for mapping
  include __DIR__ . '/../processes/populate_filter.php';
  $decodedOptions = json_decode($jsonOptions, true);

    $student_id = $_GET['id'] ?? '';

    $sql = "SELECT * FROM student_info WHERE student_id = :student_id";
    $stmt = $con->prepare($sql);
    
    try {
        $stmt->execute([
            ':student_id' => $student_id,
        ]);

        $studentInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$studentInfo) {
            die("No matching student found.");
        } else {
          $number                 = $studentInfo['student_number'] ?? '';
          $fname                  = $studentInfo['student_fname'] ?? '';
          $mname                  = $studentInfo['student_mname'] ?? '';
          $lname                  = $studentInfo['student_lname'] ?? '';
          $suffix                 = $studentInfo['student_suffix'] ?? '';
          $college_id             = $studentInfo['student_college'] ?? '';
          $program_id             = $studentInfo['student_program'] ?? '';
          $year_level             = $studentInfo['student_year'] ?? '';
          $section                = $studentInfo['student_section'] ?? '';
          $birthdate_raw          = $studentInfo['student_birthdate'] ?? '';
          $sex                    = $studentInfo['student_sex'] ?? '';
          $socioeconomic          = $studentInfo['student_socioeconomic'] ?? '';
          $living_id              = $studentInfo['student_living'] ?? '';
          $address_number         = $studentInfo['student_address_number'] ?? '';
          $address_street         = $studentInfo['student_address_street'] ?? '';
          $address_barangay       = $studentInfo['student_address_barangay'] ?? '';
          $address_city           = $studentInfo['student_address_city'] ?? '';
          $address_province       = $studentInfo['student_address_province'] ?? '';
          $address_postal         = $studentInfo['student_address_postal'] ?? '';
          $work                   = $studentInfo['student_work'] ?? '';
          $scholarship            = $studentInfo['student_scholarship'] ?? '';
          $language_id            = $studentInfo['student_language'] ?? '';
          $last_school            = $studentInfo['student_last_school'] ?? '';

          // Map college ID to name
          $college = '';
          foreach ($decodedOptions['collegeOptions'] as $collegeOption) {
            if ($collegeOption['id'] == $college_id) {
              $college = $collegeOption['name'];
              break;
            }
          }
          // Map program ID to name
          $program = '';
          foreach ($decodedOptions['programOptions'] as $college_id_key => $programs) {
            foreach ($programs as $programOption) {
              if ($programOption['id'] == $program_id) {
                $program = $programOption['name'];
                break 2;
              }
            }
          }
          
          // Map program ID to name
          $living = '';
          foreach ($decodedOptions['arrangementOptions'] as $arrangementOption) {
            if ($arrangementOption['id'] == $living_id) {
              $living = $arrangementOption['name'];
              break;
            }
          }
          
          // Map program ID to name
          $language = '';
          foreach ($decodedOptions['languageOptions'] as $languageOption) {
            if ($languageOption['id'] == $language_id) {
              $language = $languageOption['name'];
              break;
            }
          }
          
          $full_name = $lname . ', ' . $fname;
            if (!empty($mname)) {
                $full_name .= ' ' . $mname;
            }
            if (!empty($suffix)) {
                $full_name .= ' ' . $suffix;
            }

          //AGE
          $birthdate = new DateTime($birthdate_raw);
          $today = new DateTime();
          $age = $today->diff($birthdate)->y;

          $parts = [];
          if (!empty($address_number)) $parts[] = $address_number;
          if (!empty($address_street)) $parts[] = $address_street;
          if (!empty($address_barangay)) $parts[] = $address_barangay;
          if (!empty($address_city)) $parts[] = $address_city;
          if (!empty($address_province)) $parts[] = $address_province;
          if (!empty($address_postal)) $parts[] = $address_postal;
          $full_address = implode(', ', $parts);

        }
    } catch (PDOException $e) {
        die("Query Failed: " . $e->getMessage());
    }
}
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
       
        <!-- READONLY STUDENT INFORMATION -->
        <div class="content">
          <div class="container-wrapper">
            <div class="container">             
                <h2>Student Information Overview</h2>
                <div class="form-container">
                  <div class="form-group row-group">
                    <div>
                      <label for="studentId">Student ID:</label>
                      <input type="text" name="studentId" id="studentId" value="<?=$number?>" readonly>
                    </div>
                    <div>
                      <label for="studentName">Full Name:</label>
                      <input type="text" name="studentName" id="studentName" value="<?=$full_name?>" readonly>
                    </div>
                  </div>
                  <div class="form-group row-group">
                    <div>
                      <label for="studentField1">College:</label>
                      <input type="text" name="studentField1" id="studentField1" value="<?=$college?>" readonly>
                    </div>
                    <div>
                      <label for="studentField2">Program:</label>
                      <input type="text" name="studentField2" id="studentField2" value="<?=$program?>" readonly>
                    </div>
                  </div>
                  <div class="form-group row-group">
                    <div>
                      <label for="studentField5">Age:</label>
                      <input type="text" name="studentField5" id="studentField5" value="<?=$age?>" readonly>
                    </div>
                    <div>
                      <label for="studentField6">Sex:</label>
                      <input type="text" name="studentField6" id="studentField6" value="<?=$sex?>" readonly>
                    </div>
                  </div>
                  <div class="form-group row-group">
                    <div>
                      <label for="studentField7">Socioeconomic Status:</label>
                      <input type="text" name="studentField7" id="studentField7" placeholder="Amount - Status" value="<?=$socioeconomic?>" readonly>
                    </div>
                    <div>
                      <label for="studentField9">Living Arrangement:</label>
                      <input type="text" name="studentField9" id="studentField9" value="<?=$living?>" readonly>
                    </div>
                  </div>
                  <div class="form-group">
                    <div>
                      <label for="studentField8">Address:</label>
                      <input type="text" name="studentField8" id="studentField8" value="<?=$full_address?>" readonly>
                    </div>
                  </div>
                  <div class="form-group row-group">
                    <div>
                      <label for="studentField10">Work Status:</label>
                      <input type="text" name="studentField10" id="studentField10" value="<?=$work?>" readonly>
                    </div>
                    <div>
                      <label for="studentField11">Scholarship/Grant:</label>
                      <input type="text" name="studentField11" id="studentField11" value="<?=$scholarship?>" readonly>
                    </div>
                  </div>
                  <div class="form-group row-group">
                    <div>
                      <label for="studentField12">Language Spoken at Home:</label>
                      <input type="text" name="studentField12" id="studentField12" value="<?=$language?>" readonly>
                    </div>
                    <div>
                      <label for="studentField13">Last School Attended (SHS):</label>
                      <input type="text" name="studentField13" id="studentField13" value="<?=$last_school?>" readonly>
                    </div>
                  </div>
                  <div class="button-container">
                    <button class="button back-page btn-clear" data-href="masterlist" title="Go back to student info.">Back</button>
                    <button class="button" onclick="openEditModal()" title="Click to edit the student's information.">Edit Student</button>
                  </div>
              </div> <!--- Form container -->
              
            </div> <!--- Container -->
          </div> <!--- Container wrapper -->
        </div> <!--- Content wrapper -->
    </div> <!--- Main wrapper -->

    <!-- Edit Student Modal -->
    <div id="editStudentModal" class="modal">
        <div class="modal-content">
            <h2>Update Student Information</h2>
            <form id="addStudentForm" action="submit_student_info.php" method="POST">
            <input type="hidden" name="form_type" value="update_student_info_masterlist">
            <input type="hidden" name="studentId" id="studentId" value="<?=$student_id?>">
                <div class="form-group full-width">
                  <input type="hidden" name="academic_year" id="academic_year" value="<?=$academic_year?>">
                  <!--<div>
                    <label for="filterAcademicYear" class="form-label">Academic Year</label>
                      <select id="AcademicYear" name="academic_year">
                          <option value="">Select Academic Year</option>
                          <option value="2022-2023">2022-2023</option>
                          <option value="2023-2024">2023-2024</option>
                          <option value="2024-2025">2024-2025</option>
                          <option value="2025-2026">2025-2026</option>
                      </select>
                  </div>-->
                </div>

                <div class="form-group">
                  <label>Full Name:</label>
                  <div class="row-group row-group-name">
                      <input type="text" id="lname" name="student_lname" placeholder="Last Name">
                      <input type="text" id="fname" name="student_fname" placeholder="First Name">
                      <input type="text" id="mname" name="student_mname" placeholder="Middle Name">
                      <input type="text" id="Suffix" name="student_suffix" placeholder="Suffix" class="smaller-input">
                  </div>
                </div>

              <div class="form-group row-group">
                <div>
                    <label for="filterCollege">College:</label>
                    <select id="filterCollege" name="student_college" onchange="populateFilterPrograms()">
                        <option value="">Select College</option>
                    </select>
                </div>
                <div>
                    <label for="filterProgram">Program:</label>
                    <select id="filterProgram" name="student_program" onchange="populateFilterYears()">
                        <option value="">Select Program</option>
                    </select>
                </div>
            </div>

            <div class="form-group row-group">
                <div>
                    <label for="socioeconomicStatus">Socioeconomic Status (PHP):</label>
                    <input type="number" id="socioeconomicStatus" name="student_socioeconomic" placeholder="Amount in PHP">
                </div>
                <div>
                    <label for="livingArrangement">Living Arrangement:</label>
                    <select id="livingArrangement" name="student_living">
                        <option value="">Select Living Arrangement</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Address:</label>
                <div class="row-group row-group-address">
                    <input type="text" id="houseNo" name="student_address_number" placeholder="House No.">
                    <input type="text" id="street" name="student_address_street" placeholder="Street">
                    <input type="text" id="barangay" name="student_address_barangay" placeholder="Barangay">
                </div>
                <div class="row-group row-group-address">
                    <input type="text" id="city" name="student_address_city" placeholder="City">
                    <input type="text" id="state" name="student_address_province" placeholder="Province">
                    <input type="text" id="postalCode" name="student_address_postal" placeholder="ZIP Code">
                </div>
            </div>

            <div class="form-group row-group">
                <div>
                    <label for="workStatus">Work Status:</label>
                    <select id="workStatus" name="student_work">
                        <option value="">Select Work Status</option>
                        <option value="Full-time">Full-time</option>
                        <option value="Part-time">Part-time</option>
                        <option value="Not-Working">Not Working</option>
                    </select>
                </div>
                <div>
                    <label for="scholarship">Scholarship Status:</label>
                    <select id="scholarship" name="student_scholarship">
                        <option value="">Select Scholarship Status</option>
                        <option value="Internal">MCU-Funded</option>
                        <option value="External">External</option>
                        <option value="None">None</option>
                    </select>
                </div>
            </div>
                <div class="modal-buttons">
                    <button type="button" id="cancelEdit" class="button btn-clear">Cancel</button>
                    <button type="submit" id="updateBtn" class="button">
                  Update
                  <div class="loader" id="loader" style="display:none;"></div>
                  </button>
                </div>
            </form>
        </div>
    </div>
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