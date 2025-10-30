/******************************UPON LOADING THE PAGE***********************************/
window.addEventListener('DOMContentLoaded', () => {
    const content = document.querySelector('.content');
    if (content) {
      content.classList.add('fade-in');
      // Ensure DataTables recalculates widths after fade-in animation completes
      content.addEventListener('animationend', () => {
        if (window.studentInfoTable) {
          window.studentInfoTable.columns.adjust().responsive.recalc();
        }
      });
    }

  // Handle incoming filter data from filter.php
  handleIncomingFilterData();  
});

/************************************ LOADERS LOGIC ***********************************/
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
      e.preventDefault();
      const target = el.href || el.dataset.href;

      if (!target) return;

      NProgress.start();

      setTimeout(() => {
        NProgress.set(0.7);
      }, 300);

      setTimeout(() => {
        NProgress.done();
        window.location.href = target;
      }, 1200);
    });
  });

  window.onload = function () {
    NProgress.done();
  };
});

/********** Loading animation for actions on the same page **********/
document.addEventListener('DOMContentLoaded', function () {
  const samePageButtons = document.querySelectorAll(".same-page");

  samePageButtons.forEach(function (button) {
    button.addEventListener("click", function (e) {
      e.preventDefault(); // Prevent default form submit

      NProgress.start();

      setTimeout(() => {
        NProgress.set(0.7);
      }, 300);

      setTimeout(() => {
        NProgress.done();

        // Submit the form manually
        const form = button.closest("form");
        if (form) form.submit();
      }, 1000); // Adjust time as needed
    });
  });
});



/**************************************DATA TABLES CUSTOMIZATION***********************************/
$(document).ready(function () {
  const dataTable = $('#myTable').DataTable({
    scrollX: true,
    responsive: true,
    ordering: false,
    dom: '<"top-controls d-flex justify-content-between align-items-center"f<"btn-group ms-2">>t<"bottom-controls"ip>',
    language: {
      search: "",
      lengthMenu: "Show _MENU_ entries",
      info: "",
      paginate: {
        previous: "Previous",
        next: "Next"
      }
    },
    initComplete: function () {
      $(".dataTables_filter input").attr("placeholder", "Search students...");

      $(".btn-group").html(`
        <button class="btn btn-outline-primary me-1" id="filterButton">
          <i class="bi bi-funnel-fill"></i> Filter
        </button>
        <button class="btn btn-outline-secondary" id="metricsButton">
          <i class="bi bi-arrow-left-right"></i> Change Metric
        </button>
      `);

      // Add click event for filter button
      $("#filterButton").on("click", function() {
        openFilterModal();
      });
    }
  });

  // Expose for global adjustments and do an initial adjustment after render
  window.studentInfoTable = dataTable;
  setTimeout(() => {
    dataTable.columns.adjust().responsive.recalc();
  }, 200);
  
  // Also adjust on window resize
  window.addEventListener('resize', () => {
    if (window.studentInfoTable) {
      window.studentInfoTable.columns.adjust().responsive.recalc();
    }
  });

  // Customize table header colors
  $('#myTable thead th').css({
    'background-color': 'var(--primary)',
    'color': 'var(--light)'
  });

  /************************************** DELETE BUTTON HANDLER ***********************************/
  $('#deleteBtn').on('click', activateDeleteMode);

  /************************************** CHECK STUDENT ID EXISTENCE ***********************************/
  $('#checkStudentBtn').on('click', function (e) {
  e.preventDefault();

  const studentIdToFind = $('#findstudentId').val().trim();
  const resultP = $('#checkStudentResult');
  const loader = $('#loader');
  const btnText = $('.btn-text');
  const checkBtn = $(this);
  const formContainer = $('#registrationForm'); // optional

  resultP.hide();
  loader.show();         // Show spinner
  btnText.hide();        // Hide text
  checkBtn.addClass('loading').prop('disabled', true);

  if (!studentIdToFind) {
    loader.hide();
    btnText.show();
    checkBtn.removeClass('loading').prop('disabled', false);
    resultP.text("Please enter a student ID.").show();
    formContainer.hide(); // optional
    return;
  }

  let exists = false;

  setTimeout(() => {
    const table = $('#myTable').DataTable();
    const allData = table.rows().data();

    allData.each(function (row) {
      const htmlString = row[1]; // Student ID column
      const tempDiv = document.createElement('div');
      tempDiv.innerHTML = htmlString;
      const studentIdInTable = tempDiv.textContent || tempDiv.innerText;

      if (studentIdInTable.includes(studentIdToFind)) {
        exists = true;
      }
    });

    loader.hide();
    btnText.show();
    checkBtn.removeClass('loading').prop('disabled', false);

    if (exists) {
  const encodedId = encodeURIComponent(studentIdToFind);
  // Find the student name in the table
  let studentName = '';
  let studentField1 = '';
  let studentField2 = '';
  let studentField3 = '';
  let studentField4 = '';
  let studentField5 = '';
  let studentField6 = '';
  allData.each(function (row) {
    const htmlString = row[1];
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = htmlString;
    const studentIdInTable = tempDiv.textContent || tempDiv.innerText;
    if (studentIdInTable.includes(studentIdToFind)) {
      studentName = row[2]; // Student Name column
      studentField1 = row[3]; // Student Unique Field 1 column 
      studentField2 = row[4]; // Student Unique Field 2 column
      studentField3 = row[5]; // Student Unique Field 3 column
      studentField4 = row[6]; // Student Unique Field 4 column
      studentField5 = row[7]; // Student Unique Field 5 column
      studentField6 = row[8]; // Student Unique Field 6 column
    }
  });
  const encodedName = encodeURIComponent(studentName);
  const targetUrl = checkBtn.data('href');
  NProgress.start();
  setTimeout(() => {
    NProgress.done();
    window.location.href = `${targetUrl}?studentId=${encodedId}`;
  }, 1000);
} else {
      resultP.text("Student does not exist in this section.").show();
    }
  }, 500);
});

});
  

