<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";

// Set up filter variables from POST data
$college       = $_POST['college'] ?? '';
$program       = $_POST['program'] ?? '';
$filter_year_batch    = $_POST['yearBatch'] ?? '';
$filter_board_batch       = $_POST['boardBatch'] ?? '';

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
    si.student_suffix,
    sl.exam_result,
    sl.exam_date_taken,
    (
        SELECT COUNT(*)
        FROM student_licensure_exam AS sle
        INNER JOIN board_batch AS bbb 
            ON sle.batch_id = bbb.batch_id
        WHERE bbb.student_number = si.student_number
          AND bbb.year <= ?
          AND bbb.program_id = ?
          AND bbb.is_active = 1
    ) AS attempt_count
FROM student_info AS si
LEFT JOIN board_batch AS bb 
    ON si.student_number = bb.student_number
LEFT JOIN student_licensure_exam AS sl 
    ON bb.batch_id = sl.batch_id
WHERE si.is_active = 1 
  AND bb.is_active = 1";

$params = [];
    $params[] = $filter_year_batch;
// Program filter logic is also based on session level
if ($_SESSION['level'] == 3) {
    $params[] = $_SESSION['program'];
} else if (($_SESSION['level'] == 2 || $_SESSION['level'] == 1) && !empty($program) && $program !== 'none') {
    $params[] = $program;
} else if ($_SESSION['level'] == 0 && !empty($program) && $program !== 'none') {
    $params[] = $program;
}

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
    // Level 0 users can filter any program
    $query .= " AND bb.program_id = ?";
    $params[] = $program;
}

if (!empty($filter_board_batch) && $filter_board_batch !== 'none') {
    $query .= " AND bb.batch_number = ?";
    $params[] = $filter_board_batch;
}

$query .= " ORDER BY si.student_number";

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

    $status = $row['exam_result'] ?? '-';
    $date_taken = $row['exam_date_taken'] ?? '-';
    if ($row['attempt_count'] > 1){
        $count = 'NO';
    } else if ($row['attempt_count'] == 1){
        $count = 'YES';
    } else {
        $count = '-';
    }

    echo "<tr>";
    if ($_SESSION['level'] != 0) {
        echo "<td class='select-column hidden'><input type='checkbox' class='row-select' /></td>";
        echo "<td><a data-href='edit-licensure-exam-results?studentId=" . htmlspecialchars(urlencode($row['batch_id'])) . "' title='Click to edit student info' class='next-page'>" . htmlspecialchars($row['student_number']) . "</a></td>";
    } else {
        echo "<td>" . htmlspecialchars($row['student_number']) . "</td>";
    }
    echo "<td>" . htmlspecialchars($full_name) . "</td>";
    echo "<td>" .  $date_taken . "</td>";
    echo "<td>" .  $status . "</td>";
    echo "<td>" .  $count . "</td>";
    echo "</tr>";
}
?>