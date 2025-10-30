<?php 
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";

header('Content-Type: application/json');

$collegeOptions = [];  // [{id, name}]
$programOptions = [];  // college_id => [{id, name}]
$yearLevelOptions = []; // program_id => [{id, name}]

$query = "
    SELECT 
        c.college_id, c.name AS college, 
        p.program_id, p.name AS program, 
        y.year_level_id, y.name AS year_level
    FROM colleges c
    JOIN programs p ON p.college_id = c.college_id
    JOIN year_level y ON y.program_id = p.program_id
    ORDER BY y.year_level_id, c.college_id, p.program_id
";

$stmt = $con->query($query);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $college_id = $row['college_id'];
    $college_name = strtoupper($row['college']);
    $program_id = $row['program_id'];
    $program_name = strtoupper($row['program']);
    $year_id = $row['year_level_id'];
    $year_name = strtoupper($row['year_level']);

    // College options
    if (!array_key_exists($college_id, $collegeOptions)) {
        $collegeOptions[$college_id] = [
            'id' => $college_id,
            'name' => $college_name
        ];
    }

    // Program options
    if (!isset($programOptions[$college_id])) {
        $programOptions[$college_id] = [];
    }
    // FIX: Use in_array instead of array_key_exists
    $existingProgramIds = array_column($programOptions[$college_id], 'id');
    if (!in_array($program_id, $existingProgramIds)) {
        $programOptions[$college_id][] = [
            'id' => $program_id,
            'name' => $program_name
        ];
    }

    // Year level options
    if (!isset($yearLevelOptions[$program_id])) {
        $yearLevelOptions[$program_id] = [];
    }
    $existingYearIds = array_column($yearLevelOptions[$program_id], 'id');
    if (!in_array($year_id, $existingYearIds)) {
        $yearLevelOptions[$program_id][] = [
            'id' => $year_id,
            'name' => $year_name
        ];
    }
}

// 🔚 Output all three sets
$jsonOptions = json_encode([
    "collegeOptions" => array_values($collegeOptions),
    "programOptions" => $programOptions,
    "yearLevelOptions" => $yearLevelOptions
]);

?>