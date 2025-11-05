/******************************UPON LOADING THE PAGE***********************************/
window.addEventListener('DOMContentLoaded', () => {
  const content = document.querySelector('.content');
  if (content) {
    // No fade-in animation
  }

  
});

// Guard navigation based on current DOM state only (no localStorage)
function reportExistsInDOM() {
const reportSummary = document.getElementById('reportSummary');
const generatedReport = document.getElementById('generatedReport');
if (!reportSummary || !generatedReport) return false;
const visible = !reportSummary.classList.contains('hidden') && !generatedReport.classList.contains('hidden');
const hasContent = reportSummary.innerHTML && reportSummary.innerHTML.trim().length > 0;
return visible && hasContent;
}

// Enable/disable print and export controls based on report existence
function setReportActionsEnabled(enabled) {
const printBtn = document.querySelector('.print-btn');
const exportPdfBtn = document.getElementById('exportPdfBtn'); // standalone PDF export button
const exportWordBtn = document.getElementById('exportWordBtn'); // standalone Word export button
const exportDropdownBtn = document.getElementById('exportDropdownBtn'); // dropdown trigger
const exportPdfDropdownBtn = document.getElementById('exportPdfDropdownBtn'); // dropdown PDF button
const exportWordDropdownBtn = document.getElementById('exportWordDropdownBtn'); // dropdown Word button

if (printBtn) {
  printBtn.disabled = !enabled;
  printBtn.classList.toggle('disabled', !enabled);
}

// Disable standalone export buttons (if they exist)
if (exportPdfBtn) {
  exportPdfBtn.disabled = !enabled;
  exportPdfBtn.classList.toggle('disabled', !enabled);
}

if (exportWordBtn) {
  exportWordBtn.disabled = !enabled;
  exportWordBtn.classList.toggle('disabled', !enabled);
}

// Disable dropdown trigger and options
if (exportDropdownBtn) {
  exportDropdownBtn.disabled = !enabled;
  exportDropdownBtn.classList.toggle('disabled', !enabled);
}

if (exportPdfDropdownBtn) {
  exportPdfDropdownBtn.disabled = !enabled;
  exportPdfDropdownBtn.classList.toggle('disabled', !enabled);
}

if (exportWordDropdownBtn) {
  exportWordDropdownBtn.disabled = !enabled;
  exportWordDropdownBtn.classList.toggle('disabled', !enabled);
}
}

// Scroll Notification Functions
function isSmallScreen() {
  return window.innerWidth <= 768;
}

function showScrollNotification() {
  const notification = document.getElementById('scrollNotification');
  if (notification && isSmallScreen()) {
    notification.classList.add('show');
    
    // Auto-dismiss after 8 seconds
    setTimeout(() => {
      hideScrollNotification();
    }, 8000);
  }
}

function hideScrollNotification() {
  const notification = document.getElementById('scrollNotification');
  if (notification) {
    notification.classList.remove('show');
    // Mark as dismissed when user manually closes it
    markNotificationAsDismissed();
  }
}

// Check if user has already dismissed the notification in this session
function hasUserDismissedNotification() {
  return sessionStorage.getItem('scrollNotificationDismissed') === 'true';
}

function markNotificationAsDismissed() {
  sessionStorage.setItem('scrollNotificationDismissed', 'true');
}

// Listen for window resize to show notification on small screens
window.addEventListener('resize', function() {
  if (isSmallScreen() && reportExistsInDOM() && !hasUserDismissedNotification()) {
    showScrollNotification();
  }
});

//////////for loading animation on next pages
document.addEventListener('DOMContentLoaded', function () {
  // Select all links with the "next-page" class (which wraps the buttons)
  const nextPageButtons = document.querySelectorAll(".next-page");
      
  // Add event listener for each button inside the links
  nextPageButtons.forEach(function(button) {
  button.addEventListener("click", function (e) {
  e.preventDefault(); // Prevent the default link behavior
      
  NProgress.start(); // Start the loading bar
      
  setTimeout(() => {
      NProgress.set(0.7); // Move the bar 70% quickly
  }, 300); // Adjust the speed here
      
  setTimeout(() => {
      NProgress.done(); // Complete the bar animation
      window.location.href = button.href; // Redirect after the loading bar completes
  }, 1200); // Adjust the time here based on the animation duration
});
});
  
// Ensure the progress bar is fully complete when the page is loaded
window.onload = function () {
  NProgress.done();
};
});

/********** Loading animation for going back to previous pages **********/
document.addEventListener('DOMContentLoaded', function () {
  const backPageLinks = document.querySelectorAll(".back-page");

  backPageLinks.forEach(function (el) {
    el.addEventListener("click", function (e) {
      const target = el.href || el.dataset.href;

      if (!target) return;

      // Check if there's a report - if yes, let beforeunload handle it
      // But don't block if session has expired
      if (reportExistsInDOM() && !window.sessionExpired) {
        // Store target for beforeunload handler
        window.pendingNavigation = target;
        // Don't prevent default - let the browser handle it naturally
        return; // Let beforeunload handle the confirmation
      } else {
        // No report - start NProgress immediately
        e.preventDefault();
        NProgress.start();
        
        setTimeout(() => {
          NProgress.set(0.7);
        }, 500);
        
        setTimeout(() => {
          NProgress.done();
          window.location.href = target;
        }, 2000);
      }
    });
  });

  // Handle the actual navigation after beforeunload confirmation (only when report exists)
  window.addEventListener('beforeunload', function (e) {
    // Don't block navigation if session has expired
    if (window.sessionExpired) {
      return;
    }
    
    if (reportExistsInDOM() && window.pendingNavigation) {
      e.preventDefault();
      e.returnValue = '';
      
      // Clear the pending navigation to prevent loops
      const target = window.pendingNavigation;
      window.pendingNavigation = null;
      // Navigate immediately without nprogress when report exists
      window.location.href = target;
      
      return '';
    }
  });

  window.onload = function () {
    NProgress.done();
  };
});







////////////////TEST SHOW FOR REPORT GENERATION///////////////////////////////////
let lastDataPoints = []; // store last dataset globally
const statToolSelect = document.getElementById('statTool');
//const inputContainer = document.getElementById('inputContainer');
const reportSummary = document.getElementById('reportSummary');

statToolSelect.addEventListener('change', function () {
  //inputContainer.innerHTML = '';
  // Don't hide reportSummary here - it should only be hidden when a new report is actually generated
  // reportSummary.classList.add('hidden');

  const selected = this.value;

  /* Show the other containers when a tool is selected
  if (selected) {
    document.getElementById('dynamicInputsContainer').classList.remove('hidden');
  } else {
    document.getElementById('dynamicInputsContainer').classList.add('hidden');
  }*/

  if (selected === 'pearson') {
    showPearsonInputs();
  } else if (selected === 'stddev') {
    showStdDevInputs();
  } else if (selected === 'mean') {
    showMeanInputs();
  }
});

function showPearsonInputs() {
  inputContainer.innerHTML = `
    <div class="form-group">
      <label>Enter X values (comma separated):</label>
      <input type="text" id="pearsonX" placeholder="e.g. 1, 2, 3">
    </div>
    <div class="form-group">
      <label>Enter Y values (comma separated):</label>
      <input type="text" id="pearsonY" placeholder="e.g. 4, 5, 6">
    </div>
    <button onclick="generatePearson()">Generate Report</button>
  `;
}

function showStdDevInputs() {
  inputContainer.innerHTML = `
    <div class="form-group">
      <label>Enter dataset values (comma separated):</label>
      <input type="text" id="stddevInput" placeholder="e.g. 10, 15, 20">
    </div>
    <button onclick="generateStdDev()">Generate Report</button>
  `;
}

function showMeanInputs() {
  inputContainer.innerHTML = `
    <div class="form-group">
      <label>Enter values for Mean (comma separated):</label>
      <input type="text" id="meanInput" placeholder="e.g. 5, 7, 9">
    </div>
    <button onclick="generateMean()">Generate Report</button>
  `;
}

function generatePearson() {
  const x = document.getElementById('pearsonX').value;
  const y = document.getElementById('pearsonY').value;
  reportSummary.innerHTML = `
    <h4>Pearson R Report</h4>
    <p><strong>X:</strong> ${x}</p>
    <p><strong>Y:</strong> ${y}</p>
    <p><em>Sample output:</em> Pearson R = 0.87 (Strong positive correlation)</p>
  `;
  reportSummary.classList.remove('hidden');
}

function generateStdDev() {
  const data = document.getElementById('stddevInput').value;
  reportSummary.innerHTML = `
    <h4>Standard Deviation Report</h4>
    <p><strong>Values:</strong> ${data}</p>
    <p><em>Sample output:</em> Standard Deviation = 3.2</p>
  `;
  reportSummary.classList.remove('hidden');
}

function generateMean() {
  const data = document.getElementById('meanInput').value;
  reportSummary.innerHTML = `
    <h4>Mean Report</h4>
    <p><strong>Values:</strong> ${data}</p>
    <p><em>Sample output:</em> Mean = 6.7</p>
  `;
  reportSummary.classList.remove('hidden');
}

// Show modal on page load
document.addEventListener('DOMContentLoaded', function() {
  // Initialize print/export disabled until a report exists
  setReportActionsEnabled(reportExistsInDOM());
});

// Disable Ctrl+P keyboard shortcut to prevent conflicts with custom print button
document.addEventListener('keydown', function(event) {
  // Check if Ctrl+P is pressed (Ctrl key + P key)
  if (event.ctrlKey && event.key === 'p') {
    event.preventDefault(); // Prevent the default browser print dialog
    event.stopPropagation(); // Stop the event from bubbling
    return false;
  }
});

// Modal Functions
function openStatisticalToolModal() {
document.getElementById('statisticalToolModal').classList.add('show');
document.body.classList.add('modal-open');
// Form fields will retain their previous values to allow generating new reports
}

function closeStatisticalToolModal() {
const modal = document.getElementById('statisticalToolModal');
modal.classList.add('closing');

// Wait for animation to complete before hiding
setTimeout(() => {
  modal.classList.remove('show', 'closing');
  document.body.classList.remove('modal-open');
}, 500);
}

