<?php 
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['filter_type'] = $_POST['filter_type'] ?? '';
    if ($_SESSION['filter_type'] == 'section') {
        $_SESSION['filter_academic_year'] = ($_POST['academic_year'] ?? '');
        $_SESSION['filter_semester'] = ($_POST['semester'] ?? '');
        $_SESSION['filter_college'] = ($_POST['college'] ?? $_SESSION['college'] ?? '');
        $_SESSION['filter_program'] = ($_POST['program'] ?? $_SESSION['program'] ?? '');
        $_SESSION['filter_year_level'] = ($_POST['year_level'] ?? '');
        $_SESSION['filter_section'] = ($_POST['section'] ?? ''); 
    }
    else if ($_SESSION['filter_type'] == 'batch') {
        $_SESSION['filter_college'] = ($_POST['college'] ?? $_SESSION['college'] ?? '');
        $_SESSION['filter_program'] = ($_POST['program'] ?? $_SESSION['program'] ?? '');
        $_SESSION['filter_year_batch'] = ($_POST['yearBatch'] ?? '');
        $_SESSION['filter_board_batch'] = ($_POST['boardBatch'] ?? '');
    }
    exit();
}
?>