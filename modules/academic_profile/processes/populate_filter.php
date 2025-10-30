<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";

//header('Content-Type: application/json');

$level = $_SESSION['level'] ?? '';
$college = $_SESSION['college'] ?? '';
$program = $_SESSION['program'] ?? '';
$params = [];

$collegeOptions = [];      // Flat list of college names
$programOptions = [];      // College → [Programs]
$yearLevelOptions = [];    // Program → [Year Levels]
$subjectOptions = [];      // Program → [Subject Codes]
$mockSubjectOptions = [];  // Program → [Mock Subjects]
$categoryOptions = [];  // Program → [Mock Subjects]
$simulationOptions = [];  // Program → [Mock Subjects]
$genSubOptions = [];  // Program → [Mock Subjects]
$awardOptions = [];  // Awards
$arrangementOptions = [];  // Awards
$languageOptions = [];  // Awards
$collegeForProgramsOptions = []; // Flat list of college names
$socioeconomicOptions = []; // Socioeconomic Statuses
$sectionOptions = [];  // Program → Year Level → Semester → Academic Year → [Sections]

$query = "
    SELECT 
        c.college_id, c.name AS college, 
        p.program_id, p.name AS program, p.years,
        bs.subject_id, bs.subject_name,
        ms.mock_subject_id, ms.mock_subject_name,
        rc.category_id, rc.category_name,
        se.simulation_id, se.simulation_name,
        gs.general_subject_id, gs.general_subject_name
    FROM colleges c
    LEFT JOIN programs p ON p.college_id = c.college_id
    LEFT JOIN board_subjects bs ON bs.program_id = p.program_id
    LEFT JOIN mock_subjects ms ON bs.program_id = ms.program_id
    LEFT JOIN rating_category rc ON bs.program_id = rc.program_id
    LEFT JOIN simulation_exams se ON bs.program_id = se.program_id
    LEFT JOIN general_subjects gs ON bs.program_id = gs.program_id
";

// Program filter logic is also based on session level
if ($level == 2) {
    // Level 2 users are restricted to their assigned program
    $query .= "WHERE p.program_id = ? ";
    $params = [$program];
} else if ($level == 1 && !empty($college) && $college !== 'none') {
    // Level 1 users can filter any program within their college
    $query .= "WHERE c.college_id = ? ";
    $params = [$college];
}

$query .= "ORDER BY c.name, p.name, bs.subject_name";

$stmt = $con->prepare($query);
$stmt->execute($params);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $college_id = $row['college_id'];
    $college_name = strtoupper($row['college']);
    $program_id = $row['program_id'];
    $program_name = strtoupper($row['program']);
    $years = (int) $row['years'];
    $subject_id = $row['subject_id'];
    $subject_name = isset($row['subject_name']) ? strtoupper($row['subject_name']) : null;
    $mock_subject_id = $row['mock_subject_id'];
    $mock_subject_name = isset($row['mock_subject_name']) ? strtoupper($row['mock_subject_name']) : null;
    $category_id = $row['category_id'];
    $category_name = isset($row['category_name']) ? strtoupper($row['category_name']) : null;
    $simulation_id = $row['simulation_id'];
    $simulation_name = isset($row['simulation_name']) ? strtoupper($row['simulation_name']) : null;
    $general_subject_id = $row['general_subject_id'];
    $general_subject_name = isset($row['general_subject_name']) ? strtoupper($row['general_subject_name']) : null;

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

    // Subject Codes options
    if (!isset($subjectOptions[$program_id])) {
        $subjectOptions[$program_id] = [];
    }
    $existingSubjectIds = array_column($subjectOptions[$program_id], 'id');
    if (!in_array($subject_id, $existingSubjectIds) && $subject_id !== null) {
        $subjectOptions[$program_id][] = [
            'id' => $subject_id,
            'name' => $subject_name
        ];
    }

    // Mock Subject Name options
    if (!isset($mockSubjectOptions[$program_id])) {
        $mockSubjectOptions[$program_id] = [];
    }
    $existingMockSubjectIds = array_column($mockSubjectOptions[$program_id], 'id');
    if (!in_array($mock_subject_id, $existingMockSubjectIds) && $mock_subject_id !== null) {
        $mockSubjectOptions[$program_id][] = [
            'id' => $mock_subject_id,
            'name' => $mock_subject_name
        ];
    }

    // Rating Category options
    if (!isset($categoryOptions[$program_id])) {
        $categoryOptions[$program_id] = [];
    }
    $existingCategoryIds = array_column($categoryOptions[$program_id], 'id');
    if (!in_array($category_id, $existingCategoryIds) && $category_id !== null) {
        $categoryOptions[$program_id][] = [
            'id' => $category_id,
            'name' => $category_name
        ];
    }

    // Simulation Exam options
    if (!isset($simulationOptions[$program_id])) {
        $simulationOptions[$program_id] = [];
    }
    $existingSimulationIds = array_column($simulationOptions[$program_id], 'id');
    if (!in_array($simulation_id, $existingSimulationIds) && $simulation_id !== null) {
        $simulationOptions[$program_id][] = [
            'id' => $simulation_id,
            'name' => $simulation_name
        ];
    }

    // Simulation Exam options
    if (!isset($simulationOptions[$program_id])) {
        $simulationOptions[$program_id] = [];
    }
    $existingSimulationIds = array_column($simulationOptions[$program_id], 'id');
    if (!in_array($simulation_id, $existingSimulationIds) && $simulation_id !== null) {
        $simulationOptions[$program_id][] = [
            'id' => $simulation_id,
            'name' => $simulation_name
        ];
    }

    // General Subjects options
    if (!isset($genSubOptions[$program_id])) {
        $genSubOptions[$program_id] = [];
    }
    $existingGenSubIds = array_column($genSubOptions[$program_id], 'id');
    if (!in_array($general_subject_id, $existingGenSubIds) && $general_subject_id !== null) {
        $genSubOptions[$program_id][] = [
            'id' => $general_subject_id,
            'name' => $general_subject_name
        ];
    }
}

