<?php 
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$_SESSION['filter_college'] = ($_POST['college'] ?? $_SESSION['college'] ?? $_SESSION['filter_college'] ?? '');
$_SESSION['filter_program'] = ($_POST['program'] ?? $_SESSION['program'] ?? $_SESSION['filter_program'] ?? '');
$_SESSION['filter_year_batch'] = ($_POST['year'] ?? $_SESSION['filter_year'] ?? '');
$_SESSION['filter_board_batch'] = ($_POST['board_batch'] ?? $_SESSION['filter_batch'] ?? '');
$_SESSION['filter_metric'] = ($_POST['metric'] ?? $_SESSION['filter_metric'] ?? '');

$metric = ($_POST['metric'] ?? $_SESSION['filter_metric'] ?? '');

if ($metric === "ReviewCenter"){
    header('Location: student-review-center');
    exit();

} else if ($metric === "MockScores"){
    header('Location: mock-board-scores');
    exit();

} else if ($metric === "LicensureResult"){
    header('Location: licensure-exam-results');
    exit();

} else if ($metric === "ExameDate"){
    header('Location: exam-date-taken');
    exit();

} else if ($metric === "TakeAttempt"){
    header('Location: exam-takes');
    exit();

}}
?>