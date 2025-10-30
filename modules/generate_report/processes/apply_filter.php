<?php 
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['filter_type'] = $_POST['filter_type'] ?? '';
    if ($_SESSION['filter_type'] == 'batch') {
        $_SESSION['filter_college'] = ($_POST['college'] ?? $_SESSION['college'] ?? $_SESSION['filter_college'] ?? '');
        $_SESSION['filter_program'] = ($_POST['program'] ?? $_SESSION['program'] ?? $_SESSION['filter_program'] ?? '');
        $_SESSION['filter_year_start'] = ($_POST['yearBatchStart'] ?? $_SESSION['filter_year'] ?? '');
        $_SESSION['filter_year_end'] = ($_POST['yearBatchEnd'] ?? $_SESSION['filter_year'] ?? '');
        $_SESSION['filter_board_batch'] = ($_POST['boardBatch'] ?? $_SESSION['filter_batch'] ?? '');
        echo 'generate-report-batch';
        exit();
    }
    else if ($_SESSION['filter_type'] == 'programs') {
        $_SESSION['filter_year_batch'] = ($_POST['yearBatch'] ?? '');
         echo 'generate-report-programs';
        exit();
    }
}
?>