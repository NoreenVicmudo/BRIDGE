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

  // Handle incoming filter data from student_info_filter.php
  handleIncomingFilterData();
  
});


/*************************** LOADERS LOGIC FOR NEXT AND BACK PAGES ***************************/
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
    $(".dataTables_filter input").attr("placeholder", "Search transactions...");

    $(".btn-group").html(`
      <button class="btn btn-outline-primary me-1" id="filterButton">
        <i class="bi bi-funnel-fill"></i> Filter
      </button>
    `);

    // Add click event for filter button
    $("#filterButton").on("click", function() {
      $("#filterModal").css("display", "flex");
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


/******************************* HANDLE INCOMING FILTER DATA ***************************/
/******************************* HANDLE INCOMING FILTER DATA ***************************/
/******************************* HANDLE INCOMING FILTER DATA ***************************/
/******************************* HANDLE INCOMING FILTER DATA ***************************/
/******************************* HANDLE INCOMING FILTER DATA ***************************/
/******************************* HANDLE INCOMING FILTER DATA ***************************/
function handleIncomingFilterData() {
  // Check if we have URL parameters from filter page
  const urlParams = new URLSearchParams(window.location.search);
  const fromFilter = urlParams.get('from_filter');
  
  if (fromFilter === 'true') {
      // Get filter data from URL parameters
      const academicYear = localStorage.getItem('academicYear');
      const college = localStorage.getItem('college');
      const program = localStorage.getItem('program');
      const yearLevel = localStorage.getItem('year_level');
      const section = localStorage.getItem('section');
      
      // Display the active filters
      displayActiveFilters(academicYear, college, program, yearLevel, section);
      
      // Store in localStorage for persistence
      const filterData = {
          academicYear: academicYear,
          college: collegeText,
          program: programText,
          yearLevel: yearLevelText,
          section: section
      };
      localStorage.setItem('studentFilterData', JSON.stringify(filterData));
  } else {
      // Check localStorage for existing filter data
      const storedData = localStorage.getItem('studentFilterData');
      if (storedData) {
          const filterData = JSON.parse(storedData);
          displayActiveFilters(
              filterData.academicYear,
              filterData.collegeText,
              filterData.programText,
              filterData.yearLevelText,
              filterData.section
          );
      }
  }
}

function displayActiveFilters(college, action) {
  // Create filter display HTML
  let filterDisplayHTML = '<div class="form-container">';
  
  if (college && college !== 'none') {
      filterDisplayHTML += `
          <div class="form-group">
              <label>College:</label>
              <span>${college}</span>
          </div>`;
  }
  
  if (action && action !== 'none') {
      filterDisplayHTML += `
          <div class="form-group">
              <label>Action:</label>
              <span>${action}</span>
          </div>`;
  }

  filterDisplayHTML += '</div>';

  // Update the display
  const activeFiltersDisplay = document.getElementById("activeFiltersDisplay");
  if (activeFiltersDisplay) {
      activeFiltersDisplay.innerHTML = filterDisplayHTML;
  }
}

function closeFilterModal() {
  const modal = document.getElementById("filterModal");
  modal.classList.add("closing");
  
  // Wait for animation to complete before hiding
  setTimeout(() => {
    modal.style.display = "none";
    modal.classList.remove("closing");
  }, 500);
}

// Event Listeners for Filter Modal
document.addEventListener('DOMContentLoaded', function() {
  // Close modal when clicking outside
  window.onclick = function(event) {
      const modal = document.getElementById('filterModal');
      if (event.target === modal) {
          closeFilterModal();
      }
  }

  // Show filter modal on page load if URL contains showFilterModal=true
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('showFilterModal') === 'true') {
      document.getElementById('filterModal').style.display = 'flex';
  }
  
  // Pre-populate filter modal with existing data when opened
  const filterButton = document.getElementById('filterButton');
  if (filterButton) {
      filterButton.addEventListener('click', function() {
          populateFilterModalWithExistingData();
      });
  }
});

function populateFilterModalWithExistingData() {
  // Get existing filter data from localStorage
  const storedData = localStorage.getItem('studentFilterData');
  if (storedData) {
      const filterData = JSON.parse(storedData);
      
      // Pre-populate the filter modal fields
      if (filterData.academicYear) {
          document.getElementById('filterAcademicYear').value = filterData.academicYear;
      }
      if (filterData.college) {
          document.getElementById('filterCollege').value = filterData.college;
          populateFilterPrograms(); // Populate programs based on college
      }
      if (filterData.program) {
          // Need to wait for programs to populate
          setTimeout(() => {
              document.getElementById('filterProgram').value = filterData.program;
              populateFilterYears(); // Populate years based on program
          }, 100);
      }
      if (filterData.yearLevel) {
          // Need to wait for years to populate
          setTimeout(() => {
              document.getElementById('filterYearLevel').value = filterData.yearLevel;
              populateFilterSections(); // Populate sections based on year
          }, 200);
      }
      if (filterData.section) {
          // Need to wait for sections to populate
          setTimeout(() => {
              document.getElementById('filterSection').value = filterData.section;
          }, 300);
      }
  }
}


  //document.addEventListener('DOMContentLoaded', function () {
/******************************* FILTER BUTTON ***************************/
let collegeOptions = [];

// Initial fetch of data from PHP
fetch("populate_filter.php")
.then(res => res.json())
.then(data => {
  collegeOptions = data.collegeOptions;

  // --- Session-based filter logic ---
  const session = window.userSession || {};
  const level = parseInt(session.level, 10);

  populateFilterColleges();

  const collegeSelect = document.getElementById("filterCollege");


  if (level === 0) {
    // Admin: All filters enabled
    collegeSelect.disabled = false;
  } else if (level === 1) {
    // Dean: College fixed, programs under that college
    collegeSelect.value = session.college;
    collegeSelect.disabled = true;
  } else if (level === 2) {
    // Program Head: College and program fixed
    collegeSelect.value = session.college;
    collegeSelect.disabled = true;
  }
});

function populateFilterColleges() {
  const collegeSelect = document.getElementById("filterCollege");
  collegeSelect.innerHTML = '<option value="all">ALL</option>';

  collegeOptions.forEach(college => {
      const option = document.createElement("option");
      option.text = college.name;
      option.value = college.id;
      collegeSelect.add(option);
  });
}


/************************************DATA TABLES FILTER CONNECTED TO DATABASE*************************************/
function applyFilters() {
const college = document.getElementById("filterCollege").value;
const collegeText = document.getElementById("filterCollege").options[document.getElementById("filterCollege").selectedIndex].text;
const action = document.getElementById("filterAction").value;
const actionText = document.getElementById("filterAction").options[document.getElementById("filterAction").selectedIndex].text;


// Check if all fields are selected (I put this here to ensure all fields are selected before proceeding)
  if (
      college === '' ||
      action === ''
  ) {
      alert('Please select all filter options before proceeding.');
      return false;
  }
  
  // Update the display with new filter values
  displayActiveFilters(collegeText, actionText);

  
  // Store updated filter data in localStorage
  const filterData = {
      college: college,
      collegeText: collegeText,
      action: action,
      actionText: actionText
  };
  localStorage.setItem('studentFilterData', JSON.stringify(filterData));
  
  closeFilterModal();

// Prepare form data
const formData = new FormData();
formData.append("college", college);
formData.append("action", action);

fetch("modules/transaction_logs/processes/filter_table_transaction.php", {
  method: "POST",
  body: formData
})
.then(res => res.text()) // we expect HTML rows here
.then(data => {
  // Update rows using DataTables API to prevent header/body misalignment
  const table = window.studentInfoTable || $('#myTable').DataTable();
  table.clear();
  const $rows = $(data).filter('tr');
  $rows.each(function () { table.row.add(this); });
  table.draw(false);
  table.columns.adjust().responsive.recalc();
})
.catch(err => {
  console.error("Error loading table:", err);
});
}


/************************************PAGE ON LOAD WITH FILTER*************************************/
document.addEventListener('DOMContentLoaded', function () {
  const fromFilter = new URLSearchParams(window.location.search).get('from_filter');

  if (fromFilter === 'true') {
      const filterData = JSON.parse(localStorage.getItem('studentFilterData') || '{}');

      const formData = new FormData();
      formData.append('academic_year', filterData.academicYear);
      formData.append('college', filterData.college);
      formData.append('program', filterData.program);
      formData.append('year_level', filterData.yearLevel);
      formData.append('section', filterData.section);

      fetch('filter_table.php', {
          method: 'POST',
          body: formData
      })
      .then(res => res.text())
      .then(data => {
          // Update rows using DataTables API to prevent header/body misalignment
          const table = window.studentInfoTable || $('#myTable').DataTable();
          table.clear();
          const $rows = $(data).filter('tr');
          $rows.each(function () { table.row.add(this); });
          table.draw(false);
          table.columns.adjust().responsive.recalc();
      })
      .catch(err => {
          console.error('Error loading filtered table:', err);
      });
  }
});