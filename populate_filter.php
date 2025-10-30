<?php
include 'core/j_conn.php';
session_start();
header('Content-Type: application/json');

$level = $_SESSION['level'] ?? '';
$college = $_SESSION['college'] ?? '';
$program = $_SESSION['program'] ?? '';
$params = [];

// Check if this is called from additional_entry module
$isAdditionalEntryModule = isset($_GET['module']) && $_GET['module'] === 'additional_entry';

// Check if dean's college is hidden (for level 1 users)
$deanCollegeHidden = false;
if ($level == 1 && !empty($college)) {
    $checkCollegeStmt = $con->prepare("SELECT is_active FROM colleges WHERE college_id = ?");
    $checkCollegeStmt->execute([$college]);
    $collegeStatus = $checkCollegeStmt->fetchColumn();
    $deanCollegeHidden = ($collegeStatus == 0); // 0 = hidden
}

// For admins, we need to show all colleges (including hidden ones) but hide programs from hidden colleges
// BUT only for additional_entry module - other modules should only show active colleges
$showAllCollegesForAdmin = ($level == 0 && $isAdditionalEntryModule);

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
$batchOptions = [];  // Program → Year Level → Semester → Academic Year → [Sections]
$batchYearOptions = [];  // Program → Year Level → Semester → Academic Year → [Sections]

$query = "
    SELECT 
        c.college_id, c.name AS college, c.is_active AS college_is_active,
        p.program_id, p.name AS program, p.years, p.is_active AS program_is_active,
        bs.subject_id, bs.subject_name,
        ms.mock_subject_id, ms.mock_subject_name,
        rc.category_id, rc.category_name,
        se.simulation_id, se.simulation_name,
        gs.general_subject_id, gs.general_subject_name
    FROM colleges c
    LEFT JOIN programs p ON p.college_id = c.college_id
    LEFT JOIN board_subjects bs ON bs.program_id = p.program_id
    LEFT JOIN mock_subjects ms ON ms.program_id = p.program_id
    LEFT JOIN rating_category rc ON rc.program_id = p.program_id
    LEFT JOIN simulation_exams se ON se.program_id = p.program_id
    LEFT JOIN general_subjects gs ON gs.program_id = p.program_id
";

// Program filter logic is also based on session level
if ($level == 3) {
    // Level 3 users are restricted to their assigned program
    $query .= "WHERE p.program_id = ? ";
    $params = [$program];
} else if (($level == 1 || $level == 2) && !empty($college) && $college !== 'none') {
    // Level 1/2 users can filter any program within their college
    $query .= "WHERE c.college_id = ? ";
    $params = [$college];
}

// For admins in additional_entry module, show all colleges but only active programs
// For other modules (even admins), show only active colleges and programs
if ($showAllCollegesForAdmin) {
    $query .= "AND p.is_active = 1 ORDER BY c.name, p.name, bs.subject_name";
} else {
    $query .= "AND p.is_active = 1 AND c.is_active = 1 ORDER BY c.name, p.name, bs.subject_name";
}

$stmt = $con->prepare($query);
$stmt->execute($params);

