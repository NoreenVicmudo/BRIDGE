<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";

// Set up filter variables from POST data
$college            = $_POST['college'] ?? '';
$program            = $_POST['program'] ?? '';
$filter_year_batch  = $_POST['yearBatch'] ?? '';
$filter_board_batch = $_POST['boardBatch'] ?? '';

// Store filter values in session
$_SESSION['filter_college'] = $college;
$_SESSION['filter_program'] = $program;
$_SESSION['filter_year_batch'] = $filter_year_batch;
$_SESSION['filter_board_batch'] = $filter_board_batch;

$query = "SELECT
    si.student_id,
    bb.batch_id,
    si.student_number,
    si.student_fname,
    si.student_mname,
    si.student_lname,
    si.student_suffix
FROM
    student_info AS si
LEFT JOIN
    board_batch AS bb ON si.student_number = bb.student_number
WHERE
    si.is_active = 1 and bb.is_active = 1";

$params = [];

// Append filters based on user input and session level
if (!empty($filter_year_batch) && $filter_year_batch !== 'none') {
    $query .= " AND bb.year = ?";
    $params[] = $filter_year_batch;
}

// College filter logic aligned with academic_profile roles
if ($_SESSION['level'] == 3) {
    // Program Head: force to assigned college
    $query .= " AND si.student_college = ?";
    $params[] = $_SESSION['college'];
} else if (($_SESSION['level'] == 2 || $_SESSION['level'] == 1)) {
    // Dean / Administrative Assistant: force to assigned college
    $query .= " AND si.student_college = ?";
    $params[] = $_SESSION['college'];
} else if ($_SESSION['level'] == 0 && !empty($college) && $college !== 'none') {
    // Admin: can select any college
    $query .= " AND si.student_college = ?";
    $params[] = $college;
}

// Program filter logic is also based on session level
if ($_SESSION['level'] == 3) {
    // Program Head: force to assigned program
    $query .= " AND bb.program_id = ?";
    $params[] = $_SESSION['program'];
} else if (($_SESSION['level'] == 2 || $_SESSION['level'] == 1) && !empty($program) && $program !== 'none') {
    // Dean / Administrative Assistant: can select program within their college
    $query .= " AND bb.program_id = ?";
    $params[] = $program;
} else if ($_SESSION['level'] == 0 && !empty($program) && $program !== 'none') {
    // Admin: can select any program
    $query .= " AND bb.program_id = ?";
    $params[] = $program;
}

if (!empty($filter_board_batch) && $filter_board_batch !== 'none') {
    $query .= " AND bb.batch_number = ?";
    $params[] = $filter_board_batch;
}

$query .= " ORDER BY si.student_number";

// Step 1: Fetch only subjects assigned to this program
$subjectStmt = $con->prepare("
    SELECT mock_subject_id, mock_subject_name
    FROM mock_subjects
    WHERE program_id = :program_id
");
$subjectStmt->execute(['program_id' => $_SESSION['filter_program']]);
$subjects = $subjectStmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Step 2: Fetch Students
$studentStmt = $con->prepare($query);
$studentStmt->execute($params);
$students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

// Step 3: Fetch Grades
$studentNumbers = array_column($students, 'batch_id');

if (!empty($studentNumbers)) {
    $in = str_repeat('?,', count($studentNumbers) - 1) . '?';
    $gradeStmt = $con->prepare("
        SELECT batch_id, mock_subject_id, student_score, total_score
        FROM student_mock_board_scores
        WHERE batch_id IN ($in)
    ");
    $gradeStmt->execute($studentNumbers);
    $grades = [];
    while ($row = $gradeStmt->fetch(PDO::FETCH_ASSOC)) {
        $grades[$row['batch_id']][$row['mock_subject_id']] = $row['student_score'] . '/' . $row['total_score'];
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
    echo "<td><a data-href='edit-mock-board-scores?studentId=" . htmlspecialchars(urlencode($student['batch_id'])) . "' title='Click to edit student info' class='next-page'>" . htmlspecialchars($student['student_number']) . "</a></td>";
    }
    else {
        echo "<td>" . htmlspecialchars($student['student_number']) . "</td>";
    }
    echo "<td>{$full_name}</td>";
    foreach ($subjects as $subjectId => $subjectName) {
        $grade = $grades[$student['batch_id']][$subjectId] ?? '-';
        echo "<td>$grade</td>";
    }
    echo "</tr>";
}
?>