// Statistical Tool Change Handler
function handleStatToolChange() {
  const statTool = document.getElementById('statTool');
  const fieldInferential = document.getElementById('fieldInferential');
  const statToolInferential = document.getElementById('statToolInferential');
  const fieldSelectionDescriptive = document.getElementById('fieldSelectionDescriptive');
  const fieldSelection = document.getElementById('fieldSelection');
  const fieldSelection1 = document.getElementById('fieldSelection1');
  const fieldSelection2 = document.getElementById('fieldSelection2');
  const fieldCategory = document.getElementById('field0Category');
  const fieldCategory1 = document.getElementById('field1Category');
  const fieldCategory2 = document.getElementById('field2Category');
  const studentInfoField0 = document.getElementById('field0StudentField');
  const studentInfoField1 = document.getElementById('field1StudentField');
  const studentInfoField2 = document.getElementById('field2StudentField');
  const academicProfileField0 = document.getElementById('field0AcademicMetric');
  const academicProfileField1 = document.getElementById('field1AcademicMetric');
  const academicProfileField2 = document.getElementById('field2AcademicMetric');
  const programMetricsField0 = document.getElementById('field0ProgramMetric');
  const programMetricsField1 = document.getElementById('field1ProgramMetric');
  const programMetricsField2 = document.getElementById('field2ProgramMetric');
  const subMetricGroup = document.getElementById("subMetricGroup");
  const subMetricGroup1 = document.getElementById("subMetricGroup1");
  const subMetricGroup2 = document.getElementById("subMetricGroup2");
  const subMetricSelect = document.getElementById("subMetricSelect");
  const subMetricSelect1 = document.getElementById("subMetricSelect1");
  const subMetricSelect2 = document.getElementById("subMetricSelect2");

  // Only reset fields if the statistical tool has actually changed
  // Store the previous values to compare
  if (!window.previousStatTool) {
    window.previousStatTool = '';
  }
  if (!window.previousStatToolInferential) {
    window.previousStatToolInferential = '';
  }
  
  // If neither the statistical tool nor the inferential tool has changed, don't reset the form
  if (statTool.value === window.previousStatTool && statToolInferential.value === window.previousStatToolInferential) {
    return;
  }
  
  // Update the previous values
  window.previousStatTool = statTool.value;
  window.previousStatToolInferential = statToolInferential.value;

  fieldCategory.value = "";
  fieldCategory1.value = "";
  fieldCategory2.value = "";
  studentInfoField0.value = "";
  studentInfoField1.value = "";
  studentInfoField2.value = "";
  academicProfileField0.value = "";
  academicProfileField1.value = "";
  academicProfileField2.value = "";
  programMetricsField0.value = "";
  programMetricsField1.value = "";
  programMetricsField2.value = "";
  subMetricSelect.value = "";
  subMetricSelect1.value = "";
  subMetricSelect2.value = "";
  
  // Reset field tracking
  selectedField1 = { category: '', field: '', subMetric: '' };
  selectedField2 = { category: '', field: '', subMetric: '' };
  
  subMetricGroup.classList.add('hidden');
  subMetricGroup1.classList.add('hidden');
  subMetricGroup2.classList.add('hidden');
  document.getElementById("expectedGroup").classList.add('hidden');
  document.getElementById("expectedForm").innerHTML = '';
  
  
  if (statTool.value == 'descriptive') {
    statToolInferential.value = "";
    fieldSelectionDescriptive.classList.remove('hidden');
    fieldSelection.classList.add('hidden');
    fieldSelection1.classList.add('hidden');
    fieldSelection2.classList.add('hidden');
    fieldInferential.classList.add('hidden');
  } else if (statTool.value == 'inferential') {
    fieldSelectionDescriptive.classList.add('hidden');
    fieldInferential.classList.remove('hidden');
    
    // Only execute specific tool logic if a tool is actually selected
    if (statToolInferential.value && statToolInferential.value !== '') {
      if (statToolInferential.value == 'regression') {
        populateStudentField(studentInfoField1, "");
        populateStudentField(studentInfoField2, "");
        populateAcademicMetric(academicProfileField1, "");
        populateAcademicMetric(academicProfileField2, "");
        populateProgramMetric(programMetricsField1, "");
        populateProgramMetric(programMetricsField2, "");
        fieldSelection.classList.remove('hidden');
        fieldSelection1.classList.remove('hidden');
        fieldSelection2.classList.remove('hidden');
      }
      if (statToolInferential.value == 'pearson') {
        populateStudentField(studentInfoField1, "");
        populateStudentField(studentInfoField2, "");
        populateAcademicMetric(academicProfileField1, "");
        populateAcademicMetric(academicProfileField2, "");
        populateProgramMetric(programMetricsField1, "");
        populateProgramMetric(programMetricsField2, "");
        fieldSelection.classList.remove('hidden');
        fieldSelection1.classList.remove('hidden');
        fieldSelection2.classList.remove('hidden');
      }
      if (statToolInferential.value == 'chiSquareGOF') {
        populateStudentField(studentInfoField1, "");
        populateAcademicMetric(academicProfileField1, "");
        populateProgramMetric(programMetricsField1, "");
        fieldSelection.classList.remove('hidden');
        fieldSelection1.classList.remove('hidden');
        fieldSelection2.classList.add('hidden');
      }
      if (statToolInferential.value == 'chiSquareTOI') {
        populateStudentField(studentInfoField1, "");
        populateStudentField(studentInfoField2, "");
        populateAcademicMetric(academicProfileField1, "");
        populateAcademicMetric(academicProfileField2, "");
        populateProgramMetric(programMetricsField1, "");
        populateProgramMetric(programMetricsField2, "");
        fieldSelection.classList.remove('hidden');
        fieldSelection1.classList.remove('hidden');
        fieldSelection2.classList.remove('hidden');
      }
      if (statToolInferential.value == 'tTestIND') {
        populateStudentField(studentInfoField1, "ind");
        populateStudentField(studentInfoField2, "dep");
        populateAcademicMetric(academicProfileField1, "ind");
        populateAcademicMetric(academicProfileField2, "dep");
        populateProgramMetric(programMetricsField1, "ind");
        populateProgramMetric(programMetricsField2, "dep");
        fieldSelection.classList.remove('hidden');
        fieldSelection1.classList.remove('hidden');
        fieldSelection2.classList.remove('hidden');
      }
      if (statToolInferential.value == 'tTestDEP') {
        // For dependent t-test, hide student info category in both variables
        hideStudentInfoInVariable1();
        hideStudentInfoInVariable2();
        populateAcademicMetric(academicProfileField1, "");
        populateAcademicMetric(academicProfileField2, "");
        populateProgramMetric(programMetricsField1, "");
        populateProgramMetric(programMetricsField2, "");
        fieldSelection.classList.remove('hidden');
        fieldSelection1.classList.remove('hidden');
        fieldSelection2.classList.remove('hidden');
      } else {
        // Restore student info options for other tools (not dependent t-test)
        restoreStudentInfoInVariable1();
        restoreStudentInfoInVariable2();
      }
    }
  } else {
    fieldSelection.classList.add('hidden');
    fieldSelection1.classList.add('hidden');
    fieldSelection2.classList.add('hidden');
    fieldSelectionDescriptive.classList.add('hidden');
    fieldInferential.classList.add('hidden');
  }
}

// Field 0 Category Change Handler
function handleField0CategoryChange() {
  const category = document.getElementById('field0Category').value;
  const studentInfo = document.getElementById('field0StudentInfo');
  const academicProfile = document.getElementById('field0AcademicProfile');
  const programMetrics = document.getElementById('field0ProgramMetrics');
  const subMetricGroup = document.getElementById("subMetricGroup");
  
  // Reset all subsequent fields when category changes
  resetSubsequentFields(0, 'category');
  
  // Hide all field containers
  studentInfo.classList.add('hidden');
  academicProfile.classList.add('hidden');
  programMetrics.classList.add('hidden');
  subMetricGroup.classList.add('hidden');
  
  // Show selected field container
  switch(category) {
      case 'studentInfo':
          studentInfo.classList.remove('hidden');
          subMetricGroup.classList.add('hidden');
          break;
      case 'academicProfile':
          academicProfile.classList.remove('hidden');
          subMetricGroup.classList.add('hidden');
          break;
      case 'programMetrics':
          programMetrics.classList.remove('hidden');
          break;
      default:
          subMetricGroup.classList.add('hidden');
          break;
  }
}

// Field 1 Category Change Handler
function handleField1CategoryChange() {
  const category = document.getElementById('field1Category').value;
  const studentInfo = document.getElementById('field1StudentInfo');
  const academicProfile = document.getElementById('field1AcademicProfile');
  const programMetrics = document.getElementById('field1ProgramMetrics');
  const subMetricGroup = document.getElementById("subMetricGroup1");
  
  // Reset all subsequent fields when category changes
  resetSubsequentFields(1, 'category');
  
  // Hide all field containers
  studentInfo.classList.add('hidden');
  academicProfile.classList.add('hidden');
  programMetrics.classList.add('hidden');
  subMetricGroup.classList.add('hidden');
  document.getElementById("expectedGroup").classList.add('hidden');
  document.getElementById("expectedForm").innerHTML = '';
  
  // Show selected field container
  switch(category) {
      case 'studentInfo':
          studentInfo.classList.remove('hidden');
          subMetricGroup.classList.add('hidden');
          break;
      case 'academicProfile':
          academicProfile.classList.remove('hidden');
          subMetricGroup.classList.add('hidden');
          break;
      case 'programMetrics':
          programMetrics.classList.remove('hidden');
          subMetricGroup.classList.add('hidden');
          break;
      default:
          subMetricGroup.classList.add('hidden');
          break;
  }
  
  // Update variable 2 options when variable 1 category changes
  updateVariable2Options();
  
  // For regression/pearson r: If changing from studentInfo to another category, restore student info in variable 2
  const statToolInferential = document.getElementById('statToolInferential').value;
  if ((statToolInferential === 'regression' || statToolInferential === 'pearson') && category !== 'studentInfo') {
    restoreStudentInfoInVariable2();
  }
}

// Field 2 Category Change Handler
function handleField2CategoryChange() {
  const category = document.getElementById('field2Category').value;
  const studentInfo = document.getElementById('field2StudentInfo');
  const academicProfile = document.getElementById('field2AcademicProfile');
  const programMetrics = document.getElementById('field2ProgramMetrics');
  const subMetricGroup = document.getElementById("subMetricGroup2");
  
  // Reset all subsequent fields when category changes
  resetSubsequentFields(2, 'category');
  
  // Hide all field containers
  studentInfo.classList.add('hidden');
  academicProfile.classList.add('hidden');
  programMetrics.classList.add('hidden');
  subMetricGroup.classList.add('hidden');
  
  // Show selected field container
  switch(category) {
      case 'studentInfo':
          studentInfo.classList.remove('hidden');
          subMetricGroup.classList.add('hidden');
          // Apply filtering to student info options
          setTimeout(() => filterStudentInfoOptions(document.getElementById('field2StudentField')), 100);
          break;
      case 'academicProfile':
          academicProfile.classList.remove('hidden');
          subMetricGroup.classList.add('hidden');
          // Apply filtering to academic profile options
          setTimeout(() => filterAcademicProfileOptions(document.getElementById('field2AcademicMetric')), 100);
          break;
      case 'programMetrics':
          programMetrics.classList.remove('hidden');
          subMetricGroup.classList.add('hidden');
          // Apply filtering to program metrics options
          setTimeout(() => filterProgramMetricsOptions(document.getElementById('field2ProgramMetric')), 100);
          break;
      default:
          subMetricGroup.classList.add('hidden');
          break;
  }
}

  // Helper to create option with value and label
  const createOption = (value, label) => {
      const opt = document.createElement("option");
      opt.value = value;
      opt.textContent = label;
      return opt;
  };

// Student Info Metrics Change Handlers
function handleField0StudentInfoMetricChange() {
  const metric = document.getElementById('field0StudentField').value;
  const subMetricSelect = document.getElementById("subMetricSelect");

  // Reset subsequent fields when metric changes
  resetSubsequentFields(0, 'metric');

  selectedMetric = metric;
  subMetricSelect.onchange = null;
  subMetricSelect.innerHTML = "";

  // Always add "Select"
  subMetricSelect.appendChild(createOption("Select", "Select"));
}

function handleField1StudentInfoMetricChange() {
  const metric = document.getElementById('field1StudentField').value;
  const subMetricSelect = document.getElementById("subMetricSelect1");
  console.log(metric);

  // Reset subsequent fields when metric changes
  resetSubsequentFields(1, 'metric');

  selectedMetric = metric;
  
  // Update field tracking
  updateFieldTracking(1, 'studentInfo', metric);
  
  subMetricSelect.onchange = null;
  subMetricSelect.innerHTML = "";
  document.getElementById("expectedGroup").classList.add('hidden');
  document.getElementById("expectedForm").innerHTML = '';

  // Always add "Select"
  subMetricSelect.appendChild(createOption("Select", "Select"));

  // For regression/pearson r: If age is selected in variable 1, hide student info category in variable 2
  const statToolInferential = document.getElementById('statToolInferential').value;
  if ((statToolInferential === 'regression' || statToolInferential === 'pearson') && metric === 'age') {
    hideStudentInfoInVariable2();
  } else {
    // Restore student info option if not age or different tool
    restoreStudentInfoInVariable2();
  }

  if (document.getElementById('statToolInferential').value == "chiSquareGOF") {
  const session = window.userSession || {};
  const college = session.filter_college;
  const program = session.filter_program;
  const yearBatchStart = session.filter_year_start;
  const yearBatchEnd = session.filter_year_end;
  const boardBatch = session.filter_board_batch;
        
  // Prepare form data
  const formData = new FormData();
  formData.append("college", college);
  formData.append("program", program);
  formData.append("yearBatchStart", yearBatchStart);
  formData.append("yearBatchEnd", yearBatchEnd);
  formData.append("boardBatch", boardBatch);
  formData.append("action", "getCategories");
  formData.append("field", metric);

    fetch(`/bridge/modules/generate_report/processes/chi_square_gof.php`, {
      method: "POST",
      body: formData
    })
    .then(res => res.text())
      .then(html => {
        document.getElementById("expectedForm").innerHTML = html;
        document.getElementById("expectedGroup").classList.remove('hidden');
    });
  }
}

function handleField2StudentInfoMetricChange() {
  const metric = document.getElementById('field2StudentField').value;
  const subMetricSelect = document.getElementById("subMetricSelect2");

  // Reset subsequent fields when metric changes
  resetSubsequentFields(2, 'metric');

  selectedMetric = metric;
  subMetricSelect.onchange = null;
  subMetricSelect.innerHTML = "";

  // Always add "Select"
  subMetricSelect.appendChild(createOption("Select", "Select"));
}

