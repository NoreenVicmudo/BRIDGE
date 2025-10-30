<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";
require_once PROJECT_PATH . "/functions.php";

$userData = null;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int) $_GET['id'];

    $sql = "
      SELECT u.user_id,
             u.user_username,
             u.user_email,
             CONCAT(u.user_lastname, ', ', u.user_firstname) AS full_name,
             u.user_college,
             u.user_program,
             u.user_level,
             c.name AS college_name,
             p.name AS program_name,
             CASE u.user_level
               WHEN 0 THEN 'Admin'
               WHEN 1 THEN 'Dean'
               WHEN 2 THEN 'Administrative Assistant'
               WHEN 3 THEN 'Program Head'
               ELSE 'Unknown'
             END AS position_name
      FROM user_account u
      LEFT JOIN colleges c ON u.user_college = c.college_id
      LEFT JOIN programs p ON u.user_program = p.program_id
      WHERE u.user_id = :id
      LIMIT 1
    ";

    $stmt = $con->prepare($sql);
    $stmt->execute([':id' => $id]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        // graceful fallback — don't die; show empty fields instead
        $userData = null;
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
    <link rel="stylesheet" href="modules/user_information/css/update_user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.js"></script>
    <!-- Bootstrap 5.3.3 CSS -->
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
    
      <!-- READONLY STUDENT INFORMATION -->
      <div class="content">
        <div class="container-wrapper">
          <div class="container">             
              <h2>User Information Overview</h2>
              <div class="form-container">
                <div class="form-group row-group">
                  <div>
                    <label for="student_id_display">Username:</label>
                    <input type="text" name="student_id_display" id="student_id_display" value="<?= htmlspecialchars($userData['user_username'] ?? '') ?>" readonly>
                  </div>
                  <div>
                    <label for="college_display">Email:</label>
                    <input type="text" name="college_display" id="college_display" value="<?= htmlspecialchars($userData['user_email'] ?? '') ?>" readonly>
                  </div>
                </div>
                <div class="form-group full-width">
                  <label for="fullname_display">Full Name:</label>
                  <input type="text" name="fullname_display" id="fullname_display" value="<?= htmlspecialchars($userData['full_name'] ?? '') ?>" readonly>                   
                </div>
                <div class="form-group full-width">
                  <label for="program_display">College:</label>
                  <input type="text" name="program_display" id="program_display" value="<?= htmlspecialchars($userData['college_name'] ?? '') ?>" readonly>
                </div>
                <div class="form-group full-width">
                  <label for="yearlevel_display">Position:</label>
                  <input type="text" name="yearlevel_display" id="yearlevel_display" value="<?= htmlspecialchars($userData['position_name'] ?? ($userData['user_level'] ?? '')) ?>" readonly>
                </div>
                <?php
                  // only show program if there is one (and optionally only for Program Head)
                  $showProgram = !empty($userData['program_name']);
                  // If you only want Program Heads to see it, use:
                  // $showProgram = (!empty($userData['program_name']) && (int)($userData['user_level'] ?? 0) === 3);
                  if ($showProgram): ?>
                    <div class="form-group full-width">
                      <label for="program_name_display">Program:</label>
                      <input type="text" id="program_name_display"
                            value="<?= htmlspecialchars($userData['program_name']) ?>"
                            readonly>
                    </div>
                <?php endif; ?>
                <div class="button-container">
                  <button class="button btn-back-modal back-page" data-href="users" title="Go back to student info.">Back</button>
                  <button type="button" class="button" 
                    onclick="openEditModal(<?= isset($userData['user_id']) ? (int)$userData['user_id'] : 0 ?>)" 
                    title="Click to edit the student's information.">Update</button>
                </div>
            </div> <!--- Form container -->
            
          </div> <!--- Container -->
        </div> <!--- Container wrapper -->
      </div> <!--- Content wrapper -->
    </div> <!--- Main wrapper -->

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <h2>Update User Information</h2>
            <form method="POST" id="editUser">
              <input type="hidden" id="userId" name="id">

              <div class="form-group">
                <label>Full Name:</label>
                <div class="row-group row-group-name">
                  <input type="text" id="lname" name="lname" placeholder="Last Name">
                  <input type="text" id="fname" name="fname" placeholder="First Name">
                </div>
              </div>

              <div class="form-group full-width">
                <label for="filterCollege">College:</label>
                <select id="filterCollege" name="filterCollege"></select>
              </div>

              <div class="form-group full-width">
                <label for="filterPosition">Position:</label>
                <select id="filterPosition" name="filterPosition"></select>
              </div>

              <div class="form-group full-width" id="programGroup" style="display:none;">
                <label for="filterProgram">Program:</label>
                <select id="filterProgram" name="filterProgram"></select>
              </div>

              <p class="note"> WARNING: Editing user data will affect their access and will force the user to relogin.</p>

              <div class="modal-buttons">
                <button type="button" id="cancelEdit" class="button btn-back-modal form-btn">Cancel</button>
                <button type="submit" id="updateUserBtn" class="button form-btn">
                  <span class="btn-text">Update</span>
                  <div class="loader"></div>
                </button>
              </div>
            </form>
        </div>
    </div>
    <script src="modules/user_information/js/edit_user.js"></script>
    <script src="core/get_pending_count.js"></script>
    <script src="core/session_warning.js"></script>
</body>
</html>