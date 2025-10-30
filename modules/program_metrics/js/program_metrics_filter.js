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
let batchOptions = {};

  fetch("/bridge/populate_filter.php")
    .then(res => res.json())
    .then(data => {
      console.log("Fetched JSON:", data);
      collegeOptions = data.collegeOptions;
      programOptions = data.programOptions;
      yearLevelOptions = data.yearLevelOptions;
      batchOptions = data.batchOptions;
      
    // --- Session-based filter logic ---
    const session = window.userSession || {};
    const level = parseInt(session.level, 10);

      populateColleges(); // DOM is ready at this point
      populateYears('Year'); // DOM is ready at this point
      populateBatch(); // DOM is ready at this point

      
    const collegeSelect = document.getElementById("displayCollege");
    const programSelect = document.getElementById("displayProgram");
    
          if (level == 0) {
      // Admin: All filters enabled
      collegeSelect.disabled = false;
      programSelect.disabled = false;
    } else if (level == 1 || level == 2) {
      // Dean: College fixed, programs under that college
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
    collegeSelect.innerHTML = '<option value disabled selected="none">Select</option>';

    collegeOptions.forEach(college => {
        const option = document.createElement("option");
        option.text = college.name;
        option.value = college.id;
        collegeSelect.add(option);
    });

    // Optionally reset the other dropdowns
    document.getElementById("displayProgram").innerHTML = '<option value disabled selected="none">Select</option>';
    populateYears('Year');
    document.getElementById("boardBatch").innerHTML = '<option value disabled selected="none">Select</option>';
}

function populatePrograms() {
    const college = document.getElementById("displayCollege").value.toUpperCase();
    const programSelect = document.getElementById("displayProgram");
    programSelect.innerHTML = '<option value disabled selected="none">Select</option>';

    if (programOptions[college]) {
        programOptions[college].forEach(program => {
            const option = document.createElement("option");
            option.text = program.name;
            option.value = program.id;
            programSelect.add(option);
        });
    }

    populateYears('Year');
    populateBatch(); // DOM is ready at this point
}

function populateYears(selectId) {
  const startYear = 2000;
  const currentYear = new Date().getFullYear();
  const latestYear = (new Date().getMonth() >= 5) ? currentYear : currentYear - 1;

  const select = document.getElementById(selectId);
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
    const programId = document.getElementById("displayProgram").value;
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

function clearFilters() {
    const session = window.userSession || {};
    const level = parseInt(session.level, 10);

    const collegeSelect = document.getElementById("displayCollege");
    const programSelect = document.getElementById("displayProgram");
    const yearSelect = document.getElementById("Year");
    const batchSelect = document.getElementById("boardBatch");

    if (level === 0) {
        // Admin: full reset
        document.getElementById("filterForm").reset();
        programSelect.innerHTML = '<option value disabled selected="none">Select</option>';
    } else if (level === 1 || level === 2) {
        // Dean/Assistant: keep college fixed
        collegeSelect.value = session.college;
        collegeSelect.disabled = true;
        populatePrograms();
        programSelect.disabled = false;
        // reset dependent selects only
        programSelect.selectedIndex = 0; // back to 'Select'
        if (yearSelect) yearSelect.selectedIndex = 0;
        if (batchSelect) batchSelect.selectedIndex = 0;
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
        if (yearSelect) yearSelect.selectedIndex = 0;
        if (batchSelect) batchSelect.selectedIndex = 0;
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
            const yearBatch = document.getElementById('Year').value;
            const boardBatch = document.getElementById('boardBatch').value;

            // Check if all fields are selected (case-insensitive, also check for empty)
            if (
                !college || college.toLowerCase() === 'none' ||
                !program || program.toLowerCase() === 'none' ||
                !yearBatch || yearBatch.toLowerCase() === 'none' ||
                !boardBatch || boardBatch.toLowerCase() === 'none'
            ) {
                showToast('Please select all filter options before proceeding.');
                return false;
            }
            
            // Store filter data in localStorage for the main page
            const filterData = {
                college: college,
                collegeText: collegeText,
                program: program,
                programText: programText,
                yearBatch: yearBatch,
                boardBatch: boardBatch
            };
            
            localStorage.setItem('studentFilterData', JSON.stringify(filterData));
            
            // Redirect to student_info.php with filter parameters
            const params = new URLSearchParams();
              params.append('college', college);
              params.append('program', program);
              params.append('yearBatch', yearBatch);
              params.append('boardBatch', boardBatch);
              params.append('from_filter', 'true');

            fetch("/bridge/modules/program_metrics/processes/apply_filter.php", {
              method: "POST",
              body: params
            })
            .then(res => res.text())
            .then(response => console.log(response));
            
            // Start NProgress before redirecting
            NProgress.start();
            setTimeout(() => {
                NProgress.set(0.7);
            }, 300);

            setTimeout(() => {
                NProgress.done();
                // Force a repaint before redirect
                setTimeout(() => {
                    window.location.href = 'student-information';  //////////////////////////////////////////////////////////////////////////////////
		                //document.getElementById("filterForm").submit();
                }, 100); // Short delay to allow NProgress to show
            }, 1200);
        });
    }

    // Handle Filter Students button click
    document.getElementById('filterStudentsBtn').addEventListener('click', function() {
        // Get all filter values
        const college = document.getElementById('displayCollege').value;
        const collegeText = document.getElementById('displayCollege').options[document.getElementById('displayCollege').selectedIndex].text;
        const program = document.getElementById('displayProgram').value;
        const programText = document.getElementById('displayProgram').options[document.getElementById('displayProgram').selectedIndex].text;
        const yearBatch = document.getElementById('Year').value;
        const boardBatch = document.getElementById('boardBatch').value;

        // Check if all fields are selected (case-insensitive, also check for empty)
            if (
                !college || college.toLowerCase() === 'none' ||
                !program || program.toLowerCase() === 'none' ||
                !yearBatch || yearBatch.toLowerCase() === 'none' ||
                !boardBatch || boardBatch.toLowerCase() === 'none'
            ) {
                showToast('Please select all filter options before proceeding.');
                return false;
            }

        // Store filter data globally
        filterData = {
            college: college,
            collegeText: collegeText,
            program: program,
            programText: programText,
            yearBatch: yearBatch,
            boardBatch: boardBatch
          };

        // Optionally store in localStorage if you want
        localStorage.setItem('studentFilterData', JSON.stringify(filterData));

        // Show the modal
        openMetricsModal();
    });
});