// Academic Profile Metric Change Handlers
function handleField0AcademicMetricChange() {
const session = window.userSession || {};
const metric = document.getElementById('field0AcademicMetric').value;
const subMetricGroup = document.getElementById("subMetricGroup");
const subMetricLabel = document.getElementById("subMetricLabel");
const subMetricSelect = document.getElementById("subMetricSelect");

// Reset subsequent fields when metric changes
resetSubsequentFields(0, 'metric');

selectedMetric = metric;
subMetricSelect.onchange = null;
subMetricSelect.innerHTML = "";
subMetricGroup.classList.add('hidden');

  // Always add "Select"
  subMetricSelect.appendChild(createOption("Select", "Select"));

  if (metric === "GWA") { // BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS
      subMetricLabel.textContent = "Select Year and Semester:";
      populateYearSemester(session.filter_program, subMetricSelect);
      subMetricGroup.classList.remove('hidden');
  }
  if (metric === "BoardGrades") { // BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS
      subMetricLabel.textContent = "Select Board Subject:";
      populateSubjects(session.filter_program, subMetricSelect);
      subMetricGroup.classList.remove('hidden');
  }
  if (metric === "Retakes") { // BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS
      subMetricLabel.textContent = "Select Subject:";
      populateGeneralSubjects(session.filter_program, subMetricSelect);
      subMetricGroup.classList.remove('hidden');
  }
  if (metric === "PerformanceRating") { // BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS
      subMetricLabel.textContent = "Select Category:";
      populateRatings(session.filter_program, subMetricSelect);
      subMetricGroup.classList.remove('hidden');
  }
  if (metric === "SimExam") { // BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS
      subMetricLabel.textContent = "Select Simulation:";
      populateSimulations(session.filter_program, subMetricSelect);
      subMetricGroup.classList.remove('hidden');
  }
}

function handleField1AcademicMetricChange() {
const session = window.userSession || {};
const metric = document.getElementById('field1AcademicMetric').value;
const subMetricGroup = document.getElementById("subMetricGroup1");
const subMetricLabel = document.getElementById("subMetricLabel1");
const subMetricSelect = document.getElementById("subMetricSelect1");

// Reset subsequent fields when metric changes
resetSubsequentFields(1, 'metric');

selectedMetric = metric;

// Update field tracking
updateFieldTracking(1, 'academicProfile', metric);

subMetricSelect.onchange = null;
subMetricSelect.innerHTML = "";
subMetricGroup.classList.add('hidden');
document.getElementById("expectedGroup").classList.add('hidden');
  document.getElementById("expectedForm").innerHTML = '';

  // Always add "Select"
  subMetricSelect.appendChild(createOption("Select", "Select"));

  if (metric === "GWA") { // BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS
      subMetricLabel.textContent = "Select Year and Semester:";
      populateYearSemester(session.filter_program, subMetricSelect);
      subMetricGroup.classList.remove('hidden');
  }
  if (metric === "BoardGrades") { // BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS
      subMetricLabel.textContent = "Select Board Subject:";
      populateSubjects(session.filter_program, subMetricSelect);
      subMetricGroup.classList.remove('hidden');
  }
  if (metric === "Retakes") { // BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS
      subMetricLabel.textContent = "Select Subject:";
      populateGeneralSubjects(session.filter_program, subMetricSelect);
      subMetricGroup.classList.remove('hidden');
  }
  if (metric === "PerformanceRating") { // BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS
      subMetricLabel.textContent = "Select Category:";
      populateRatings(session.filter_program, subMetricSelect);
      subMetricGroup.classList.remove('hidden');
  }
  if (metric === "SimExam") { // BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS
      subMetricLabel.textContent = "Select Simulation:";
      populateSimulations(session.filter_program, subMetricSelect);
      subMetricGroup.classList.remove('hidden');
  }
  if (metric === "Attendance") {
    if (document.getElementById('statToolInferential').value == "chiSquareGOF") {
      const session = window.userSession || {};
      const college = session.filter_college;
      const program = session.filter_program;
      const yearBatchStart = session.filter_year_start;
      const yearBatchEnd = session.filter_year_end;
      const boardBatch = session.filter_board_batch;
            
      // Prepare form data
      const formData = new FormData();
      formData.append("college", college);
      formData.append("program", program);
      formData.append("yearBatchStart", yearBatchStart);
      formData.append("yearBatchEnd", yearBatchEnd);
      formData.append("boardBatch", boardBatch);
      formData.append("action", "getCategories");
      formData.append("field", metric);

        fetch(`/bridge/modules/generate_report/processes/chi_square_gof.php`, {
          method: "POST",
          body: formData
        })
        .then(res => res.text())
          .then(html => {
            document.getElementById("expectedForm").innerHTML = html;
            document.getElementById("expectedGroup").classList.remove('hidden');
        });
    }
  }
}

function handleField2AcademicMetricChange() {
const session = window.userSession || {};
const metric = document.getElementById('field2AcademicMetric').value;
const subMetricGroup = document.getElementById("subMetricGroup2");
const subMetricLabel = document.getElementById("subMetricLabel2");
const subMetricSelect = document.getElementById("subMetricSelect2");

// Reset subsequent fields when metric changes
resetSubsequentFields(2, 'metric');

selectedMetric = metric;
subMetricSelect.onchange = null;
subMetricSelect.innerHTML = "";
subMetricGroup.classList.add('hidden');

  // Always add "Select"
  subMetricSelect.appendChild(createOption("Select", "Select"));

  if (metric === "GWA") { // BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS
      subMetricLabel.textContent = "Select Year and Semester:";
      populateYearSemester(session.filter_program, subMetricSelect);
      subMetricGroup.classList.remove('hidden');
      // Apply sub-metric filtering
      setTimeout(() => filterSubMetricOptions(subMetricSelect), 100);
  }
  if (metric === "BoardGrades") { // BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS
      subMetricLabel.textContent = "Select Board Subject:";
      populateSubjects(session.filter_program, subMetricSelect);
      subMetricGroup.classList.remove('hidden');
      // Apply sub-metric filtering
      setTimeout(() => filterSubMetricOptions(subMetricSelect), 100);
  }
  if (metric === "Retakes") { // BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS
      subMetricLabel.textContent = "Select Subject:";
      populateGeneralSubjects(session.filter_program, subMetricSelect);
      subMetricGroup.classList.remove('hidden');
      // Apply sub-metric filtering
      setTimeout(() => filterSubMetricOptions(subMetricSelect), 100);
  }
  if (metric === "PerformanceRating") { // BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS
      subMetricLabel.textContent = "Select Category:";
      populateRatings(session.filter_program, subMetricSelect);
      subMetricGroup.classList.remove('hidden');
      // Apply sub-metric filtering
      setTimeout(() => filterSubMetricOptions(subMetricSelect), 100);
  }
  if (metric === "SimExam") { // BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS
      subMetricLabel.textContent = "Select Simulation:";
      populateSimulations(session.filter_program, subMetricSelect);
      subMetricGroup.classList.remove('hidden');
      // Apply sub-metric filtering
      setTimeout(() => filterSubMetricOptions(subMetricSelect), 100);
  }
}

// Program Metrics Change Handlers
function handleField0ProgramMetricChange() {
const session = window.userSession || {};
const metric = document.getElementById('field0ProgramMetric').value;
const subMetricGroup = document.getElementById("subMetricGroup");
const subMetricLabel = document.getElementById("subMetricLabel");
const subMetricSelect = document.getElementById("subMetricSelect");

// Reset subsequent fields when metric changes
resetSubsequentFields(0, 'metric');

selectedMetric = metric;
subMetricSelect.onchange = null;
subMetricSelect.innerHTML = "";
subMetricGroup.classList.add('hidden');

  // Always add "Select"
  subMetricSelect.appendChild(createOption("Select", "Select"));

  if (metric === "MockScores") { // BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS
      subMetricLabel.textContent = "Select Mock Subject:";
      populateMockSubjects(session.filter_program, subMetricSelect);
      subMetricGroup.classList.remove('hidden');
  }
  if (metric === "TakeAttempt") { // BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS
      subMetricGroup.classList.add('hidden');
  }
}

function handleField1ProgramMetricChange() {
const session = window.userSession || {};
const metric = document.getElementById('field1ProgramMetric').value;
const subMetricGroup = document.getElementById("subMetricGroup1");
const subMetricLabel = document.getElementById("subMetricLabel1");
const subMetricSelect = document.getElementById("subMetricSelect1");

// Reset subsequent fields when metric changes
resetSubsequentFields(1, 'metric');

selectedMetric = metric;

// Update field tracking
updateFieldTracking(1, 'programMetrics', metric);

subMetricSelect.onchange = null;
subMetricSelect.innerHTML = "";
subMetricGroup.classList.add('hidden');
document.getElementById("expectedGroup").classList.add('hidden');
document.getElementById("expectedForm").innerHTML = '';

  // Always add "Select"
  subMetricSelect.appendChild(createOption("Select", "Select"));

  if (metric === "MockScores") { // BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS
      subMetricLabel.textContent = "Select Mock Subject:";
      populateMockSubjects(session.filter_program, subMetricSelect);
      subMetricGroup.classList.remove('hidden');
  }
  if (metric === "TakeAttempt") { // BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS
      subMetricGroup.classList.add('hidden');
  }
  if (metric === "LicensureResult" || metric === "ReviewCenter") {
    if (document.getElementById('statToolInferential').value == "chiSquareGOF") {
      const session = window.userSession || {};
      const college = session.filter_college;
      const program = session.filter_program;
      const yearBatchStart = session.filter_year_start;
      const yearBatchEnd = session.filter_year_end;
      const boardBatch = session.filter_board_batch;
            
      // Prepare form data
      const formData = new FormData();
      formData.append("college", college);
      formData.append("program", program);
      formData.append("yearBatchStart", yearBatchStart);
      formData.append("yearBatchEnd", yearBatchEnd);
      formData.append("boardBatch", boardBatch);
      formData.append("action", "getCategories");
      formData.append("field", metric);

        fetch(`/bridge/modules/generate_report/processes/chi_square_gof.php`, {
          method: "POST",
          body: formData
        })
        .then(res => res.text())
          .then(html => {
            document.getElementById("expectedForm").innerHTML = html;
            document.getElementById("expectedGroup").classList.remove('hidden');
        });
    }
  }
}

function handleField2ProgramMetricChange() {
const session = window.userSession || {};
const metric = document.getElementById('field2ProgramMetric').value;
const subMetricGroup = document.getElementById("subMetricGroup2");
const subMetricLabel = document.getElementById("subMetricLabel2");
const subMetricSelect = document.getElementById("subMetricSelect2");

// Reset subsequent fields when metric changes
resetSubsequentFields(2, 'metric');

selectedMetric = metric;
subMetricSelect.onchange = null;
subMetricSelect.innerHTML = "";
subMetricGroup.classList.add('hidden');

  // Always add "Select"
  subMetricSelect.appendChild(createOption("Select", "Select"));

  if (metric === "MockScores") { // BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS
      subMetricLabel.textContent = "Select Mock Subject:";
      populateMockSubjects(session.filter_program, subMetricSelect);
      subMetricGroup.classList.remove('hidden');
      // Apply sub-metric filtering
      setTimeout(() => filterSubMetricOptions(subMetricSelect), 100);
  }
  if (metric === "TakeAttempt") { // BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS
      subMetricGroup.classList.add('hidden');document.getElementById("expectedGroup").classList.remove('hidden');
  }
}

// Sub-metric change handlers
function handleField0SubMetricChange(){
  // Reset subsequent fields when sub-metric changes
  resetSubsequentFields(0, 'subMetric');
}

