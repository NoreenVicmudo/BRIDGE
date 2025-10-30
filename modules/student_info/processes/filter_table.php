<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";
include 'populate_filter.php';

// Decode options outside the conditional block
$decodedOptions = json_decode($jsonOptions, true);

$filter_type = $_POST['filter_type'] ?? 'section';
$_SESSION['filter_type'] = $filter_type;

if ($filter_type == 'section') {
    // Set up filter variables from POST data
    $filter_year_batch  = '';
    $filter_board_batch = '';

    $academic_year = $_POST['academic_year'] ?? '';
    $college       = $_POST['college'] ?? '';
    $program       = $_POST['program'] ?? '';
    $semester      = $_POST['semester'] ?? $_SESSION['filter_semester'] ?? '';
    $year_level    = $_POST['year_level'] ?? '';
    $section       = $_POST['section'] ?? '';

    $_SESSION['filter_academic_year'] = $academic_year;
    $_SESSION['filter_semester'] = $semester;
    $_SESSION['filter_college'] = $college;
    $_SESSION['filter_program'] = $program;
    $_SESSION['filter_year_level'] = $year_level;
    $_SESSION['filter_section'] = $section; 
}
else if ($filter_type == 'batch') {
    // Set up filter variables from POST data
    $academic_year = '';
    $semester      = '';
    $year_level    = '';
    $section       = '';

    $college            = $_POST['college'] ?? '';
    $program            = $_POST['program'] ?? '';
    $filter_year_batch  = $_POST['yearBatch'] ?? '';
    $filter_board_batch = $_POST['boardBatch'] ?? '';

    $_SESSION['filter_college'] = $college;
    $_SESSION['filter_program'] = $program;
    $_SESSION['filter_year_batch'] = $filter_year_batch;
    $_SESSION['filter_board_batch'] = $filter_board_batch;
}

$query = "SELECT
    si.student_id,
    si.student_number,
    ss.academic_year,
    si.student_fname,
    si.student_mname,
    si.student_lname,
    si.student_suffix,
    si.student_college,
    si.student_program,
    ss.year_level,
    ss.section,
    si.student_birthdate,
    si.student_sex,
    si.student_socioeconomic,
    si.student_living,
    si.student_address_number,
    si.student_address_street,
    si.student_address_barangay,
    si.student_address_city,
    si.student_address_province,
    si.student_address_postal,
    si.student_work,
    si.student_scholarship,
    si.student_language,
    si.student_last_school
FROM
    student_info AS si
LEFT JOIN
    student_section AS ss ON si.student_number = ss.student_number
LEFT JOIN
    board_batch AS bb ON si.student_number = bb.student_number
WHERE
    si.is_active = 1";

$params = [];

// Append filters based on user input and session level
if (!empty($academic_year) && $academic_year !== 'none' && $filter_type == 'section') {
    $query .= " AND ss.academic_year = ?";
    $params[] = $academic_year;
}

// College filter logic based on user level
if ($_SESSION['level'] == 1 || $_SESSION['level'] == 2) {
    // Dean/Assistant: always restrict to their assigned college
    $query .= " AND si.student_college = ?";
    $params[] = $_SESSION['college'];
} else if ($_SESSION['level'] == 3) {
    // Program Head: also restrict to their assigned college
    $query .= " AND si.student_college = ?";
    $params[] = $_SESSION['college'];
} else if ($_SESSION['level'] == 0 && !empty($college) && $college !== 'none') {
    // Admin: optional college filter
    $query .= " AND si.student_college = ?";
    $params[] = $college;
}

if ($filter_type == 'section') {
    // Program filter logic is also based on session level
    if ($_SESSION['level'] == 3) {
        // Program Head: always restricted to their assigned program
        $query .= " AND ss.program_id = ?";
        $params[] = $_SESSION['program'];
    } else if (!empty($program) && $program !== 'none') {
        // Admin/Dean/Assistant: apply program filter only if explicitly selected
        $query .= " AND ss.program_id = ?";
        $params[] = $program;
    }

} else if ($filter_type == 'batch') {
    // Program filter logic is also based on session level
    if ($_SESSION['level'] == 3) {
        // Program Head: always restricted to their assigned program
        $query .= " AND bb.program_id = ?";
        $params[] = $_SESSION['program'];
    } else if (!empty($program) && $program !== 'none') {
        // Admin/Dean/Assistant: apply program filter only if explicitly selected
        $query .= " AND bb.program_id = ?";
        $params[] = $program;
    }
}

if (!empty($semester) && $semester !== 'none' && $filter_type == 'section') {
    $query .= " AND ss.semester = ?";
    $params[] = $semester;
}
if (!empty($year_level) && $year_level !== 'none' && $filter_type == 'section') {
    $query .= " AND ss.year_level = ?";
    $params[] = $year_level;
}
if (!empty($section) && $section !== 'none' && $filter_type == 'section') {
    $query .= " AND ss.section = ?";
    $params[] = $section;
}


