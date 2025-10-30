/******************************UPON LOADING THE PAGE***********************************/
window.addEventListener('DOMContentLoaded', () => {
    const content = document.querySelector('.content');
    if (content) {
      content.classList.add('fade-in');
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


/////////////////////*************FILTERING STUDENTS*****************/////////////////////
let programOptions = {};
let yearLevelOptions = {};
let collegeOptions = [];
let batchYearOptions = [];

  fetch("/bridge/populate_filter.php")
    .then(res => res.json())
    .then(data => {
      console.log("Fetched JSON:", data);
      collegeOptions = data.collegeOptions;
      programOptions = data.programOptions;
      yearLevelOptions = data.yearLevelOptions;
      batchYearOptions = data.batchYearOptions;
      
    // --- Session-based filter logic ---
    const session = window.userSession || {};
    const level = parseInt(session.level, 10);

      populateColleges(); // DOM is ready at this point
      populateYearStart(); // DOM is ready at this point
      populateYearEnd(); // DOM is ready at this point

      
    const collegeSelect = document.getElementById("displayCollege");
    const programSelect = document.getElementById("displayProgram");
    
          if (level == 0) {
      // Admin: All filters enabled
      collegeSelect.disabled = false;
      programSelect.disabled = false;
    } else if (level == 1) {
      // Dean: College fixed, programs under that college
      collegeSelect.value = session.college;
      collegeSelect.disabled = true;
      populatePrograms();
      programSelect.disabled = false;
    } else if (level == 2) {
      // Assistant: Same as Dean - College fixed, programs under that college
      collegeSelect.value = session.college;
      collegeSelect.disabled = true;
      populatePrograms();
      programSelect.disabled = false;
    } else if (level == 3) {
      // Program Head: College and program fixed
      collegeSelect.value = session.college;
      collegeSelect.disabled = true;
      populatePrograms();
      programSelect.value = session.program;
      programSelect.disabled = true;
    }
  });


function populateColleges() {
    const collegeSelect = document.getElementById("displayCollege");
    collegeSelect.innerHTML = '<option value="none" disabled selected>Select</option>';

    collegeOptions.forEach(college => {
        const option = document.createElement("option");
        option.text = college.name;
        option.value = college.id;
        collegeSelect.add(option);
    });

    // Optionally reset the other dropdowns
    document.getElementById("displayProgram").innerHTML = '<option value="none" disabled selected>Select</option>';
}

function populatePrograms() {
    const college = document.getElementById("displayCollege").value.toUpperCase();
    const programSelect = document.getElementById("displayProgram");
    programSelect.innerHTML = '<option value="none" disabled selected>Select</option>';

    if (programOptions[college]) {
        programOptions[college].forEach(program => {
            const option = document.createElement("option");
            option.text = program.name;
            option.value = program.id;
            programSelect.add(option);
        });
    }
}

/* A common utility to simplify the code
const getYearConstants = () => {
    const minYear = 2015;
    // Set a reasonable absolute max year (e.g., current year + 5)
    // Current year is 2025, so maxYear is 2030
    const maxYear = new Date().getFullYear() + 5; 
    return { minYear, maxYear };
};*/

// --------------------------------------------------------------------------------------------------

function populateYearStart() {
    const programId = document.getElementById("displayProgram")?.value; // assumes you have a Program dropdown
    const select = document.getElementById("YearStart");
    const tempStartYear = select.value;

    select.innerHTML = '<option value="" disabled selected>Select Start Year</option>';

    if (!programId || !batchYearOptions[programId]) return;

    const years = [...batchYearOptions[programId]].sort((a, b) => b - a); // descending

    years.forEach(year => {
        const option = document.createElement('option');
        option.value = year;
        option.textContent = year;
        select.appendChild(option);
    });

    // Restore previous value if still valid
    if (years.includes(parseInt(tempStartYear))) {
        select.value = tempStartYear;
    }

    populateYearEnd(); // refresh YearEnd accordingly
}

function populateYearEnd() {
    const programId = document.getElementById("displayProgram")?.value;
    const select = document.getElementById("YearEnd");
    const tempEndYear = select.value;
    const startYear = parseInt(document.getElementById("YearStart")?.value);

    select.innerHTML = '<option value="" disabled selected>Select End Year</option>';

    if (!programId || !batchYearOptions[programId]) return;

    let years = [...batchYearOptions[programId]].sort((a, b) => b - a);

    // Optional: restrict end years >= start year
    if (!isNaN(startYear)) {
        years = years.filter(y => y >= startYear);
    }

    years.forEach(year => {
        const option = document.createElement('option');
        option.value = year;
        option.textContent = year;
        select.appendChild(option);
    });

    if (years.includes(parseInt(tempEndYear))) {
        select.value = tempEndYear;
    }
}

function clearFilters() {
    const session = window.userSession || {};
    const level = parseInt(session.level, 10);

    const collegeSelect = document.getElementById("displayCollege");
    const programSelect = document.getElementById("displayProgram");
    const yearStartSelect = document.getElementById("YearStart");
    const yearEndSelect = document.getElementById("YearEnd");

    if (level === 0) {
        // Admin: full reset
        document.getElementById("filterForm").reset();
        programSelect.innerHTML = '<option value="none" disabled selected>Select</option>';
        if (yearStartSelect) yearStartSelect.innerHTML = '<option value="none" disabled selected>Select</option>';
        if (yearEndSelect) yearEndSelect.innerHTML = '<option value="none" disabled selected>Select</option>';
    } else if (level === 1 || level === 2) {
        // Dean/Assistant: keep college fixed
        collegeSelect.value = session.college;
        collegeSelect.disabled = true;
        populatePrograms();
        programSelect.disabled = false;
        // reset dependent selects only
        programSelect.selectedIndex = 0; // back to 'Select'
        if (yearStartSelect) yearStartSelect.selectedIndex = 0;
        if (yearEndSelect) yearEndSelect.selectedIndex = 0;
    } else if (level === 3) {
        // Program Head: keep college and program fixed
        collegeSelect.value = session.college;
        collegeSelect.disabled = true;
        populatePrograms();
        setTimeout(() => {
            programSelect.value = session.program;
            programSelect.disabled = true;
        }, 0);
        // reset dependent selects only
        if (yearStartSelect) yearStartSelect.selectedIndex = 0;
        if (yearEndSelect) yearEndSelect.selectedIndex = 0;
    }
}

/******************************* FORM VALIDATION AND SUBMISSION ***************************/
let filterData = null; // Store filter data globally

document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterForm');
    
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get all filter values
            const college = document.getElementById('displayCollege').value;
            const collegeText = document.getElementById('displayCollege').options[document.getElementById('displayCollege').selectedIndex].text;
            const program = document.getElementById('displayProgram').value;
            const programText = document.getElementById('displayProgram').options[document.getElementById('displayProgram').selectedIndex].text;
            const yearBatchStart = document.getElementById('YearStart').value;
            const yearBatchEnd = document.getElementById('YearEnd').value;
            
            // Get all filter values
            const yearFilterField = document.getElementById('yearFilterField').value;

            const yearFilter = document.getElementById("yearFilter");

            // Check if all fields are selected (case-insensitive, also check for empty)
            if (yearFilter.style.display === "none") {
              if (!college || college.toLowerCase() === 'none' ||
                  !program || program.toLowerCase() === 'none' ||
                  !yearBatchStart || yearBatchStart.toLowerCase() === 'none' ||
                  !yearBatchEnd || yearBatchEnd.toLowerCase() === 'none') {
                  showToast('Please select all filter options before proceeding.');
                  return false;
                }
            } else {
              if (!yearFilterField || yearFilterField.toLowerCase() === 'none') {
                  showToast('Please select all filter options before proceeding.');
                  return false;
                }
              }
            
            
            // Store filter data in localStorage for the main page
            if (yearFilter.style.display === "none") {
                const filterData = {
                    filterType: 'batch',
                    college: college,
                    collegeText: collegeText,
                    program: program,
                    programText: programText,
                    yearBatchStart: yearBatchStart,
                    yearBatchEnd: yearBatchEnd
                };
                localStorage.setItem('studentFilterData', JSON.stringify(filterData));
            } else {
                const filterData = {
                    filterType: 'batch',
                    yearFilterField: yearFilterField
                };
                localStorage.setItem('studentFilterData', JSON.stringify(filterData));
            }
            
            // Redirect to student_info.php with filter parameters
            const params = new FormData();

            if (yearFilter.style.display === "none") {
              params.append('filter_type', 'batch');
              params.append('college', college);
              params.append('program', program);
              params.append('yearBatchStart', yearBatchStart);
              params.append('yearBatchEnd', yearBatchEnd);
              params.append('from_filter', 'true');
            } else {
              params.append('filter_type', 'programs');
              params.append('yearBatch', yearFilterField);
              params.append('from_filter', 'true');
            }

            fetch("/bridge/modules/generate_report/processes/apply_filter.php", {
              method: "POST",
              body: params
            })
            .then(res => res.text())
            .then(response => {
              console.log("Server response:", response);
            
              // Start NProgress before redirecting
              NProgress.start();
              setTimeout(() => {
                  NProgress.set(0.7);
              }, 300);

              setTimeout(() => {
                  NProgress.done();
                  // Force a repaint before redirect
                  setTimeout(() => {
                      window.location.href = response;
                      //document.getElementById("filterForm").submit();
                  }, 100); // Short delay to allow NProgress to show
              }, 1200);

              });
        });
    }
});

/*TOGGLE FILTER*/
const toggleBtn = document.getElementById("toggleFilter");
  const batchFilter = document.getElementById("batchFilter");
  const yearFilter = document.getElementById("yearFilter");
  const headTitle = document.getElementById("headTitle");

  toggleBtn.addEventListener("click", (e) => {
    e.preventDefault(); // avoid submitting form / triggering validations
    if (yearFilter.style.display === "none") {
      yearFilter.style.display = "block";
      batchFilter.style.display = "none";
      const span = toggleBtn.querySelector('span');
      if (span) span.innerText = "Switch to Batch Reports";
      headTitle.innerText = "Report Generation\nProgram Statistics";
    //clearFilters();
    } else {
      yearFilter.style.display = "none";
      batchFilter.style.display = "block";
      const span = toggleBtn.querySelector('span');
      if (span) span.innerText = "Switch to Programs Statistics";
      headTitle.innerText = "Report Generation\nBatch Reports";
    //clearFilters();
    }
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