function handleField1SubMetricChange(){
const session = window.userSession || {};
const metric = document.getElementById("subMetricSelect1").value;
const college = session.filter_college;
const program = session.filter_program;
const yearBatchStart = session.filter_year_start;
const yearBatchEnd = session.filter_year_end;
const boardBatch = session.filter_board_batch;
let field1;
const field1Category = document.getElementById('field1Category').value;
  switch(field1Category) {
      case 'academicProfile':
          field1 = document.getElementById('field1AcademicMetric').value;
          break;
      case 'programMetrics':
          field1 = document.getElementById('field1ProgramMetric').value;
          break;
  }
  
  // Reset subsequent fields when sub-metric changes
  resetSubsequentFields(1, 'subMetric');
  
  // Update field tracking with sub-metric
  updateFieldTracking(1, field1Category, field1, metric);
  
console.log("Works");

  if (metric == "Select" || !metric) {
    document.getElementById("expectedGroup").classList.add('hidden');
    document.getElementById("expectedForm").innerHTML = '';
  }

  if (document.getElementById('statToolInferential').value == "chiSquareGOF") {
  // Prepare form data
  const formData = new FormData();
  formData.append("college", college);
  formData.append("program", program);
  formData.append("yearBatchStart", yearBatchStart);
  formData.append("yearBatchEnd", yearBatchEnd);
  formData.append("boardBatch", boardBatch);
  formData.append("action", "getCategories");
  formData.append("field", field1);
  formData.append("sub_metric_1", metric);

    fetch(`/bridge/modules/generate_report/processes/chi_square_gof.php`, {
      method: "POST",
      body: formData
    })
    .then(res => res.text())
      .then(html => {
        document.getElementById("expectedForm").innerHTML = html;
        document.getElementById("expectedGroup").classList.remove('hidden');
    })
    
.catch(err => {
  console.error("Error loading table:", err);
});
  }
}

function handleField2SubMetricChange(){
  // Reset subsequent fields when sub-metric changes
  resetSubsequentFields(2, 'subMetric');
}

const statProcess = {
  "descriptive": "/bridge/modules/generate_report/processes/descriptive_statistics.php",
  "pearson": "/bridge/modules/generate_report/processes/pearson_r.php",
  "regression": "/bridge/modules/generate_report/processes/regression.php",
  "chiSquareGOF": "/bridge/modules/generate_report/processes/chi_square_gof.php",
  "chiSquareTOI": "/bridge/modules/generate_report/processes/chi_square_toi.php",
  "tTestIND": "/bridge/modules/generate_report/processes/t_test_ind.php",
  "tTestDEP": "/bridge/modules/generate_report/processes/t_test_dep.php"
};

// Comprehensive validation function for required fields
function validateRequiredFields(statTool, statToolInferential, field0Category, field0, field1Category, field1, field2Category, field2, subMetricSelect, subMetricSelect1, subMetricSelect2, form) {
  // Basic validation - statTool must be selected
  if (!statTool) {
    return false;
  }

  // Descriptive statistics validation
  if (statTool === 'descriptive') {
    if (!field0Category || !field0) {
      return false;
    }
    
    // Check if sub-metric is required for certain fields
    const requiresSubMetric = ['GWA', 'BoardGrades', 'Retakes', 'PerformanceRating', 'SimExam', 'MockScores'];
    if (requiresSubMetric.includes(field0) && (!subMetricSelect || subMetricSelect === 'Select' || subMetricSelect === '')) {
      return false;
    }
    
    return true;
  }

  // Inferential statistics validation
  if (statTool === 'inferential') {
    if (!statToolInferential) {
      return false;
    }

    // Chi-Square Goodness of Fit validation
    if (statToolInferential === 'chiSquareGOF') {
      if (!field1Category || !field1) {
        return false;
      }
      
      // Check if sub-metric is required
      const requiresSubMetric = ['GWA', 'BoardGrades', 'Retakes', 'PerformanceRating', 'SimExam', 'MockScores'];
      if (requiresSubMetric.includes(field1) && (!subMetricSelect1 || subMetricSelect1 === 'Select' || subMetricSelect1 === '')) {
        return false;
      }
      
      // Check if expected form is required and filled
      if (form && form.querySelector('input[type="number"]')) {
        const numberInputs = form.querySelectorAll('input[type="number"]');
        for (let input of numberInputs) {
          if (!input.value || input.value.trim() === '') {
            return false;
          }
        }
      }
      
      return true;
    }

    // All other inferential tests (regression, pearson, chiSquareTOI, tTestIND, tTestDEP)
    if (!field1Category || !field1 || !field2Category || !field2) {
      return false;
    }
    
    // Check if sub-metrics are required for field1
    const requiresSubMetric = ['GWA', 'BoardGrades', 'Retakes', 'PerformanceRating', 'SimExam', 'MockScores'];
    if (requiresSubMetric.includes(field1) && (!subMetricSelect1 || subMetricSelect1 === 'Select' || subMetricSelect1 === '')) {
      return false;
    }
    
    // Check if sub-metrics are required for field2
    if (requiresSubMetric.includes(field2) && (!subMetricSelect2 || subMetricSelect2 === 'Select' || subMetricSelect2 === '')) {
      return false;
    }
    
    return true;
  }

  return false;
}

// Mapping functions to convert values to display text
function getStatisticalToolDisplayText(value) {
  const toolMap = {
    'descriptive': 'Descriptive Statistics',
    'regression': 'Regression Analysis',
    'pearson': 'Pearson R Correlation',
    'chiSquareGOF': 'Chi Square - Goodness of Fit',
    'chiSquareTOI': 'Chi Square - Test of Independence',
    'tTestIND': 'Independent T Test',
    'tTestDEP': 'Dependent T Test'
  };
  return toolMap[value] || value.toUpperCase();
}

function getFieldDisplayText(category, field) {
  const fieldMaps = {
    'studentInfo': {
      'age': 'Age',
      'socioeconomicStatus': 'Socioeconomic Status',
      'gender': 'Gender',
      'livingArrangement': 'Current Living Arrangement',
      'workStatus': 'Work Status',
      'scholarship': 'Scholarship/Grant',
      'language': 'Language Spoken at Home',
      'lastSchool': 'Last School Attended',
      'studentId': 'Student ID',
      'studentName': 'Student Name',
      'college': 'College',
      'program': 'Program',
      'yearLevel': 'Year Level',
      'section': 'Section',
      'permanentAddress': 'Permanent Address'
    },
    'academicProfile': {
      'GWA': 'GWA',
      'BoardGrades': 'Grades in Board Subjects',
      'Retakes': 'Back Subjects/Retakes',
      'PerformanceRating': 'Performance Rating',
      'SimExam': 'Simulation Exam Results',
      'Attendance': 'Attendance in Review Classes',
      'Recognition': 'Academic Recognition'
    },
    'programMetrics': {
      'MockScores': 'Mock Board Scores',
      'TakeAttempt': 'Number of Exam Attempts',
      'ReviewCenter': 'Student Review Center',
      'LicensureResult': 'Licensure Exam Result',
      'ExameDate': 'Date of Exam Taken'
    }
  };
  
  return fieldMaps[category]?.[field] || field.toUpperCase();
}

// Generate Report Function
function generateReport() {
  const statTool = document.getElementById('statTool').value;
  const statToolInferential = document.getElementById('statToolInferential').value;
  const field0Category = document.getElementById('field0Category').value;
  const field1Category = document.getElementById('field1Category').value;
  const field2Category = document.getElementById('field2Category').value;
  let form = document.getElementById('expectedForm');
  const subMetricSelect = document.getElementById('subMetricSelect').value;
  const subMetricSelect1 = document.getElementById('subMetricSelect1').value;
  const subMetricSelect2 = document.getElementById('subMetricSelect2').value;

  if (statToolInferential == 'chiSquareGOF') {
    const metric = statToolInferential;
    let total = 0;
    new FormData(form).forEach((value, key) => {
        // Attempt to convert the value to an integer
        const num = parseInt(value);

        // Check if the parsed value is a valid number and is not NaN
        if (!isNaN(num)) {
            total += num;
        }
    });
    if (total != 100) {
      showToast(`The sum of expected frequencies must equal 100%. Please adjust your inputs. ${total}% currently.`);
      return;
    }
  }
  
  // Get selected fields based on categories
  let field0, field1, field2;
  
  switch(field0Category) {
      case 'studentInfo':
          field0 = document.getElementById('field0StudentField').value;
          break;
      case 'academicProfile':
          field0 = document.getElementById('field0AcademicMetric').value;
          break;
      case 'programMetrics':
          field0 = document.getElementById('field0ProgramMetric').value;
          break;
  }
  
  switch(field1Category) {
      case 'studentInfo':
          field1 = document.getElementById('field1StudentField').value;
          break;
      case 'academicProfile':
          field1 = document.getElementById('field1AcademicMetric').value;
          break;
      case 'programMetrics':
          field1 = document.getElementById('field1ProgramMetric').value;
          break;
  }
  
  switch(field2Category) {
      case 'studentInfo':
          field2 = document.getElementById('field2StudentField').value;
          break;
      case 'academicProfile':
          field2 = document.getElementById('field2AcademicMetric').value;
          break;
      case 'programMetrics':
          field2 = document.getElementById('field2ProgramMetric').value;
          break;
  }
  
  // Validate selections based on statistical tool
  if (!validateRequiredFields(statTool, statToolInferential, field0Category, field0, field1Category, field1, field2Category, field2, subMetricSelect, subMetricSelect1, subMetricSelect2, form)) {
      showToast('Please select all required fields');
      return;
  }

  // Check for duplicate field selections (only for inferential statistics that require two variables)
  if (statTool === 'inferential' && field1Category === field2Category && field1 === field2) {
    if (subMetricSelect1 === subMetricSelect2 && subMetricSelect1 !== '' && subMetricSelect1 !== 'Select') {
      showToast('Cannot compare the same field and sub-metric. Please select different options for Variable 2.');
      return;
    } else if (subMetricSelect1 === '' && subMetricSelect2 === '') {
      showToast('Cannot compare the same field. Please select different options for Variable 2.');
      return;
    }
  }

const session = window.userSession || {};
const college = session.filter_college;
const program = session.filter_program;
const yearBatchStart = session.filter_year_start;
const yearBatchEnd = session.filter_year_end;
const boardBatch = session.filter_board_batch;
let metric = '';

  
  // Close modal
  closeStatisticalToolModal();
  
  // Show loading state
  const reportSummary = document.getElementById('reportSummary');
  const generatedReport = document.getElementById('generatedReport');
  const generatedReportSummary = document.getElementById('generatedReportSummary');
  const reportHeader = document.querySelector('.report-header');
  const reportFooter = document.querySelector('footer');
  const reportWrapper = document.getElementById('reportWrapper');

  // Hide report content, header, and footer during loading
  if (generatedReport) {
    generatedReport.classList.add('hidden');
    generatedReport.classList.remove('show');
    generatedReport.style.display = 'none'; // Force hide during loading
    console.log('Hiding generated report:', generatedReport);
  }
  if (reportHeader) {
    reportHeader.classList.remove('show');
    reportHeader.style.display = 'none'; // Force hide during loading
    console.log('Hiding report header:', reportHeader);
  }
  if (reportFooter) {
    reportFooter.classList.remove('show');
    reportFooter.style.display = 'none'; // Force hide during loading
    console.log('Hiding report footer:', reportFooter);
  }

  // Prepare form data
const formData = new FormData();
formData.append("college", college);
formData.append("program", program);
formData.append("yearBatchStart", yearBatchStart);
formData.append("yearBatchEnd", yearBatchEnd);
formData.append("boardBatch", boardBatch);

if (statTool == 'descriptive') {
  metric = statTool;
  formData.append("field0", field0);
  formData.append("subMetricSelect", subMetricSelect);
} else if (statToolInferential == 'chiSquareGOF') {
  metric = statToolInferential;
  formData.append("field", field1);
  formData.append("sub_metric_1", subMetricSelect1);
  new FormData(form).forEach((value, key) => {
    formData.append(key, value);
  });
  formData.append("action", "calculate");
} else {
  metric = statToolInferential;
  formData.append("field1", field1);
  formData.append("field2", field2);
  formData.append("subMetricSelect1", subMetricSelect1);
  formData.append("subMetricSelect2", subMetricSelect2);
}

const statisticalTreatment = statProcess[metric];

// Only apply this validation for inferential statistics that require two variables
if (statTool === 'inferential' && ((field1Category == 'studentInfo' && field2Category == 'studentInfo') ||
           (field1 == 'ReviewCenter' && field2 == 'ReviewCenter') ||
           (field1 == 'LicensureResult' && field2 == 'LicensureResult') ||
           (field1 == 'TakeAttempt' && field2 == 'TakeAttempt') ||
           (field1 == 'Attendance' && field2 == 'Attendance'))){
  if (field1 === field2) {
  showToast("Do not compare same values!")
  return;
} else if (subMetricSelect1 === subMetricSelect2 && 
           field1 != 'ReviewCenter' && field2 != 'ReviewCenter' &&
           field1 != 'LicensureResult' && field2 != 'LicensureResult' &&
           field1 != 'TakeAttempt' && field2 != 'TakeAttempt' &&
           field1 != 'Attendance' && field2 != 'Attendance') {
  showToast("Do not compare same values AAAA!")
  return;
} else {

}
}


fetch(statisticalTreatment, {
  method: "POST",
  body: formData
})
.then(res => res.json()) // expect JSON
.then(data => {
  if (!data.success) {
    showToast(data.error);
    return;
  }
  if (program !== session.filter_program) {
    window.location.reload();
  } else {
    console.log('Successful!');
    console.log(data);
    setReportActionsEnabled(true);

    // Show report wrapper and set timestamp
    if (typeof showReportWrapper === 'function') {
      showReportWrapper();
    }

    reportSummary.classList.remove('hidden');
    // Keep generatedReport hidden during loading - it will be shown later

    reportSummary.innerHTML = `
      <div class="loading-container">
        <div class="loading-spinner">
          <div class="spinner-ring"></div>
          <div class="spinner-ring"></div>
          <div class="spinner-ring"></div>
        </div>
        <div class="loading-text">
          <h3>Generating Report</h3>
          <p>Please wait while we process your data...<br>
          Use horizontal scroll or swipe right to view the entire report...</p>
          <div class="loading-dots">
            <span></span>
            <span></span>
            <span></span>
          </div>
        </div>
        <div class="loading-progress">
          <div class="progress-bar">
            <div class="progress-fill"></div>
          </div>
          <span class="progress-text">Processing data...</span>
        </div>
      </div>
    `;

    setTimeout(() => {
        const chartContainer = document.getElementById("reportChart");
        chartContainer.style.width = 100 + "%";
        chartContainer.style.height = "400px"; // keep height fixed, adjust if needed
        
      if (statTool == 'descriptive') {
        const toolDisplay = getStatisticalToolDisplayText(statTool);
        const fieldDisplay = getFieldDisplayText(field0Category, field0);
        reportSummary.innerHTML = `
       
          <p>Statistical Tool: ${toolDisplay}<br>
          Field: ${fieldDisplay}</p>
        `;
        // No animation classes
        if (data.consolidatedData){
          barGraph(data.consolidatedData);
        }
      } else if (statToolInferential == 'chiSquareGOF') {
        const toolDisplay = getStatisticalToolDisplayText(statToolInferential);
        const fieldDisplay = getFieldDisplayText(field1Category, field1);
        reportSummary.innerHTML = `
         
          <p>Statistical Tool: ${toolDisplay}<br>
          Field: ${fieldDisplay}</p>
        `;
        // No animation classes
        chiSquareChart(data.consolidatedData);
      } else {
        const toolDisplay = getStatisticalToolDisplayText(statToolInferential);
        const field1Display = getFieldDisplayText(field1Category, field1);
        const field2Display = getFieldDisplayText(field2Category, field2);
        reportSummary.innerHTML = `
         
          <p>Statistical Tool: ${toolDisplay}<br>
          Field 1: ${field1Display}<br>
          Field 2: ${field2Display}</p>
        `;
        // No animation classes
      }

      if (statToolInferential == 'regression'){
        regressionGraph(data.consolidatedData);
      }

      if (statToolInferential == 'pearson'){
        pearsonGraph(data.consolidatedData);
      }

      if (statToolInferential == 'chiSquareTOI'){
        chiSquareChart(data.consolidatedData);
      }

      if (statToolInferential == 'tTestIND' || statToolInferential == 'tTestDEP'){
        tTestChart(data.consolidatedData);
      }

      generatedReportSummary.innerHTML = data.htmlDisplay;
      
      // Apply compact styling to tables with 1-2 columns
      setTimeout(() => {
        applyCompactTableStyling();
      }, 100);
      
        // Show report elements without animations
      setTimeout(() => {
        const reportWrapper = document.getElementById('reportWrapper');
        const reportSummary = document.getElementById('reportSummary');
        const generatedReport = document.getElementById('generatedReport');
        const reportHeader = document.querySelector('.report-header');
        const reportFooter = document.querySelector('footer');
        
        if (reportWrapper) {
          reportWrapper.classList.add('show');
        }
        
        if (reportSummary) {
          // No animation classes
        }
        
        // Show hidden report elements
        if (reportHeader) {
          reportHeader.classList.add('show');
          reportHeader.style.display = ''; // Remove inline style to use CSS
          console.log('Showing report header:', reportHeader);
        }
        
        if (reportFooter) {
          reportFooter.classList.add('show');
          reportFooter.style.display = ''; // Remove inline style to use CSS
          console.log('Showing report footer:', reportFooter);
        }
        
        // Show report meta info (title and generated info)
        const reportMetaInfo = document.getElementById('reportMetaInfo');
        if (reportMetaInfo) {
          reportMetaInfo.classList.remove('hidden');
          reportMetaInfo.classList.add('show');
          reportMetaInfo.style.display = ''; // Remove inline style to use CSS
          console.log('Showing report meta info:', reportMetaInfo);
        }
        
        if (generatedReport) {
          generatedReport.classList.remove('hidden');
          generatedReport.classList.add('show');
          generatedReport.style.display = ''; // Remove inline style to use CSS
          console.log('Showing generated report:', generatedReport);
          
          // Show scroll notification for small screens after report is displayed
          setTimeout(() => {
            if (!hasUserDismissedNotification()) {
              showScrollNotification();
            }
          }, 500);
        }
      }, 100);
    }, 3000);
  }
})
.catch(err => {
  console.error("Error loading table:", err);
});
  

}