/******************************* SIDE BAR TOGGLE CUSTOMIZATION ***************************/
document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('sidebar');
  const icon = document.getElementById('toggleIcon');

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
});

function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const icon = document.getElementById('toggleIcon');
  const content = document.querySelector('.content');
  const sidebarOverlay = document.querySelector('.sidebar-overlay');

  sidebar.classList.toggle('open');

  if (window.innerWidth > 768) {
    // Desktop behavior
    icon.className = sidebar.classList.contains('open')
      ? 'bi bi-chevron-double-left'
      : 'bi bi-list';
  } else {
    // Mobile behavior
    if (sidebar.classList.contains('open')) {
      sidebarOverlay.classList.add('active'); // show overlay
    } else {
      sidebarOverlay.classList.remove('active'); // hide overlay
    }

    icon.className = sidebar.classList.contains('open')
      ? 'bi bi-chevron-double-left'
      : 'bi bi-list';

    content.style.paddingLeft = ''; // Let CSS handle mobile padding
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

document.querySelector('.sidebar-overlay').addEventListener('click', () => {
  const sidebar = document.getElementById('sidebar');
  const icon = document.getElementById('toggleIcon');
  const sidebarOverlay = document.querySelector('.sidebar-overlay');

  sidebar.classList.remove('open');
  sidebarOverlay.classList.remove('active');
  icon.className = 'bi bi-list'; // back to hamburger
});




/****************************BUTTON CHANGE AND MODALS***************************/
function activateDeleteMode() {
  const tableCheckboxes = document.querySelectorAll('.select-column');
  const buttonContainer = document.querySelector('.button-container');

  tableCheckboxes.forEach(col => col.classList.remove('hidden'));

  buttonContainer.innerHTML = `
    <button class="button delete-confirm" id="confirmDelete">Remove</button>
    <button class="button cancel-delete btn-clear" id="cancelDelete">Cancel</button>
  `;

  document.getElementById('cancelDelete').addEventListener('click', () => {
    tableCheckboxes.forEach(col => col.classList.add('hidden'));
    document.querySelectorAll('.row-select').forEach(box => box.checked = false);
    restoreOriginalButtons();
  });

  document.getElementById('confirmDelete').addEventListener('click', () => {
    const selected = document.querySelectorAll('.row-select:checked');
    if (selected.length === 0) {
      alert("Please select at least one student to delete.");
      return;
    }
    // Store selected rows globally for use in modal confirmation
    window.selectedToDelete = selected;
    // Gather selected students' names from the table
    const selectedStudents = Array.from(window.selectedToDelete).map(checkbox => {
      const row = checkbox.closest('tr');
      // Student name is in the 3rd cell (index 2), adjust if needed
      const name = row ? row.cells[2].textContent.trim() : 'Unknown';
      return { name };
    });
    openEnhancedDeleteModal(selectedStudents);
  });
}

function restoreOriginalButtons() {
  const buttonContainer = document.querySelector('.button-container');
  buttonContainer.innerHTML = `
    <button class="button" id="addStudentBtn">Add Student</button>
    <button class="button" id="deleteBtn">Delete Student</button>
  `;

  document.getElementById('addStudentBtn').addEventListener('click', openAddStudentModal);
  document.getElementById('deleteBtn').addEventListener('click', activateDeleteMode);
}

function openAddStudentModal() {
  document.getElementById('addStudentModal').classList.add('show');
  // Show initial options and hide other sections
  document.getElementById('initialOptions').style.display = 'flex';
  document.getElementById('importFileSection').style.display = 'none';
  document.getElementById('findStudentSection').style.display = 'none';
}

// Function to close modal with fade out animation
function closeModalWithAnimation(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    console.log('Adding closing class to modal');
    modal.classList.add('closing');
    
    // Force a reflow to ensure the class is applied
    modal.offsetHeight;
    
    // Remove the modal after animation completes
    setTimeout(() => {
      console.log('Removing modal classes');
      modal.classList.remove('show', 'closing');
    }, 500); // Match the animation duration
  }
}

function closeAddStudentModal() {
  closeModalWithAnimation('addStudentModal');
}

function openEnhancedDeleteModal(selectedStudents) {
  const modal = document.getElementById('enhancedDeleteModal');
  modal.classList.add('show');
  // Store the selected students globally for mode switching
  window._enhancedDeleteSelectedStudents = selectedStudents;
  renderEnhancedDeleteModal('single');
}

