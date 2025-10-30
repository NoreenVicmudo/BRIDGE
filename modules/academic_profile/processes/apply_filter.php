<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$_SESSION['filter_academic_year'] = ($_POST['academic_year'] ?? $_SESSION['filter_academic_year'] ?? '');
$_SESSION['filter_semester'] = ($_POST['semester'] ?? $_SESSION['filter_semester'] ?? '');
$_SESSION['filter_college'] = ($_POST['college'] ?? $_SESSION['college'] ?? $_SESSION['filter_college'] ?? '');
$_SESSION['filter_program'] = ($_POST['program'] ?? $_SESSION['program'] ?? $_SESSION['filter_program'] ?? '');
$_SESSION['filter_year_level'] = ($_POST['year_level'] ?? $_SESSION['filter_year_level'] ?? '');
$_SESSION['filter_section'] = ($_POST['section'] ?? $_SESSION['filter_section'] ?? '');
$_SESSION['filter_metric'] = ($_POST['metric'] ?? $_SESSION['filter_metric'] ?? '');

$metric = ($_POST['metric'] ?? $_SESSION['filter_metric'] ?? '');

if ($metric === "GWA"){
    header('Location: general-weighted-average');
    exit();

} else if ($metric === "BoardGrades"){
    header('Location: board-subject-grades');
    exit();

} else if ($metric === "Retakes"){
    header('Location: back-subjects-retakes');
    exit();

} else if ($metric === "PerformanceRating"){
    header('Location: performance-rating');
    exit();

} else if ($metric === "SimExam"){
    header('Location: simulation-exam-results');
    exit();

} else if ($metric === "Attendance"){
    header('Location: review-classes-attendance');
    exit();

} else if ($metric === "Recognition"){
    header('Location: academic-recognition');
    exit();
}
}
?>