$awardStmt = $con->query("
    SELECT award_id, award_name FROM awards
    WHERE is_active = 1
");
while ($row = $awardStmt->fetch(PDO::FETCH_ASSOC)) {
    $award_id = $row['award_id'];
    $award_name = strtoupper($row['award_name']);
    if (!array_key_exists($award_id, $awardOptions)) {
        $awardOptions[$award_id] = [
            'id' => $award_id,
            'name' => $award_name
        ];
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

$collegeStmt = $con->query("
    SELECT college_id, name FROM colleges
    WHERE is_active = 1
");
while ($row = $collegeStmt->fetch(PDO::FETCH_ASSOC)) {
    $college_to_program_id = $row['college_id'];
    $college_to_program_name = strtoupper($row['name']);
    if (!array_key_exists($college_to_program_id, $collegeForProgramsOptions)) {
        $collegeForProgramsOptions[$college_to_program_id] = [
            'id' => $college_to_program_id,
            'name' => $college_to_program_name
        ];
    }
}

$socioeconomicStmt = $con->query("
    SELECT minimum, maximum, status FROM socioeconomic_status
    ORDER BY minimum DESC
");
while ($row = $socioeconomicStmt->fetch(PDO::FETCH_ASSOC)) {
    $status = $row['status'];
    $minimum = $row['minimum'];
    $maximum = $row['maximum'];
    if (!array_key_exists($status, $socioeconomicOptions)) {
        $socioeconomicOptions[$status] = [
            'status' => $status,
            'minimum' => $minimum,
            'maximum' => $maximum
        ];
    }
}

$sectionStmt = $con->query("
    SELECT DISTINCT 
        program_id, 
        year_level, 
        semester, 
        academic_year, 
        section
    FROM student_section
    ORDER BY program_id, year_level, semester, academic_year, section
");

while ($row = $sectionStmt->fetch(PDO::FETCH_ASSOC)) {
    $program_id  = $row['program_id'];
    $year_level  = $row['year_level'];
    $semester    = $row['semester'];
    $acad_year   = $row['academic_year'];
    $section     = strtoupper($row['section']);

    if (!isset($sectionOptions[$program_id])) {
        $sectionOptions[$program_id] = [];
    }
    if (!isset($sectionOptions[$program_id][$year_level])) {
        $sectionOptions[$program_id][$year_level] = [];
    }
    if (!isset($sectionOptions[$program_id][$year_level][$semester])) {
        $sectionOptions[$program_id][$year_level][$semester] = [];
    }
    if (!isset($sectionOptions[$program_id][$year_level][$semester][$acad_year])) {
        $sectionOptions[$program_id][$year_level][$semester][$acad_year] = [];
    }

    // Prevent duplicates
    if (!in_array($section, $sectionOptions[$program_id][$year_level][$semester][$acad_year])) {
        $sectionOptions[$program_id][$year_level][$semester][$acad_year][] = $section;
    }
}

// 🔚 Output all four sets
echo json_encode([
    "collegeOptions" => array_values($collegeOptions),
    "programOptions" => $programOptions,
    "yearLevelOptions" => $yearLevelOptions,
    "sectionOptions" => $sectionOptions,
    "subjectOptions" => $subjectOptions,
    "mockSubjectOptions" => $mockSubjectOptions,
    "categoryOptions" => $categoryOptions,
    "simulationOptions" => $simulationOptions,
    "genSubOptions" => $genSubOptions,
    "awardOptions" => array_values($awardOptions),
    "arrangementOptions" => array_values($arrangementOptions),
    "languageOptions" => array_values($languageOptions),
    "collegeForProgramsOptions" => array_values($collegeForProgramsOptions),
    "socioeconomicOptions" => array_values($socioeconomicOptions)
]);

// Only output JSON if accessed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    echo $jsonOptions;
}
?>