function renderEnhancedDeleteModal(mode) {
  const selectedStudents = window._enhancedDeleteSelectedStudents || [];
  // Set radio button
  document.querySelector('input[name="deleteMode"][value="' + mode + '"]').checked = true;
  document.getElementById('singleReasonSection').style.display = (mode === 'single') ? 'block' : 'none';
  document.getElementById('multipleReasonSection').style.display = (mode === 'multiple') ? 'block' : 'none';

  // List students in single reason section
  const singleStudentList = document.getElementById('singleStudentList');
  singleStudentList.innerHTML = '';
  selectedStudents.forEach(student => {
    const div = document.createElement('div');
    div.textContent = student.name;
    div.style.marginBottom = '2px';
    singleStudentList.appendChild(div);
  });

  // Populate per-student reason section
  const container = document.getElementById('multipleReasonSection');
  container.innerHTML = '';
  selectedStudents.forEach(student => {
    const row = document.createElement('div');
    row.className = 'student-reason-row';
    row.innerHTML = `
      <span style="min-width: 80px;">${student.name}</span>
      <select class="custom-combobox">
        <option value="" disabled selected>Select</option>
        <option value="Incorrect or Incomplete Entry">Incorrect or Incomplete Entry</option>
        <option value="Transferred">The student transferred</option>
        <option value="Withdrawn">The student withdrawn or dropped out</option>
        <option value="Other">Other (please specify)</option>
      </select>
      <textarea placeholder="Please specify the reason"></textarea>
    `;
    // Show textarea if 'Other' is selected
    const select = row.querySelector('select');
    const textarea = row.querySelector('textarea');
    select.addEventListener('change', function() {
      textarea.style.display = (this.value === 'Other') ? 'block' : 'none';
    });
    container.appendChild(row);
      });
}

// Switch between single/multiple reason
Array.from(document.querySelectorAll('input[name="deleteMode"]')).forEach(radio => {
  radio.addEventListener('change', function() {
    renderEnhancedDeleteModal(this.value);
  });
});

// Show textarea for 'Other' in single reason
const singleReasonSelect = document.getElementById('singleReasonSelect');
if (singleReasonSelect) {
  singleReasonSelect.addEventListener('change', function() {
    document.getElementById('singleOtherReason').style.display = (this.value === 'Other') ? 'block' : 'none';
  });
}

// Cancel button
const cancelDeleteWithReason = document.getElementById('cancelDeleteWithReason');
if (cancelDeleteWithReason) {
  cancelDeleteWithReason.addEventListener('click', function() {
    closeModalWithAnimation('enhancedDeleteModal');
  });
}

// Confirm and Delete button
const confirmDeleteWithReason = document.getElementById('confirmDeleteWithReason');
if (confirmDeleteWithReason) {
  confirmDeleteWithReason.addEventListener('click', function() {
    // Collect reasons here and proceed with deletion
    // Example: if single reason, get value from select/textarea
    // if multiple, loop through each row and get select/textarea values
    closeModalWithAnimation('enhancedDeleteModal');
    // ... your deletion logic ...
  });
}

// --- Hook into existing delete modal flow ---
// Replace the confirmYes click handler to show the enhanced modal
const confirmYesBtn = document.getElementById('confirmYes');
if (confirmYesBtn) {
  confirmYesBtn.addEventListener('click', function() {
    if (window.selectedToDelete) {
      // Gather selected students' names from the table
      const selectedStudents = Array.from(window.selectedToDelete).map(checkbox => {
        const row = checkbox.closest('tr');
        // Student name is in the 3rd cell (index 2), adjust if needed
        const name = row ? row.cells[2].textContent.trim() : 'Unknown';
        return { name };
      });
    closeDeleteModal();
      openEnhancedDeleteModal(selectedStudents);
    }
  });
}

/******************************* ADD STUDENT MODAL SELECTION AND IMPORT MODAL ***************************/
document.addEventListener('DOMContentLoaded', function() {
    // Import File button click handler
    document.getElementById('importFileBtn').addEventListener('click', function() {
        document.getElementById('initialOptions').style.display = 'none';
        document.getElementById('importFileSection').style.display = 'block';
        document.getElementById('findStudentSection').style.display = 'none';
    });

    // Manual Entry button click handler
    document.getElementById('manualEntryBtn').addEventListener('click', function() {
        document.getElementById('initialOptions').style.display = 'none';
        document.getElementById('importFileSection').style.display = 'none';
        document.getElementById('findStudentSection').style.display = 'block';
    });

    // Cancel Import button click handler
    document.getElementById('cancelImport').addEventListener('click', function () {
        closeModalWithAnimation('addStudentModal');
        document.body.classList.remove("modal-open");
        document.getElementById('findstudentId').value = '';
        document.getElementById('checkStudentResult').style.display = 'none';
    });

    // File input change handler
    document.getElementById('fileInput').addEventListener('change', handleFileSelect);

    // Drag and drop handlers
    const dropZone = document.getElementById('dropZone');
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });

    function highlight(e) {
        dropZone.classList.add('dragover');
    }

    function unhighlight(e) {
        dropZone.classList.remove('dragover');
    }

    dropZone.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles(files);
    }
});

document.addEventListener('click', function (event) {
  const modal = document.getElementById('addStudentModal');
  const initialOptions = document.getElementById('initialOptions');

  // Only close if the modal is shown, initial options are visible, and clicked on the background
  if (
    modal.classList.contains('show') &&
    initialOptions.style.display === 'flex' &&
    event.target === modal
  ) {
    closeModalWithAnimation('addStudentModal');
    document.body.classList.remove("modal-open");
  }
});

function handleFileSelect(e) {
    const files = e.target.files;
    handleFiles(files);
}