// Close modal when clicking outside
window.onclick = function(event) {
  const modal = document.getElementById('statisticalToolModal');
  if (event.target == modal) {
      closeStatisticalToolModal();
  }
}




// Force opacity for export/print operations
function forceOpacityForExport() {
  const elements = document.querySelectorAll('.section, .generated-report, .report-summary, #reportSummary, #generatedReport, .report-content, .report-header, .report-footer');
  elements.forEach(el => {
    el.style.opacity = '1';
    el.style.visibility = 'visible';
    el.style.display = 'block';
    el.style.animation = 'none';
    el.style.transition = 'none';
    el.style.transform = 'none';
  });
}

// Restore opacity after export/print operations
function restoreOpacityAfterExport() {
  const elements = document.querySelectorAll('.section, .generated-report, .report-summary, #reportSummary, #generatedReport, .report-content, .report-header, .report-footer');
  elements.forEach(el => {
    el.style.opacity = '';
    el.style.visibility = '';
    el.style.display = '';
    el.style.animation = '';
    el.style.transition = '';
    el.style.transform = '';
  });
}

function printReportContent() {
  try {
      const container = document.getElementById('reportContainer');
      const frozenWidth = container ? container.offsetWidth : 0;
      if (frozenWidth) {
          document.documentElement.style.setProperty('--export-width-px', frozenWidth + 'px');
          document.documentElement.classList.add('export-freeze');
      }
      
      // Force opacity fix for print
      forceOpacityForExport();
      
  } catch (e) {}
  window.print();
  // Best-effort cleanup after print
  setTimeout(() => {
      document.documentElement.classList.remove('export-freeze');
      document.documentElement.style.removeProperty('--export-width-px');
      restoreOpacityAfterExport();
  }, 0);
}

