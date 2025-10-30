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
    si.student_suffix,
    sar.sessions_attended,
    sar.sessions_total
FROM
    student_info AS si
LEFT JOIN
    student_section AS ss ON si.student_number = ss.student_number
LEFT JOIN
    student_attendance_reviews AS sar ON si.student_number = sar.student_number
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
    
$query .= " GROUP BY student_number";

$stmt = $con->prepare($query);
$stmt->execute($params);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Your HTML echoing logic for the table rows remains the same
    // (e.g., building full name, age, address, and mapping IDs)
    // FULL NAME
    $full_name = $row['student_lname'] . ', ' . $row['student_fname'];
    if (!empty($row['student_mname'])) {
        $full_name .= ' ' . $row['student_mname'];
    }
    if (!empty($row['student_suffix'])) {
        $full_name .= ' ' . $row['student_suffix'];
    }

    $attended = $row['sessions_attended'] ?? '-';
    $total = $row['sessions_total'] ?? '-';

    echo "<tr>";
    if ($_SESSION['level'] != 0) {
        echo "<td class='select-column hidden'><input type='checkbox' class='row-select' /></td>";
        echo "<td><a data-href='edit-review-classes-attendance?studentId=" . htmlspecialchars(urlencode($row['student_id'])) . "' title='Click to edit student info' class='next-page'>" . htmlspecialchars($row['student_number']) . "</a></td>";
    } else {
        echo "<td>" . htmlspecialchars($row['student_number']) . "</td>";
    }
    echo "<td>" . htmlspecialchars($full_name) . "</td>";
    echo "<td>" .  $attended . "</td>";
    echo "<td>" .  $total . "</td>";
    echo "</tr>";
}
?>