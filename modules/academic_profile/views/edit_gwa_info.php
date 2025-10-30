<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";
require_once PROJECT_PATH . "/access_check.php";
require_once PROJECT_PATH . "/functions.php";

// Check if user's college/program is hidden
checkUserAccess($con);

if (isset($_GET['studentId'])) {

    $student_id = $_GET['studentId'] ?? '';
    $current_academic_year = $_SESSION['filter_academic_year'];
    $current_college = $_SESSION['filter_college'];
    $current_program = $_SESSION['filter_program'];
    $current_semester = $_SESSION['filter_semester'];
    $current_year_level = $_SESSION['filter_year_level'];
    $current_section = $_SESSION['filter_section'];

    $sql = "SELECT
    si.student_id,
    si.student_number,
    si.student_fname,
    si.student_mname,
    si.student_lname,
    si.student_suffix,
    si.student_program,
    sg.year_level,
    sg.semester,
    sg.gwa
FROM
    student_info AS si
LEFT JOIN
    student_section AS ss ON si.student_number = ss.student_number
LEFT JOIN
    student_gwa AS sg ON si.student_number = sg.student_number
WHERE
    si.is_active = 1
    AND ss.is_active = 1
    AND (si.student_id = :student_id OR si.student_number = :student_id)
    AND ss.academic_year = :current_academic_year
    AND si.student_college = :current_college
    AND si.student_program = :current_program
    AND ss.semester = :current_semester
    AND ss.year_level = :current_year_level
    AND ss.section = :current_section";
    $stmt = $con->prepare($sql);
    
    try {
        $stmt->execute([
            ':student_id' => $student_id,
            ':current_academic_year' => $current_academic_year,
            ':current_college' => $current_college,
            ':current_program' => $current_program,
            ':current_semester' => $current_semester,
            ':current_year_level' => $current_year_level,
            ':current_section' => $current_section,
        ]);

        $studentInfo = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$studentInfo) {
            die("No matching student found.");
        }

        // Separate personal info + grades
        $studentData = null;
        $gwa = [];

    foreach ($studentInfo as $row) {
      if (!$studentData) {
        $studentData = [
          'student_id' => $row['student_id'],
          'student_number' => $row['student_number'],
          'fname' => $row['student_fname'],
          'mname' => $row['student_mname'],
          'lname' => $row['student_lname'],
          'suffix' => $row['student_suffix'],
          'program' => $row['student_program']
        ];
        $full_name = $studentData['lname'] . ', ' . $studentData['fname'];
        if (!empty($studentData['mname'])) {
          $full_name .= ' ' . $studentData['mname'];
        }
        if (!empty($studentData['suffix'])) {
          $full_name .= ' ' . $studentData['suffix'];
        }
      }
      if (!empty($row['year_level']) && !empty($row['semester'])) {
        $gwa[$row['year_level']][$row['semester']] = $row['gwa'];
      }
    }
    } catch (PDOException $e) {
        die("Query Failed: " . $e->getMessage());
    }
}

