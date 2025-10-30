<?php
require_once __DIR__ . "/../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";
require_once PROJECT_PATH . "/functions.php";
?>

<!DOCTYPE html>

<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>BRIDGE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
      <link rel="stylesheet" href="public/css/main_page.css">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.css">
      <script src="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.js"></script>
      <!-- Bootstrap 5.3.3 CSS -->
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
      <link rel="shortcut icon" href="Pictures/favicon.ico" type="image/x-icon">
  </head>
  <body>
    <!-- Simple Loading Screen -->
    <div id="loadingScreen" class="loading-screen">
      <img src="Pictures/white_logo.png" alt="MCU Logo" class="loading-logo">
      <div class="loading-dots">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </div>

    <div class="main-wrapper">
      <?php echo renderSidebar(); ?>

      <!-- Sidebar Toggle Button -->
      <button class="toggle-btn" onclick="toggleSidebar()">
          <i id="toggleIcon" class="bi bi-chevron-double-left"></i>
      </button>
        
      <div class="main-content">
        <header>
          <a href="#" class="same-page">
            <img src="Pictures/white_logo.png" alt="MCU Logo" class="logo img-fluid">
          </a>
        </header>

        <section class="hero">
          <div class="hero-content hero-content-left">
            <div class="hero-welcome">Welcome to</div>
            <div class="hero-align-group">
              <div class="hero-bridge">BRIDGE</div>
              <div class="hero-subtitle">Board Readiness Intelligence and Data Governance Engine</div>
            </div>
          </div>
        </section>
        
        <section class="modules">
          <div class="container">
            <div class="row module-grid g-4 justify-content-center mx-auto">
              <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex">
                <a href="student-information-filter" class="module-card next-page w-100">
                  <div class="module-icon">
                    <i class="fas fa-user-graduate"></i>
                  </div>
                  <div class="module-content">
                    <h3>Student Information</h3>
                    <p>Access comprehensive student data and records</p>
                  </div>
                </a>
              </div>
              <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex">
                <a href="academic-profile-filter" class="module-card next-page w-100">
                  <div class="module-icon">
                    <i class="fas fa-book"></i>
                  </div>
                  <div class="module-content">
                    <h3>Academic Profile</h3>
                    <p>Access student academic performance metrics</p>
                  </div>
                </a>
              </div>
              <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex">
                <a href="program-metrics-filter" class="module-card next-page w-100">
                  <div class="module-icon">
                    <i class="fas fa-chart-line"></i>
                  </div>
                  <div class="module-content">
                    <h3>Program Metrics</h3>
                    <p>Analyze student board preparations</p>
                  </div>
                </a>
              </div>
              <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex">
                <a href="generate-report-filter" class="module-card next-page w-100">
                  <div class="module-icon">
                    <i class="fas fa-file-alt"></i>
                  </div>
                  <div class="module-content">
                    <h3>Generate Reports</h3>
                    <p>Create detailed reports and analytics</p>
                  </div>
                </a>
              </div>
              <?php if (isset($_SESSION['level']) && $_SESSION['level'] == 0): ?>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex">
                  <a href="users" class="module-card next-page w-100">
                    <div class="module-icon">
                      <i class="fas fa-tools"></i>
                    </div>
                    <div class="module-content">
                      <h3>User Management</h3>
                      <p>Manage user accounts and permissions</p>
                    </div>
                  </a>
                </div>
              <?php endif; ?>
              <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex">
                <a href="student-information-entry" class="module-card next-page w-100">
                  <div class="module-icon">
                    <i class="fas fa-plus-circle"></i>
                  </div>
                  <div class="module-content">
                    <h3>Additional Entry</h3>
                    <p>Add new data and information</p>
                  </div>
                </a>
              </div>
              <?php if (isset($_SESSION['level']) && $_SESSION['level'] == 0): ?>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex">
                  <a href="transaction-logs" class="module-card next-page w-100">
                    <div class="module-icon">
                      <i class="fas fa-book-open"></i>
                    </div>
                    <div class="module-content">
                      <h3>Transaction Logs</h3>
                      <p>Track system activities and changes</p>
                    </div>
                  </a>
                </div>
              <?php endif; ?>
              <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex">
                <a href="settings" class="module-card next-page w-100">
                  <div class="module-icon">
                    <i class="fas fa-cog"></i>
                  </div>
                  <div class="module-content">
                    <h3>Settings</h3>
                    <p>Change account details</p>
                  </div>
                </a>
              </div>
              <!--
              <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex">
                <div class="module-card w-100">
                  <div class="module-icon">
                    <i class="bi bi-three-dots"></i>
                  </div>
                  <div class="module-content">
                    <h3>Soon</h3>
                    <p>More features coming soon</p>
                  </div>
                </div>
              </div>-->
            </div>
          </div>
        </section>
      </div>
    </div>     

    <script src="public/js/main_page.js"></script>
    <!-- Bootstrap 5.3.3 JS Bundle (with Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="core/logout_inform.js"></script>
    <script src="core/get_pending_count.js"></script>
    <script src="core/session_warning.js"></script>
  </body>
</html>