function handleFiles(files) {
    if (files.length > 0) {
        const file = files[0];
        // Check if file is Excel or CSV
        if (
            file.type === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' || 
            file.type === 'application/vnd.ms-excel' ||
            file.type === 'text/csv' ||
            file.type === 'application/csv'
        ) {
            // Create FormData to send the file
            const formData = new FormData();
            formData.append('file', file);

            // Send using fetch API
            fetch('/bridge/modules/program_metrics/processes/import_program_metrics.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
            const session = window.userSession || {};
            const metric = session.filter_metric;
            const tableType = metricTable[metric];
            const filterData = JSON.parse(localStorage.getItem('studentFilterData')) || {};
            loadFilteredTable(tableType, filterData);
                if (data.success) {
                    showToast(`Imported successfully!\n
            - Inserted: ${data.inserted}\n
            - Updated: ${data.updated}\n
            - Skipped: ${data.skipped}`);
            console.log(data.errors);
                } else {
                    showToast("Import failed: " + data.errors.join(", "));
                }
            })
            .catch(err => console.error('Error:', err));

        } else {
            showToast('Please select an Excel or CSV file (.xlsx, .xls, .csv)');
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
  const cancelEditBtn = document.getElementById('cancelEdit');

  if (cancelEditBtn) {
    cancelEditBtn.addEventListener('click', function () {
      closeModalWithAnimation('addStudentModal');
      document.body.classList.remove("modal-open");
      document.getElementById('findstudentId').value = '';
      document.getElementById('checkStudentResult').style.display = 'none';
    });
  }
});


/****************************INPUT VALIDATION FOR STUDENT ID***************************/
function allowOnlyNumbers(event) {
    const key = event.key;

    // Allow only digits (0-9), Backspace, Delete, Arrow keys, and Tab
    if (
        !/^\d$/.test(key) &&  // Not a digit
        key !== 'Backspace' &&
        key !== 'Delete' &&
        key !== 'ArrowLeft' &&
        key !== 'ArrowRight' &&
        key !== 'Tab'
    ) {
        event.preventDefault();
    }
}






/******************************* HANDLE INCOMING FILTER DATA ***************************/
function handleIncomingFilterData() {
    // Program Metrics pages use college/program/yearBatch/boardBatch
    const storedData = localStorage.getItem('studentFilterData');
    if (storedData) {
        const filterData = JSON.parse(storedData);
        const collegeLabel = filterData.collegeText || filterData.college || '';
        const programLabel = filterData.programText || filterData.program || '';
        const yearBatch = filterData.yearBatch || '';
        const boardBatch = filterData.boardBatch || '';
        displayActiveFilters(collegeLabel, programLabel, yearBatch, boardBatch);
    }
}

function displayActiveFilters(college, program, yearBatch, boardBatch) {
    let filterDisplayHTML = '<div class="form-container">';

    if (college && college !== 'none') {
        filterDisplayHTML += `
            <div class="form-group">
                <label>College:</label>
                <span>${college}</span>
            </div>`;
    }

    if (program && program !== 'none') {
        filterDisplayHTML += `
            <div class="form-group">
                <label>Program:</label>
                <span>${program}</span>
            </div>`;
    }

    if (yearBatch && yearBatch !== 'none') {
        filterDisplayHTML += `
            <div class="form-group">
                <label>Year:</label>
                <span>${yearBatch}</span>
            </div>`;
    }

    if (boardBatch && boardBatch !== 'none') {
        filterDisplayHTML += `
            <div class="form-group">
                <label>Board Exam Batch:</label>
                <span>${boardBatch}</span>
            </div>`;
    }

    filterDisplayHTML += '</div>';

    const activeFiltersDisplay = document.getElementById('activeFiltersDisplay');
    if (activeFiltersDisplay) {
        activeFiltersDisplay.innerHTML = filterDisplayHTML;
    }
}

const metricTable = {
    "ReviewCenter": "/bridge/modules/program_metrics/processes/filter_table_review.php",
    "MockScores": "/bridge/modules/program_metrics/processes/filter_table_mock.php",
    "LicensureResult": "/bridge/modules/program_metrics/processes/filter_table_exam.php",
    "ExameDate": "/bridge/modules/program_metrics/processes/filter_table_examdate.php",
    "TakeAttempt": "/bridge/modules/program_metrics/processes/filter_table_takes.php"
};

function applyFilters() {
  const session = window.userSession || {};
  const college = document.getElementById('filterCollege').value;
  const collegeText = document.getElementById('filterCollege').options[document.getElementById('filterCollege').selectedIndex].text;
  const program = document.getElementById('filterProgram').value;
  const programText = document.getElementById('filterProgram').options[document.getElementById('filterProgram').selectedIndex].text;
  const yearBatch = document.getElementById('Year').value;
  const boardBatch = document.getElementById('boardBatch').value;
  const metric = session.filter_metric;
  const tableType = metricTable[metric];

  // Check if all fields are selected (I put this here to ensure all fields are selected before proceeding)
    if (
        college === '' ||
        program === '' ||
        yearBatch === '' ||
        boardBatch === ''
    ) {
        alert('Please select all filter options before proceeding.');
        return false;
    }

    // Update the display with new filter values (prefer labels)
    displayActiveFilters(collegeText, programText, yearBatch, boardBatch);
    
    // Store updated filter data in localStorage
    const filterData = {
      college: college,
      collegeText: collegeText,
      program: program,
      programText: programText,
      yearBatch: yearBatch,
      boardBatch: boardBatch
    };
    localStorage.setItem('studentFilterData', JSON.stringify(filterData));

    // Load filtered table data
    loadFilteredTable(tableType, filterData);
    
    closeFilterModal();
}

function loadFilteredTable(tableType, filterData, session = window.userSession || {}) { 
  // Prepare form data
  const formData = new FormData();
      formData.append('college', filterData.college);
      formData.append('program', filterData.program);
      formData.append('yearBatch', filterData.yearBatch);
      formData.append('boardBatch', filterData.boardBatch);

  fetch(tableType, {
    method: "POST",
    body: formData
  })
  .then(res => res.text()) // we expect HTML rows here
  .then(data => {
    if (
        filterData.program !== session.filter_program
    ) {
      window.location.reload();
    } else {
    // Update rows using DataTables API to prevent header/body misalignment
    const table = window.dataTable || $('#myTable').DataTable();
    table.clear();
    const $rows = $(data).filter('tr');
    $rows.each(function () { table.row.add(this); });
    table.draw(false);
    table.columns.adjust().responsive.recalc();
    }
  })
  .catch(err => {
    console.error("Error loading table:", err);
  });
}