if (!empty($filter_year_batch) && $filter_year_batch !== 'none' && $filter_type == 'batch') {
    $query .= " AND bb.year = ?";
    $params[] = $filter_year_batch;
}
if (!empty($filter_board_batch) && $filter_board_batch !== 'none' && $filter_type == 'batch') {
    $query .= " AND bb.batch_number = ?";
    $params[] = $filter_board_batch;
}

if ($filter_type == 'section') {
        $query .= " AND ss.is_active = 1";
} else if ($filter_type == 'batch') {
        $query .= " AND bb.is_active = 1";
}
    
$query .= " GROUP BY student_number ORDER BY student_number ASC";

$stmt = $con->prepare($query);
$stmt->execute($params);

$statusStmt = $con->query('SELECT status, minimum, maximum FROM socioeconomic_status');
$incomeBrackets = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

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

    // AGE
    $birthdate = new DateTime($row['student_birthdate']);
    $today = new DateTime();
    $age = $today->diff($birthdate)->y;

    // ADDRESS
    $parts = [];
    if (!empty($row['student_address_number'])) $parts[] = $row['student_address_number'];
    if (!empty($row['student_address_street'])) $parts[] = $row['student_address_street'];
    if (!empty($row['student_address_barangay'])) $parts[] = $row['student_address_barangay'];
    if (!empty($row['student_address_city'])) $parts[] = $row['student_address_city'];
    if (!empty($row['student_address_province'])) $parts[] = $row['student_address_province'];
    if (!empty($row['student_address_postal'])) $parts[] = $row['student_address_postal'];
    $full_address = implode(', ', $parts);

    // Map college ID to name
    $college_name = '';
    foreach ($decodedOptions['collegeOptions'] as $collegeOption) {
        if ($collegeOption['id'] == $row['student_college']) {
            $college_name = $collegeOption['name'];
            break;
        }
    }

    // Map program ID to name
    $program_name = '';
    foreach ($decodedOptions['programOptions'] as $college_id => $programs) {
        foreach ($programs as $program) {
            if ($program['id'] == $row['student_program']) {
                $program_name = $program['name'];
                break 2;
            }
        }
    }

    // Map year level ID to name
    $year_name = '';
    foreach ($decodedOptions['yearLevelOptions'] as $program_id => $years) {
        foreach ($years as $year) {
            if ($year['id'] == $row['year_level']) {
                $year_name = $year['name'];
                break 2;
            }
        }
    }

    $socioeconomic_status = '';
    foreach ($incomeBrackets as $stat) {
        $min = $stat['minimum'];
        $max = $stat['maximum'];

        if (($min === null || $row['student_socioeconomic'] >= $min) && ($max === null || $row['student_socioeconomic'] <= $max)) {
            $socioeconomic_status = $stat['status'];
        }
    }

    $living_arrangement = '';
    foreach ($decodedOptions['arrangementOptions'] as $arrangementOption) {
        if ($arrangementOption['id'] == $row['student_living']) {
            $living_arrangement = $arrangementOption['name'];
            break;
        }
        $living_arrangement = 'HOME';
    }

    $language_spoken = '';
    foreach ($decodedOptions['languageOptions'] as $languageOption) {
        if ($languageOption['id'] == $row['student_language']) {
            $language_spoken = $languageOption['name'];
            break;
        }
        $language_spoken = 'FILIPINO';
    }

    echo "<tr>";
    if ($_SESSION['level'] != 0) {
        echo "<td class='select-column hidden'><input type='checkbox' class='row-select' /></td>";
        echo "<td><a data-href='edit-student-information?id=" . htmlspecialchars(urlencode($row['student_id'])) . "' title='Click to edit student info' class='next-page'>" . htmlspecialchars($row['student_number']) . "</a></td>";
    } else {
        echo "<td>" . htmlspecialchars($row['student_number']) . "</td>";
    }
    echo "<td>" . htmlspecialchars($full_name) . "</td>";
    echo "<td>" . htmlspecialchars($college_name) . "</td>";
    echo "<td>" . htmlspecialchars($program_name) . "</td>";
    echo "<td>" . htmlspecialchars($age) . "</td>";
    echo "<td>" . htmlspecialchars($row['student_sex']) . "</td>";
    echo "<td>" . htmlspecialchars($socioeconomic_status) . "</td>";
    echo "<td>" . htmlspecialchars($full_address) . "</td>";
    echo "<td>" . htmlspecialchars($living_arrangement) . "</td>";
    echo "<td>" . htmlspecialchars($row['student_work']) . "</td>";
    echo "<td>" . htmlspecialchars($row['student_scholarship']) . "</td>";
    echo "<td>" . htmlspecialchars($language_spoken) . "</td>";
    echo "<td>" . htmlspecialchars($row['student_last_school']) . "</td>";
    echo "</tr>";
}
//echo $query;
?>