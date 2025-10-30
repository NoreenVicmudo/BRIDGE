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

fetch("populate_filter.php")
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
    console.log(level);

    populateAY();
    populateColleges(); // DOM is ready at this point
    populateCollegesBatch();

    const collegeSelect = document.getElementById("displayCollege");
    const programSelect = document.getElementById("displayProgram");
    const yearSelect = document.getElementById("displayYearLevel");
    const collegeBatchSelect = document.getElementById("batchCollege");
    const programBatchSelect = document.getElementById("batchProgram");

    if (level == 0) {
      console.log("Level 0 user detected:", session);
      // Admin: All filters enabled
      collegeSelect.disabled = false;
      programSelect.disabled = false;
      collegeBatchSelect.disabled = false;
      programBatchSelect.disabled = false;
      yearSelect.disabled = false;
    } else if (level == 1 || level == 2) {
      console.log("Dean/Assistant detected:", session);
      // Dean or Administrative Assistant: College fixed, programs under that college
      collegeSelect.value = session.college;
      collegeSelect.disabled = true;
      populatePrograms();
      collegeBatchSelect.value = session.college;
      collegeBatchSelect.disabled = true;
      populateProgramsBatch();
      programSelect.disabled = false;
      programBatchSelect.disabled = false;
      yearSelect.disabled = false;
    } else if (level == 3) {
      console.log("Program Head detected:", session);
      // Program Head: College and program fixed
      collegeSelect.value = session.college;
      collegeSelect.disabled = true;
      populatePrograms(); // repopulate programs for this college
      // Wait for programs to be populated before setting value
      setTimeout(() => {
        console.log("Program Head session:", session);
        programSelect.value = session.program;
        programSelect.disabled = true;
        populateYears();
        yearSelect.disabled = false;
      }, 0);

      collegeBatchSelect.value = session.college;
      collegeBatchSelect.disabled = true;
      // Refactored: use callback to set program after options are present
      populateProgramsBatch(); // repopulate programs for this college
      // Wait for programs to be populated before setting value
      setTimeout(() => {
        console.log("Program Head session:", session);
        programBatchSelect.value = session.program;
        programBatchSelect.disabled = true;
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

function populateCollegesBatch() {
  const collegeSelect = document.getElementById("batchCollege");
  collegeSelect.innerHTML =
    '<option value="none" disabled selected>Select</option>';

  collegeOptions.forEach((college) => {
    const option = document.createElement("option");
    option.text = college.name;
    option.value = college.id;
    collegeSelect.add(option);
  });

  // Optionally reset the other dropdowns
  document.getElementById("batchProgram").innerHTML =
    '<option value="none" disabled selected>Select</option>';
  //document.getElementById("Year").innerHTML = '<option value="none">Select</option>';
  //document.getElementById("boardBatch").innerHTML = '<option value="none">Select</option>';
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

function populateProgramsBatch() {
  const college = document.getElementById("batchCollege").value.toUpperCase();
  const programSelect = document.getElementById("batchProgram");
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
    '<option value="" disabled selected>Select</option>';

  // === ADMIN SIDE → use backend sectionOptions
  if (level === 0) {
    // Guard: require all keys before attempting to list sections
    if (!programId || !yearLevel || !semester || !acadYear) {
      return; // keep default placeholder until all selections are made
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

  // === NON-ADMIN (Dean/Assistant/Program Head) → generate 1–20 sections
  if (yearLevel && yearLevel !== "none") {
    for (let i = 1; i <= 20; i++) {
      const section = `${yearLevel[0]}-${i}`;
      const option = document.createElement("option");
      option.text = section;
      option.value = section;
      sectionSelect.add(option);
    }
  }
}

function clearFilters() {
  const session = window.userSession || {};
  const level = parseInt(session.level, 10);
  // Only reset the whole form for admin; for dean/assistant/program head, preserve fixed fields
  if (level === 0) {
    document.getElementById("filterForm").reset();
    document.getElementById("displayProgram").innerHTML =
      '<option value="none" disabled selected>Select</option>';
    document.getElementById("displayYearLevel").innerHTML =
      '<option value="none" disabled selected>Select</option>';
    document.getElementById("displaySection").innerHTML =
      '<option value="none" disabled selected>Select</option>';
  } else if (level === 1 || level === 2) {
    // Dean/Assistant: keep college fixed, repopulate programs for that college, reset dependent fields
    populatePrograms(); // repopulate program list for fixed college
    // Leave program at "Select"
    document.getElementById("displayYearLevel").innerHTML =
      '<option value="none" disabled selected>Select</option>';
    document.getElementById("displaySection").innerHTML =
      '<option value="none" disabled selected>Select</option>';
    // Reset AY and Semester
    document.getElementById("displayAcademicYear").selectedIndex = 0;
    document.getElementById("displaySemester").selectedIndex = 0;
  } else if (level === 3) {
    // Program Head: keep college and program fixed; reset only year level and section, AY and Semester
    document.getElementById("displayYearLevel").innerHTML =
      '<option value="none" disabled selected>Select</option>';
    document.getElementById("displaySection").innerHTML =
      '<option value="none" disabled selected>Select</option>';
    // Reset AY and Semester
    document.getElementById("displayAcademicYear").selectedIndex = 0;
    document.getElementById("displaySemester").selectedIndex = 0;
  }
}

/******************************* FORM VALIDATION AND SUBMISSION ***************************/
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

      const collegeBatch = document.getElementById("batchCollege").value;
      const collegeBatchText =
        document.getElementById("batchCollege").options[
          document.getElementById("batchCollege").selectedIndex
        ].text;
      const programBatch = document.getElementById("batchProgram").value;
      const programBatchText =
        document.getElementById("batchProgram").options[
          document.getElementById("batchProgram").selectedIndex
        ].text;
      const yearBatch = document.getElementById("Year").value;
      const boardBatch = document.getElementById("boardBatch").value;

      console.log({ academicYear, college, program, yearLevel, section }); // Debug
      const batchFilter = document.getElementById("batchFilter");

      // Check if all fields are selected (case-insensitive, also check for empty)
      if (batchFilter.style.display === "none") {
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
      } else {
        if (
          !collegeBatch ||
          collegeBatch.toLowerCase() === "none" ||
          !programBatch ||
          programBatch.toLowerCase() === "none" ||
          !yearBatch ||
          yearBatch.toLowerCase() === "none" ||
          !boardBatch ||
          boardBatch.toLowerCase() === "none"
        ) {
          showToast("Please select all filter options before proceeding.");
          return false;
        }
      }

      // Store filter data in localStorage for the main page
      if (batchFilter.style.display === "none") {
        const filterData = {
          filterType: "section",
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
      } else {
        const filterData = {
          filterType: "batch",
          batchCollege: collegeBatch,
          batchCollegeText: collegeBatchText,
          batchProgram: programBatch,
          batchProgramText: programBatchText,
          yearBatch: yearBatch,
          boardBatch: boardBatch,
        };
        localStorage.setItem("studentFilterData", JSON.stringify(filterData));
      }

      // Redirect to student_info.php with filter parameters
      const params = new FormData();

      if (batchFilter.style.display === "none") {
        params.append("filter_type", "section");
        params.append("academic_year", academicYear);
        params.append("college", college);
        params.append("program", program);
        params.append("semester", semester);
        params.append("year_level", yearLevel);
        params.append("section", section);
        params.append("from_filter", "true");
      } else {
        params.append("filter_type", "batch");
        params.append("college", collegeBatch);
        params.append("program", programBatch);
        params.append("yearBatch", yearBatch);
        params.append("boardBatch", boardBatch);
        params.append("from_filter", "true");
      }

      fetch("modules/student_info/processes/apply_filter.php", {
        method: "POST",
        body: params,
      })
        .then((res) => res.text())
        .then((response) => console.log(response));

      // Start NProgress before redirecting
      NProgress.start();
      setTimeout(() => {
        NProgress.set(0.7);
      }, 300);

      setTimeout(() => {
        NProgress.done();
        // Force a repaint before redirect
        setTimeout(() => {
          window.location.href = "student-information";
          //document.getElementById("filterForm").submit();
        }, 100); // Short delay to allow NProgress to show
      }, 1200);
    });
  }
});

/*TOGGLE FILTER*/
const toggleBtn = document.getElementById("toggleFilter");
const sectionFilter = document.getElementById("sectionFilter");
const batchFilter = document.getElementById("batchFilter");

toggleBtn.addEventListener("click", (e) => {
  e.preventDefault(); // avoid submitting form / triggering validations
  if (batchFilter.style.display === "none") {
    batchFilter.style.display = "block";
    sectionFilter.style.display = "none";
    const span = toggleBtn.querySelector("span");
    if (span) span.innerText = "Switch to Section Filter";
    //clearFilters();
  } else {
    batchFilter.style.display = "none";
    sectionFilter.style.display = "block";
    const span = toggleBtn.querySelector("span");
    if (span) span.innerText = "Switch to Batch Filter";
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