function closeFilterModal() {
  closeModalWithAnimation("filterModal");
}

function openFilterModal() {
  // Always prefill from applied filters before showing
  populateFilterModalWithExistingData();
  document.getElementById('filterModal').classList.add('show');
}

// Event Listeners for Filter Modal
document.addEventListener('DOMContentLoaded', function() {

    // Show filter modal on page load if URL contains showFilterModal=true
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('showFilterModal') === 'true') {
        openFilterModal();
    }
    
    // Pre-populate filter modal with existing data when opened
    const filterButton = document.getElementById('filterButton');
    if (filterButton) {
        filterButton.addEventListener('click', function() {
            populateFilterModalWithExistingData();
        });
    }

    // Add click event for metrics button
    const metricsButton = document.getElementById("metricsButton");
    if (metricsButton) {
        metricsButton.addEventListener("click", openMetricsModal);
    }
});

function populateFilterModalWithExistingData() {
  // Prefill in this priority: localStorage (applied) -> server session (applied) -> leave defaults
  const storedData = localStorage.getItem('studentFilterData');
  const session = window.userSession || {};

  const values = storedData
    ? (function() { const d = JSON.parse(storedData); return { college: d.college, program: d.program, yearBatch: d.yearBatch, boardBatch: d.boardBatch }; })()
    : { college: session.filter_college || '', program: session.filter_program || '', yearBatch: session.filter_year_batch || '', boardBatch: session.filter_board_batch || '' };

  // Reset all selects to default so only applied filters remain visible
  const colSel0 = document.getElementById('filterCollege');
  const progSel0 = document.getElementById('filterProgram');
  const yearSel0 = document.getElementById('Year');
  const batchSel0 = document.getElementById('boardBatch');
  if (colSel0) colSel0.value = 'none';
  if (progSel0) progSel0.innerHTML = '<option value="none" disabled selected>Select</option>';
  if (yearSel0) yearSel0.value = 'none';
  if (batchSel0) batchSel0.value = 'none';

  // Apply with proper dependency ordering: College -> Program -> Year -> Board Batch
  if (values.college) {
    const colSel = document.getElementById('filterCollege');
    if (colSel) {
      colSel.value = values.college;
      populateFilterPrograms();
    }
  }
  if (values.program) {
    setTimeout(() => {
      const progSel = document.getElementById('filterProgram');
      if (progSel) progSel.value = values.program;
    }, 60);
  }
  if (values.yearBatch) {
    setTimeout(() => {
      const yearSel = document.getElementById('Year');
      if (yearSel) yearSel.value = values.yearBatch;
    }, 80);
  }
  if (values.boardBatch) {
    setTimeout(() => {
      populateBatch();
      const batchSel = document.getElementById('boardBatch');
      if (batchSel) batchSel.value = values.boardBatch;
    }, 100);
  }
}

/******************************* FILTER BUTTON ***************************/
let programOptions = {};
let yearLevelOptions = {};
let collegeOptions = [];
let batchOptions = {};
let subjectOptions = {};
let mockSubjectOptions = {};

// Initial fetch of data from PHP
fetch("/bridge/populate_filter.php")
  .then(res => res.json())
  .then(data => {
	console.log("Fetched JSON:", data);
    collegeOptions = data.collegeOptions;
    programOptions = data.programOptions;
    yearLevelOptions = data.yearLevelOptions;
    batchOptions = data.batchOptions;
	  subjectOptions = data.subjectOptions;
	  mockSubjectOptions = data.mockSubjectOptions;
	
  // --- Session-based filter logic ---
    const session = window.userSession || {};
    const level = parseInt(session.level, 10);

    populateFilterColleges();
    populateYears();
    populateBatch();

    const collegeSelect = document.getElementById("filterCollege");
    const programSelect = document.getElementById("filterProgram");
    const yearSelect = document.getElementById("filterYearLevel");
    const subjectSelect = document.getElementById("subjectCode");
    const mockSubjectSelect = document.getElementById("mockSubject");

 
    if (level == 0) {
      // Admin: All filters enabled
      collegeSelect.disabled = false;
      programSelect.disabled = false;
      yearSelect.disabled = false;
    } else if (level == 1 || level == 2) {
      // Dean: College fixed, programs under that college
      collegeSelect.value = session.college;
      collegeSelect.disabled = true;
      populateFilterPrograms();
      programSelect.disabled = false;
      yearSelect.disabled = false;
    } else if (level == 3) {
      // Program Head: College and program fixed
      collegeSelect.value = session.college;
      collegeSelect.disabled = true;
      populateFilterPrograms();
      programSelect.value = session.program;
      programSelect.disabled = true;
      populateFilterYears();
      yearSelect.disabled = false;
    }

    // Optionally, trigger population of years if program is set
    if (level === 3 && session.program) {
      populateBatch();
    }
  });

function populateFilterColleges() {
    const collegeSelect = document.getElementById("filterCollege");
    collegeSelect.innerHTML = '<option value="" disabled selected>Select</option>';

    collegeOptions.forEach(college => {
        const option = document.createElement("option");
        option.text = college.name;
        option.value = college.id;
        collegeSelect.add(option);
    });

    // Optionally reset the other dropdowns
    document.getElementById("filterProgram").innerHTML = '<option value="" disabled selected>Select</option>';
    populateYears();
    document.getElementById("boardBatch").innerHTML = '<option value disabled selected="none">Select</option>';
}

