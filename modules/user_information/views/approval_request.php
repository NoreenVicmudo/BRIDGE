<?php
require_once __DIR__ . "/../../../core/config.php";
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
      <!-- Correct jQuery CDN -->
      <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
      <!-- Correct DataTables JS CDN -->
      <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
      <!-- Correct DataTables CSS CDN 
      <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" />-->
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
      <link rel="stylesheet" href="modules/user_information/css/approval_request.css">
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

          <!-- NEW content wrapper to prevent overlap -->
          <div class="content">
            <div class="container-wrapper">
              <div class="container">
                <h2>Approval Requests</h2>
                <div class="dataTables_wrapper">
                    <div class="table-wrapper">
                        <table id="myTable" class="display nowrap approval-table">
                            <thead>
                                <tr class="top">
                                <th>Username</th>
                                <th>Name</th>
                                <th>College</th>
                                <th>Position</th>
                                <th>Requested</th>
                                <th style="text-align:right;">Actions</th>
                          </tr>
                        </thead>
                        <tbody id="approval-body"></tbody>
                      </table>
                    </div> <!--table wrapper-->
                  </div> <!-- datatables wrapper-->
              </div> <!-- container-->
            </div> <!-- container wrapper-->
          </div> <!-- content-->
      </div> <!-- main wrapper-->


      <!-- Select Metrics Modal -->
      <div id="validateModal" class="modal">
          <div class="modal-content">
              <h2></h2> 
              <p></p>
              <div class="modal-buttons ">
                  <button class="btn-cancel-modal" onclick="cancelValidationModal()">
                    <span class="btn-text">Cancel</span>
                  </button>     
                  <button onclick="goToValidationModal(this)">
                    <span class="btn-text">Confirm</span>
                  </button>                             
              </div> 
          </div>
      </div>

      <!-- Bootstrap JS -->
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
      <script src="modules/user_information/js/approval_request.js"></script>
      <script src="core/get_pending_count.js"></script>
      <script src="core/session_warning.js"></script>
  </body>
</html>