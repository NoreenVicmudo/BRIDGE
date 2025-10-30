<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";

header('Content-Type: application/json');

$collegeOptions = [];  // [{id, name}]
$programOptions = [];  // college_id => [{id, name}]

// Fetch colleges
$stmt = $con->query("SELECT college_id, name FROM colleges WHERE is_active = 1 ORDER BY name");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $collegeOptions[] = [
        'id' => $row['college_id'],
        'name' => strtoupper($row['name'])
    ];
}

// Fetch programs
$stmt = $con->query("SELECT program_id, name, college_id FROM programs WHERE is_active = 1 ORDER BY college_id, name");
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

// Base positions (always available)
$basePositions = [
    ['id' => 1, 'name' => 'Dean'],
    ['id' => 2, 'name' => 'Administrative Assistant']
];

// Program Head (conditionally used in frontend)
$programHead = ['id' => 3, 'name' => 'Program Head'];

// Output JSON
echo json_encode([
    "collegeOptions" => $collegeOptions,
    "programOptions" => $programOptions,
    // send everything to JS, but frontend will decide when to show Program Head
    "positionOptions" => array_merge($basePositions, [$programHead])
]);