function populateFilterPrograms() {
    const college = document.getElementById("filterCollege").value;
    const programSelect = document.getElementById("filterProgram");
    programSelect.innerHTML = '<option value="" disabled selected>Select</option>';

    if (programOptions[college]) {
        programOptions[college].forEach(program => {
            const option = document.createElement("option");
            option.text = program.name;
            option.value = program.id;
            programSelect.add(option);
        });
    }
    populateYears();
    populateBatch(); // DOM is ready at this point

    }

function populateFilterYears() {
    const program = document.getElementById("filterProgram").value;
    const yearSelect = document.getElementById("filterYearLevel");
    yearSelect.innerHTML = '<option value="" disabled selected>Select</option>';

    if (yearLevelOptions[program]) {
        yearLevelOptions[program].forEach(year => {
            const option = document.createElement("option");
            option.text = year.name;
            option.value = year.id;
            yearSelect.add(option);
        });
    }

    document.getElementById("filterSection").innerHTML = '<option value="" disabled selected>Select</option>';
    populateBatch();
}

function populateYears(selectId) {
  const startYear = 2000;
  const currentYear = new Date().getFullYear();
  const latestYear = (new Date().getMonth() >= 5) ? currentYear : currentYear - 1;

  const select = document.getElementById('Year');
  select.innerHTML = ''; // Clear old options
  select.innerHTML = '<option value disabled selected="none">Select</option>';
  
  for (let year = latestYear + 2; year >= startYear; year--) {
    const option = document.createElement('option');
    option.value = `${year}`;
    option.textContent = `${year}`;
    select.appendChild(option);
  }

  // Select current academic year by default
  select.value = ``;
  
  populateBatch(); // DOM is ready at this point
}

function populateBatch() {
    const programId = document.getElementById("filterProgram").value;
    const year = document.getElementById("Year").value; // use value, not text
    
    const batchSelect = document.getElementById('boardBatch');
    batchSelect.innerHTML = '<option value="" disabled selected>Select</option>';

        const batch =
           batchOptions?.[programId]?.[year] || [];

        if (batch.length === 0) {
            const opt = document.createElement("option");
            opt.text = "No sections available";
            opt.disabled = true;
            batchSelect.add(opt);
        } else {
            batch.forEach(sec => {
                const opt = document.createElement("option");
                opt.value = sec;
                opt.text = sec;
                batchSelect.add(opt);
            });
        }
        return;
}

function populateFilterSections() {
    const year = document.getElementById("filterYearLevel").options[document.getElementById("filterYearLevel").selectedIndex].text;
    const sectionSelect = document.getElementById("filterSection");
    sectionSelect.innerHTML = '<option value="" disabled selected>Select</option>';

    if (year && year !== 'none') {
    for (let i = 1; i <= 20; i++) {
            const section = `${year[0]}-${i}`;
            const option = document.createElement("option");
            option.text = section;
            option.value = section;
            sectionSelect.add(option);
    }
}
}

function populateSubjects() {
    const program = document.getElementById("filterProgram").value;
    const subjectSelect = document.getElementById("subjectCode");
    subjectSelect.innerHTML = '<option value="" disabled selected>Select</option>';

    if (subjectOptions[program]) {
        subjectOptions[program].forEach(subject => {
            const option = document.createElement("option");
            option.text = subject.name;
            option.value = subject.id;
            subjectSelect.add(option);
        });
    }
}

function populateMockSubjects() {
    const program = document.getElementById("filterProgram").value;
    const mockSelect = document.getElementById("mockSubject");
    mockSelect.innerHTML = '<option value="" disabled selected>Select</option>';

    if (mockSubjectOptions[program]) {
        mockSubjectOptions[program].forEach(subject => {
            const option = document.createElement("option");
            option.text = subject.name;
            option.value = subject.id;
            mockSelect.add(option);
        });
    }
}

  	//populateSubjects();

/******************************* METRICS MODAL ***************************/

$(document).on("click", "#metricsButton", function () {
    openMetricsModal();
});

/******************* METRICS MODAL FUNCTIONALITY *******************/
let selectedMetric = "";

function openMetricsModal() {
  const session = window.userSession || {};
  // Prefer server session metric if available
  let metric = session.filter_metric || "";

  // If not available, infer from current page
  if (!metric) {
    const currentPage = window.location.pathname.split('/').pop();
    const pageToMetric = {
      'student-review-center': 'ReviewCenter',
      'mock-board-scores': 'MockScores',
      'licensure-exam-results': 'LicensureResult',
      'exam-date-taken': 'ExameDate',
      'exam-takes': 'TakeAttempt'
    };
    metric = pageToMetric[currentPage] || "";
  }

  // Prefill select if we resolved a metric
  const select = document.getElementById('metricSelect');
  if (select && metric) {
    select.value = metric;
    selectedMetric = metric;
  }

  document.getElementById("metricsModal").classList.add("show");
}

function closeMetricsModal() {
    closeModalWithAnimation("metricsModal");
}

function handleMetricChange() {
    selectedMetric = document.getElementById("metricSelect").value;
}

const metricToPage = {
    "ReviewCenter": "student-review-center",
    "MockScores": "mock-board-scores",
    "LicensureResult": "licensure-exam-results",
    "ExameDate": "exam-date-taken",
    "TakeAttempt": "exam-takes"
};

