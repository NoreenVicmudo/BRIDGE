<?php
require_once __DIR__ . "/../../../core/config.php";
require_once PROJECT_PATH . "/j_conn.php";
require_once PROJECT_PATH . "/auth.php";
require_once PROJECT_PATH . "/access_check.php";
require_once PROJECT_PATH . "/functions.php";

// Check if user's college/program is hidden
checkUserAccess($con);

// Function to get college logo path based on college ID
function getCollegeLogoPath($collegeId, $con) {
    if (empty($collegeId)) {
        return null;
    }
    
    // First, try to get logo from database
    try {
        $stmt = $con->prepare("SELECT logo_path FROM colleges WHERE college_id = ? AND is_active = 1");
        $stmt->execute([$collegeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If logo path exists in database and file exists, return the endpoint URL
        // PROJECT_PATH is in core/, so we need the parent directory (bridge) for file path
        if ($row && !empty($row['logo_path']) && file_exists(dirname(PROJECT_PATH) . '/' . $row['logo_path'])) {
            return 'modules/generate_report/processes/get_college_logo.php?college_id=' . htmlspecialchars($collegeId, ENT_QUOTES, 'UTF-8');
        }
    } catch (Exception $e) {
        // If database query fails, fallback to static array
        error_log("Error fetching college logo from database: " . $e->getMessage());
    }
    
    // Fallback to static array for backward compatibility
    $collegeLogos = [
        '1' => '1_cmt.png',  // med tech
        '2' => '2_cn.png',   // nursing
        '3' => '3_cd.png',   // dentistry
        '4' => '4_sbm.png',  // school of business and management
        '5' => '5_co.png',   // optometry
        '6' => '6_cas.png',  // college of arts and sciences
        '7' => '7_cp.png',   // pharmacy
        '8' => '8_ie.png',   // institute of education
        '9' => '9_cpt.png',  // physical therapy
        '10' => '10_cm.png'  // medicine
    ];
    
    if (isset($collegeLogos[$collegeId])) {
        return 'assets/img/' . $collegeLogos[$collegeId];
    }
    
    return null; // No logo if college not found
}

// Function to get college footer data (color, name, email)
function getCollegeFooterData($collegeId, $con) {
    // Default fallback values (matching database defaults)
    $defaults = [
        'color' => '#5c297c',
        'name' => 'College of Arts and Sciences',
        'email' => 'hello@mcu.edu.ph'
    ];
    
    if (empty($collegeId)) {
        return $defaults;
    }
    
    // Try to get data from database
    try {
        $stmt = $con->prepare("SELECT name, brand_color, college_email FROM colleges WHERE college_id = ? AND is_active = 1");
        $stmt->execute([$collegeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            return [
                'color' => !empty($row['brand_color']) ? $row['brand_color'] : $defaults['color'],
                'name' => !empty($row['name']) ? $row['name'] : $defaults['name'],
                'email' => !empty($row['college_email']) ? $row['college_email'] : $defaults['email']
            ];
        }
    } catch (Exception $e) {
        // If database query fails, fallback to static array
        error_log("Error fetching college footer data from database: " . $e->getMessage());
    }
    
    // Fallback to static array for backward compatibility
    $collegeData = [
        '1' => [
            'color' => '#930147',
            'name' => 'College of Medical Technology',
            'email' => 'mcucollegeofmedtech@gmail.com'
        ],
        '2' => [
            'color' => '#f0a71f',
            'name' => 'College of Nursing',
            'email' => 'mcunursing@gmail.com'
        ],
        '3' => [
            'color' => '#ed145b',
            'name' => 'College of Dentistry',
            'email' => 'dentistry@mcu.edu.ph'
        ],
        '4' => [
            'color' => '#fdc131',
            'name' => 'School of Business and Management',
            'email' => 'sbm@mcu.edu.ph'
        ],
        '5' => [
            'color' => '#d07b61',
            'name' => 'College of Optometry',
            'email' => 'coo@mcu.edu.ph'
        ],
        '6' => [
            'color' => '#417784',
            'name' => 'College of Arts and Sciences',
            'email' => 'cas@mcu.edu.ph'
        ],
        '7' => [
            'color' => '#b7c69c',
            'name' => 'College of Pharmacy',
            'email' => 'cph@mcu.edu.ph'
        ],
        '8' => [
            'color' => '#b3ccc8',
            'name' => 'Institute of Education',
            'email' => 'ioe@mcu.edu.ph'
        ],
        '9' => [
            'color' => '#d69893',
            'name' => 'College of Physical Therapy',
            'email' => 'cpt@mcu.edu.ph'
        ],
        '10' => [
            'color' => '#76a13d',
            'name' => 'College of Medicine',
            'email' => 'medicine@mcu.edu.ph'
        ]
    ];
    
    if (isset($collegeData[$collegeId])) {
        return $collegeData[$collegeId];
    }
    
    // Final fallback to defaults
    return $defaults;
}

// Get college logo path - use filter_college if available, otherwise use college from session
$collegeLogoPath = getCollegeLogoPath($_SESSION['filter_college'] ?? $_SESSION['college'] ?? '', $con);

// Get college footer data
$collegeFooterData = getCollegeFooterData($_SESSION['filter_college'] ?? $_SESSION['college'] ?? '', $con);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>BRIDGE</title>
        <!-- Correct jQuery CDN -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- Correct DataTables JS CDN -->
        <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
        <!-- Correct DataTables CSS CDN 
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" />-->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <!-- Google Fonts - Libre Baskerville for footer -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="modules/generate_report/css/generate_report.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.js"></script>
        <!-- Bootstrap 5.3.3 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
        <link rel="shortcut icon" href="Pictures/favicon.ico" type="image/x-icon">
    </head>
    <body>
        <header class="top-header">
            <a href="home">
                <img src="Pictures/white_logo.png" alt="MCU Logo" class="logo img-fluid">
            </a>
        </header>

        <h2 class="headings">Generate Report</h2>

        <div class="button-container">
            <a href="generate-report-filter" class="control return-btn back-page" title="Return to Filter Students">
                <i class="bi bi-arrow-return-left"></i>Return
            </a>
            <button onclick="openStatisticalToolModal()" class="primary-button select-tool-btn" title="Compare Students">
                <i class="bi bi-bar-chart-line"></i>Select Statistical Tool</button>
            <button id="printBtn" class="primary-button print-btn" title="Print Report">
                <i class="bi bi-printer"></i> Print
            </button>
            <!--
            <button id="exportPdfBtn" class="primary-button export-btn" title="Save report as PDF">
                <i class="bi bi-file-earmark-pdf"></i> Export as PDF
            </button>
            <button id="exportWordBtn" class="primary-button export-btn" title="Export as Word Document">
                <i class="bi bi-file-earmark-word"></i> Export as Word
            </button>         -->
            <div class="export-dropdown">
                <button class="primary-button export-btn" id="exportDropdownBtn" type="button">
                    <i class="bi bi-download"></i> Export
                    <i class="bi bi-caret-down"></i>
                </button>
                <div class="export-menu" id="exportMenu">
                    <button class="export-option" id="exportPdfDropdownBtn">
                        <i class="bi bi-file-earmark-pdf"></i> Export as PDF
                    </button>
                    <button class="export-option" id="exportWordDropdownBtn">
                    <i class="bi bi-file-earmark-word"></i> Export as Word
                    </button>
                </div>
            </div>
        </div>



        <!-- Scroll Notification Banner -->
        <div id="scrollNotification" class="scroll-notification">
            <i class="bi bi-arrow-left-right icon"></i>
            <span>Swipe right or scroll horizontally to view full report</span>
            <button class="close-btn" onclick="hideScrollNotification()">&times;</button>
        </div>

        <div class="report-wrapper hidden" id="reportWrapper">
            <div id="report">
                
                <header class="report-header">
                    <div class="header-logos">
                        <!-- Logo on the left top -->
                        <img src="assets/img/formal.png" alt="Formal Logo" class="report-logo">
                        
                        <!-- College logo on the right top (dynamic based on selected college) - Only show if not admin -->
                        <?php if (isset($_SESSION['level']) && $_SESSION['level'] != 0 && $collegeLogoPath): ?>
                            <img src="<?php echo htmlspecialchars($collegeLogoPath); ?>" alt="College Logo" class="college-logo">
                        <?php endif; ?>
                    </div>
                    
                    <!-- Keep generatedAt accessible for JavaScript -->
                    <span id="generatedAt" style="display: none;"></span>
                </header>
                <section class="section">
        
                    <!-- Report Title and Meta Information -->
                    <div id="reportMetaInfo" class="hidden">
                        <div class="title">BRIDGE Statistical Report</div>
                        <div class="meta">Time Created: <span id="generatedAtDisplay"></span></div>
                        <div class="meta">Generated by: 
                            <?php 
                                if (isset($_SESSION['firstname'], $_SESSION['lastname'])) {
                                    echo $_SESSION['firstname'] . ' ' . $_SESSION['lastname'];
                                } else {
                                    echo 'User'; 
                                }
                            ?>
                        </div>
                    </div>
                    
                    <!-- Report Summary -->
                    <div id="reportSummary"></div>
                    
                    <div id="generatedReport" class="hidden generated-report">
                        <h2>Generated Report:</h2>
                        <div id="generatedReportSummary" class="report-summary"></div>
                        <div id="reportChart" class="chart"></div>
                    </div>
                </section>

                <footer class="report-footer">
                    <div class="footer-container">
                        <?php if (isset($_SESSION['level']) && $_SESSION['level'] != 0): ?>
                            <!-- College-specific footer (left side) - Only show if not admin -->
                            <div class="footer-left" style="background-color: <?php echo htmlspecialchars($collegeFooterData['color']); ?>;">
                                <div class="footer-university">Manila Central University</div>
                                <div class="footer-college"><?php echo htmlspecialchars($collegeFooterData['name']); ?></div>
                                <div class="footer-email"><?php echo htmlspecialchars($collegeFooterData['email']); ?></div>
                            </div>
                        <?php endif; ?>
                        <!-- Right footer - Full width for admin, normal width for others -->
                        <div class="footer-right" <?php if (isset($_SESSION['level']) && $_SESSION['level'] == 0): ?>style="flex: 1 1 100%; width: 100%;"<?php endif; ?>>
                            <div class="footer-right-content">
                                <div class="footer-address">EDSA, Caloocan City 1400</div>
                                <div class="footer-phone">+63 2 8364-10-71 to 78</div>
                                <div class="footer-email-right">hello@mcu.edu.ph</div>
                            </div>
                            <div class="footer-website">www.mcu.edu.ph</div>
                            <div class="footer-qr">
                                <img src="assets/img/qr.png" alt="QR Code" class="qr-code">
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </div>
        



<!--
        <div class="main-wrapper">

            <header>
                <a href="../../public/main_page.php">
                <img src="../../Pictures/white_logo.png" alt="MCU Logo" class="logo img-fluid">
                </a>
            </header>

            

            
            <div class="content">
                <div class="container-wrapper">
                    <div class="container">

                        <div class="button-container">
                            <button onclick="openStatisticalToolModal()" class="primary-button">Select Statistical Tool</button>
                            <button onclick="printReportContent()" class="primary-button print-btn" disabled>
                                <i class="bi bi-printer"></i> Print 
                            </button>
                            <button onclick="exportReport('pdf')" class="primary-button export-btn" disabled>
                                <i class="bi bi-file-earmark-pdf"></i> Export as PDF
                            </button>
                    
                            <div class="export-dropdown">
                                <button class="primary-button export-btn" id="exportDropdownBtn" type="button">
                                    <i class="bi bi-download"></i> Export
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                                <div class="export-menu" id="exportMenu">
                                    <button class="export-option" onclick="exportReport('pdf')">
                                    <i class="bi bi-file-earmark-pdf"></i> Export as PDF
                                    </button>
                                    <button class="export-option" onclick="exportReport('word')">
                                    <i class="bi bi-file-earmark-word"></i> Export as Word
                                    </button>
                                </div>
                            </div>
                        </div>
                        

           
                        <div id="reportSummary" class="result-box hidden"></div><br>

                  
                        <div id="generatedReport" class="hidden generated-report">
                            <h3>Generated Report:</h3>
                            <div id="generatedReportSummary" class="report-summary"></div>
                            <div id="reportChart" class="chart"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>-->

        <!-- Statistical Tool Selection Modal -->
        <div id="statisticalToolModal" class="modal">
            <div class="modal-content">
                <h2 class="modal-header">Statistical Analysis Configuration</h2>
                <div class="form-container">
                    <!-- Step 1: Statistical Tool Selection -->
                    <div class="form-group">
                        <label for="statTool">Statistical Tool:</label>
                        <select id="statTool" onchange="handleStatToolChange()">
                            <option value="" disabled selected>Select</option>
                            <option value="descriptive">Descriptive</option>
                            <option value="inferential">Inferential</option>
                        </select>
                    </div>
                    <div  id="fieldInferential" class="hidden">
                        <div class="form-group">
                            <label for="statToolInferential">Inferential:</label>
                            <select id="statToolInferential" onchange="handleStatToolChange()">
                                <option value="" disabled selected>Select</option>
                                <option value="regression">Regression</option>
                                <option value="pearson">Pearson R</option>
                                <option value="chiSquareGOF">Chi Square - Goodness of Fit</option>
                                <option value="chiSquareTOI">Chi Square - Test of Independence</option>
                                <option value="tTestIND">Independent T Test</option>
                                <option value="tTestDEP">Dependent T Test</option>
                            </select>
                        </div>
                    </div>

                    <div id="fieldSelectionDescriptive" class="hidden">
                        <!-- Field 0 Selection -->
                        <div class="field-group">
                            <h4>Variable</h4>
                            <div class="form-group">
                                <label for="field0Category">Category:</label>
                                <select id="field0Category" onchange="handleField0CategoryChange()">
                                    <option value="" disabled selected>Select</option>
                                    <option value="studentInfo">Student Information</option>
                                    <option value="academicProfile">Academic Profile</option>
                                    <option value="programMetrics">Program Metrics</option>
                                </select>
                            </div>

                            <!-- Student Info Fields -->
                            <div id="field0StudentInfo" class="form-group hidden">
                                <label for="field0StudentField">Field:</label>
                                <select id="field0StudentField" onchange="handleField0StudentInfoMetricChange()">
                                    <option value="" disabled selected>Select</option>
                                    <option value="age">Age</option>
                                    <option value="socioeconomicStatus">Socioeconomic Status</option>
                                </select>
                            </div>

                            <!-- Academic Profile Fields -->
                            <div id="field0AcademicProfile" class="form-group hidden">
                                <label for="field0AcademicMetric">Metric:</label>
                                <select id="field0AcademicMetric" onchange="handleField0AcademicMetricChange()">
                                    <option value="" disabled selected>Select</option>
                                    <option value="GWA">GWA</option>
                                    <option value="BoardGrades">Grades in Board Subjects</option>
                                    <option value="Retakes">Back Subjects/Retakes</option>
                                    <option value="PerformanceRating">Performance Rating</option>
                                    <option value="SimExam">Simulation Exam Results</option>
                                    <option value="Attendance">Attendance in Review Classes</option>
                                    <option value="Recognition">Academic Recognition</option>
                                </select>
                            </div>

                            <!-- Program Metrics Fields -->
                            <div id="field0ProgramMetrics" class="form-group hidden">
                                <label for="field0ProgramMetric">Metric:</label>
                                <select id="field0ProgramMetric" onchange="handleField0ProgramMetricChange()">
                                    <option value="" disabled selected>Select</option>
                                    <option value="MockScores">Mock Board Scores</option>
                                    <option value="TakeAttempt">Number of Exam Attempts</option>
                                </select>
                            </div>

                            <!-- Dynamic sub-combobox -->
                            <div id="subMetricGroup" class="form-group hidden">
                                <label for="subMetricSelect" id="subMetricLabel"></label>
                                <select name="subMetricSelect" id="subMetricSelect" onchange="handleField0SubMetricChange()">
                                    <option value="" disabled selected>Select</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Field Selection -->
                <div id="fieldSelection" class="hidden">
                    <h3>Variables to Compare</h3>
                        
                        <!-- Field 1 Selection -->
                        <div id="fieldSelection1" class="field-group hidden">
                            <h4>Variable 1</h4>
                            <div class="form-group">
                                <label for="field1Category">Category:</label>
                                <select id="field1Category" onchange="handleField1CategoryChange()">
                                    <option value="" disabled selected>Select</option>
                                    <option value="studentInfo">Student Information</option>
                                    <option value="academicProfile">Academic Profile</option>
                                    <option value="programMetrics">Program Metrics</option>
                                </select>
                            </div>

                            <!-- Student Info Fields -->
                            <div id="field1StudentInfo" class="form-group hidden">
                                <label for="field1StudentField">Field:</label>
                                <select id="field1StudentField">
                                    <option value="" disabled selected>Select</option>
                                </select>
                            </div>

                            <!-- Academic Profile Fields -->
                            <div id="field1AcademicProfile" class="form-group hidden">
                                <label for="field1AcademicMetric">Metric:</label>
                                <select id="field1AcademicMetric" onchange="handleField1AcademicMetricChange()">
                                    <option value="" disabled selected>Select</option>
                                </select>
                            </div>

                            <!-- Program Metrics Fields -->
                            <div id="field1ProgramMetrics" class="form-group hidden">
                                <label for="field1ProgramMetric">Metric:</label>
                                <select id="field1ProgramMetric" onchange="handleField1ProgramMetricChange()">
                                    <option value="" disabled selected>Select</option>
                                </select>
                            </div>

                            <!-- Dynamic sub-combobox -->
                            <div id="subMetricGroup1" class="form-group hidden">
                                <label for="subMetricSelect1" id="subMetricLabel1"></label>
                                <select id="subMetricSelect1" onchange="handleField1SubMetricChange()">
                                    <option value="" disabled selected>Select</option>
                                </select>
                            </div>

                            <div id="expectedGroup" class="form-group hidden">
                                <form id="expectedForm" method="POST"></form>
                            </div>

                        </div>

                        <!-- Field 2 Selection -->
                        <div id="fieldSelection2" class="field-group hidden">
                            <h4>Variable 2</h4>
                            <div class="form-group">
                                <label for="field2Category">Category:</label>
                                <select id="field2Category" onchange="handleField2CategoryChange()">
                                    <option value="" disabled selected>Select</option>
                                    <option value="studentInfo">Student Information</option>
                                    <option value="academicProfile">Academic Profile</option>
                                    <option value="programMetrics">Program Metrics</option>
                                </select>
                            </div>

                            <!-- Student Info Fields -->
                            <div id="field2StudentInfo" class="form-group hidden">
                                <label for="field2StudentField">Field:</label>
                                <select id="field2StudentField">
                                    <option value="" disabled selected>Select</option>
                                    <option value="studentId">Student ID</option>
                                    <option value="studentName">Student Name</option>
                                    <option value="college">College</option>
                                    <option value="program">Program</option>
                                    <option value="yearLevel">Year Level</option>
                                    <option value="section">Section</option>
                                    <option value="age">Age</option>
                                    <option value="gender">Gender</option>
                                    <option value="socioeconomicStatus">Socioeconomic Status</option>
                                    <option value="permanentAddress">Permanent Address</option>
                                    <option value="livingArrangement">Current Living Arrangement</option>
                                    <option value="workStatus">Work Status</option>
                                    <option value="scholarship">Scholarship/Grant</option>
                                    <option value="language">Language Spoken at Home</option>
                                    <option value="lastSchool">Last School Attended</option>
                                </select>
                            </div>

                            <!-- Academic Profile Fields -->
                            <div id="field2AcademicProfile" class="form-group hidden">
                                <label for="field2AcademicMetric">Metric:</label>
                                <select id="field2AcademicMetric" onchange="handleField2AcademicMetricChange()">
                                    <option value="" disabled selected>Select</option>
                                    <option value="GWA">GWA</option>
                                    <option value="BoardGrades">Grades in Board Subjects</option>
                                    <option value="Retakes">Back Subjects/Retakes</option>
                                    <option value="PerformanceRating">Performance Rating</option>
                                    <option value="SimExam">Simulation Exam Results</option>
                                    <option value="Attendance">Attendance in Review Classes</option>
                                    <option value="Recognition">Academic Recognition</option>
                                </select>
                            </div>

                            <!-- Program Metrics Fields -->
                            <div id="field2ProgramMetrics" class="form-group hidden">
                                <label for="field2ProgramMetric">Metric:</label>
                                <select id="field2ProgramMetric" onchange="handleField2ProgramMetricChange()">
                                    <option value="" disabled selected>Select</option>
                                    <option value="ReviewCenter">Student Review Center</option>
                                    <option value="MockScores">Mock Board Scores</option>
                                    <option value="LicensureResult">Licensure Exam Result</option>
                                    <option value="ExameDate">Date of Exam Taken</option>
                                    <option value="TakeAttempt">Number of Exam Attempts</option>
                                </select>
                            </div>

                            <!-- Dynamic sub-combobox -->
                            <div id="subMetricGroup2" class="form-group hidden">
                                <label for="subMetricSelect2" id="subMetricLabel2"></label>
                                <select name="subMetricSelect2" id="subMetricSelect2" onchange="handleField2SubMetricChange()">
                                    <option value="" disabled selected>Select</option>
                                </select>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="modal-buttons">                  
                    <button onclick="closeStatisticalToolModal()" class="btn-clear">Cancel</button>
                    <button onclick="generateReport()">Generate Report</button>
                </div>
            </div>
        </div>

        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script>
    
            function delay(ms){ return new Promise(res=>setTimeout(res,ms)); }

            // Helper to get header snapshot
            async function getHeaderSnapshot(scale = 2) {
                const header = document.querySelector('.report-header');
                if (!header) return null;
                try {
                    const canvas = await html2canvas(header, {
                        scale: scale,
                        useCORS: true,
                        allowTaint: true,
                        logging: false,
                        windowWidth: 1366,
                        width: 1366,
                        backgroundColor: '#ffffff'
                    });
                    return canvas;
                } catch (e) {
                    console.error('Header snapshot error:', e);
                    return null;
                }
            }

            // Helper to get footer snapshot
            async function getFooterSnapshot(scale = 2) {
                const footer = document.querySelector('#report footer');
                if (!footer) return null;
                try {
                    const canvas = await html2canvas(footer, {
                        scale: scale,
                        useCORS: true,
                        allowTaint: true,
                        logging: false,
                        windowWidth: 1366,
                        width: 1366,
                        backgroundColor: '#ffffff'
                    });
                    return canvas;
                } catch (e) {
                    console.error('Footer snapshot error:', e);
                    return null;
                }
            }

            // Helper to get content snapshot (without header/footer)
            async function getContentSnapshot(scale = 2) {
                const report = document.getElementById('report');
                const section = report?.querySelector('.section');
                if (!section) return null;
                try {
                    const canvas = await html2canvas(section, {
                        scale: scale,
                        useCORS: true,
                        allowTaint: true,
                        logging: false,
                        windowWidth: 1366,
                        width: 1366,
                        backgroundColor: '#ffffff'
                    });
                    return canvas;
                } catch (e) {
                    console.error('Content snapshot error:', e);
                    return null;
                }
            }

            // Unified snapshot function (from test.html) - kept for backward compatibility
            async function getSnapshot(scale = 2) {
                try { 
                    if (window.myChart && window.myChart.resize) window.myChart.resize(); 
                } catch (e) {}
                await delay(300); // allow chart to finish rendering
                // ensure report width is the intended desktop width during snapshot
                const report = document.getElementById('report');
                const prevWidth = report.style.width;
                report.style.width = getComputedStyle(report).width || report.style.width || (1366 + "px");
                const canvas = await html2canvas(report, {
                    scale: scale,
                    useCORS: true,
                    allowTaint: true,
                    logging: false,
                    windowWidth: 1366,
                    width: 1366,
                    backgroundColor: '#ffffff'
                });
                report.style.width = prevWidth; // restore
                return canvas;
            }

            // Helper to scale canvas with high quality using progressive multi-step downscaling
            // This preserves maximum sharpness when downscaling high-resolution images
            function scaleCanvasHighQuality(originalCanvas, targetWidth, targetHeight) {
                const originalWidth = originalCanvas.width;
                const originalHeight = originalCanvas.height;
                
                // If already at target size or smaller, no scaling needed
                if (originalWidth === targetWidth && originalHeight === targetHeight) {
                    return originalCanvas;
                }
                
                // If scaling up, use single step with high quality
                if (targetWidth >= originalWidth && targetHeight >= originalHeight) {
                    const resultCanvas = document.createElement('canvas');
                    resultCanvas.width = targetWidth;
                    resultCanvas.height = targetHeight;
                    const ctx = resultCanvas.getContext('2d');
                    ctx.imageSmoothingEnabled = true;
                    ctx.imageSmoothingQuality = 'high';
                    ctx.drawImage(originalCanvas, 0, 0, targetWidth, targetHeight);
                    return resultCanvas;
                }
                
                // For downscaling, use progressive multi-step approach for maximum quality
                // Progressive steps: 4x -> 2x -> 1x (or similar ratios)
                let currentCanvas = originalCanvas;
                let currentWidth = originalWidth;
                let currentHeight = originalHeight;
                
                // Progressive downscaling: scale down in steps of ~50% each time
                while (currentWidth > targetWidth * 1.5 || currentHeight > targetHeight * 1.5) {
                    const nextWidth = Math.max(Math.floor(currentWidth * 0.5), targetWidth);
                    const nextHeight = Math.max(Math.floor(currentHeight * 0.5), targetHeight);
                    
                    const nextCanvas = document.createElement('canvas');
                    nextCanvas.width = nextWidth;
                    nextCanvas.height = nextHeight;
                    const nextCtx = nextCanvas.getContext('2d');
                    nextCtx.imageSmoothingEnabled = true;
                    nextCtx.imageSmoothingQuality = 'high';
                    nextCtx.drawImage(currentCanvas, 0, 0, nextWidth, nextHeight);
                    
                    currentCanvas = nextCanvas;
                    currentWidth = nextWidth;
                    currentHeight = nextHeight;
                }
                
                // Final step: Scale to exact target size
                if (currentWidth !== targetWidth || currentHeight !== targetHeight) {
                    const finalCanvas = document.createElement('canvas');
                    finalCanvas.width = targetWidth;
                    finalCanvas.height = targetHeight;
                    const finalCtx = finalCanvas.getContext('2d');
                    finalCtx.imageSmoothingEnabled = true;
                    finalCtx.imageSmoothingQuality = 'high';
                    finalCtx.drawImage(currentCanvas, 0, 0, targetWidth, targetHeight);
                    return finalCanvas;
                }
                
                return currentCanvas;
            }

            // Helper to split content into pages with header/footer
            function splitContentIntoPages(contentCanvas, headerCanvas, footerCanvas, pageWidthPx, pageHeightPx, marginPx) {
                const pages = [];
                const contentHeight = contentCanvas.height;
                
                // Calculate scales
                const contentAreaWidth = pageWidthPx - (marginPx * 2);
                const contentScale = contentAreaWidth / contentCanvas.width;
                const headerScale = headerCanvas ? contentAreaWidth / headerCanvas.width : 1;
                const footerScale = footerCanvas ? contentAreaWidth / footerCanvas.width : 1;
                
                // Calculate actual heights after scaling
                const scaledHeaderHeight = headerCanvas ? headerCanvas.height * headerScale : 0;
                const scaledFooterHeight = footerCanvas ? footerCanvas.height * footerScale : 0;
                const gap = 10; // Small gap between header and content
                
                // Available content height per page = page height - margins - scaled header - gap - scaled footer
                const availableContentHeight = pageHeightPx - (marginPx * 2) - scaledHeaderHeight - gap - scaledFooterHeight;
                
                // Convert available height back to original content canvas scale
                const availableContentHeightInOriginalScale = availableContentHeight / contentScale;
                
                let currentY = 0;
                while (currentY < contentHeight) {
                    // Create a new page canvas at high resolution (4x for maximum HD quality)
                    // This ensures sharp quality even when zooming
                    const hdScale = 4;
                    const hdPageWidth = pageWidthPx * hdScale;
                    const hdPageHeight = pageHeightPx * hdScale;
                    const hdMargin = marginPx * hdScale;
                    const hdGap = gap * hdScale;
                    
                    const pageCanvas = document.createElement('canvas');
                    pageCanvas.width = hdPageWidth;
                    pageCanvas.height = hdPageHeight;
                    const ctx = pageCanvas.getContext('2d');
                    
                    // Enable high-quality rendering
                    ctx.imageSmoothingEnabled = true;
                    ctx.imageSmoothingQuality = 'high';
                    
                    // Fill white background
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, hdPageWidth, hdPageHeight);
                    
                    let yPos = hdMargin;
                    
                    // Scale and draw header at top using high-quality scaling
                    if (headerCanvas) {
                        const scaledHeaderWidth = headerCanvas.width * headerScale * hdScale;
                        const scaledHeaderHeight = headerCanvas.height * headerScale * hdScale;
                        const scaledHeader = scaleCanvasHighQuality(headerCanvas, scaledHeaderWidth, scaledHeaderHeight);
                        ctx.drawImage(scaledHeader, hdMargin, yPos);
                        yPos += scaledHeaderHeight + hdGap;
                    }
                    
                    // Draw content section - slice that fits in remaining space
                    const remainingContentHeight = contentHeight - currentY;
                    const contentSliceHeight = Math.min(availableContentHeightInOriginalScale, remainingContentHeight);
                    
                    // Create a temporary canvas for the content slice at full resolution
                    const sourceCanvas = document.createElement('canvas');
                    sourceCanvas.width = contentCanvas.width;
                    sourceCanvas.height = contentSliceHeight;
                    const sourceCtx = sourceCanvas.getContext('2d');
                    
                    // Copy content slice without scaling
                    sourceCtx.imageSmoothingEnabled = false; // No scaling, just copy
                    sourceCtx.drawImage(contentCanvas, 0, currentY, contentCanvas.width, contentSliceHeight, 0, 0, contentCanvas.width, contentSliceHeight);
                    
                    // Scale the slice using high-quality multi-step scaling
                    const scaledContentWidth = contentCanvas.width * contentScale * hdScale;
                    const scaledContentHeight = contentSliceHeight * contentScale * hdScale;
                    const scaledContent = scaleCanvasHighQuality(sourceCanvas, scaledContentWidth, scaledContentHeight);
                    
                    ctx.drawImage(scaledContent, hdMargin, yPos);
                    
                    // Scale and draw footer at bottom using high-quality scaling
                    if (footerCanvas) {
                        const footerY = hdPageHeight - hdMargin - (footerCanvas.height * footerScale * hdScale);
                        const scaledFooterWidth = footerCanvas.width * footerScale * hdScale;
                        const scaledFooterHeight = footerCanvas.height * footerScale * hdScale;
                        const scaledFooter = scaleCanvasHighQuality(footerCanvas, scaledFooterWidth, scaledFooterHeight);
                        ctx.drawImage(scaledFooter, hdMargin, footerY);
                    }
                    
                    // Keep the page at HD resolution - don't scale down to preserve quality
                    // The export functions will handle the scaling appropriately
                    pages.push(pageCanvas);
                    
                    currentY += contentSliceHeight;
                }
                
                return pages;
            }

            // Helper to resize canvas to fit Word printable area with maximum quality
            function resizeCanvasForWord(originalCanvas) {
                // Custom margins: top: 0.5cm, bottom: 0.5cm, left: 0.5cm, right: 0.5cm
                // Page is 8.5in (Letter width)
                // Left: 0.5cm = 0.197in, Right: 0.5cm = 0.197in
                // Available width: 8.5in - 0.197in - 0.197in = 8.106in
                const printableWidthPx = 8.106 * 96; // 777.38px ≈ 778px at 96dpi
                const originalWidth = originalCanvas.width;
                const originalHeight = originalCanvas.height;
                const scaleFactor = printableWidthPx / originalWidth;
                const newHeight = Math.floor(originalHeight * scaleFactor);

                // Strategy: Use multiple intermediate steps for better quality
                // Step 1: Scale to a very high resolution first
                const highResFactor = 4; // Scale to 4x the target size
                const highResWidth = printableWidthPx * highResFactor;
                const highResHeight = newHeight * highResFactor;
                
                const highResCanvas = document.createElement('canvas');
                highResCanvas.width = highResWidth;
                highResCanvas.height = highResHeight;
                const highResCtx = highResCanvas.getContext('2d');
                
                highResCtx.imageSmoothingEnabled = true;
                highResCtx.imageSmoothingQuality = 'high';
                highResCtx.drawImage(originalCanvas, 0, 0, highResWidth, highResHeight);
                
                // Step 2: Scale down to 2x target size
                const midResWidth = printableWidthPx * 2;
                const midResHeight = newHeight * 2;
                
                const midResCanvas = document.createElement('canvas');
                midResCanvas.width = midResWidth;
                midResCanvas.height = midResHeight;
                const midResCtx = midResCanvas.getContext('2d');
                
                midResCtx.imageSmoothingEnabled = true;
                midResCtx.imageSmoothingQuality = 'high';
                midResCtx.drawImage(highResCanvas, 0, 0, midResWidth, midResHeight);
                
                // Step 3: Final scale to target size
                const finalCanvas = document.createElement('canvas');
                finalCanvas.width = printableWidthPx;
                finalCanvas.height = newHeight;
                const finalCtx = finalCanvas.getContext('2d');
                
                finalCtx.imageSmoothingEnabled = true;
                finalCtx.imageSmoothingQuality = 'high';
                finalCtx.drawImage(midResCanvas, 0, 0, printableWidthPx, newHeight);
                
                return finalCanvas;
            }
                
                // Open modal on page load
                window.addEventListener('load', function() {
                    openStatisticalToolModal();
                });
                
                // Function to show report wrapper and set timestamp when report is generated
                function showReportWrapper() {
                    document.getElementById('reportWrapper').classList.remove('hidden');
                    const timestamp = new Date().toLocaleString();
                    document.getElementById('generatedAt').textContent = timestamp;
                    const generatedAtDisplay = document.getElementById('generatedAtDisplay');
                    if (generatedAtDisplay) {
                        generatedAtDisplay.textContent = timestamp;
                    }
                }

                // Chart.js setup (guard if canvas exists)
                const chartCanvasEl = document.getElementById('chartCanvas');
                if (chartCanvasEl) {
                const ctx = chartCanvasEl.getContext('2d');
                window.myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['A','B','C','D','E','F','G','H'],
                    datasets: [{
                    label: 'Score',
                    data: [85,90,78,65,92,71,71,71],
                    backgroundColor: ['#4caf50','#2196f3','#ffb300','#f44336','#9c27b0','#3f51b5','#e91e63','#607d8b']
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero:true, max:100 } }
                }
                });
                }

                /* --------------------------
                   PRINT (no about:blank tab)
                   - create a hidden iframe
                   - write a printable HTML with pages that have header and footer on each page
                   - call iframe.contentWindow.print()
                   - remove iframe
                   -------------------------- */
                document.getElementById('printBtn').addEventListener('click', async () => {
                    try {
                        if (window.myChart && window.myChart.resize) window.myChart.resize();
                        await delay(300);
                        
                        // Get separate snapshots for header, footer, and content with very high scale for maximum HD quality
                        const [headerCanvas, footerCanvas, contentCanvas] = await Promise.all([
                            getHeaderSnapshot(6),
                            getFooterSnapshot(6),
                            getContentSnapshot(6)
                        ]);

                        if (!contentCanvas) {
                            alert("Failed to capture report content");
                            return;
                        }

                        // Letter page dimensions: 8.5in x 11in = 816px x 1056px at 96dpi
                        // With 0.5cm margins (≈14px), content area is 788px x 1028px
                        const pageWidthPx = 816; // Letter width at 96dpi
                        const pageHeightPx = 1056; // Letter height at 96dpi
                        const marginPx = 14; // 0.5cm ≈ 14px at 96dpi

                        // Split content into pages with header and footer
                        const pages = splitContentIntoPages(contentCanvas, headerCanvas, footerCanvas, pageWidthPx, pageHeightPx, marginPx);

                        // Create hidden iframe
                        const iframe = document.createElement("iframe");
                        iframe.style.position = "fixed";
                        iframe.style.right = "0";
                        iframe.style.bottom = "0";
                        iframe.style.width = "0";
                        iframe.style.height = "0";
                        iframe.style.border = "0";
                        iframe.setAttribute("aria-hidden", "true");
                        document.body.appendChild(iframe);

                        const doc = iframe.contentWindow.document;
                        
                        // Build printable HTML with pages - use high quality PNG encoding
                        let pagesHTML = pages.map((page, idx) => {
                            const imgData = page.toDataURL("image/png", 1.0); // Maximum quality
                            return `<div class="page"><img src="${imgData}" alt="report page ${idx + 1}"/></div>`;
                        }).join('');

                        doc.open();
                        doc.write(`
                            <html>
                              <head>
                                <title>Print</title>
                                <style>
                                  @page { 
                                    size: Letter portrait; 
                                    margin: 0; 
                                  }
                                  html, body { 
                                    margin: 0; 
                                    padding: 0; 
                                    height: 100%; 
                                  }
                                  .page { 
                                    width: 8.5in;
                                    height: 11in;
                                    page-break-after: always;
                                    box-sizing: border-box;
                                    margin: 0;
                                    padding: 0;
                                  }
                                  .page:last-child {
                                    page-break-after: auto;
                                  }
                                  .page img { 
                                    display: block;
                                    width: 100%;
                                    height: 100%;
                                    object-fit: contain;
                                  }
                                </style>
                              </head>
                              <body>
                                ${pagesHTML}
                              </body>
                            </html>
                        `);
                        doc.close();

                        // wait for images to load inside iframe before printing
                        const printPromise = new Promise((resolve) => {
                            const imgs = doc.querySelectorAll("img");
                            if (imgs.length === 0) { resolve(); return; }
                            let loaded = 0;
                            const checkComplete = () => {
                                loaded++;
                                if (loaded === imgs.length) resolve();
                            };
                            imgs.forEach(img => {
                                if (img.complete) checkComplete();
                                else {
                                    img.onload = checkComplete;
                                    img.onerror = checkComplete;
                                }
                            });
                        });

                        await printPromise;

                        // call print on the iframe window — avoids opening a blank tab
                        iframe.contentWindow.focus();
                        iframe.contentWindow.print();

                        // Give the browser a moment, then remove the iframe to clean up
                        setTimeout(() => {
                            try { document.body.removeChild(iframe); } catch (e) {}
                        }, 500);
                    } catch (err) {
                        console.error("Print snapshot error:", err);
                        alert("Print failed — check console.");
                    }
                });

            // Also stabilize onbeforeprint: match test.html (just ensure chart is laid out)
            window.onbeforeprint = async () => {
            try {
                if (window.myChart && window.myChart.resize) window.myChart.resize();
                await delay(300);
            } catch (e) {
                console.error('beforeprint error:', e);
            }
            };

            window.onafterprint = () => {
            try { if (window.myChart && window.myChart.resize) window.myChart.resize(); } catch (e) {}
            };


                /* --------------------------
                   PDF export with header/footer on each page
                   -------------------------- */
                const exportPdfBtn = document.getElementById('exportPdfBtn');
                if (exportPdfBtn) {
                    exportPdfBtn.addEventListener('click', async () => {
                    try {
                        if (window.myChart && window.myChart.resize) window.myChart.resize();
                        await delay(300);
                        
                        // Get separate snapshots for header, footer, and content with very high scale for maximum HD quality
                        const [headerCanvas, footerCanvas, contentCanvas] = await Promise.all([
                            getHeaderSnapshot(6),
                            getFooterSnapshot(6),
                            getContentSnapshot(6)
                        ]);

                        if (!contentCanvas) {
                            alert("Failed to capture report content");
                            return;
                        }

                        const { jsPDF } = window.jspdf;
                        const pdf = new jsPDF({ unit: "pt", format: "letter", orientation: "portrait" });

                        const pageWidth = pdf.internal.pageSize.getWidth(); // 612pt (8.5in)
                        const pageHeight = pdf.internal.pageSize.getHeight(); // 792pt (11in)
                        
                        // 0.5cm margins: 0.5cm = 0.19685 inches = 14.17 points (at 72 dpi)
                        const marginPt = 14.17;
                        const marginPx = 14; // For pixel calculations at 96dpi
                        
                        // Convert to pixels for page splitting (at 96dpi)
                        const pageWidthPx = 816; // 8.5in at 96dpi
                        const pageHeightPx = 1056; // 11in at 96dpi

                        // Split content into pages with header and footer
                        const pages = splitContentIntoPages(contentCanvas, headerCanvas, footerCanvas, pageWidthPx, pageHeightPx, marginPx);

                        // Add each page to PDF - pages are at 4x resolution, display at 1x for HD quality
                        pages.forEach((page, idx) => {
                            if (idx > 0) {
                                pdf.addPage();
                            }
                            const imgData = page.toDataURL("image/png", 1.0); // Maximum quality PNG
                            // Add image at full resolution but display at page size - this preserves HD quality
                            pdf.addImage(imgData, "PNG", 0, 0, pageWidth, pageHeight, undefined, 'FAST', 0);
                        });

                        pdf.save("report.pdf");
                    } catch (err) {
                        console.error("PDF export error:", err);
                        alert("Export to PDF failed — check console.");
                    }
                    });
                }

                /* --------------------------
                   WORD export with header/footer on each page
                   - Split content into pages with header and footer on each page
                   - Each page fits letter size (8.5in x 11in)
                   -------------------------- */
                const exportWordBtn = document.getElementById('exportWordBtn');
                if (exportWordBtn) {
                    exportWordBtn.addEventListener('click', async () => {
                    try {
                        if (window.myChart && window.myChart.resize) window.myChart.resize();
                        await delay(300);
                        
                        // Get separate snapshots for header, footer, and content with very high scale for maximum quality
                        // Using scale 6 for Word export to ensure HD quality even when zooming
                        const [headerCanvas, footerCanvas, contentCanvas] = await Promise.all([
                            getHeaderSnapshot(6),
                            getFooterSnapshot(6),
                            getContentSnapshot(6)
                        ]);

                        if (!contentCanvas) {
                            alert("Failed to capture report content");
                            return;
                        }

                        // Letter page dimensions at 96dpi
                        const pageWidthPx = 816; // 8.5in at 96dpi
                        const pageHeightPx = 1056; // 11in at 96dpi
                        const marginPx = 14; // 0.5cm ≈ 14px at 96dpi

                        // Split content into pages with header and footer
                        const pages = splitContentIntoPages(contentCanvas, headerCanvas, footerCanvas, pageWidthPx, pageHeightPx, marginPx);

                        // Scale pages down to correct size for Word export using high-quality scaling
                        // Pages are at 4x resolution (hdScale=4), need to scale to 1x for proper fit
                        const scaledPages = pages.map(page => {
                            return scaleCanvasHighQuality(page, pageWidthPx, pageHeightPx);
                        });

                        // Build pages HTML - use high quality PNG encoding
                        const pagesHTML = scaledPages.map((page, idx) => {
                            const imgData = page.toDataURL("image/png", 1.0); // Maximum quality
                            return `<div class="word-page"><img src="${imgData}" alt="report page ${idx + 1}" /></div>`;
                        }).join('');

                        // Build an HTML wrapper with custom margins: top: 0.5cm, bottom: 0.5cm, left: 0.5cm, right: 0.5cm
                        // Convert cm to twips: 1cm = 567 twips (Word's measurement unit)
                        // top: 0.5cm = 284 twips, bottom: 0.5cm = 284 twips, left: 0.5cm = 284 twips, right: 0.5cm = 284 twips
                        const htmlContent = `
<html xmlns:o='urn:schemas-microsoft-com:office:office'
      xmlns:w='urn:schemas-microsoft-com:office:word'
      xmlns='http://www.w3.org/TR/REC-html40'>
<head><meta charset="utf-8"><title>Report</title>
<meta name="ProgId" content="Word.Document">
<!--[if gte mso 9]>
<xml>
  <w:WordDocument>
    <w:View>Print</w:View>
    <w:Zoom>100</w:Zoom>
    <w:DoNotOptimizeForBrowser/>
  </w:WordDocument>
  <w:LatentStyles DefLockedState="false" DefUnhideWhenUsed="false" DefSemiHidden="false" DefQFormat="false" DefPriority="99" LatentStyleCount="376">
  </w:LatentStyles>
</xml>
<![endif]-->
<!--[if gte mso 9]><xml>
  <w:WordDocument>
    <w:View>Print</w:View>
    <w:Zoom>100</w:Zoom>
  </w:WordDocument>
</xml><![endif]-->
<style>
  @page {
    margin-top: 0.5cm;
    margin-bottom: 0.5cm;
    margin-left: 0.5cm;
    margin-right: 0.5cm;
    size: 8.5in 11in;
    mso-page-border-surround-header: no;
    mso-page-border-surround-footer: no;
  }
  /* Custom margins: top: 0.5cm, bottom: 0.5cm, left: 0.5cm, right: 0.5cm */
  .word-page {
    page-break-after: always;
    margin-bottom: 0;
    width: 8.5in;
    height: 11in;
    box-sizing: border-box;
  }
  .word-page:last-child {
    page-break-after: auto;
  }
  .word-page img {
    width: 8.5in;
    height: 11in;
    display: block;
    margin: 0;
    padding: 0;
  }
  body { 
    margin:0 !important; 
    padding:0 !important; 
  }
  html { 
    margin:0 !important; 
    padding:0 !important; 
  }
  /* Additional Word-specific: Ensure no extra spacing */
  p, div { margin: 0; padding: 0; }
  @page Section1 {
    size: 8.5in 11in;
    margin: 0.5cm 0.5cm 0.5cm 0.5cm;
    mso-header-margin: 0cm;
    mso-footer-margin: 0cm;
    mso-paper-source: 0;
  }
  div.Section1 {
    page: Section1;
  }
</style>
<!--[if gte mso 9]>
<style>
  /* Section formatting */
  table {
    mso-displayed-decimal-separator: "\\.";
    mso-displayed-thousand-separator: "\\,";
  }
</style>
<![endif]-->
<body>
<div class=Section1>
  ${pagesHTML}
<!--[if gte mso 9]><xml>
  <w:sectPr>
    <w:pgSz w:w="12240" w:h="15840" w:orient="portrait"/>
    <w:pgMar w:top="284" w:right="284" w:bottom="284" w:left="284" w:header="0" w:footer="0" w:gutter="0"/>
    <w:cols w:space="720"/>
    <w:docGrid w:line-pitch="360"/>
  </w:sectPr>
</xml><![endif]-->
</div>
</body>
</html>`;

                        // Create a blob and trigger download as .doc
                        const blob = new Blob(['\ufeff', htmlContent], { type: 'application/msword' });
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement("a");
                        a.href = url;
                        a.download = "report.doc";
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                    } catch (err) {
                        console.error("Word export error:", err);
                        alert("Export to Word failed — check console.");
                    }
                    });
                }

                // Add event listeners for dropdown export buttons
                const exportPdfDropdownBtn = document.getElementById('exportPdfDropdownBtn');
                if (exportPdfDropdownBtn) {
                    exportPdfDropdownBtn.addEventListener('click', async () => {
                    try {
                        if (window.myChart && window.myChart.resize) window.myChart.resize();
                        await delay(300);
                        
                        // Get separate snapshots for header, footer, and content with very high scale for maximum HD quality
                        const [headerCanvas, footerCanvas, contentCanvas] = await Promise.all([
                            getHeaderSnapshot(6),
                            getFooterSnapshot(6),
                            getContentSnapshot(6)
                        ]);

                        if (!contentCanvas) {
                            alert("Failed to capture report content");
                            return;
                        }

                        const { jsPDF } = window.jspdf;
                        const pdf = new jsPDF({ unit: "pt", format: "letter", orientation: "portrait" });

                        const pageWidth = pdf.internal.pageSize.getWidth(); // 612pt (8.5in)
                        const pageHeight = pdf.internal.pageSize.getHeight(); // 792pt (11in)
                        
                        // 0.5cm margins: 0.5cm = 0.19685 inches = 14.17 points (at 72 dpi)
                        const marginPt = 14.17;
                        const marginPx = 14; // For pixel calculations at 96dpi
                        
                        // Convert to pixels for page splitting (at 96dpi)
                        const pageWidthPx = 816; // 8.5in at 96dpi
                        const pageHeightPx = 1056; // 11in at 96dpi

                        // Split content into pages with header and footer
                        const pages = splitContentIntoPages(contentCanvas, headerCanvas, footerCanvas, pageWidthPx, pageHeightPx, marginPx);

                        // Add each page to PDF - pages are at 4x resolution, display at 1x for HD quality
                        pages.forEach((page, idx) => {
                            if (idx > 0) {
                                pdf.addPage();
                            }
                            const imgData = page.toDataURL("image/png", 1.0); // Maximum quality PNG
                            // Add image at full resolution but display at page size - this preserves HD quality
                            pdf.addImage(imgData, "PNG", 0, 0, pageWidth, pageHeight, undefined, 'FAST', 0);
                        });

                        pdf.save("report.pdf");
                    } catch (err) {
                        console.error("PDF export error:", err);
                        alert("Export to PDF failed — check console.");
                    }
                    });
                }

                const exportWordDropdownBtn = document.getElementById('exportWordDropdownBtn');
                if (exportWordDropdownBtn) {
                    exportWordDropdownBtn.addEventListener('click', async () => {
                    try {
                        if (window.myChart && window.myChart.resize) window.myChart.resize();
                        await delay(300);
                        
                        // Get separate snapshots for header, footer, and content with very high scale for maximum quality
                        // Using scale 6 for Word export to ensure HD quality even when zooming
                        const [headerCanvas, footerCanvas, contentCanvas] = await Promise.all([
                            getHeaderSnapshot(6),
                            getFooterSnapshot(6),
                            getContentSnapshot(6)
                        ]);

                        if (!contentCanvas) {
                            alert("Failed to capture report content");
                            return;
                        }

                        // Letter page dimensions at 96dpi
                        const pageWidthPx = 816; // 8.5in at 96dpi
                        const pageHeightPx = 1056; // 11in at 96dpi
                        const marginPx = 14; // 0.5cm ≈ 14px at 96dpi

                        // Split content into pages with header and footer
                        const pages = splitContentIntoPages(contentCanvas, headerCanvas, footerCanvas, pageWidthPx, pageHeightPx, marginPx);

                        // Scale pages down to correct size for Word export using high-quality scaling
                        // Pages are at 4x resolution (hdScale=4), need to scale to 1x for proper fit
                        const scaledPages = pages.map(page => {
                            return scaleCanvasHighQuality(page, pageWidthPx, pageHeightPx);
                        });

                        // Build pages HTML - use high quality PNG encoding
                        const pagesHTML = scaledPages.map((page, idx) => {
                            const imgData = page.toDataURL("image/png", 1.0); // Maximum quality
                            return `<div class="word-page"><img src="${imgData}" alt="report page ${idx + 1}" /></div>`;
                        }).join('');

                        // Build an HTML wrapper with custom margins: top: 0.5cm, bottom: 0.5cm, left: 0.5cm, right: 0.5cm
                        // Convert cm to twips: 1cm = 567 twips (Word's measurement unit)
                        // top: 0.5cm = 284 twips, bottom: 0.5cm = 284 twips, left: 0.5cm = 284 twips, right: 0.5cm = 284 twips
                        const htmlContent = `
<html xmlns:o='urn:schemas-microsoft-com:office:office'
      xmlns:w='urn:schemas-microsoft-com:office:word'
      xmlns='http://www.w3.org/TR/REC-html40'>
<head><meta charset="utf-8"><title>Report</title>
<meta name="ProgId" content="Word.Document">
<!--[if gte mso 9]>
<xml>
  <w:WordDocument>
    <w:View>Print</w:View>
    <w:Zoom>100</w:Zoom>
    <w:DoNotOptimizeForBrowser/>
  </w:WordDocument>
  <w:LatentStyles DefLockedState="false" DefUnhideWhenUsed="false" DefSemiHidden="false" DefQFormat="false" DefPriority="99" LatentStyleCount="376">
  </w:LatentStyles>
</xml>
<![endif]-->
<!--[if gte mso 9]><xml>
  <w:WordDocument>
    <w:View>Print</w:View>
    <w:Zoom>100</w:Zoom>
  </w:WordDocument>
</xml><![endif]-->
<style>
  @page {
    margin-top: 0.5cm;
    margin-bottom: 0.5cm;
    margin-left: 0.5cm;
    margin-right: 0.5cm;
    size: 8.5in 11in;
    mso-page-border-surround-header: no;
    mso-page-border-surround-footer: no;
  }
  /* Custom margins: top: 0.5cm, bottom: 0.5cm, left: 0.5cm, right: 0.5cm */
  .word-page {
    page-break-after: always;
    margin-bottom: 0;
    width: 8.5in;
    height: 11in;
    box-sizing: border-box;
  }
  .word-page:last-child {
    page-break-after: auto;
  }
  .word-page img {
    width: 8.5in;
    height: 11in;
    display: block;
    margin: 0;
    padding: 0;
  }
  body { 
    margin:0 !important; 
    padding:0 !important; 
  }
  html { 
    margin:0 !important; 
    padding:0 !important; 
  }
  /* Additional Word-specific: Ensure no extra spacing */
  p, div { margin: 0; padding: 0; }
  @page Section1 {
    size: 8.5in 11in;
    margin: 0.5cm 0.5cm 0.5cm 0.5cm;
    mso-header-margin: 0cm;
    mso-footer-margin: 0cm;
    mso-paper-source: 0;
  }
  div.Section1 {
    page: Section1;
  }
</style>
<!--[if gte mso 9]>
<style>
  /* Section formatting */
  table {
    mso-displayed-decimal-separator: "\\.";
    mso-displayed-thousand-separator: "\\,";
  }
</style>
<![endif]-->
<body>
<div class=Section1>
  ${pagesHTML}
<!--[if gte mso 9]><xml>
  <w:sectPr>
    <w:pgSz w:w="12240" w:h="15840" w:orient="portrait"/>
    <w:pgMar w:top="284" w:right="284" w:bottom="284" w:left="284" w:header="0" w:footer="0" w:gutter="0"/>
    <w:cols w:space="720"/>
    <w:docGrid w:line-pitch="360"/>
  </w:sectPr>
</xml><![endif]-->
</div>
</body>
</html>`;

                        // Create a blob and trigger download as .doc
                        const blob = new Blob(['\ufeff', htmlContent], { type: 'application/msword' });
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement("a");
                        a.href = url;
                        a.download = "report.doc";
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                    } catch (err) {
                        console.error("Word export error:", err);
                        alert("Export to Word failed — check console.");
                    }
                    });
                }

                window.onbeforeprint = () => { try { if (window.myChart && window.myChart.resize) window.myChart.resize(); } catch(e){} };
                window.onafterprint = () => { try { if (window.myChart && window.myChart.resize) window.myChart.resize(); } catch(e){} };
        </script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="core/logout_inform.js"></script>
        <script src="core/get_pending_count.js"></script>
        <script src="core/session_warning.js"></script>
        <script src="modules/generate_report/js/generate_report.js">
        </script>
        <script>
            window.userSession = {
            level: <?php echo json_encode($_SESSION['level'] ?? ''); ?>,
            college: <?php echo json_encode($_SESSION['college'] ?? ''); ?>,
            program: <?php echo json_encode($_SESSION['program'] ?? ''); ?>,
            filter_college: <?php echo json_encode($_SESSION['filter_college'] ?? ''); ?>,
            filter_program: <?php echo json_encode($_SESSION['filter_program'] ?? ''); ?>,
            filter_year_start: <?php echo json_encode($_SESSION['filter_year_start'] ?? ''); ?>,
            filter_year_end: <?php echo json_encode($_SESSION['filter_year_end'] ?? ''); ?>,
            filter_board_batch: <?php echo json_encode($_SESSION['filter_board_batch'] ?? ''); ?>
            };
        document.getElementById("statTool").value = "";
        document.getElementById("subMetricSelect1").addEventListener("change", handleField1SubMetricChange);
        document.getElementById("field1StudentField").addEventListener("change", handleField1StudentInfoMetricChange);
    </script>
        
    
    </body>
</html>