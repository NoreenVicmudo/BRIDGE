<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";
include 'populate_filter.php';

// Decode options outside the conditional block
$decodedOptions = json_decode($jsonOptions, true);

// Set up filter variables from POST data
$college       = $_SESSION['college'] ?? '';
$program       = $_SESSION['program'] ?? '';


$query = "SELECT
    si.student_id,
    si.student_number,
    si.student_fname,
    si.student_mname,
    si.student_lname,
    si.student_suffix,
    si.student_college,
    si.student_program,
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
WHERE
    si.is_active = 1";

$params = [];
// College filter logic is now based on session level
if ($_SESSION['level'] == 3) {
    // Level 2 users are restricted to their assigned college
    $query .= " AND si.student_college = ?";
    $params[] = $_SESSION['college'];
} else if (($_SESSION['level'] == 1 || $_SESSION['level'] === 2) && !empty($college) && $college !== 'none') {
    // Level 1 users can filter within their assigned college
    $query .= " AND si.student_college = ?";
    $params[] = $college;
} else if ($_SESSION['level'] == 0 && !empty($college) && $college !== 'none') {

}

// Program filter logic is also based on session level
if ($_SESSION['level'] == 3) {
    // Level 2 users are restricted to their assigned program
    $query .= " AND si.student_program = ?";
    $params[] = $_SESSION['program'];
} else if (($_SESSION['level'] == 1 || $_SESSION['level'] === 2) && !empty($program) && $program !== 'none') {
    // Level 1 users can filter any program within their college
    $query .= " AND si.student_program = ?";
    $params[] = $program;
} else if ($_SESSION['level'] == 0 && !empty($program) && $program !== 'none') {

}
    
$query .= " GROUP BY student_number ORDER BY student_program ASC, student_number ASC";

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
        echo "<td><a data-href='edit-student-masterlist?id=" . htmlspecialchars(urlencode($row['student_id'])) . "' title='Click to edit student info' class='next-page'>" . htmlspecialchars($row['student_number']) . "</a></td>";
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
?>