/******************* METRICS MODAL FUNCTIONALITY *******************/
let selectedMetric = "";

function openMetricsModal() {
    const session = window.userSession || {};
    // Prefer server session metric if available
    let metric = session.filter_metric || "";
    // Prefill select if we resolved a metric
    const select = document.getElementById('metricSelect');
    if (select && metric) {
        select.value = metric;
        selectedMetric = metric;
    }
    document.getElementById("metricsModal").classList.add("show");
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

function closeMetricsModal() {
    closeModalWithAnimation("metricsModal");
}

function closeFilterModal() {
    closeModalWithAnimation("filterModal");
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
        showToast("Please select a valid metric.");
        return;
    }

    if (!filterData) {
        showToast("Please select filter options first.");
        return;
    }

    const url = metricToPage[selectedMetric];
    const params = new FormData();
    params.append('college', filterData.college);
    params.append('program', filterData.program);
    // Server expects 'year' and 'board_batch'
    params.append('year', filterData.yearBatch);
    params.append('board_batch', filterData.boardBatch);
    params.append('metric', selectedMetric);

    fetch("/bridge/modules/program_metrics/processes/apply_filter.php", {
      method: "POST",
      body: params
    })
    .then(() => {
      // Start NProgress before redirecting
      NProgress.start();
      setTimeout(() => { NProgress.set(0.7); }, 300);
      setTimeout(() => {
        NProgress.done();
        window.location.href = url;
      }, 1200);
    })
    .catch(() => {
      window.location.href = url;
    });
}

function cancelMetricsModal() {
    closeMetricsModal();
}

// Initialize modals on page load
window.addEventListener('DOMContentLoaded', () => {
    // Show filter modal if URL has showFilterModal=true
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('showFilterModal') === 'true') {
        const filterModal = document.getElementById("filterModal");
        if (filterModal) {
            filterModal.classList.add('show');
        }
    }

    // Add click event for metrics button (if it exists)
    const metricsButton = document.getElementById("metricsButton");
    if (metricsButton) {
        metricsButton.addEventListener("click", openMetricsModal);
    }
});

// Add click event for clicking outside the modal
window.addEventListener('click', function(event) {
    const filterModal = document.getElementById('filterModal');
    if (filterModal && event.target === filterModal) {
        closeFilterModal();
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