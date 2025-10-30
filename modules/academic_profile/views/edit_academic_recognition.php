<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";
require_once PROJECT_PATH . "/access_check.php";
require_once PROJECT_PATH . "/functions.php";

// Define the static award name
const STATIC_AWARD_NAME = "Dean's List";

// Check if user's college/program is hidden
checkUserAccess($con);

$studentData = null;
$awardCount = 0; // Initialize award count

if (isset($_GET['studentId'])) {

    $student_id = $_GET['studentId'] ?? '';
    $current_academic_year = $_SESSION['filter_academic_year'];
    $current_college = $_SESSION['filter_college'];
    $current_program = $_SESSION['filter_program'];
    $current_semester = $_SESSION['filter_semester'];
    $current_year_level = $_SESSION['filter_year_level'];
    $current_section = $_SESSION['filter_section'];

    // Updated SQL query: Removes 'award_id' from SELECT and logic,
    // and simplifies the join to get the single award_count.
    $sql = "SELECT
        si.student_id,
        si.student_number,
        si.student_fname,
        si.student_mname,
        si.student_lname,
        si.student_suffix,
        sr.award_count
    FROM
        student_info AS si
    LEFT JOIN
        student_section AS ss ON si.student_number = ss.student_number
    LEFT JOIN
        student_academic_recognition AS sr ON si.student_number = sr.student_number
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

        $studentInfo = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch only one row since we expect only one award_count

        if (!$studentInfo) {
            die("No matching student found.");
        }

        // Process the fetched data
        $studentData = [
            'student_id' => $studentInfo['student_id'],
            'student_number' => $studentInfo['student_number'],
            'fname' => $studentInfo['student_fname'],
            'mname' => $studentInfo['student_mname'],
            'lname' => $studentInfo['student_lname'],
            'suffix' => $studentInfo['student_suffix'],
            'program' => $studentInfo['student_program']
        ];
        
        // Use the single award_count
        $awardCount = $studentInfo['award_count'] ?? 0;

        $full_name = $studentData['lname'] . ', ' . $studentData['fname'];
        if (!empty($studentData['mname'])) {
            $full_name .= ' ' . $studentData['mname'];
        }
        if (!empty($studentData['suffix'])) {
            $full_name .= ' ' . $studentData['suffix'];
        }

    } catch (PDOException $e) {
        die("Query Failed: " . $e->getMessage());
    }
}

// Since there is only one static award, the separate $awardStmt query is removed,
// and the $awards variable is no longer needed.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BRIDGE</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="modules/academic_profile/css/update_academic_metrics.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="Pictures/favicon.ico" type="image/x-icon">
</head>
<body>
    <div class="main-wrapper">
    <?php echo renderSidebar(); ?>

            <button class="toggle-btn" onclick="toggleSidebar()">
                <i id="toggleIcon" class="bi bi-chevron-double-left"></i>
            </button>

            <div class="sidebar-overlay"></div>

            <header>
                <a href="home">
                <img src="Pictures/white_logo.png" alt="MCU Logo" class="logo img-fluid">
                </a>
            </header>

      <div class="content">
        <div class="container-wrapper">
          <div class="container">
            <h2>Student Academic Recognition Overview</h2>
            <div class="form-container">
              <div class="form-group row-group">
                <div>
                  <label for="studentId">Student ID:</label>
                  <input type="text" id="studentId" name="studentId" placeholder="Student ID" value="<?php echo htmlspecialchars($studentData['student_number'] ?? ''); ?>" readonly required>
                </div>
                <div>
                  <label for="studentName">Student Name:</label>
                  <input type="text" id="studentName" name="studentName" placeholder="Student Name" value="<?php echo htmlspecialchars($full_name ?? ''); ?>" readonly required>
                </div>
              </div>
              <div class="form-group full-width">
                  <label>Award and Count:</label>
                  <div id="subjectGradesList">
                    <?php
                    // Display the single static award name and its count
                    if (!empty($studentData)) {
                        echo '<div class="subject-grade-row" style="display:flex;align-items:center;gap:16px;margin-bottom:8px;">';
                        echo '<input type="text" value="' . htmlspecialchars(STATIC_AWARD_NAME) . '" readonly style="min-width:200px; margin-right:8px;" />';
                        echo '<input type="text" value="' . htmlspecialchars($awardCount) . '" readonly style="width:100px; font-weight:bold;" />';
                        echo '</div>';
                    } else {
                        echo '<span>No student information loaded.</span>';
                    }
                    ?>
                  </div>
                </div>
                <div class="button-container">
                  <button class="button back-page btn-clear" data-href="academic-recognition" title="Go back to student info.">Back</button>
                  <button class="button" onclick="openEditModal()" title="Click to edit the student's information.">Edit Student</button>
                </div>
            </div> </div> </div> </div> </div> <div id="editStudentModal" class="modal">
      <div class="modal-content">
        <h2>Update Student Academic Recognition</h2>
        <form id="addStudentForm" action="modules/academic_profile/processes/submit_academic_profile.php" method="POST">
          <div class="form-group full-width">
            <label for="studentId">Student Number:</label>
            <input type="text" name="studentId" id="modalStudentId" value="<?php echo htmlspecialchars($studentData['student_number'] ?? ''); ?>" readonly>
          </div>
          <input type="hidden" name="form_type" value="awards">
            
          <div class="form-group full-width">
            <label for="filterAwards">Award:</label>
            <input type="text" id="filterAwards" value="<?php echo htmlspecialchars(STATIC_AWARD_NAME); ?>" readonly>
            <input type="hidden" name="filterAwards" value="static_award"> 
          </div>
          <div class="form-group full-width">
            <label for="awardCount">Count:</label>
            <input type="number" name="awardCount" id="awardCount" value="<?php echo htmlspecialchars($awardCount); ?>" required>
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
    // Since there is only one static award, the logic to populate and load award options is no longer needed.
    // The previous DOMContentLoaded block is removed.
    </script>
    <script>
        // The logic for changing award selection to load the count is no longer needed.
        // The count is loaded directly into the modal input field in the PHP above.
        // Remove or comment out this block if the associated JS in academic_profile_tables_edit_add.js is also updated.
        /*
        $('#filterAwards').on('change', function () {
            const awardId = $(this).val();
            if (recognitions[awardId]) {
                $('#awardCount').val(recognitions[awardId]);
            } else {
                $('#awardCount').val('');
            }
        });
        */
    </script>
</html>