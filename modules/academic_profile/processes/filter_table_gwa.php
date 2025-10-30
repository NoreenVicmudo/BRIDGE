<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";

// Set up filter variables from POST data
$academic_year = $_POST['academic_year'] ?? '';
$college       = $_POST['college'] ?? '';
$program       = $_POST['program'] ?? '';
$semester      = $_POST['semester'] ?? '';
$year_level    = $_POST['year_level'] ?? '';
$section       = $_POST['section'] ?? '';

// Store filter values in session
$_SESSION['filter_academic_year'] = $academic_year;
$_SESSION['filter_college'] = $college;
$_SESSION['filter_program'] = $program;
$_SESSION['filter_semester'] = $semester;
$_SESSION['filter_year_level'] = $year_level;
$_SESSION['filter_section'] = $section;

$query = "SELECT
    si.student_id,
    si.student_number,
    ss.academic_year,
    si.student_fname,
    si.student_mname,
    si.student_lname,
    si.student_suffix
FROM
    student_info AS si
LEFT JOIN
    student_section AS ss ON si.student_number = ss.student_number
WHERE
    si.is_active = 1 AND ss.is_active = 1";

$params = [];

// Append filters based on user input and session level
if (!empty($academic_year) && $academic_year !== 'none') {
    $query .= " AND ss.academic_year = ?";
    $params[] = $academic_year;
}

// College filter logic is now based on session level
if ($_SESSION['level'] == 3) {
    // Level 3 users are restricted to their assigned college
    $query .= " AND si.student_college = ?";
    $params[] = $_SESSION['college'];
} else if (($_SESSION['level'] == 1 || $_SESSION['level'] == 2) && !empty($college) && $college !== 'none') {
    // Level 1/2 users can filter within their assigned college
    $query .= " AND si.student_college = ?";
    $params[] = $college;
} else if ($_SESSION['level'] == 0 && !empty($college) && $college !== 'none') {
    // Level 0 users can filter any college
    $query .= " AND si.student_college = ?";
    $params[] = $college;
}

// Program filter logic is also based on session level
if ($_SESSION['level'] == 3) {
    // Level 3 users are restricted to their assigned program
    $query .= " AND ss.program_id = ?";
    $params[] = $_SESSION['program'];
} else if (($_SESSION['level'] == 1 || $_SESSION['level'] == 2) && !empty($program) && $program !== 'none') {
    // Level 1/2 users can filter any program within their college
    $query .= " AND ss.program_id = ?";
    $params[] = $program;
} else if ($_SESSION['level'] == 0 && !empty($program) && $program !== 'none') {
    // Level 0 users can filter any program
    $query .= " AND ss.program_id = ?";
    $params[] = $program;
}

if (!empty($semester) && $semester !== 'none') {
    $query .= " AND ss.semester = ?";
    $params[] = $semester;
}
if (!empty($year_level) && $year_level !== 'none') {
    $query .= " AND ss.year_level = ?";
    $params[] = $year_level;
}
if (!empty($section) && $section !== 'none') {
    $query .= " AND ss.section = ?";
    $params[] = $section;
}
    
// Step 1: Fetch only subjects assigned to this program
$yearStmt = $con->prepare("
    SELECT years
    FROM programs
    WHERE program_id = :program_id
");
$yearStmt->execute(['program_id' => $_SESSION['filter_program']]);
$programYears = $yearStmt->fetchColumn();


// Step 2: Fetch Students
$studentStmt = $con->prepare($query);
$studentStmt->execute($params);
$students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

// Step 3: Fetch Grades
$studentNumbers = array_column($students, 'student_number');

if (!empty($studentNumbers)) {
    $in = str_repeat('?,', count($studentNumbers) - 1) . '?';
    $gwaStmt = $con->prepare("
        SELECT student_number, year_level, semester, gwa
        FROM student_gwa
        WHERE student_number IN ($in)
    ");
    $gwaStmt->execute($studentNumbers);
    $gwa = [];
    while ($row = $gwaStmt->fetch(PDO::FETCH_ASSOC)) {
        $gwa[$row['student_number']][$row['year_level']][$row['semester']] = $row['gwa'];
    }
} else {
    exit;
}

foreach ($students as $student) {

    //FULL NAME
    $full_name = $student['student_lname'] . ', ' . $student['student_fname'];

    if (!empty($student['student_mname'])) {
        $full_name .= ' ' . $student['student_mname'];
    }

    if (!empty($student['student_suffix'])) {
        $full_name .= ' ' . $student['student_suffix'];
    }   

    echo "<tr>";
    if ($_SESSION['level'] != 0){
    echo "<td class='select-column hidden'><input type='checkbox' class='row-select' /></td>";
    echo "<td><a data-href='edit-general-weighted-average?studentId=" . htmlspecialchars(urlencode($student['student_id'])) . "' title='Click to edit student info' class='next-page'>" . htmlspecialchars($student['student_number']) . "</a></td>";
    }
    else {
        echo "<td>" . htmlspecialchars($student['student_number']) . "</td>";
    }
    echo "<td>{$full_name}</td>";
    for ($y = 1; $y <= $programYears; $y++) {
        for ($s = 1; $s <= 2; $s++) {
            if ($s == 1){
                $sem = "{$s}ST";
            } else {
                $sem = "{$s}ND";
            }
            $val = $gwa[$student['student_number']][$y][$sem] ?? null;
            echo "<td>" . ($val ? number_format($val, 2) : '-') . "</td>";
        }
    }
    echo "</tr>";
}
?>