function goToFilterModal() {
  closeMetricsModal();

  if (!selectedMetric || !metricToPage[selectedMetric]) {
    alert("Please select a valid metric.");
    return;
  }

  // Build payload from applied filters so changing metric keeps filters
  const storedData = localStorage.getItem('studentFilterData');
  const session = window.userSession || {};
  const applied = storedData
    ? JSON.parse(storedData)
    : { college: session.filter_college || '', program: session.filter_program || '', yearBatch: session.filter_year_batch || '', boardBatch: session.filter_board_batch || '' };

  const url = metricToPage[selectedMetric];

  const formData = new FormData();
  formData.append('college', applied.college || '');
  formData.append('program', applied.program || '');
  // Server expects 'year' and 'board_batch'
  formData.append('year', applied.yearBatch || '');
  formData.append('board_batch', applied.boardBatch || '');
  formData.append('metric', selectedMetric);

  // Persist filters in session on the server, then navigate
  NProgress.start();
  fetch('/bridge/modules/program_metrics/processes/apply_filter.php', { method: 'POST', body: formData })
    .then(() => {
      setTimeout(() => { NProgress.set(0.7); }, 300);
      setTimeout(() => {
        NProgress.done();
        window.location.href = url;
      }, 1200);
    })
    .catch(() => {
      NProgress.done();
      window.location.href = url;
    });
}

function cancelMetricsModal() {
    closeMetricsModal();
}

// Initialize modals on page load - consolidated with main DOMContentLoaded listener

// Add click event for clicking outside the modal
window.addEventListener('click', function(event) {
    const filterModal = document.getElementById('filterModal');
    if (event.target === filterModal) {
        closeFilterModal();
    }
});




//TRANSERING DATA FROM TABLE TO ANOTHER TEXTBOX
//TRANSERING DATA FROM TABLE TO ANOTHER TEXTBOX
//TRANSERING DATA FROM TABLE TO ANOTHER TEXTBOX
//TRANSERING DATA FROM TABLE TO ANOTHER TEXTBOX
//TRANSERING DATA FROM TABLE TO ANOTHER TEXTBOX
//TRANSERING DATA FROM TABLE TO ANOTHER TEXTBOX
//TRANSERING DATA FROM TABLE TO ANOTHER TEXTBOX
//TRANSERING DATA FROM TABLE TO ANOTHER TEXTBOX
//TRANSERING DATA FROM TABLE TO ANOTHER TEXTBOX
//TRANSERING DATA FROM TABLE TO ANOTHER TEXTBOX
/**********************************************TRANSFERING DATA FROM TABLE TO ANOTHER TEXTBOX**********************************************/
document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);
    const studentId = params.get('studentId');
    const studentName = params.get('studentName');
    const studentField1 = params.get('studentField1');
    const studentField2 = params.get('studentField2');
    const studentField3 = params.get('studentField3');
    const studentField4 = params.get('studentField4');
    const studentField5 = params.get('studentField5');
    const studentField6 = params.get('studentField6');
    /*if (studentId) {
      document.getElementById('studentId').value = studentId;
    }*/
    if (studentName) {
      document.getElementById('studentName').value = decodeURIComponent(studentName.replace(/\+/g, ' '));
    }
    if (studentField1) {
      document.getElementById('studentField1').value = decodeURIComponent(studentField1.replace(/\+/g, ' '));
    }
    if (studentField2) {
      document.getElementById('studentField2').value = decodeURIComponent(studentField2.replace(/\+/g, ' '));
    }
    if (studentField3) {
      document.getElementById('studentField3').value = decodeURIComponent(studentField3.replace(/\+/g, ' '));
    }
    if (studentField4) {
      document.getElementById('studentField4').value = decodeURIComponent(studentField4.replace(/\+/g, ' '));
    }
    if (studentField5) {
      document.getElementById('studentField5').value = decodeURIComponent(studentField5.replace(/\+/g, ' '));
    }
    if (studentField6) {
      document.getElementById('studentField6').value = decodeURIComponent(studentField6.replace(/\+/g, ' '));
    }
  });












//ADD STUDENT INFO PHP FILE JS
//ADD STUDENT INFO PHP FILE JS
//ADD STUDENT INFO PHP FILE JS
//ADD STUDENT INFO PHP FILE JS
//ADD STUDENT INFO PHP FILE JS
////////////////////////FOR CONFIRM ADD STUDENT//////////////////////////
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("viewStudentForm");
    const saveButton = document.getElementById("saveButton");
    const modal = document.getElementById("validationModal");
    const confirmSave = document.getElementById("confirmSave");
  const cancelSave = document.getElementById("cancelSave");
    const loader = document.getElementById("loader"); // Reference to the loader div

    // Prevent form submission when pressing enter or clicking the save button
    form.addEventListener("submit", function (e) {
        e.prevent
    const cancelSave = doDefault(); // Prevent actual form submission
    });

    saveButton.addEventListener("click", function (e) {
        e.preventDefault(); // Explicitly prevent the form from submitting when clicking the save button

        // Validate form
        if (form.checkValidity()) {
            document.getElementById('validationModal').classList.add('show'); // Show modal if valid
        } else {
            form.reportValidity(); // Show which fields are missing
        }
    });

    confirmSave.addEventListener("click", function () {
        // 1. Show spinner immediately
  loader.style.display = "inline-block";
  confirmSave.disabled = true;

  // 2. Wait for spinner to be visible
  setTimeout(function () {
    // 3. Show toast
    showToast("Student Data has been successfully recorded!");

    // 4. Wait for toast to be visible
    setTimeout(function () {
      // 5. Start NProgress
      NProgress.start();

      // 6. Animate NProgress to 70%
      setTimeout(function () {
        NProgress.set(0.7);
      }, 300);

      // 7. Finish NProgress and reload (imitate same-page logic)
      setTimeout(function () {
        NProgress.done();
        loader.style.display = "none";
        //window.location.href = confirmSave.getAttribute("data-href");
        document.getElementById("viewStudentForm").submit();
      }, 1200); // NProgress duration

    }, 1000); // Toast duration

  }, 100); // Spinner duration
});


  cancelSave.addEventListener("click", function () {
    closeModalWithAnimation('validationModal'); // Hide modal
  });

  window.addEventListener("click", function (event) {
    if (event.target === modal) {
      closeModalWithAnimation('validationModal'); // Hide modal
    }
  });
});




