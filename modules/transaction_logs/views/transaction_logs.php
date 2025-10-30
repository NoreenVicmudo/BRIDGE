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
    <title>Transaction Logs</title>
    <!-- Correct jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Correct DataTables JS CDN -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <!-- Correct DataTables CSS CDN 
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" />-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="modules/transaction_logs/css/transaction_logs.css">
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
                <h2>Transaction Logs</h2>
                <!-- Active Filters Display -->
                <div id="activeFiltersDisplay" class="mb-4"></div>
                
                <div class="dataTables_wrapper">
                    <div class="table-wrapper">
                        <table id="myTable" class="display nowrap">
                            <thead>
                                <tr class="top">
                                    <th>LogID</th>
                                    <th>User</th> 
                                    <th>College</th> <!-- Tanong ang for program heads if may extra field-->
                                    <th>Role</th>
                                    <th>Action</th>
                                    <th>Target Entity</th>
                                    <th>Remarks</th>
                                    <th>Date and Time</th>
                                </tr>
                            </thead>
                            <tbody>  
                            </tbody>
                        </table>
                    </div> <!--- Table wrapper -->
                </div> <!--- DataTables wrapper -->
            </div>
        </div> <!--- Container -->
    </div> <!--- Content wrapper -->

    <!-- Filter Modal -->
    <div id="filterModal" class="modal">
        <div class="modal-content">
            <h2>Filter Transaction</h2>
            <div class="form-container">
                <div class="form-group">
                    <label for="filterCollege">College:</label>
                    <select id="filterCollege" required>
                        <option value="all">ALL</option>
                    </select>
                </div>
                <!--div class="form-group">
                    <label for="filterProgram">Program:</label>
                    <select id="filterProgram" required>
                        <option value="none">Select</option>
                    </select>
                </div-->
                <div class="form-group">
                    <label for="filterAction">Action:</label>
                    <select id="filterAction" required>
                        <option value="all">ALL</option>
                        <option value="activityLog">ACTIVITY LOG</option>
                        <option value="addStudent">ADD STUDENT</option>
                        <option value="updateStudent">UPDATE STUDENT</option>
                        <option value="removeStudent">REMOVE STUDENT</option>
                        <option value="academicProfile">ACADEMIC PROFILE</option>
                        <option value="programMetrics">PROGRAM METRICS</option>
                        <option value="reportGeneration">REPORT GENERATION</option>
                        <option value="additionalEntry">ADDITIONAL ENTRY</option>
                    </select>
                </div>
            </div>
            <div class="modal-buttons">               
                <button onclick="closeFilterModal()">Cancel</button>
                <button onclick="applyFilters()">Apply</button>
            </div>
        </div>
    </div>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="modules/transaction_logs/js/transaction_logs.js"></script>
    <script src="core/logout_inform.js"></script>
    <script src="core/session_warning.js"></script>
    <script>
            window.userSession = {
            level: <?php echo json_encode($_SESSION['level']); ?>,
            college: <?php echo json_encode($_SESSION['college']); ?>,
            program: <?php echo json_encode($_SESSION['program']); ?>,
            filter_college: <?php echo json_encode($_SESSION['filter_college'] ?? ''); ?>,
            filter_program: <?php echo json_encode($_SESSION['filter_program'] ?? ''); ?>,
            filter_action: <?php echo json_encode($_SESSION['filter_action'] ?? ''); ?>
            };

            document.addEventListener('DOMContentLoaded', function () {

                const formData = new FormData();

                formData.append('college', <?php echo json_encode($_SESSION['filter_college'] ?? ''); ?>);
                formData.append('program', <?php echo json_encode($_SESSION['filter_program'] ?? ''); ?>);
                formData.append('action', <?php echo json_encode($_SESSION['filter_action'] ?? 'none'); ?>);
                formData.append('from_filter', 'true');

                    fetch("modules/transaction_logs/processes/filter_table_transaction.php", {
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
                        //table.columns.adjust().responsive.recalc();
                    })
                    .catch(err => {
                        console.error("Error loading table:", err);
                    });
                });
        </script>
</body>
</html>