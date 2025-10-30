<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";

//header('Content-Type: application/json');

$collegeOptions = [];  // [{id, name}]
$programOptions = [];  // college_id => [{id, name}]
$yearLevelOptions = []; // program_id => [{id, name}]
$arrangementOptions = [];  // Awards
$languageOptions = [];  // Awards

$query = "
    SELECT 
        c.college_id, c.name AS college, 
        p.program_id, p.name AS program, p.years
    FROM colleges c
    LEFT JOIN programs p ON p.college_id = c.college_id
    ORDER BY c.college_id, p.program_id
";

$stmt = $con->query($query);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $college_id = $row['college_id'];
    $college_name = strtoupper($row['college']);
    $program_id = $row['program_id'];
    $program_name = strtoupper($row['program']);
    $years = (int) $row['years'];

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

    // Year level options (dynamic)
    if (!isset($yearLevelOptions[$program_id])) {
        $yearLevelOptions[$program_id] = [];
        for ($i = 1; $i <= $years; $i++) {
            $suffix = match($i) {
                1 => 'ST',
                2 => 'ND',
                3 => 'RD',
                default => 'TH'
            };
            $yearLevelOptions[$program_id][] = [
                'id' => $i,
                'name' => strtoupper("{$i}{$suffix} YEAR")
            ];
        }
    }
}

$arrangementStmt = $con->query("
    SELECT arrangement_id, arrangement_name FROM living_arrangement
    WHERE is_active = 1
");
while ($row = $arrangementStmt->fetch(PDO::FETCH_ASSOC)) {
    $arrangement_id = $row['arrangement_id'];
    $arrangement_name = strtoupper($row['arrangement_name']);
    if (!array_key_exists($arrangement_id, $arrangementOptions)) {
        $arrangementOptions[$arrangement_id] = [
            'id' => $arrangement_id,
            'name' => $arrangement_name
        ];
    }
}

$langStmt = $con->query("
    SELECT language_id, language_name FROM language_spoken
    WHERE is_active = 1
");
while ($row = $langStmt->fetch(PDO::FETCH_ASSOC)) {
    $language_id = $row['language_id'];
    $language_name = strtoupper($row['language_name']);
    if (!array_key_exists($language_id, $languageOptions)) {
        $languageOptions[$language_id] = [
            'id' => $language_id,
            'name' => $language_name
        ];
    }
}

// 🔚 Output all three sets
// Prepare JSON options
$jsonOptions = json_encode([
    "collegeOptions" => array_values($collegeOptions),
    "programOptions" => $programOptions,
    "yearLevelOptions" => $yearLevelOptions,
    "arrangementOptions" => $arrangementOptions,
    "languageOptions" => $languageOptions
]);

// Only output JSON if accessed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    echo $jsonOptions;
}
?>