/////EDIT STUDENT INFORMATION MODAL FUNCTIONALITY
/////EDIT STUDENT INFORMATION MODAL FUNCTIONALITY
/////EDIT STUDENT INFORMATION MODAL FUNCTIONALITY
/////EDIT STUDENT INFORMATION MODAL FUNCTIONALITY
/////EDIT STUDENT INFORMATION MODAL FUNCTIONALITY
/****************************EDIT STUDENT INFORMATION***************************/
// --- EDIT GWA MODAL BUTTON LOGIC ---
document.addEventListener("DOMContentLoaded", function () {
  const updateBtn = document.getElementById("updateBtn"); 
  const editLoader = document.getElementById("editLoader");
  const editModal = document.getElementById("editStudentModal");
const form = document.getElementById("addStudentForm");

  if (updateBtn && editLoader) {
    updateBtn.addEventListener("click", function () {
  console.log("Update button clicked");
      if (!form.reportValidity()) {
        const label = `Please fill out the required information!`;
        showToast(label);
        return;
      }

      editLoader.style.display = "inline-block";
      updateBtn.disabled = true;

      setTimeout(() => {
        const label = `Student data has been successfully updated!`;
        showToast(label);

        setTimeout(() => {
          NProgress.start();
          setTimeout(() => NProgress.set(0.7), 300);

          setTimeout(() => {
            NProgress.done();
            editLoader.style.display = "none";
            updateBtn.disabled = false;
            closeEditModal();
            document.getElementById("addStudentForm").submit();
          }, 1200);
        }, 1000);
      }, 100);
    });
  }
});


// Helper to open the edit modal and ensure input is editable
function openEditModal(data = {}, context = '') {
  const modal = document.getElementById('editStudentModal');

  // Dynamically fill fields
  Object.keys(data).forEach((key, index) => {
    const input = document.getElementById(`studentField${index + 1}`);
    if (input) {
      input.value = data[key];
      input.readOnly = false;
    }
  });

  // Store context if provided
  if (context) {
    modal.setAttribute('data-context', context);
  } else {
    modal.removeAttribute('data-context'); // Remove if no context needed
  }

  modal.classList.add('show');
  document.body.classList.add('modal-open');
  document.getElementById('cancelEdit').addEventListener('click', closeEditModal);
}

function closeEditModal() {
  const modal = document.getElementById('editStudentModal');
  if (modal) {
    closeModalWithAnimation('editStudentModal');
    document.body.classList.remove('modal-open');
  }
}



/******************************* DATE INPUT TYPE CUSTOM CLICK ANYWHERE ***************************/
document.addEventListener('DOMContentLoaded', function () {
  const datetaken = document.getElementById('datetaken');
  if (datetaken) {
    datetaken.addEventListener('click', () => {
      if (datetaken.showPicker) {
        datetaken.showPicker();
      }
    });
  }
});



/************************************OPTION IF THE USER HAS UNSAVED DATA MUST BE AT THE END OF THE SCRIPT*************************************/
document.addEventListener("DOMContentLoaded", function () {
  let isFormDirty = false;

  // 1. Mark form as dirty when any input/select changes
  document.querySelectorAll("#viewStudentForm input, #viewStudentForm select").forEach(el => {
    el.addEventListener("change", () => {
      isFormDirty = true;
    });
  });

  // 2. Intercept navigation (like closing or reloading the tab)
  window.addEventListener("beforeunload", function (e) {
    if (isFormDirty) {
      e.preventDefault(); // Required for some browsers
      e.returnValue = ""; // Triggers browser confirmation dialog
      return "";
    }
  });

  // 3. If user confirms Save via modal, mark form as not dirty
  document.getElementById("confirmSave").addEventListener("click", function () {
  isFormDirty = false;
  // Do NOT redirect here! The main confirmSave handler will handle the redirect with UI feedback.
});

  // 4. Also mark not dirty if navigating back intentionally
  document.querySelector(".back-page").addEventListener("click", function (e) {
    isFormDirty = false;
    // Do NOT redirect here; let the global handler handle navigation and NProgress
});
});

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
        toast.style.background = "#60357a";
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

// Helper to build a URL with only the needed student fields
function buildStudentUrl(targetUrl, params) {
  const urlParams = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      urlParams.append(key, value);
    }
  });
  return `${targetUrl}?${urlParams.toString()}`;
}

// Intercept Student ID link clicks and redirect with data
$(document).ready(function () {
  $('#myTable').on('click', 'a.next-page', function (e) {
    e.preventDefault();

    // Use data-href if present
    const targetUrl = $(this).data('href');

    NProgress.start();
    setTimeout(function() {
      NProgress.set(0.7);
    }, 300);
    setTimeout(function() {
      NProgress.done();
      window.location.href = targetUrl;
    }, 1200);
  });
});









