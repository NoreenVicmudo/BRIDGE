/******************************UPON LOADING THE PAGE***********************************/
window.addEventListener('DOMContentLoaded', () => {
    const content = document.querySelector('.content');
    if (content) {
      content.classList.add('fade-in');
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
  const exportPdfBtn = document.querySelector('.export-btn'); // direct PDF export button
  const exportDropdownBtn = document.getElementById('exportDropdownBtn'); // legacy dropdown trigger (may not exist)
  const exportOptions = document.querySelectorAll('.export-option'); // legacy dropdown options (may not exist)

  if (printBtn) {
    printBtn.disabled = !enabled;
    printBtn.classList.toggle('disabled', !enabled);
  }

  // Preferred: disable the actual Export as PDF button
  if (exportPdfBtn) {
    exportPdfBtn.disabled = !enabled;
    exportPdfBtn.classList.toggle('disabled', !enabled);
  }

  // Backward compatibility: if dropdown exists, disable it too
  if (exportDropdownBtn) {
    exportDropdownBtn.disabled = !enabled;
    exportDropdownBtn.classList.toggle('disabled', !enabled);
  }
  if (exportOptions && exportOptions.length) {
    exportOptions.forEach(btn => {
      btn.disabled = !enabled;
      btn.classList.toggle('disabled', !enabled);
    });
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

// Filter option containers (initialized early to avoid TDZ when functions run before fetch completes)
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


/******************************* SIDE BAR TOGGLE CUSTOMIZATION ***************************/
document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('sidebar');
  const icon = document.getElementById('toggleIcon');

  if (sidebar && icon) {
    // Initialize sidebar state based on screen width
    if (window.innerWidth <= 768) {
      sidebar.classList.remove('open');
      icon.className = 'bi bi-list'; // show hamburger
    } else {
      sidebar.classList.add('open');
      icon.className = 'bi bi-chevron-double-left'; // show collapse icon
    }

    // Recheck on resize
    window.addEventListener('resize', () => {
      if (window.innerWidth <= 768) {
        sidebar.classList.remove('open');
        icon.className = 'bi bi-list';
      } else {
        sidebar.classList.add('open');
        icon.className = 'bi bi-chevron-double-left';
      }
    });
  }
});

function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const icon = document.getElementById('toggleIcon');
  const content = document.querySelector('.content');

  if (sidebar && icon) {
    sidebar.classList.toggle('open');

    // Change sidebar icon based on the open state
    if (window.innerWidth > 768) {
      icon.className = sidebar.classList.contains('open')
        ? 'bi bi-chevron-double-left'
        : 'bi bi-list';
    } else {
      icon.className = sidebar.classList.contains('open')
        ? 'bi bi-chevron-double-left'
        : 'bi bi-list';

      // Let CSS handle padding on mobile
      if (content) {
        content.style.paddingLeft = '';
      }
    }
  }
}

// Handle dropdown toggle with smooth animation
document.querySelectorAll('.sidebar .dropdown-toggle').forEach(toggle => {
    toggle.addEventListener('click', function (e) {
        e.preventDefault();
        const parentLi = this.parentElement;
        const dropdownMenu = parentLi.querySelector('.dropdown-menu');

        // Close all other open dropdowns
        document.querySelectorAll('.sidebar .dropdown.open').forEach(openDropdown => {
            if (openDropdown !== parentLi) {
                const openMenu = openDropdown.querySelector('.dropdown-menu');
                if (openMenu) {
                    openMenu.style.maxHeight = '0';
                    openMenu.style.opacity = '0';
                }
                openDropdown.classList.remove('open');
            }
        });

        // Toggle the clicked dropdown
        if (parentLi.classList.contains('open')) {
            dropdownMenu.style.maxHeight = '0';
            dropdownMenu.style.opacity = '0';
            parentLi.classList.remove('open');
        } else {
            dropdownMenu.style.maxHeight = dropdownMenu.scrollHeight + 'px';
            dropdownMenu.style.opacity = '1';
            parentLi.classList.add('open');
        }
    });
});

const profileToggle = document.getElementById('profileToggle');
if (profileToggle) {
  const profileSection = profileToggle.parentElement;

  // Create profile overlay
  const profileOverlay = document.createElement('div');
  profileOverlay.className = 'profile-overlay';
  document.body.appendChild(profileOverlay);

  // Toggle on click
  profileToggle.addEventListener('click', function (event) {
    event.stopPropagation(); // Prevent the click from bubbling up to the document
    const isOpen = profileSection.classList.toggle('open');
    
    if (isOpen) {
        // Profile is open - activate overlay and disable other hovers
        profileOverlay.classList.add('active');
        document.body.classList.add('profile-active');
    } else {
        // Profile is closed - deactivate overlay and enable other hovers
        profileOverlay.classList.remove('active');
        document.body.classList.remove('profile-active');
    }
  });

  // Close when clicking outside
  document.addEventListener('click', function (event) {
      if (!profileSection.contains(event.target)) {
          profileSection.classList.remove('open');
          profileOverlay.classList.remove('active');
          document.body.classList.remove('profile-active');
      }
  });

  // Close when clicking on overlay
  profileOverlay.addEventListener('click', function () {
      profileSection.classList.remove('open');
      profileOverlay.classList.remove('active');
      document.body.classList.remove('profile-active');
  });
}

const sidebarOverlay = document.querySelector('.sidebar-overlay');
if (sidebarOverlay) {
  sidebarOverlay.addEventListener('click', () => {
    const sidebar = document.getElementById('sidebar');
    const icon = document.getElementById('toggleIcon');

    if (sidebar && icon) {
      sidebar.classList.remove('open');
      sidebarOverlay.classList.remove('active');
      icon.className = 'bi bi-list'; // back to hamburger
    }
  });
}


////////////////TEST SHOW FOR REPORT GENERATION///////////////////////////////////

let lastDataPoints = []; // store last dataset globally
const statToolSelect = document.getElementById('statTool');
  //const inputContainer = document.getElementById('inputContainer');
  const reportSummary = document.getElementById('reportSummary');

  if (statToolSelect) statToolSelect.addEventListener('change', function () {
    //inputContainer.innerHTML = '';
    reportSummary.classList.add('hidden');

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
    openStatisticalToolModal();
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
    const fieldCategory = document.getElementById('field0Category');
    const studentInfoField0 = document.getElementById('field0StudentField');
    const academicProfileField0 = document.getElementById('field0AcademicMetric');
    const programMetricsField0 = document.getElementById('field0ProgramMetric');
    const subMetricGroup = document.getElementById("subMetricGroup");
    const subMetricSelect = document.getElementById("subMetricSelect");
    
    fieldCategory.value = "";
    studentInfoField0.value = "";
    academicProfileField0.value = "";
    programMetricsField0.value = "";
    subMetricSelect.value = "";
    
    subMetricGroup.classList.add('hidden');

      statToolInferential.value = "";
      fieldSelectionDescriptive.classList.remove('hidden');
      fieldSelection.classList.add('hidden');
      fieldSelection1.classList.add('hidden');
      fieldSelection2.classList.add('hidden');
      fieldInferential.classList.add('hidden');
}

// Field 0 Category Change Handler
function handleField0CategoryChange() {
    const category = document.getElementById('field0Category').value;
    const studentInfo = document.getElementById('field0StudentInfo');
    const academicProfile = document.getElementById('field0AcademicProfile');
    const programMetrics = document.getElementById('field0ProgramMetrics');
    const subMetricGroup = document.getElementById("subMetricGroup");
    
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

// Helper to create option with value and label (function declaration is hoisted)
function createOption(value, label) {
  const opt = document.createElement("option");
  opt.value = value;
  opt.textContent = label;
  return opt;
}

// Function to get display text for field values (same as in generate_report.js)
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

// Student Info Metrics Change Handlers
function handleField0StudentInfoMetricChange() {
    const metric = document.getElementById('field0StudentField').value;
    const subMetricSelect = document.getElementById("subMetricSelect");

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

  subMetricSelect.onchange = null;
  subMetricSelect.innerHTML = "";
  subMetricGroup.classList.add('hidden');

    // Always add "Select"
    subMetricSelect.appendChild(createOption("Select", "Select"));
}

// Program Metrics Change Handlers
function handleField0ProgramMetricChange() {
  const session = window.userSession || {};
  const metric = document.getElementById('field0ProgramMetric').value;
  const subMetricGroup = document.getElementById("subMetricGroup");
  const subMetricLabel = document.getElementById("subMetricLabel");
  const subMetricSelect = document.getElementById("subMetricSelect");

  subMetricSelect.onchange = null;
  subMetricSelect.innerHTML = "";
  subMetricGroup.classList.add('hidden');

    // Always add "Select"
    subMetricSelect.appendChild(createOption("Select", "Select"));
}

// Generate Report Function
function generateReport() {
    const field0Category = document.getElementById('field0Category').value;
    const subMetricSelect = document.getElementById('subMetricSelect').value;
    
    // Get selected fields based on categories
    let field0;
    
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
    
    // Validate selections
    if (!field0Category || !field0 || !subMetricSelect) {
        console.log(field0Category, field0, subMetricSelect);
        showToast('Please select all required fields');
        return;
    } else {
        console.log(field0Category, field0, subMetricSelect);
    }

  const session = window.userSession || {};
  const yearBatch = session.filter_year_batch;
  
    
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
  formData.append("yearBatch", yearBatch);
  formData.append("field0", field0);
  formData.append("subMetricSelect", subMetricSelect);

  fetch('/bridge/modules/generate_report/processes/program_statistics.php', {
    method: "POST",
    body: formData
  })
  .then(res => res.json()) // expect JSON
  .then(data => {
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
        
      const toolDisplay = 'Descriptive Statistics';
      const fieldDisplay = getFieldDisplayText(field0Category, field0);
      reportSummary.innerHTML = `
     
        <p>Statistical Tool: ${toolDisplay}<br>
        Field: ${fieldDisplay}</p>
      `;
      // Reset any existing animation classes and apply fade-in
      reportSummary.classList.remove('report-fade-in');
      setTimeout(() => {
        reportSummary.classList.add('report-fade-in');
      }, 10);
      barGraph(data.consolidatedData);

      generatedReportSummary.innerHTML = data.htmlDisplay;
      
      // Apply compact styling to tables with 1-2 columns
      setTimeout(() => {
        applyCompactTableStyling();
      }, 100);
      
      // Add fade-in animation to the entire report
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
          reportSummary.classList.add('report-fade-in');
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




function printReportContent() {
    try {
        const container = document.getElementById('report');
        const frozenWidth = container ? container.offsetWidth : 0;
        if (frozenWidth) {
            document.documentElement.style.setProperty('--export-width-px', frozenWidth + 'px');
            document.documentElement.classList.add('export-freeze');
        }
    } catch (e) {}
    window.print();
    setTimeout(() => {
        document.documentElement.classList.remove('export-freeze');
        document.documentElement.style.removeProperty('--export-width-px');
    }, 0);
}

function exportReport(type) {
    const content = document.getElementById('report');

    if (type === 'pdf') {
        exportReportToPdfSnapshotPrograms();
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

// Snapshot-based PDF export for programs page
function exportReportToPdfSnapshotPrograms() {
    const target = document.getElementById('report');
    if (!target) return;

    // Freeze width to current on-screen size to prevent any reflow
    const frozenWidth = target.offsetWidth;
    if (frozenWidth) {
        document.documentElement.style.setProperty('--export-width-px', frozenWidth + 'px');
        document.documentElement.classList.add('export-freeze');
    }
    // Apply print-visibility styling (hide UI, solid colors) without changing flow
    document.documentElement.classList.add('pdf-export');

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
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
  const exportDropdownBtn = document.getElementById('exportDropdownBtn');
  const exportDropdown = exportDropdownBtn ? exportDropdownBtn.closest('.export-dropdown') : null;
  const exportMenu = document.getElementById('exportMenu');

  if (exportDropdownBtn && exportDropdown) {
    exportDropdownBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      const disabled = this.disabled || this.classList.contains('disabled');
      if (disabled) return; // prevent opening when disabled
      exportDropdown.classList.toggle('open');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function (e) {
      if (exportDropdown && !exportDropdown.contains(e.target)) {
        exportDropdown.classList.remove('open');
      }
    });
  }
  
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
/* filter option variables are declared at top to avoid TDZ/redeclaration */

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
  ysSelect.innerHTML = '<option value="" disabled>Select Year and Semester</option>';
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

// 🔄 Re-render on window resize (with last dataset)
// Debounced resize handler to avoid excessive re-renders
let __gr_resize_timeout = null;
window.addEventListener("resize", () => {
  if (__gr_resize_timeout) clearTimeout(__gr_resize_timeout);
  __gr_resize_timeout = setTimeout(() => {
    if (lastDataPoints.length > 0) {
      // Chart.js handles resize automatically, just trigger resize
      if (window.myChart && window.myChart.resize) {
        window.myChart.resize();
      }
    }
  }, 150);
});

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