// If dean's college is hidden, redirect to access restricted page
if ($deanCollegeHidden) {
    header("Location: public/access_restricted.php?reason=college_hidden");
    exit;
}

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $college_id = $row['college_id'];
    $college_name = strtoupper($row['college']);
    $college_is_active = $row['college_is_active'];
    $program_id = $row['program_id'];
    $program_name = strtoupper($row['program']);
    $program_is_active = $row['program_is_active'];
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

    // College options - show all colleges for admins in additional_entry module, only active for others
    if (!array_key_exists($college_id, $collegeOptions)) {
        if ($showAllCollegesForAdmin || $college_is_active == 1) {
            $collegeOptions[$college_id] = [
                'id' => $college_id,
                'name' => $college_name
            ];
        }
    }

    // Program options - only show programs from active colleges (even for admins)
    if (!isset($programOptions[$college_id])) {
        $programOptions[$college_id] = [];
    }
    // FIX: Use in_array instead of array_key_exists
    $existingProgramIds = array_column($programOptions[$college_id], 'id');
    if (!in_array($program_id, $existingProgramIds) && $program_id !== null && $program_name !== null && $college_is_active == 1) {
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

// Check if awards table exists
$tableCheck = $con->query("SHOW TABLES LIKE 'awards'");
if ($tableCheck->rowCount() > 0) {
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
}

// Check if living_arrangement table exists
$tableCheck = $con->query("SHOW TABLES LIKE 'living_arrangement'");
if ($tableCheck->rowCount() > 0) {
    $arrangementStmt = $con->query("
        SELECT arrangement_id, arrangement_name, is_active FROM living_arrangement
        ORDER BY is_active DESC, arrangement_name ASC
    ");
    while ($row = $arrangementStmt->fetch(PDO::FETCH_ASSOC)) {
        $arrangement_id = $row['arrangement_id'];
        $arrangement_name = strtoupper($row['arrangement_name']);
        $is_active = $row['is_active'];
        if (!array_key_exists($arrangement_id, $arrangementOptions)) {
            $arrangementOptions[$arrangement_id] = [
                'id' => $arrangement_id,
                'name' => $arrangement_name,
                'is_active' => $is_active
            ];
        }
    }
}

// Check if language_spoken table exists
$tableCheck = $con->query("SHOW TABLES LIKE 'language_spoken'");
if ($tableCheck->rowCount() > 0) {
    $langStmt = $con->query("
        SELECT language_id, language_name, is_active FROM language_spoken
        ORDER BY is_active DESC, language_name ASC
    ");
    while ($row = $langStmt->fetch(PDO::FETCH_ASSOC)) {
        $language_id = $row['language_id'];
        $language_name = strtoupper($row['language_name']);
        $is_active = $row['is_active'];
        if (!array_key_exists($language_id, $languageOptions)) {
            $languageOptions[$language_id] = [
                'id' => $language_id,
                'name' => $language_name,
                'is_active' => $is_active
            ];
        }
    }
}

// For admins in additional_entry module, show all colleges; for others, show only active colleges
if ($showAllCollegesForAdmin) {
    $collegeStmt = $con->query("
        SELECT college_id, name, is_active FROM colleges
        ORDER BY is_active DESC, name ASC
    ");
} else {
    $collegeStmt = $con->query("
        SELECT college_id, name FROM colleges
        WHERE is_active = 1
    ");
}

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

// Check if socioeconomic_status table exists
$tableCheck = $con->query("SHOW TABLES LIKE 'socioeconomic_status'");
if ($tableCheck->rowCount() > 0) {
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

$batchStmt = $con->query("
    SELECT DISTINCT 
        program_id, 
        year,
        batch_number
    FROM board_batch
    ORDER BY program_id, year, batch_number
");

while ($row = $batchStmt->fetch(PDO::FETCH_ASSOC)) {
    $program_id     = $row['program_id'];
    $year           = $row['year'];
    $batch_number   = $row['batch_number'];

    if (!isset($batchOptions[$program_id])) {
        $batchOptions[$program_id] = [];
    }
    if (!isset($batchOptions[$program_id][$year])) {
        $batchOptions[$program_id][$year] = [];
    }

    // Prevent duplicates
    if (!in_array($batch_number, $batchOptions[$program_id][$year])) {
        $batchOptions[$program_id][$year][] = $batch_number;
    }
}

$batchStmt = $con->query("
    SELECT DISTINCT 
        program_id, 
        year,
        batch_number
    FROM board_batch
    ORDER BY program_id, year, batch_number
");

while ($row = $batchStmt->fetch(PDO::FETCH_ASSOC)) {
    $program_id     = $row['program_id'];
    $year           = $row['year'];
    $batch_number   = $row['batch_number'];

    if (!isset($batchOptions[$program_id])) {
        $batchOptions[$program_id] = [];
    }
    if (!isset($batchOptions[$program_id][$year])) {
        $batchOptions[$program_id][$year] = [];
    }

    // Prevent duplicates
    if (!in_array($batch_number, $batchOptions[$program_id][$year])) {
        $batchOptions[$program_id][$year][] = $batch_number;
    }
}

$batchYearStmt = $con->query("
    SELECT DISTINCT 
        program_id, 
        year
    FROM board_batch
    ORDER BY program_id, year
");

while ($row = $batchYearStmt->fetch(PDO::FETCH_ASSOC)) {
    $program_id     = $row['program_id'];
    $year           = $row['year'];

    if (!isset($batchYearOptions[$program_id])) {
        $batchYearOptions[$program_id] = [];
    }

    // No need for in_array check due to SQL's DISTINCT clause
    $batchYearOptions[$program_id][] = $year;
}

// 🔚 Output all four sets
echo json_encode([
    "collegeOptions" => array_values($collegeOptions),
    "programOptions" => $programOptions,
    "yearLevelOptions" => $yearLevelOptions,
    "sectionOptions" => $sectionOptions,
    "batchOptions" => $batchOptions,
    "batchYearOptions" => $batchYearOptions,
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

//echo $jsonOptions;
?>

