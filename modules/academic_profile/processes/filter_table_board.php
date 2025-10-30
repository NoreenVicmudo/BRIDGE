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
    si.student_fname,
    si.student_mname,
    si.student_lname,
    si.student_suffix
FROM
    student_info AS si
LEFT JOIN
    student_section AS ss ON si.student_number = ss.student_number
WHERE
    si.is_active = 1 and ss.is_active = 1";

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
$subjectStmt = $con->prepare("
    SELECT subject_id, subject_name
    FROM board_subjects
    WHERE program_id = :program_id
");
$subjectStmt->execute(['program_id' => $program]);
$subjects = $subjectStmt->fetchAll(PDO::FETCH_KEY_PAIR); // [subject_id => subject_name]

// Step 2: Fetch Students
$studentStmt = $con->prepare($query);
$studentStmt->execute($params);
$students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

// Step 3: Fetch Grades
$studentNumbers = array_column($students, 'student_number');

if (!empty($studentNumbers)) {
    $in = str_repeat('?,', count($studentNumbers) - 1) . '?';
    $gradeStmt = $con->prepare("
        SELECT student_number, subject_id, subject_grade
        FROM student_board_subjects_grades
        WHERE student_number IN ($in)
    ");
    $gradeStmt->execute($studentNumbers);
    $grades = [];
    while ($row = $gradeStmt->fetch(PDO::FETCH_ASSOC)) {
        $grades[$row['student_number']][$row['subject_id']] = $row['subject_grade'];
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
    echo "<td><a data-href='edit-board-subject-grades?studentId=" . htmlspecialchars(urlencode($student['student_id'])) . "' title='Click to edit student info' class='next-page'>" . htmlspecialchars($student['student_number']) . "</a></td>";
    }
    else {
        echo "<td>" . htmlspecialchars($student['student_number']) . "</td>";
    }
    echo "<td>{$full_name}</td>";
    foreach ($subjects as $subjectId => $subjectName) {
        $grade = $grades[$student['student_number']][$subjectId] ?? '-';
        echo "<td>$grade</td>";
    }
    echo "</tr>";
}
?>