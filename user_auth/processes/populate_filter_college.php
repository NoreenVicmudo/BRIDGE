<?php
include '../../core/j_conn.php';

header('Content-Type: application/json');

$collegeOptions = [];  // [{id, name}]
$programOptions = [];  // college_id => [{id, name}]

// Fetch colleges
$stmt = $con->query("SELECT college_id, name FROM colleges ORDER BY name");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $collegeOptions[] = [
        'id' => $row['college_id'],
        'name' => strtoupper($row['name'])
    ];
}

// Fetch programs
$stmt = $con->query("SELECT program_id, name, college_id FROM programs ORDER BY college_id, name");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $college_id = $row['college_id'];
    if (!isset($programOptions[$college_id])) {
        $programOptions[$college_id] = [];
    }
    $programOptions[$college_id][] = [
        'id' => $row['program_id'],
        'name' => strtoupper($row['name'])
    ];
}

// Hardcode positions (mapped from user_level)
$positionOptions = [
    ['id' => 1, 'name' => 'Dean'],
    ['id' => 2, 'name' => 'Administrative Assistant'],
    ['id' => 3, 'name' => 'Program Head']
];

// Output JSON
echo json_encode([
    "collegeOptions" => $collegeOptions,
    "programOptions" => $programOptions,
    "positionOptions" => $positionOptions
]);