/*EXPORT LOGIC*/
function exportReport(type) {
  const content = document.getElementById('reportContainer');

  if (type === 'pdf') {
      exportReportToPdfSnapshot();
  } else if (type === 'word') {
      // Export only the report summary area with matching styles
      const reportSummaryEl = document.getElementById('reportSummary');
      const reportHTML = reportSummaryEl ? reportSummaryEl.outerHTML : '<p>No report available.</p>';

      const styles = `
        body { font-family: Montserrat, Arial, sans-serif; color: #000; }
        .result-box { background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 15px; border-left: 5px solid #5c297c; }
        .generated-report { margin-top: 20px; }
        .generated-report h3 { color: #5c297c; margin-bottom: 10px; }
        .report-summary { background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px; }
        .chart { background-color: #ffffff; padding: 15px; border-radius: 8px; box-shadow: none; }
        .no-export { display: none !important; }
        /* FIX OPACITY ISSUES FOR WORD EXPORT */
        .report-wrapper, .report-content, .report-header, .report-footer, .generated-report, .report-summary, #report, #reportSummary, #generatedReport, .section, .chart, #reportChart, table, th, td, h1, h2, h3, h4, h5, h6, p, div, span {
          opacity: 1 !important;
          transform: none !important;
          animation: none !important;
          transition: none !important;
          visibility: visible !important;
          display: block !important;
        }
        table { display: table !important; opacity: 1 !important; }
        th, td { display: table-cell !important; opacity: 1 !important; }
      `;

      const docHTML = `<!DOCTYPE html>
        <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
        <head>
          <meta charset='utf-8'>
          <title>Report</title>
          <style>${styles}</style>
        </head>
        <body>${reportHTML}</body>
        </html>`;

      const blob = new Blob([docHTML], { type: 'application/msword;charset=utf-8' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'report.doc';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
  }
}

// Snapshot-based PDF export: capture the current layout as seen on laptop and paginate into Letter PDF
function exportReportToPdfSnapshot() {
  const target = document.getElementById('reportContainer');
  if (!target) return;

  // Freeze width to current on-screen size to prevent any reflow
  const frozenWidth = target.offsetWidth;
  if (frozenWidth) {
      document.documentElement.style.setProperty('--export-width-px', frozenWidth + 'px');
      document.documentElement.classList.add('export-freeze');
  }
  // Apply print-visibility styling (hide UI, solid colors) without changing flow
  document.documentElement.classList.add('pdf-export');
  
  // Force opacity fix for PDF export
  forceOpacityForExport();

  // Small timeout to allow styles to apply
  requestAnimationFrame(async () => {
      try {
          const canvas = await html2canvas(target, {
              scale: 2,
              useCORS: true,
              backgroundColor: '#ffffff',
              logging: false,
              scrollX: 0,
              scrollY: 0,
              windowWidth: document.documentElement.clientWidth
          });

          const imgData = canvas.toDataURL('image/jpeg', 0.98);
          const pdf = new jspdf.jsPDF({ unit: 'in', format: 'letter', orientation: 'portrait' });

          const marginIn = 0.5;
          const pageWidthIn = 8.5 - marginIn * 2;  // 7.5in usable width
          const pageHeightIn = 11 - marginIn * 2;  // 10in usable height

          // Convert pixels to inches assuming 96 DPI for CSS pixels
          const pxToIn = (px) => px / 96;
          const imgWidthIn = pageWidthIn; // fit to width
          const imgHeightIn = pxToIn(canvas.height) * (imgWidthIn / pxToIn(canvas.width));

          let y = marginIn;
          let remainingHeightIn = imgHeightIn;
          const sliceHeightIn = pageHeightIn;

          if (imgHeightIn <= pageHeightIn) {
              pdf.addImage(imgData, 'JPEG', marginIn, y, imgWidthIn, imgHeightIn, undefined, 'FAST');
          } else {
              // Slice the tall image into page-height chunks by drawing portions
              const pageHeightPx = Math.floor(sliceHeightIn * 96 * (pxToIn(canvas.width) / imgWidthIn));
              let sY = 0;
              while (remainingHeightIn > 0) {
                  const sliceCanvas = document.createElement('canvas');
                  sliceCanvas.width = canvas.width;
                  sliceCanvas.height = Math.min(pageHeightPx, canvas.height - sY);
                  const ctx = sliceCanvas.getContext('2d');
                  ctx.drawImage(canvas, 0, sY, canvas.width, sliceCanvas.height, 0, 0, canvas.width, sliceCanvas.height);
                  const sliceData = sliceCanvas.toDataURL('image/jpeg', 0.98);
                  const sliceHeightInReal = pxToIn(sliceCanvas.height) * (imgWidthIn / pxToIn(canvas.width));

                  pdf.addImage(sliceData, 'JPEG', marginIn, marginIn, imgWidthIn, sliceHeightInReal, undefined, 'FAST');

                  remainingHeightIn -= sliceHeightIn;
                  sY += sliceCanvas.height;
                  if (remainingHeightIn > 0) pdf.addPage();
              }
          }

          pdf.save('report.pdf');
      } finally {
          document.documentElement.classList.remove('pdf-export');
          document.documentElement.classList.remove('export-freeze');
          document.documentElement.style.removeProperty('--export-width-px');
          restoreOpacityAfterExport();
      }
  });
}

document.addEventListener('DOMContentLoaded', function () {
const exportDropdownBtn = document.getElementById('exportDropdownBtn');
const exportDropdown = exportDropdownBtn.closest('.export-dropdown');
const exportMenu = document.getElementById('exportMenu');

exportDropdownBtn.addEventListener('click', function (e) {
  e.stopPropagation();
  const disabled = this.disabled || this.classList.contains('disabled');
  if (disabled) return; // prevent opening when disabled
  exportDropdown.classList.toggle('open');
});

// Close dropdown when clicking outside
document.addEventListener('click', function (e) {
  if (!exportDropdown.contains(e.target)) {
    exportDropdown.classList.remove('open');
  }
});

// Standard browser validation for navigation
window.addEventListener('beforeunload', function (e) {
  // Don't block navigation if session has expired
  if (window.sessionExpired) {
    return;
  }
  
  if (reportExistsInDOM()) {
    e.preventDefault();
    e.returnValue = '';
    return '';
  }
});
});

/******************************* FILTER BUTTON ***************************/
let programOptions = {};
let yearLevelOptions = {};
let collegeOptions = [];
let subjectOptions = {};
let genSubOptions = {};
let categoryOptions = {};
let simulationOptions = {};
let awardOptions = {};
let mockSubjectOptions = [];
let arrangementOptions = [];
let languageOptions = [];

// Initial fetch of data from PHP
fetch("/bridge/populate_filter.php")
.then(res => res.json())
.then(data => {
console.log("Fetched JSON:", data);
  collegeOptions = data.collegeOptions;
  programOptions = data.programOptions;
  yearLevelOptions = data.yearLevelOptions;
  subjectOptions = data.subjectOptions;
  genSubOptions = data.genSubOptions;
  categoryOptions = data.categoryOptions;
  simulationOptions = data.simulationOptions;
  awardOptions = data.awardOptions;
  mockSubjectOptions = data.mockSubjectOptions;
  arrangementOptions = data.arrangementOptions;
  languageOptions = data.languageOptions;
});

let selectedMetric = "";

// Field tracking system to prevent duplicate selections
let selectedField1 = {
  category: '',
  field: '',
  subMetric: ''
};

let selectedField2 = {
  category: '',
  field: '',
  subMetric: ''
};

// Cascading reset system - resets all subsequent comboboxes when a higher-level selection changes
function resetSubsequentFields(fieldNumber, resetFromLevel) {
  if (fieldNumber === 0) {
    // Reset Field 0 sub-metrics and dependent fields
    if (resetFromLevel === 'category') {
      // Reset metric selection
      const field0StudentField = document.getElementById('field0StudentField');
      const field0AcademicMetric = document.getElementById('field0AcademicMetric');
      const field0ProgramMetric = document.getElementById('field0ProgramMetric');
      
      if (field0StudentField) field0StudentField.value = "";
      if (field0AcademicMetric) field0AcademicMetric.value = "";
      if (field0ProgramMetric) field0ProgramMetric.value = "";
      
      // Reset sub-metric
      const subMetricSelect = document.getElementById("subMetricSelect");
      if (subMetricSelect) {
        subMetricSelect.value = "";
        subMetricSelect.innerHTML = '<option value="" disabled>Select</option>';
      }
      
      // Hide sub-metric group
      const subMetricGroup = document.getElementById("subMetricGroup");
      if (subMetricGroup) subMetricGroup.classList.add('hidden');
    }
    
    if (resetFromLevel === 'metric') {
      // Reset sub-metric
      const subMetricSelect = document.getElementById("subMetricSelect");
      if (subMetricSelect) {
        subMetricSelect.value = "";
        subMetricSelect.innerHTML = '<option value="" disabled>Select</option>';
      }
      
      // Hide sub-metric group
      const subMetricGroup = document.getElementById("subMetricGroup");
      if (subMetricGroup) subMetricGroup.classList.add('hidden');
    }
  }
  
  if (fieldNumber === 1) {
    // Reset Field 1 sub-metrics and dependent fields
    if (resetFromLevel === 'category') {
      // Reset metric selection
      const field1StudentField = document.getElementById('field1StudentField');
      const field1AcademicMetric = document.getElementById('field1AcademicMetric');
      const field1ProgramMetric = document.getElementById('field1ProgramMetric');
      
      if (field1StudentField) field1StudentField.value = "";
      if (field1AcademicMetric) field1AcademicMetric.value = "";
      if (field1ProgramMetric) field1ProgramMetric.value = "";
      
      // Reset sub-metric
      const subMetricSelect1 = document.getElementById("subMetricSelect1");
      if (subMetricSelect1) {
        subMetricSelect1.value = "";
        subMetricSelect1.innerHTML = '<option value="" disabled>Select</option>';
      }
      
      // Hide sub-metric group and expected form
      const subMetricGroup1 = document.getElementById("subMetricGroup1");
      if (subMetricGroup1) subMetricGroup1.classList.add('hidden');
      const expectedGroup = document.getElementById("expectedGroup");
      if (expectedGroup) expectedGroup.classList.add('hidden');
      const expectedForm = document.getElementById("expectedForm");
      if (expectedForm) expectedForm.innerHTML = '';
      
      // Reset field tracking
      selectedField1 = { category: '', field: '', subMetric: '' };
    }
    
    if (resetFromLevel === 'metric') {
      // Reset sub-metric
      const subMetricSelect1 = document.getElementById("subMetricSelect1");
      if (subMetricSelect1) {
        subMetricSelect1.value = "";
        subMetricSelect1.innerHTML = '<option value="" disabled>Select</option>';
      }
      
      // Hide sub-metric group and expected form
      const subMetricGroup1 = document.getElementById("subMetricGroup1");
      if (subMetricGroup1) subMetricGroup1.classList.add('hidden');
      const expectedGroup = document.getElementById("expectedGroup");
      if (expectedGroup) expectedGroup.classList.add('hidden');
      const expectedForm = document.getElementById("expectedForm");
      if (expectedForm) expectedForm.innerHTML = '';
      
      // Update field tracking
      selectedField1.field = '';
      selectedField1.subMetric = '';
    }
    
    if (resetFromLevel === 'subMetric') {
      // Hide expected form
      const expectedGroup = document.getElementById("expectedGroup");
      if (expectedGroup) expectedGroup.classList.add('hidden');
      const expectedForm = document.getElementById("expectedForm");
      if (expectedForm) expectedForm.innerHTML = '';
      
      // Update field tracking
      selectedField1.subMetric = '';
    }
    
    // Update variable 2 options when variable 1 changes
    updateVariable2Options();
  }
  
  if (fieldNumber === 2) {
    // Reset Field 2 sub-metrics and dependent fields
    if (resetFromLevel === 'category') {
      // Reset metric selection
      const field2StudentField = document.getElementById('field2StudentField');
      const field2AcademicMetric = document.getElementById('field2AcademicMetric');
      const field2ProgramMetric = document.getElementById('field2ProgramMetric');
      
      if (field2StudentField) field2StudentField.value = "";
      if (field2AcademicMetric) field2AcademicMetric.value = "";
      if (field2ProgramMetric) field2ProgramMetric.value = "";
      
      // Reset sub-metric
      const subMetricSelect2 = document.getElementById("subMetricSelect2");
      if (subMetricSelect2) {
        subMetricSelect2.value = "";
        subMetricSelect2.innerHTML = '<option value="" disabled>Select</option>';
      }
      
      // Hide sub-metric group
      const subMetricGroup2 = document.getElementById("subMetricGroup2");
      if (subMetricGroup2) subMetricGroup2.classList.add('hidden');
      
      // Reset field tracking
      selectedField2 = { category: '', field: '', subMetric: '' };
    }
    
    if (resetFromLevel === 'metric') {
      // Reset sub-metric
      const subMetricSelect2 = document.getElementById("subMetricSelect2");
      if (subMetricSelect2) {
        subMetricSelect2.value = "";
        subMetricSelect2.innerHTML = '<option value="" disabled>Select</option>';
      }
      
      // Hide sub-metric group
      const subMetricGroup2 = document.getElementById("subMetricGroup2");
      if (subMetricGroup2) subMetricGroup2.classList.add('hidden');
      
      // Update field tracking
      selectedField2.field = '';
      selectedField2.subMetric = '';
    }
    
    if (resetFromLevel === 'subMetric') {
      // Update field tracking
      selectedField2.subMetric = '';
    }
  }
}

// Function to update field tracking
function updateFieldTracking(fieldNumber, category, field, subMetric = '') {
  if (fieldNumber === 1) {
    selectedField1 = { category, field, subMetric };
  } else if (fieldNumber === 2) {
    selectedField2 = { category, field, subMetric };
  }
  
  // Update variable 2 options when variable 1 changes
  if (fieldNumber === 1) {
    updateVariable2Options();
  }
}

// Function to check if a field is already selected in variable 1
function isFieldSelectedInVariable1(category, field, subMetric = '') {
  return selectedField1.category === category && 
         selectedField1.field === field && 
         selectedField1.subMetric === subMetric;
}

// Function to update variable 2 options based on variable 1 selection
function updateVariable2Options() {
  const field2Category = document.getElementById('field2Category').value;
  const field2StudentField = document.getElementById('field2StudentField');
  const field2AcademicMetric = document.getElementById('field2AcademicMetric');
  const field2ProgramMetric = document.getElementById('field2ProgramMetric');
  
  if (field2Category === 'studentInfo') {
    filterStudentInfoOptions(field2StudentField);
  } else if (field2Category === 'academicProfile') {
    filterAcademicProfileOptions(field2AcademicMetric);
    // Also filter sub-metrics if they exist
    const subMetricSelect2 = document.getElementById('subMetricSelect2');
    if (subMetricSelect2 && !subMetricSelect2.classList.contains('hidden')) {
      filterSubMetricOptions(subMetricSelect2);
    }
  } else if (field2Category === 'programMetrics') {
    filterProgramMetricsOptions(field2ProgramMetric);
    // Also filter sub-metrics if they exist
    const subMetricSelect2 = document.getElementById('subMetricSelect2');
    if (subMetricSelect2 && !subMetricSelect2.classList.contains('hidden')) {
      filterSubMetricOptions(subMetricSelect2);
    }
  }
}

// Function to filter sub-metric options
function filterSubMetricOptions(selectElement) {
  if (!selectElement) return;
  
  const options = selectElement.querySelectorAll('option');
  options.forEach(option => {
    if (option.value && option.value !== '' && option.value !== 'Select') {
      const shouldHide = isFieldSelectedInVariable1(selectedField1.category, selectedField1.field, option.value);
      option.style.display = shouldHide ? 'none' : 'block';
      option.disabled = shouldHide;
      
      // If the currently selected option should be hidden, reset the selection
      if (shouldHide && option.selected) {
        selectElement.value = '';
      }
    }
  });
}

// Function to filter student info options
function filterStudentInfoOptions(selectElement) {
  if (!selectElement) return;
  
  const options = selectElement.querySelectorAll('option');
  options.forEach(option => {
    if (option.value && option.value !== '') {
      const shouldHide = isFieldSelectedInVariable1('studentInfo', option.value);
      option.style.display = shouldHide ? 'none' : 'block';
      option.disabled = shouldHide;
      
      // If the currently selected option should be hidden, reset the selection
      if (shouldHide && option.selected) {
        selectElement.value = '';
      }
    }
  });
}

// Function to filter academic profile options
function filterAcademicProfileOptions(selectElement) {
  if (!selectElement) return;
  
  const options = selectElement.querySelectorAll('option');
  options.forEach(option => {
    if (option.value && option.value !== '') {
      const shouldHide = isFieldSelectedInVariable1('academicProfile', option.value);
      option.style.display = shouldHide ? 'none' : 'block';
      option.disabled = shouldHide;
      
      // If the currently selected option should be hidden, reset the selection
      if (shouldHide && option.selected) {
        selectElement.value = '';
      }
    }
  });
}

// Function to filter program metrics options
function filterProgramMetricsOptions(selectElement) {
  if (!selectElement) return;
  
  const options = selectElement.querySelectorAll('option');
  options.forEach(option => {
    if (option.value && option.value !== '') {
      const shouldHide = isFieldSelectedInVariable1('programMetrics', option.value);
      option.style.display = shouldHide ? 'none' : 'block';
      option.disabled = shouldHide;
      
      // If the currently selected option should be hidden, reset the selection
      if (shouldHide && option.selected) {
        selectElement.value = '';
      }
    }
  });
}

// Helper function to hide student info in variable 2
function hideStudentInfoInVariable2() {
  const field2Category = document.getElementById('field2Category');
  const field2StudentInfo = document.getElementById('field2StudentInfo');
  
  // Hide student info option in variable 2 category dropdown
  const studentInfoOption = field2Category.querySelector('option[value="studentInfo"]');
  if (studentInfoOption) {
    studentInfoOption.style.display = 'none';
    studentInfoOption.disabled = true;
  }
  
  // If student info was selected in variable 2, reset it
  if (field2Category.value === 'studentInfo') {
    field2Category.value = '';
    field2StudentInfo.classList.add('hidden');
  }
}

// Helper function to restore student info in variable 2
function restoreStudentInfoInVariable2() {
  const field2Category = document.getElementById('field2Category');
  const studentInfoOption = field2Category.querySelector('option[value="studentInfo"]');
  if (studentInfoOption) {
    studentInfoOption.style.display = 'block';
    studentInfoOption.disabled = false;
  }
}

// Helper function to hide student info in variable 1
function hideStudentInfoInVariable1() {
  const field1Category = document.getElementById('field1Category');
  const field1StudentInfo = document.getElementById('field1StudentInfo');
  
  // Hide student info option in variable 1 category dropdown
  const studentInfoOption = field1Category.querySelector('option[value="studentInfo"]');
  if (studentInfoOption) {
    studentInfoOption.style.display = 'none';
    studentInfoOption.disabled = true;
  }
  
  // If student info was selected in variable 1, reset it
  if (field1Category.value === 'studentInfo') {
    field1Category.value = '';
    field1StudentInfo.classList.add('hidden');
  }
}

// Helper function to restore student info in variable 1
function restoreStudentInfoInVariable1() {
  const field1Category = document.getElementById('field1Category');
  const studentInfoOption = field1Category.querySelector('option[value="studentInfo"]');
  if (studentInfoOption) {
    studentInfoOption.style.display = 'block';
    studentInfoOption.disabled = false;
  }
}

function populateMockSubjects(programId, subjectSelect) {
subjectSelect.innerHTML = '<option value="" disabled>Select Subject</option>';

if (subjectSelect){
  if (mockSubjectOptions[programId]) {
      mockSubjectOptions[programId].forEach(subject => {
        const option = document.createElement("option");
        option.text = subject.name;
        option.value = subject.id;
        subjectSelect.add(option);
    });
  }
}
  subjectSelect.value = "";
}

function populateYearSemester(programId, ysSelect) {
ysSelect.innerHTML = `<option value="" disabled>Select Year and Semester</option>
                      <option value="all_gwa">Average GWA</option>`;
let semText = "";

if (ysSelect){
  if (yearLevelOptions[programId]) {
    yearLevelOptions[programId].forEach(year => {
      for(let sem = 1; sem <= 2; sem++) {
        if (sem == 1) {
          semText = "1ST"
        } else {
          semText = "2ND"
        }
        let option = document.createElement("option");
        option.text = `${year.name} - ${semText} SEMESTER`;
        option.value = `${year.id}Y_${sem}S`;
        ysSelect.add(option);
      }
    });
  }
}
  ysSelect.value = "";
}

function populateSubjects(programId, subjectSelect) {
subjectSelect.innerHTML = '<option value="" disabled>Select Subject</option>';

if (subjectSelect){
  if (subjectOptions[programId]) {
    subjectOptions[programId].forEach(subject => {
        const option = document.createElement("option");
        option.text = subject.name;
        option.value = subject.id;
        subjectSelect.add(option);
    });
  }
}
  subjectSelect.value = "";
}

function populateGeneralSubjects(programId, genSubSelect) {
genSubSelect.innerHTML = '<option value="" disabled>Select Subject</option>';

if (genSubSelect){
  if (genSubOptions[programId]) {
    genSubOptions[programId].forEach(subject => {
        const option = document.createElement("option");
        option.text = subject.name;
        option.value = subject.id;
        genSubSelect.add(option);
    });
  }
}
  genSubSelect.value = "";
}

function populateRatings(programId, ratingSelect) {
ratingSelect.innerHTML = '<option value="" disabled>Select Category</option>';

if (ratingSelect){
  if (categoryOptions[programId]) {
    categoryOptions[programId].forEach(subject => {
        const option = document.createElement("option");
        option.text = subject.name;
        option.value = subject.id;
        ratingSelect.add(option);
    });
  }
}
  ratingSelect.value = "";
}

function populateSimulations(programId, simulationSelect) {
simulationSelect.innerHTML = '<option value="" disabled>Select Simulation</option>';

if (simulationSelect){
  if (simulationOptions[programId]) {
    simulationOptions[programId].forEach(subject => {
        const option = document.createElement("option");
        option.text = subject.name;
        option.value = subject.id;
        simulationSelect.add(option);
    });
  }
}
  simulationSelect.value = "";
}

function populateArrangements(arrangementSelect) {
arrangementSelect.innerHTML = '<option value="" disabled>Select Living Arrangement</option>';

if (arrangementSelect){
  if (arrangementOptions) {
    arrangementOptions.forEach(arrangement => {
        const option = document.createElement("option");
        option.text = arrangement.name;
        option.value = arrangement.id;
        arrangementSelect.add(option);
    });
  }
}
  arrangementSelect.value = "";
}

function populateLanguages(languageSelect) {
languageSelect.innerHTML = '<option value="" disabled>Select Language</option>';

if (languageSelect){
  if (languageOptions) {
    languageOptions.forEach(language => {
        const option = document.createElement("option");
        option.text = language.name;
        option.value = language.id;
        languageSelect.add(option);
    });
  }
}
  languageSelect.value = "";
}

function populateStudentField(studentInfoField, var_type) {
  const statToolInferential = document.getElementById('statToolInferential').value;
  const session = window.userSession || {};
  const yearRange = session.filter_year_end-session.filter_year_start;

  if (statToolInferential == 'regression' || statToolInferential == 'pearson') {
    studentInfoField.innerHTML = `<option value="" disabled>Select</option>
                                  <option value="age">Age</option>`;
  } else if (statToolInferential == 'chiSquareGOF') {
    studentInfoField.innerHTML = `<option value="" disabled>Select</option>
                                  <option value="gender">Gender</option>
                                  <option value="socioeconomicStatus">Socioeconomic Status</option>
                                  <option value="livingArrangement">Current Living Arrangement</option>
                                  <option value="workStatus">Work Status</option>
                                  <option value="scholarship">Scholarship/Grant</option>
                                  <option value="language">Language Spoken at Home</option>
                                  <option value="lastSchool">Last School Attended</option>`;
  } else if (statToolInferential == 'chiSquareTOI') {
    studentInfoField.innerHTML = `<option value="" disabled>Select</option>
                                  <option value="gender">Gender</option>
                                  <option value="socioeconomicStatus">Socioeconomic Status</option>
                                  <option value="livingArrangement">Current Living Arrangement</option>
                                  <option value="workStatus">Work Status</option>
                                  <option value="scholarship">Scholarship/Grant</option>
                                  <option value="language">Language Spoken at Home</option>
                                  <option value="lastSchool">Last School Attended</option>`;
  } else if (statToolInferential == 'tTestIND' && var_type == "ind") {
    studentInfoField.innerHTML = `<option value="" disabled>Select</option>
                                  <option value="gender">Gender</option>
                                  <option value="scholarship">Scholarship/Grant</option>
                                  <option value="language">Language Spoken at Home</option>
                                  <option value="lastSchool">Last School Attended</option>`;
                                  if (yearRange == 1) {studentInfoField.innerHTML += `<option value="year">Batch Year</option>`;}
  } else if (statToolInferential == 'tTestIND' && var_type == "dep") {
    studentInfoField.innerHTML = `<option value="" disabled>Select</option>
                                  <option value="age">Age</option>`;
  } else {
    studentInfoField.innerHTML = `<option value="" disabled>Select</option>`;
  }
  studentInfoField.value = "";
  
  // Apply filtering if this is field 2
  if (studentInfoField.id === 'field2StudentField') {
    setTimeout(() => filterStudentInfoOptions(studentInfoField), 100);
  }
}

function populateAcademicMetric(academicProfileField, var_type) {
  const statToolInferential = document.getElementById('statToolInferential').value;

  if (statToolInferential == 'regression' || statToolInferential == 'pearson') {
    academicProfileField.innerHTML = `<option value="" disabled>Select</option>
                                      <option value="GWA">GWA</option>
                                      <option value="BoardGrades">Grades in Board Subjects</option>
                                      <option value="Retakes">Back Subjects/Retakes</option>
                                      <option value="PerformanceRating">Performance Rating</option>
                                      <option value="SimExam">Simulation Exam Results</option>
                                      <option value="Attendance">Attendance in Review Classes</option>
                                      <option value="Recognition">Academic Recognition</option>`;
  } else if (statToolInferential == 'chiSquareGOF') {
    academicProfileField.innerHTML = `<option value="" disabled>Select</option>
                                     <option value="BoardGrades">Grades in Board Subjects</option>
                                      <option value="PerformanceRating">Performance Rating</option>
                                      <option value="SimExam">Simulation Exam Results</option>
                                      <option value="Attendance">Attendance in Review Classes</option>`;
  } else if (statToolInferential == 'chiSquareTOI') {
    academicProfileField.innerHTML = `<option value="" disabled>Select</option>
                                     <option value="BoardGrades">Grades in Board Subjects</option>
                                      <option value="PerformanceRating">Performance Rating</option>
                                      <option value="SimExam">Simulation Exam Results</option>
                                      <option value="Attendance">Attendance in Review Classes</option>`;
  } else if (statToolInferential == 'tTestIND' && var_type == "ind") {
    academicProfileField.innerHTML = `<option value="">Select</option>
                                      <option value="Retakes">Back Subjects/Retakes</option>`;
  } else if (statToolInferential == 'tTestIND' && var_type == "dep") {
    academicProfileField.innerHTML = `<option value="" disabled>Select</option>
                                     <option value="BoardGrades">Grades in Board Subjects</option>
                                      <option value="PerformanceRating">Performance Rating</option>
                                      <option value="SimExam">Simulation Exam Results</option>
                                      <option value="Attendance">Attendance in Review Classes</option>`;
  } else if (statToolInferential == 'tTestDEP') {
    academicProfileField.innerHTML = `<option value="" disabled>Select</option>
                                     <option value="BoardGrades">Grades in Board Subjects</option>
                                      <option value="PerformanceRating">Performance Rating</option>
                                      <option value="SimExam">Simulation Exam Results</option>`;
  } else  {
    academicProfileField.innerHTML = `<option value="" disabled>Select</option>`;
  }
  academicProfileField.value = "";
  
  // Apply filtering if this is field 2
  if (academicProfileField.id === 'field2AcademicMetric') {
    setTimeout(() => filterAcademicProfileOptions(academicProfileField), 100);
  }
}

function populateProgramMetric(programMetricsField, var_type) {
  const statToolInferential = document.getElementById('statToolInferential').value;
  
  if (statToolInferential == 'regression' || statToolInferential == 'pearson') {
    programMetricsField.innerHTML = `<option value="" disabled>Select</option>
                                     <option value="MockScores">Mock Board Scores</option>
                                     <option value="TakeAttempt">Number of Exam Attempts</option>`;
  } else if (statToolInferential == 'chiSquareGOF') {
    programMetricsField.innerHTML = `<option value="" disabled>Select</option>
                                     <option value="ReviewCenter">Student Review Center</option>
                                     <option value="MockScores">Mock Board Scores</option>
                                     <option value="LicensureResult">Licensure Exam Result</option>`;
  } else if (statToolInferential == 'chiSquareTOI') {
    programMetricsField.innerHTML = `<option value="" disabled>Select</option>
                                     <option value="ReviewCenter">Student Review Center</option>
                                     <option value="MockScores">Mock Board Scores</option>
                                     <option value="LicensureResult">Licensure Exam Result</option>`;
  } else if (statToolInferential == 'tTestIND' && var_type == "ind") {
    programMetricsField.innerHTML = `<option value="" disabled>Select</option>
                                     <option value="LicensureResult">Licensure Exam Result</option>
                                     <option value="TakeAttempt">Number of Exam Attempts</option>`;
  } else if (statToolInferential == 'tTestIND' && var_type == "dep") {
    programMetricsField.innerHTML = `<option value="" disabled>Select</option>
                                     <option value="MockScores">Mock Board Scores</option>`;
  } else if (statToolInferential == 'tTestDEP') {
    programMetricsField.innerHTML = `<option value="" disabled>Select</option>
                                     <option value="MockScores">Mock Board Scores</option>`;
  } else {
    programMetricsField.innerHTML = `<option value="" disabled>Select</option>`;
  }
  programMetricsField.value = "";
  
  // Apply filtering if this is field 2
  if (programMetricsField.id === 'field2ProgramMetric') {
    setTimeout(() => filterProgramMetricsOptions(programMetricsField), 100);
  }
}

function getDynamicBarWidth(dataPointsCount) {
  const container = document.getElementById("reportChart");
  const containerWidth = container ? container.offsetWidth || 600 : 600;
  const numBars = Math.max(1, dataPointsCount);

  // Reserve horizontal padding per bar (labels readability)
  const reservedPerBar = 12; // px
  const totalReserved = Math.min(reservedPerBar * numBars, 400);
  const availWidth = Math.max(200, containerWidth - totalReserved);

  // Compute bar width (clamped)
  let barWidth = Math.floor(availWidth / numBars);
  barWidth = Math.max(8, Math.min(140, barWidth));
  
  return barWidth;
}

// Chart.js helpers: inject canvas, manage instance, and draw error bars
let __gr_chart_instance = null;

function __gr_get_ctx() {
const container = document.getElementById("reportChart");
if (!container) return null;
container.innerHTML = '<canvas id="chartCanvas" style="width:100%;height:100%"></canvas>';
const canvas = document.getElementById("chartCanvas");
return canvas ? canvas.getContext("2d") : null;
}

function __gr_set_chart(newChart) {
if (window.myChart && typeof window.myChart.destroy === 'function') {
  try { window.myChart.destroy(); } catch (e) {}
}
if (__gr_chart_instance && typeof __gr_chart_instance.destroy === 'function') {
  try { __gr_chart_instance.destroy(); } catch (e) {}
}
__gr_chart_instance = newChart;
window.myChart = newChart;
}

// Lightweight error bars plugin for Chart.js (expects dataset.errorBars[{plus,minus}])
const __gr_errorBarsPlugin = {
id: 'grErrorBars',
afterDatasetsDraw(chart) {
  const { ctx, scales } = chart;
  chart.data.datasets.forEach((ds, dsIndex) => {
    if (!ds || !Array.isArray(ds.errorBars)) return;
    const meta = chart.getDatasetMeta(dsIndex);
    if (!meta || !meta.data) return;
    ctx.save();
    ctx.strokeStyle = (ds.borderColor || '#111');
    ctx.lineWidth = 1;
    const tickHalf = 6; // px
    for (let i = 0; i < meta.data.length; i++) {
      const elem = meta.data[i];
      const val = ds.data?.[i];
      const eb = ds.errorBars?.[i];
      if (!elem || val == null || !eb) continue;
      const x = elem.x;
      const yTop = scales.y.getPixelForValue(val + (eb.plus ?? 0));
      const yBottom = scales.y.getPixelForValue(val - (eb.minus ?? 0));
      ctx.beginPath();
      ctx.moveTo(x, yTop);
      ctx.lineTo(x, yBottom);
      ctx.moveTo(x - tickHalf / 2, yTop);
      ctx.lineTo(x + tickHalf / 2, yTop);
      ctx.moveTo(x - tickHalf / 2, yBottom);
      ctx.lineTo(x + tickHalf / 2, yBottom);
      ctx.stroke();
    }
    ctx.restore();
  });
}
};

function barGraph(dataPoints) {
lastDataPoints = dataPoints;
const ctx = __gr_get_ctx();
if (!ctx) return;

const labels = dataPoints.map(dp => dp.label);
const values = dataPoints.map(dp => dp.y == null ? 0 : dp.y);
// Generate distinct colors when dp.color is not provided
const colors = dataPoints.map((dp, i) => {
  if (dp.y == null) return '#d3d3d3';
  if (dp.color) return dp.color;
  const hue = (i * 47) % 360; // fairly distinct steps
  return `hsl(${hue}, 65%, 55%)`;
});

const chart = new Chart(ctx, {
  type: 'bar',
  data: {
    labels,
    datasets: [{
      label: 'Value',
      data: values,
      backgroundColor: colors,
      borderColor: colors,
      borderWidth: 0
    }]
  },
  options: {
    maintainAspectRatio: false,
    responsive: true,
    layout: { padding: { right: 8 } },
    plugins: { legend: { display: false } },
    scales: {
      x: { ticks: { maxRotation: 0, minRotation: 0 } },
      y: { beginAtZero: true }
    }
  }
});
__gr_set_chart(chart);
}

function regressionGraph(consolidatedData){
const graphData = consolidatedData;
const rawData = graphData.raw_data || [];
const lineData = graphData.regression_line || [];
const stats = graphData.stats || { slope: 0, intercept: 0, r_squared: 0 };

const ctx = __gr_get_ctx();
if (!ctx) return;

const chart = new Chart(ctx, {
  type: 'scatter',
  data: {
    datasets: [
      {
        type: 'scatter',
        label: 'Student Data',
        data: rawData.map(p => ({ x: p.x, y: p.y })),
        backgroundColor: 'rgba(49, 130, 206, 0.8)',
        pointRadius: 3
      },
      {
        type: 'line',
        label: 'Line of Best Fit',
        data: lineData.map(p => ({ x: p.x, y: p.y })),
        borderColor: '#FF0000',
        borderDash: [6, 4],
        pointRadius: 0,
        fill: false
      }
    ]
  },
  options: {
    maintainAspectRatio: false,
    responsive: true,
    plugins: {
      legend: { display: true },
      title: {
        display: true,
        text: `Regression: Y = ${stats.slope?.toFixed?.(3) ?? '0.000'}X + ${stats.intercept?.toFixed?.(3) ?? '0.000'} (RÂ²: ${stats.r_squared?.toFixed?.(3) ?? '0.000'})`
      }
    },
    scales: {
      x: { title: { display: true, text: 'Data Set 1' } },
      y: { title: { display: true, text: 'Data Set 2' } }
    }
  }
});
__gr_set_chart(chart);
}

function pearsonGraph(consolidatedData){
const graphData = consolidatedData;
const rawData = graphData.raw_data || [];
const rValue = graphData.r_value ?? 0;
const rSquared = graphData.stats?.r_squared ?? 0;

const ctx = __gr_get_ctx();
if (!ctx) return;

const chart = new Chart(ctx, {
  type: 'scatter',
  data: {
    datasets: [{
      label: 'Student Data',
      data: rawData.map(p => ({ x: p.x, y: p.y })),
      backgroundColor: 'rgba(76, 175, 80, 0.8)',
      pointRadius: 3
    }]
  },
  options: {
    maintainAspectRatio: false,
    responsive: true,
    plugins: {
      legend: { display: true },
      title: { display: true, text: 'Pearson Correlation Analysis' },
      subtitle: { display: true, text: `Pearson\'s r = ${Number(rValue).toFixed(4)} | R-squared = ${Number(rSquared).toFixed(4)}` }
    },
    scales: {
      x: { title: { display: true, text: 'Data Set 1' } },
      y: { title: { display: true, text: 'Data Set 2' } }
    }
  }
});
__gr_set_chart(chart);
}

function chiSquareChart(chartData) {
  if (!chartData || !chartData.data_series || !chartData.x_categories) {
      console.error("Invalid Chi-Square chart data provided. Missing data_series or x_categories.");
      return;
  }

  const { type, chi2, df, significance, x_categories, data_series, x_label, y_label } = chartData;
const ctx = __gr_get_ctx();
if (!ctx) return;

const labels = x_categories;
const colors = {
  Observed: '#4A90E2',
  Expected: '#7ED321'
};

const datasets = data_series.map(series => ({
  type: 'bar',
  label: series.name,
  data: series.dataPoints.map(dp => (dp?.y ?? 0)),
  backgroundColor: colors[series.name] || 'rgba(99, 102, 241, 0.8)',
  borderColor: colors[series.name] || 'rgba(99, 102, 241, 1)'
}));

const chart = new Chart(ctx, {
  type: 'bar',
  data: { labels, datasets },
  options: {
    maintainAspectRatio: false,
    responsive: true,
    layout: { padding: { right: 8 } },
    plugins: {
      legend: { display: true },
      title: { display: true, text: type === 'TOI' ? 'Chi-Square Test of Independence' : 'Chi-Square Goodness-of-Fit Test' },
      subtitle: { display: true, text: `Ï‡Â² = ${Number(chi2).toFixed(4)} (df: ${df}) | ${significance}` }
    },
    scales: {
      x: { title: { display: !!x_label, text: x_label || '' } },
      y: { title: { display: !!y_label, text: y_label || '' }, beginAtZero: true }
    }
  }
});
__gr_set_chart(chart);
}

function tTestChart(chartData) {
  if (!chartData || !chartData.groups) {
      console.error("Invalid t-test chart data.");
      return;
  }

  const { test_kind, groups, t, df, p, significance, type } = chartData;
if (!(type === 'independent_ttest' || type === 'dependent_ttest')) return;

const ctx = __gr_get_ctx();
if (!ctx) return;
  const barWidth = getDynamicBarWidth(groups.length);

const labels = groups.map(g => g.name);
const means = groups.map(g => g.mean);
const sds = groups.map(g => ({ plus: g.sd, minus: g.sd }));

// Register error bars plugin once per page
if (!Chart.registry.plugins.get('grErrorBars')) {
  Chart.register(__gr_errorBarsPlugin);
}

const chart = new Chart(ctx, {
  type: 'bar',
  data: {
    labels,
    datasets: [{
      label: 'Mean Score',
      data: means,
      backgroundColor: 'rgba(99, 102, 241, 0.8)',
      borderColor: 'rgba(99, 102, 241, 1)',
      barThickness: barWidth,
      errorBars: sds
    }]
  },
  options: {
    maintainAspectRatio: false,
    responsive: true,
    plugins: {
      legend: { display: false },
      title: { display: true, text: test_kind === 'independent' ? 'Independent Samples t-Test' : 'Paired Samples t-Test' },
      subtitle: { display: true, text: `t(${df}) = ${Number(t).toFixed(3)}, p = ${Number(p).toFixed(3)} | ${significance}` }
    },
    scales: {
      y: { beginAtZero: false, title: { display: true, text: 'Mean Score' } }
    }
  }
});
__gr_set_chart(chart);
}

// Function to apply compact styling to tables with 1-2 columns
function applyCompactTableStyling() {
  const tables = document.querySelectorAll('table.report-table');
  
  tables.forEach(table => {
    // Check if table has 1-2 columns
    const firstRow = table.querySelector('tr');
    if (!firstRow) return;
    
    const cells = firstRow.querySelectorAll('th, td');
    const columnCount = cells.length;
    
    // Remove any existing compact classes
    table.classList.remove('compact-1-col', 'compact-2-col', 'compact-table');
    
    // Apply compact styling for 1-2 column tables
    if (columnCount === 1) {
      table.classList.add('compact-1-col');
    } else if (columnCount === 2) {
      table.classList.add('compact-2-col');
    }
  });
}

// Apply compact styling when the page loads
document.addEventListener('DOMContentLoaded', function() {
  applyCompactTableStyling();
});





/*TOAST*/
////////// TOAST HELPER (Responsive) //////////
function showToast(message) {
  let toast = document.getElementById("customToast");
  if (!toast) {
      toast = document.createElement("div");
      toast.id = "customToast";
      toast.style.position = "fixed";
      toast.style.top = "20px"; // ⬆️ top position
      toast.style.left = "50%";
      toast.style.transform = "translateX(-50%) translateY(-100px)"; // hidden above
      toast.style.background = "#5c297c";
      toast.style.color = "#fff";
      toast.style.padding = "12px 20px"; // smaller padding
      toast.style.borderRadius = "8px";
      toast.style.zIndex = "99999";
      toast.style.maxWidth = "90%"; // ✅ responsive width
      toast.style.wordWrap = "break-word"; // ✅ wrap long text
      toast.style.textAlign = "center";
      toast.style.fontSize = "clamp(14px, 2vw, 18px)"; // ✅ responsive font size
      toast.style.opacity = "0";
      toast.style.transition = "all 0.5s ease";
      document.body.appendChild(toast);
  }

  toast.textContent = message;

  // Trigger slide down
  requestAnimationFrame(() => {
      toast.style.opacity = "1";
      toast.style.transform = "translateX(-50%) translateY(0)";
  });

  // Hide after 2s
  setTimeout(() => {
      toast.style.opacity = "0";
      toast.style.transform = "translateX(-50%) translateY(-100px)";
  }, 2000);
}
