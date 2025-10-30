/******************************UPON LOADING THE PAGE***********************************/
window.addEventListener("DOMContentLoaded", () => {
  const content = document.querySelector(".content");
  if (content) {
    content.classList.add("fade-in");
  }
});

//////////for loading animation on next pages
document.addEventListener("DOMContentLoaded", function () {
  // Select all links with the "next-page" class (which wraps the buttons)
  const nextPageButtons = document.querySelectorAll(".next-page");

  // Add event listener for each button inside the links
  nextPageButtons.forEach(function (button) {
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
document.addEventListener("DOMContentLoaded", function () {
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
document.addEventListener("DOMContentLoaded", () => {
  const sidebar = document.getElementById("sidebar");
  const icon = document.getElementById("toggleIcon");

  // Initialize sidebar state based on screen width
  if (window.innerWidth <= 768) {
    sidebar.classList.remove("open");
    icon.className = "bi bi-list"; // show hamburger
  } else {
    sidebar.classList.add("open");
    icon.className = "bi bi-chevron-double-left"; // show collapse icon
  }

  // Recheck on resize
  window.addEventListener("resize", () => {
    if (window.innerWidth <= 768) {
      sidebar.classList.remove("open");
      icon.className = "bi bi-list";
    } else {
      sidebar.classList.add("open");
      icon.className = "bi bi-chevron-double-left";
    }
  });
});

function toggleSidebar() {
  const sidebar = document.getElementById("sidebar");
  const icon = document.getElementById("toggleIcon");
  const content = document.querySelector(".content");
  const sidebarOverlay = document.querySelector(".sidebar-overlay");

  sidebar.classList.toggle("open");

  if (window.innerWidth > 768) {
    // Desktop behavior
    icon.className = sidebar.classList.contains("open")
      ? "bi bi-chevron-double-left"
      : "bi bi-list";
  } else {
    // Mobile behavior
    if (sidebar.classList.contains("open")) {
      sidebarOverlay.classList.add("active"); // show overlay
    } else {
      sidebarOverlay.classList.remove("active"); // hide overlay
    }

    icon.className = sidebar.classList.contains("open")
      ? "bi bi-chevron-double-left"
      : "bi bi-list";

    content.style.paddingLeft = ""; // Let CSS handle mobile padding
  }
}

// Handle dropdown toggle with smooth animation
document.querySelectorAll(".sidebar .dropdown-toggle").forEach((toggle) => {
  toggle.addEventListener("click", function (e) {
    e.preventDefault();
    const parentLi = this.parentElement;
    const dropdownMenu = parentLi.querySelector(".dropdown-menu");

    // Close all other open dropdowns
    document
      .querySelectorAll(".sidebar .dropdown.open")
      .forEach((openDropdown) => {
        if (openDropdown !== parentLi) {
          const openMenu = openDropdown.querySelector(".dropdown-menu");
          if (openMenu) {
            openMenu.style.maxHeight = "0";
            openMenu.style.opacity = "0";
          }
          openDropdown.classList.remove("open");
        }
      });

    // Toggle the clicked dropdown
    if (parentLi.classList.contains("open")) {
      dropdownMenu.style.maxHeight = "0";
      dropdownMenu.style.opacity = "0";
      parentLi.classList.remove("open");
    } else {
      dropdownMenu.style.maxHeight = dropdownMenu.scrollHeight + "px";
      dropdownMenu.style.opacity = "1";
      parentLi.classList.add("open");
    }
  });
});

const profileToggle = document.getElementById("profileToggle");
const profileSection = profileToggle.parentElement;

// Create profile overlay
const profileOverlay = document.createElement("div");
profileOverlay.className = "profile-overlay";
document.body.appendChild(profileOverlay);

// Toggle on click
profileToggle.addEventListener("click", function (event) {
  event.stopPropagation(); // Prevent the click from bubbling up to the document
  const isOpen = profileSection.classList.toggle("open");

  if (isOpen) {
    // Profile is open - activate overlay and disable other hovers
    profileOverlay.classList.add("active");
    document.body.classList.add("profile-active");
  } else {
    // Profile is closed - deactivate overlay and enable other hovers
    profileOverlay.classList.remove("active");
    document.body.classList.remove("profile-active");
  }
});

// Close when clicking outside
document.addEventListener("click", function (event) {
  if (!profileSection.contains(event.target)) {
    profileSection.classList.remove("open");
    profileOverlay.classList.remove("active");
    document.body.classList.remove("profile-active");
  }
});

// Close when clicking on overlay
profileOverlay.addEventListener("click", function () {
  profileSection.classList.remove("open");
  profileOverlay.classList.remove("active");
  document.body.classList.remove("profile-active");
});

document.querySelector(".sidebar-overlay").addEventListener("click", () => {
  const sidebar = document.getElementById("sidebar");
  const icon = document.getElementById("toggleIcon");
  const sidebarOverlay = document.querySelector(".sidebar-overlay");

  sidebar.classList.remove("open");
  sidebarOverlay.classList.remove("active");
  icon.className = "bi bi-list"; // back to hamburger
});

/////////////////////*************FILTERING STUDENTS*****************/////////////////////
let programOptions = {};
let yearLevelOptions = {};
let collegeOptions = [];
let sectionOptions = {};

fetch("/bridge/populate_filter.php")
  .then((res) => res.json())
  .then((data) => {
    console.log("Fetched JSON:", data);
    collegeOptions = data.collegeOptions;
    programOptions = data.programOptions;
    yearLevelOptions = data.yearLevelOptions;
    sectionOptions = data.sectionOptions;

    // --- Session-based filter logic ---
    const session = window.userSession || {};
    const level = parseInt(session.level, 10);

    populateAY();
    populateColleges(); // DOM is ready at this point

    const collegeSelect = document.getElementById("displayCollege");
    const programSelect = document.getElementById("displayProgram");
    const yearSelect = document.getElementById("displayYearLevel");

    if (level === 0) {
      // Admin: All filters enabled
      collegeSelect.disabled = false;
      programSelect.disabled = false;
      yearSelect.disabled = false;
    } else if (level === 1 || level === 2) {
      // Dean or Administrative Assistant: College fixed, programs under that college
      collegeSelect.value = session.college;
      collegeSelect.disabled = true;
      populatePrograms();
      programSelect.disabled = false;
      yearSelect.disabled = false;
    } else if (level === 3) {
      // Program Head: College and program fixed
      collegeSelect.value = session.college;
      collegeSelect.disabled = true;
      populatePrograms();
      // Ensure program is set after options are populated
      setTimeout(() => {
        programSelect.value = session.program;
        programSelect.disabled = true;
        populateYears();
        yearSelect.disabled = false;
      }, 0);
    }

    // Optionally, trigger population of years if program is set
    if (level === 3 && session.program) {
      populateYears();
    }
  });

function populateAY() {
    const minYear = 2000;
    const currentYear = new Date().getFullYear();
    const select = document.getElementById("displayAcademicYear");
    select.innerHTML = '<option value="none" disabled selected>Select</option>'; 

    // Loop downwards from the highest possible valid start year
    for (let year = currentYear; year > minYear; year--) {
        const option = document.createElement('option');
        option.value = `${year-1}-${year}`;
        option.textContent = `${year-1}-${year}`;
        select.appendChild(option);
    }
}

function populateColleges() {
  const collegeSelect = document.getElementById("displayCollege");
  collegeSelect.innerHTML =
    '<option value="none" disabled selected>Select</option>';

  collegeOptions.forEach((college) => {
    const option = document.createElement("option");
    option.text = college.name;
    option.value = college.id;
    collegeSelect.add(option);
  });

  // Optionally reset the other dropdowns
  document.getElementById("displayProgram").innerHTML =
    '<option value="none" disabled selected>Select</option>';
  document.getElementById("displayYearLevel").innerHTML =
    '<option value="none" disabled selected>Select</option>';
  document.getElementById("displaySection").innerHTML =
    '<option value="none" disabled selected>Select</option>';
}

function populatePrograms() {
  const college = document.getElementById("displayCollege").value.toUpperCase();
  const programSelect = document.getElementById("displayProgram");
  programSelect.innerHTML =
    '<option value="none" disabled selected>Select</option>';

  if (programOptions[college]) {
    programOptions[college].forEach((program) => {
      const option = document.createElement("option");
      option.text = program.name;
      option.value = program.id;
      programSelect.add(option);
    });
  }

  document.getElementById("displayYearLevel").innerHTML =
    '<option value="none" disabled selected>Select</option>';
  document.getElementById("displaySection").innerHTML =
    '<option value="none" disabled selected>Select</option>';
}

function populateYears() {
  const program = document.getElementById("displayProgram").value.toUpperCase();
  const yearSelect = document.getElementById("displayYearLevel");
  yearSelect.innerHTML =
    '<option value="none" disabled selected>Select</option>';

  if (yearLevelOptions[program]) {
    yearLevelOptions[program].forEach((year) => {
      const option = document.createElement("option");
      option.text = year.name;
      option.value = year.id;
      yearSelect.add(option);
    });
  }

  document.getElementById("displaySection").innerHTML =
    '<option value="none" disabled selected>Select</option>';
}

function populateSections() {
  const session = window.userSession || {};
  const level = parseInt(session.level, 10);

  const programId = document.getElementById("displayProgram").value;
  const yearLevel = document.getElementById("displayYearLevel").value; // use value, not text
  const semester = document.getElementById("displaySemester").value;
  const acadYear = document.getElementById("displayAcademicYear").value;

  const sectionSelect = document.getElementById("displaySection");
  sectionSelect.innerHTML =
    '<option value="none" disabled selected>Select</option>';
    
    if (
      !programId ||
      !yearLevel ||
      !semester ||
      !acadYear ||
      programId === "none" ||
      yearLevel === "none" ||
      semester === "none" ||
      acadYear === "none"
    ) {
      return;
    }

    const sections =
      sectionOptions?.[programId]?.[yearLevel]?.[semester]?.[acadYear] || [];

    if (sections.length === 0) {
      const opt = document.createElement("option");
      opt.text = "No sections available";
      opt.disabled = true;
      sectionSelect.add(opt);
    } else {
      sections.forEach((sec) => {
        const opt = document.createElement("option");
        opt.value = sec;
        opt.text = sec;
        sectionSelect.add(opt);
      });
    }
    return;
}

// Reset section when Semester changes; and for non-admins, repopulate if year is selected
function resetSectionAndMaybePopulate() {
  const sectionSelect = document.getElementById("displaySection");
  sectionSelect.innerHTML = '<option value="none">Select</option>';
  // Always attempt to populate after resetting so that:
  // - Admin gets sections once Semester is picked (even if Year Level was picked first)
  // - Non-admin repopulates based on Year Level
  populateSections();
}

function clearFilters() {
  const session = window.userSession || {};
  const level = parseInt(session.level, 10);

  const collegeSelect = document.getElementById("displayCollege");
  const programSelect = document.getElementById("displayProgram");
  const yearSelect = document.getElementById("displayYearLevel");
  const sectionSelect = document.getElementById("displaySection");

  if (level === 0) {
    // Admin: full reset
    document.getElementById("filterForm").reset();
    programSelect.innerHTML =
      '<option value="none" disabled selected>Select</option>';
    yearSelect.innerHTML =
      '<option value="none" disabled selected>Select</option>';
    sectionSelect.innerHTML =
      '<option value="none" disabled selected>Select</option>';
  } else if (level === 1 || level === 2) {
    // Dean/Assistant: keep college fixed
    collegeSelect.value = session.college;
    collegeSelect.disabled = true;
    populatePrograms();
    programSelect.disabled = false;
    yearSelect.innerHTML =
      '<option value="none" disabled selected>Select</option>';
    sectionSelect.innerHTML =
      '<option value="none" disabled selected>Select</option>';
    // reset AY and Semester only
    document.getElementById("displayAcademicYear").selectedIndex = 0;
    document.getElementById("displaySemester").selectedIndex = 0;
  } else if (level === 3) {
    // Program Head: keep college and program fixed
    collegeSelect.value = session.college;
    collegeSelect.disabled = true;
    populatePrograms();
    setTimeout(() => {
      programSelect.value = session.program;
      programSelect.disabled = true;
      // Repopulate year levels for the fixed program
      populateYears();
    }, 0);
    yearSelect.innerHTML =
      '<option value="none" disabled selected>Select</option>';
    sectionSelect.innerHTML =
      '<option value="none" disabled selected>Select</option>';
    // reset AY and Semester only
    document.getElementById("displayAcademicYear").selectedIndex = 0;
    document.getElementById("displaySemester").selectedIndex = 0;
  }
}

/******************************* FORM VALIDATION AND SUBMISSION ***************************/
let filterData = null; // Store filter data globally

document.addEventListener("DOMContentLoaded", function () {
  const filterForm = document.getElementById("filterForm");

  if (filterForm) {
    filterForm.addEventListener("submit", function (e) {
      e.preventDefault();

      // Get all filter values
      const academicYear = document.getElementById("displayAcademicYear").value;
      const college = document.getElementById("displayCollege").value;
      const collegeText =
        document.getElementById("displayCollege").options[
          document.getElementById("displayCollege").selectedIndex
        ].text;
      const program = document.getElementById("displayProgram").value;
      const programText =
        document.getElementById("displayProgram").options[
          document.getElementById("displayProgram").selectedIndex
        ].text;
      const semester = document.getElementById("displaySemester").value;
      const semesterText =
        document.getElementById("displaySemester").options[
          document.getElementById("displaySemester").selectedIndex
        ].text;
      const yearLevel = document.getElementById("displayYearLevel").value;
      const yearLevelText =
        document.getElementById("displayYearLevel").options[
          document.getElementById("displayYearLevel").selectedIndex
        ].text;
      const section = document.getElementById("displaySection").value;

      // Check if all fields are selected (case-insensitive, also check for empty)
      if (
        !academicYear ||
        academicYear.toLowerCase() === "none" ||
        !college ||
        college.toLowerCase() === "none" ||
        !program ||
        program.toLowerCase() === "none" ||
        !semester ||
        semester.toLowerCase() === "none" ||
        !yearLevel ||
        yearLevel.toLowerCase() === "none" ||
        !section ||
        section.toLowerCase() === "none"
      ) {
        showToast("Please select all filter options before proceeding.");
        return false;
      }

      // Store filter data in localStorage for the main page
      const filterData = {
        academicYear: academicYear,
        college: college,
        collegeText: collegeText,
        program: program,
        programText: programText,
        semester: semester,
        semesterText: semesterText,
        yearLevel: yearLevel,
        yearLevelText: yearLevelText,
        section: section,
      };

      localStorage.setItem("studentFilterData", JSON.stringify(filterData));

      // Redirect to student_info.php with filter parameters
      const params = new URLSearchParams();
      params.append("academic_year", academicYear);
      params.append("college", college);
      params.append("program", program);
      params.append("semester", semester);
      params.append("year_level", yearLevel);
      params.append("section", section);
      params.append("from_filter", "true");

      window.location.href = "student-information?" + params.toString();
    });
  }

  // Handle Filter Students button click
  document
    .getElementById("filterStudentsBtn")
    .addEventListener("click", function () {
      // Gather filter values
      const academicYear = document.getElementById("displayAcademicYear").value;
      const college = document.getElementById("displayCollege").value;
      const collegeText =
        document.getElementById("displayCollege").options[
          document.getElementById("displayCollege").selectedIndex
        ].text;
      const program = document.getElementById("displayProgram").value;
      const programText =
        document.getElementById("displayProgram").options[
          document.getElementById("displayProgram").selectedIndex
        ].text;
      const semester = document.getElementById("displaySemester").value;
      const semesterText =
        document.getElementById("displaySemester").options[
          document.getElementById("displaySemester").selectedIndex
        ].text;
      const yearLevel = document.getElementById("displayYearLevel").value;
      const yearLevelText =
        document.getElementById("displayYearLevel").options[
          document.getElementById("displayYearLevel").selectedIndex
        ].text;
      const section = document.getElementById("displaySection").value;

      // Check if all fields are selected (case-insensitive, also check for empty)
      if (
        !academicYear ||
        academicYear.toLowerCase() === "none" ||
        !college ||
        college.toLowerCase() === "none" ||
        !program ||
        program.toLowerCase() === "none" ||
        !semester ||
        semester.toLowerCase() === "none" ||
        !yearLevel ||
        yearLevel.toLowerCase() === "none" ||
        !section ||
        section.toLowerCase() === "none"
      ) {
        showToast("Please select all filter options before proceeding.");
        return false;
      }

      // Store filter data globally
      filterData = {
        academicYear: academicYear,
        college: college,
        collegeText: collegeText,
        program: program,
        programText: programText,
        semester: semester,
        semesterText: semesterText,
        yearLevel: yearLevel,
        yearLevelText: yearLevelText,
        section: section,
      };

      // Optionally store in localStorage if you want
      localStorage.setItem("studentFilterData", JSON.stringify(filterData));

      // Show the modal
      openMetricsModal();
    });
});

// Update goToFilterModal to use filterData (single definition)
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
  const formData = new FormData();
  formData.append("academic_year", filterData.academicYear);
  formData.append("college", filterData.college);
  formData.append("program", filterData.program);
  formData.append("semester", filterData.semester);
  formData.append("year_level", filterData.yearLevel);
  formData.append("section", filterData.section);
  formData.append("metric", selectedMetric);

  // Persist filter in session on the server, then navigate
  NProgress.start();
  fetch("/bridge/modules/academic_profile/processes/apply_filter.php", {
    method: "POST",
    body: formData,
  })
    .then(() => {
      setTimeout(() => {
        NProgress.set(0.7);
      }, 300);
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

/******************* METRICS MODAL FUNCTIONALITY *******************/
let selectedMetric = "";

function openMetricsModal() {
  const session = window.userSession || {};
  // Prefer server session metric if available
  let metric = session.filter_metric || "";

  // If not available, infer from current page
  if (!metric) {
    const currentPage = window.location.pathname.split("/").pop();
    const pageToMetric = {
      "general-weighted-average": "GWA",
      "board-subject-grades": "BoardGrades",
      "back-subjects-retakes": "Retakes",
      "performance-rating": "PerformanceRating",
      "simulation-exam-results": "SimExam",
      "review-classes-attendance": "Attendance",
      "academic-recognition": "Recognition",
    };
    metric = pageToMetric[currentPage] || "";
  }

  // Prefill select if we resolved a metric
  const select = document.getElementById("metricSelect");
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
    console.log("Adding closing class to modal");
    modal.classList.add("closing");

    // Force a reflow to ensure the class is applied
    modal.offsetHeight;

    // Remove the modal after animation completes
    setTimeout(() => {
      console.log("Removing modal classes");
      modal.classList.remove("show", "closing");
    }, 500); // Match the animation duration
  }
}

function closeMetricsModal() {
  closeModalWithAnimation("metricsModal");
}
function handleMetricChange() {
  selectedMetric = document.getElementById("metricSelect").value;
}

const metricToPage = {
  GWA: "general-weighted-average",
  BoardGrades: "board-subject-grades",
  Retakes: "back-subjects-retakes",
  PerformanceRating: "performance-rating",
  SimExam: "simulation-exam-results",
  Attendance: "review-classes-attendance",
  Recognition: "academic-recognition",
};

function cancelMetricsModal() {
  closeMetricsModal();
}

// Initialize modals on page load
window.addEventListener("DOMContentLoaded", () => {
  // Show filter modal if URL has showFilterModal=true
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get("showFilterModal") === "true") {
    // ensure modal opens with prefill
    if (typeof populateFilterModalWithExistingData === "function") {
      populateFilterModalWithExistingData();
    }
    document.getElementById("filterModal").classList.add("show");
  }

  // Add click event for metrics button (guard if present)
  const metricsBtn = document.getElementById("metricsButton");
  if (metricsBtn) {
    metricsBtn.addEventListener("click", openMetricsModal);
  }
});

// Add click event for clicking outside the modal
window.addEventListener("click", function (event) {
  const filterModal = document.getElementById("filterModal");
  if (event.target === filterModal) {
    closeFilterModal();
  }
});

function closeFilterModal() {
  closeModalWithAnimation("filterModal");
}

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