$yearStmt = $con->prepare("
    SELECT years
    FROM programs
    WHERE program_id = :program_id
");
$yearStmt->execute(['program_id' => $_SESSION['filter_program']]);
$programYears = $yearStmt->fetchColumn();

for ($y = 1; $y <= $programYears; $y++) {
    for ($s = 1; $s <= 2; $s++) {
        $gwaHeaders[] = "{$y}Y - {$s}S";
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
    <link rel="stylesheet" href="modules/academic_profile/css/update_academic_metrics.css">
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
              <h2>Student GWA Overview</h2>
              <div class="form-container">
                <div class="form-group row-group">
                  <div>
                    <label for="studentId">Student Number:</label>
                    <input type="text" id="studentId" name="studentId" placeholder="Student ID" value="<?php echo htmlspecialchars($studentData['student_number'] ?? ''); ?>" readonly required>
                  </div>
                  <div>
                    <label for="studentName">Student Name:</label>
                    <input type="text" id="studentName" name="studentName" placeholder="Student Name" value="<?php echo htmlspecialchars($full_name ?? ''); ?>" readonly required>
                  </div>
                </div>
                <div class="form-group full-width">
                  <label>Board Subjects and Grades:</label>
                  <div id="subjectGradesList">
                    <?php
                    if (!empty($gwaHeaders)) {
                      $headerIndex = 0;
                      for ($y = 1; $y <= $programYears; $y++) {
                        for ($s = 1; $s <= 2; $s++) {
                          $label = "{$y}Y - {$s}S";
                          if ($s == 1){
                              $sem = "{$s}ST";
                          } else {
                              $sem = "{$s}ND";
                          }
                          $grade = $gwa[$y][$sem] ?? ''; // lookup in gwa array
                          echo '<div class="subject-grade-row" style="display:flex;align-items:center;gap:16px;margin-bottom:8px;">';
                          echo '<input type="text" value="' . htmlspecialchars($label) . '" readonly style="min-width:200px; margin-right:8px;" />';
                          echo '<input type="text" value="' . htmlspecialchars($grade) . '" readonly style="width:100px; font-weight:bold;" />';
                          echo '</div>';
                          $headerIndex++;
                        }
                      }
                    } else {
                      echo '<span>No GWA records found.</span>';
                    }
                    ?>
                  </div>
                </div>
                <div class="button-container">
                  <button class="button back-page btn-clear" data-href="general-weighted-average" title="Go back to GWA.">Back</button>
                  <button class="button" onclick="openEditModal()" title="Click to edit the student's information.">Edit Student</button>
                </div>
              </div> <!--- Form container -->

              
            </div> <!--- Container -->
          </div> <!--- Container wrapper -->
        </div> <!--- Content -->
    </div> <!--- Main wrapper -->

    <!-- Edit Student Modal -->
    <div id="editStudentModal" class="modal">
        <div class="modal-content">
            <h2>Update Student GWA</h2>

            <form id="addStudentForm" action="modules/academic_profile/processes/submit_academic_profile.php" method="POST">
          <div class="form-group full-width">
            <label for="studentId">Student Number:</label>
            <input type="text" name="studentId" id="modalStudentId" value="<?php echo htmlspecialchars($studentData['student_number'] ?? ''); ?>" readonly>
          </div>
          <input type="hidden" name="form_type" value="gwa">
          <input type="hidden" id="filterProgram" name="filterProgram" value="<?php echo htmlspecialchars($studentData['program'] ?? ''); ?>">
          <div class="form-group row-group">
            <div>
              <label for="filterYearLevel">Year Level:</label>
              <select name="yearLevel" id="filterYearLevel" required>
                <option value="" disabled selected>Select Year Level</option>
              </select>
            </div>
            <div>
              <label for="semester">Semester:</label>
              <select name="semester" id="semester" required>
                <option value="" disabled selected>Select Semester</option>
                <option value="1ST">1ST SEMESTER</option>
                <option value="2ND">2ND SEMESTER</option>
              </select>
            </div>
          </div>
          <div class="form-group full-width">
            <label for="gwa">General Weighted Average:</label>
            <input type="text" name="gwa" id="gwa" required>
          </div>

          <div class="modal-buttons">
            <button type="button" id="cancelEdit" class="button btn-clear">Cancel</button>
            <button type="button" id="updateBtn" class="button">
              Update
              <div class="loader" id="editLoader" style="display:none;"></div>
            </button>
          </div>
        </form>
        </div>
    </div>
    <script src="core/logout_inform.js"></script>
    <script src="core/get_pending_count.js"></script>
    <script src="modules/academic_profile/js/academic_profile_tables_edit_add.js"></script>
    <script src="core/session_warning.js"></script>
        <script>
window.userSession = {
  level: <?php echo json_encode($_SESSION['level']); ?>,
  college: <?php echo json_encode($_SESSION['college']); ?>,
  program: <?php echo json_encode($_SESSION['program']); ?>
};
</script>
</body>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
        // Wait for subjectOptions to be loaded by the fetch in academic_profile_tables_edit_add.js
        // If subjectOptions is loaded asynchronously, you may need to wait for it
        if (typeof yearLevelOptions !== "undefined" && Object.keys(yearLevelOptions).length > 0) {
            populateFilterYears();
        } else {
            // If subjectOptions is not yet loaded, poll until it is
            let tries = 0;
            const interval = setInterval(function() {
                if (typeof yearLevelOptions !== "undefined" && Object.keys(yearLevelOptions).length > 0) {
                    populateFilterYears();
                    clearInterval(interval);
                }
                tries++;
                if (tries > 20) clearInterval(interval); // Stop after 2 seconds
            }, 100);
        }
    });
    </script>
    <script>
        const studentGWA = <?php echo json_encode($gwa); ?>;
    $('#filterYearLevel, #semester').on('change', function () {
        const yearId = $('#filterYearLevel').val();
        const semester = $('#semester').val();
        if (yearId && semester && studentGWA[yearId] && studentGWA[yearId][semester]) {
            $('#gwa').val(studentGWA[yearId][semester]);
        } else {
            $('#gwa').val('');
        }
    });
    </script>
</html>