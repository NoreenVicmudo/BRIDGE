<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";

// Define the static award name for display purposes in the parent file
const STATIC_AWARD_NAME = "Overall Academic Recognition";

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

// Step 1: Build the Student Query
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

// Append filters based on user input and session level (This filter logic remains unchanged)
if (!empty($academic_year) && $academic_year !== 'none') {
    $query .= " AND ss.academic_year = ?";
    $params[] = $academic_year;
}

// College filter logic
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

// Program filter logic
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

// Step 2: Fetch Students
$studentStmt = $con->prepare($query);
$studentStmt->execute($params);
$students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

// Step 3: Fetch Academic Recognition (Single Award Count)
$studentNumbers = array_column($students, 'student_number');
$recognition = [];

if (!empty($studentNumbers)) {
    $in = str_repeat('?,', count($studentNumbers) - 1) . '?';
    // UPDATED: Only select student_number and award_count. award_id is removed.
    $recogStmt = $con->prepare("
        SELECT student_number, award_count
        FROM student_academic_recognition
        WHERE student_number IN ($in)
    ");
    $recogStmt->execute($studentNumbers);
    
    // UPDATED: Store the count directly under student_number, no nested award_id key.
    while ($row = $recogStmt->fetch(PDO::FETCH_ASSOC)) {
        $recognition[$row['student_number']] = $row['award_count'];
    }
} else {
    // Only exit if no students were found and this script is expected to output table rows
    exit;
}


// Step 4: Output Table Rows
foreach ($students as $student) {

    //FULL NAME
    $full_name = $student['student_lname'] . ', ' . $student['student_fname'];

    if (!empty($student['student_mname'])) {
        $full_name .= ' ' . $student['student_mname'];
    }

    if (!empty($student['student_suffix'])) {
        $full_name .= ' ' . $student['student_suffix'];
    }   

    // Get the single award count for the current student
    // The count will be in $recognition[student_number]
    $recognitionCount = $recognition[$student['student_number']] ?? '-';

    echo "<tr>";
    if ($_SESSION['level'] != 0){
    echo "<td class='select-column hidden'><input type='checkbox' class='row-select' /></td>";
    echo "<td><a data-href='edit-academic-recognition?studentId=" . htmlspecialchars(urlencode($student['student_id'])) . "' title='Click to edit student info' class='next-page'>" . htmlspecialchars($student['student_number']) . "</a></td>";
    }
    else {
        echo "<td>" . htmlspecialchars($student['student_number']) . "</td>";
    }
    echo "<td>{$full_name}</td>";
    
    // UPDATED: Output only a single <td> for the static award count.
    // The previous loop using $awards is removed.
    echo "<td>$recognitionCount</td>";
    
    echo "</tr>";
}
?>