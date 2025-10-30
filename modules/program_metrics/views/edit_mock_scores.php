<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";
require_once PROJECT_PATH . "/access_check.php";
require_once PROJECT_PATH . "/functions.php";

// Check if user's college/program is hidden
checkUserAccess($con);

if (isset($_GET['studentId'])) {

    $batch_id = $_GET['studentId'] ?? '';
    $current_college = $_SESSION['filter_college'];
    $current_program = $_SESSION['filter_program'];
    $current_year = $_SESSION['filter_year_batch'];
    $current_batch = $_SESSION['filter_board_batch'];

    $sql = "SELECT
    si.student_id,
    bb.batch_id,
    si.student_number,
    si.student_fname,
    si.student_mname,
    si.student_lname,
    si.student_suffix,
    si.student_program,
    sm.mock_subject_id,
    sm.student_score,
    sm.total_score
FROM
    student_info AS si
LEFT JOIN
    board_batch AS bb ON si.student_number = bb.student_number
LEFT JOIN
    student_mock_board_scores AS sm ON bb.batch_id = sm.batch_id
WHERE
    si.is_active = 1
    AND bb.is_active = 1
    AND si.student_college = :current_college
    AND si.student_program = :current_program
    AND (bb.batch_id = :batch_id OR si.student_number = :batch_id)
    AND bb.year = :current_year
    AND bb.batch_number = :current_batch";
    $stmt = $con->prepare($sql);
    
    try {
        $stmt->execute([
            ':batch_id' => $batch_id,
            ':current_college' => $current_college,
            ':current_program' => $current_program,
            ':current_year' => $current_year,
            ':current_batch' => $current_batch
        ]);

        $studentInfo = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$studentInfo) {
            die("No matching student found.");
        }

        // Separate personal info + grades
        $studentData = null;
        $studentScore = [];
        $totalScore = [];

        foreach ($studentInfo as $row) {
            if (!$studentData) {
                $studentData = [
                    'batch_id' => $row['batch_id'],
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
            if (!empty($row['mock_subject_id'])) {
                $studentScore[$row['mock_subject_id']] = $row['student_score'];
                $totalScore[$row['mock_subject_id']] = $row['total_score'];
            }
        }
    } catch (PDOException $e) {
        die("Query Failed: " . $e->getMessage());
    }
}

$subjectStmt = $con->prepare("
    SELECT mock_subject_id, mock_subject_name
    FROM mock_subjects
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
    <link rel="stylesheet" href="modules/program_metrics/css/update_program_metrics.css">
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
              <h2>Mock Board Scores Overview</h2>
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
                    if (!empty($subjects)) {
                      foreach ($subjects as $subject_id => $subject_name) {
                        $score = isset($studentScore[$subject_id]) ? $studentScore[$subject_id] : '';
                        $total = isset($totalScore[$subject_id]) ? $totalScore[$subject_id] : '';
                        echo '<div class="subject-grade-row" style="display:flex;align-items:center;gap:16px;margin-bottom:8px;">';
                        echo '<input type="text" value="' . htmlspecialchars($subject_name) . '" readonly style="min-width:200px; margin-right:8px;" />';
                        echo '<input type="text" value="' . htmlspecialchars($score) . '" readonly style="width:100px; font-weight:bold;" />';
                        echo '/';
                        echo '<input type="text" value="' . htmlspecialchars($total) . '" readonly style="width:100px; font-weight:bold;" />';
                        echo '</div>';
                      }
                    } else {
                      echo '<span>No categories found for this program.</span>';
                    }
                    ?>
                  </div>
                </div>
                <div class="button-container"> 
                  <button class="button back-page btn-clear" data-href="mock-board-scores" title="Go back to Mock Board Scores.">Back</button>
                  <button class="button" onclick="openEditModal()" title="Click to edit the student's information.">Edit</button>
                </div>     
              </div> <!--- Form container -->
              
            </div> <!--- Container -->
          </div> <!--- Container wrapper -->
        </div> <!--- Content wrapper -->
    </div> <!--- Main wrapper -->

    <!-- Edit Student Modal -->
    <div id="editStudentModal" class="modal">
        <div class="modal-content">
            <h2>Update Mock Board Scores</h2>
            <form id="addStudentForm" action="modules/program_metrics/processes/submit_program_metrics.php" method="POST">
              <input type="hidden" name="form_type" value="mock_scores">
              <input type="hidden" id="filterProgram" name="filterProgram" value="<?php echo htmlspecialchars($studentData['program'] ?? ''); ?>">
              <div class="form-group row-group">
                <div>
                  <label for="studentId">Student ID:</label>
                  <input type="text" id="studentId" name="studentId" placeholder="Student ID" value="<?php echo htmlspecialchars($studentData['student_number'] ?? ''); ?>" readonly required>
                  <input type="hidden" name="batchId" value="<?php echo htmlspecialchars($studentData['batch_id'] ?? ''); ?>">
                </div>
                <div>
                  <label for="studentName">Student Name:</label>
                  <input type="text" id="studentName" name="studentName" placeholder="Student Name" value="<?php echo htmlspecialchars($full_name ?? ''); ?>" readonly required>
                </div>
              </div>

              <div class="form-group full-width">
                <label for="mockSubject">Subject Code:</label>
                <select name="mockSubject" id="mockSubject" required>
                  <option value="" disabled selected>Select Mock Subject</option>
                </select>
              </div>
              <div class="form-group row-group">
                <div>
                  <label for="studentScore">Score:</label>
                  <input type="number" name="studentScore" id="studentScore" required>
                </div> 
                                
                <div>
                  <label for="totalScore">Total Score:</label>
                  <input type="number" name="totalScore" id="totalScore" required>
                </div>
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
    <script src="modules/program_metrics/js/program_metrics_tables_edit_add.js"></script>
    <script src="core/logout_inform.js"></script>
    <script src="core/get_pending_count.js"></script>
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
        if (typeof subjectOptions !== "undefined" && Object.keys(subjectOptions).length > 0) {
            populateMockSubjects();
        } else {
            // If subjectOptions is not yet loaded, poll until it is
            let tries = 0;
            const interval = setInterval(function() {
                if (typeof subjectOptions !== "undefined" && Object.keys(subjectOptions).length > 0) {
                    populateMockSubjects();
                    clearInterval(interval);
                }
                tries++;
                if (tries > 20) clearInterval(interval); // Stop after 2 seconds
            }, 100);
        }
    });
    </script>
    <script>
        const studentScore = <?php echo json_encode($studentScore); ?>;
        const totalScore = <?php echo json_encode($totalScore); ?>;
        $('#mockSubject').on('change', function () {
        const subjectId = $(this).val();
        if (studentScore[subjectId]) {
            $('#studentScore').val(studentScore[subjectId]);
            $('#totalScore').val(totalScore[subjectId]);
        } else {
            $('#studentScore').val('');
            $('#totalScore').val('');
        }
    });
    </script>
</html>