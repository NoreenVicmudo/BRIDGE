<?php
session_start();
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php"; // add this so $con is availabl

$query = "
  SELECT u.user_id, u.user_username,
         CONCAT(u.user_lastname, ', ', u.user_firstname) AS full_name,
         u.user_email, c.name AS college_name, p.name AS program_name,
         u.user_level, u.date_created
  FROM user_account u
  LEFT JOIN colleges c ON u.user_college = c.college_id
  LEFT JOIN programs p ON u.user_program = p.program_id
  WHERE u.is_active = 1
    AND u.user_level != 0
";

$params = [];

if ($_SESSION['level'] == 2) {
  $query .= " AND u.user_college = ? AND u.user_program = ?";
  $params[] = $_SESSION['college'];
  $params[] = $_SESSION['program'];
} elseif ($_SESSION['level'] == 1) {
  $query .= " AND u.user_college = ?";
  $params[] = $_SESSION['college'];
}

$query .= " ORDER BY u.user_level ASC"; // move ORDER BY here


    $stmt = $con->prepare($query);
    $stmt->execute($params);


while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  echo "<tr>";

    echo "<td class='select-column hidden'><input type='checkbox' class='row-select' /></td>";
    // Username clickable link                         Can use int if ever for extra safety
    echo "<td><a href='edit-user?id=" . htmlspecialchars(urlencode($row['user_id'])) . "' class='next-page'>" . htmlspecialchars($row['user_username']) . "</a></td>";
    echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['user_email']) . "</td>";
    $collegeDisplay = (empty($row['college_name']) || $row['college_name'] === '0') 
        ? "<span class='center-dash'>-</span>" 
        : htmlspecialchars($row['college_name']);
    echo "<td>" . $collegeDisplay . "</td>";
/*
    echo "<td>" . htmlspecialchars($row['college_name']) . "</td>";*/

    // Map user_level to position
    $levels = [
        0 => 'Admin',
        1 => 'Dean',
        2 => 'Administrative Assistant',
        3 => 'Program Head'
    ];
    
    $levelName = $levels[$row['user_level']] ?? $row['user_level'];
    echo "<td>" . htmlspecialchars($levelName) . "</td>";
    // Program name
    $programDisplay = (empty($row['program_name']) || $row['program_name'] === '0') 
        ? "<span class='center-dash'>-</span>" 
        : htmlspecialchars($row['program_name']);
    echo "<td>" . $programDisplay . "</td>";
    echo "<td>" . htmlspecialchars($row['date_created']) . "</td>";
  echo "</tr>";
}
?>
