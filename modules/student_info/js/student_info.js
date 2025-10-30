/******************************UPON LOADING THE PAGE***********************************/
window.addEventListener("DOMContentLoaded", () => {
  const content = document.querySelector(".content");
  if (content) {
    content.classList.add("fade-in");
    // Ensure DataTables recalculates widths after fade-in animation completes
    content.addEventListener("animationend", () => {
      if (window.studentInfoTable) {
        window.studentInfoTable.columns.adjust().responsive.recalc();
      }
    });
  }

  // Handle incoming filter data from student_info_filter.php
  handleIncomingFilterData();
});

/*************************** LOADERS LOGIC FOR NEXT AND BACK PAGES ***************************/
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

/**************************************DATA TABLES CUSTOMIZATION***********************************/
$(document).ready(function () {
  const dataTable = $("#myTable").DataTable({
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
        next: "Next",
      },
    },
    initComplete: function () {
      $(".dataTables_filter input").attr("placeholder", "Search students...");

      $(".btn-group").html(`
      <button class="btn btn-outline-primary me-1" id="filterButton">
        <i class="bi bi-funnel-fill"></i> Filter
      </button>
    `);

      // Add click event for filter button
      $("#filterButton").on("click", function () {
        // Pre-populate the modal with current filters before showing it
        if (typeof populateFilterModalWithExistingData === "function") {
          populateFilterModalWithExistingData();
        }
        $("#filterModal").css("display", "flex");
      });
    },
  });

  // Expose for global adjustments and do an initial adjustment after render
  window.studentInfoTable = dataTable;
  setTimeout(() => {
    dataTable.columns.adjust().responsive.recalc();
  }, 200);

  // Also adjust on window resize
  window.addEventListener("resize", () => {
    if (window.studentInfoTable) {
      window.studentInfoTable.columns.adjust().responsive.recalc();
    }
  });

  // Customize table header colors
  $("#myTable thead th").css({
    "background-color": "var(--primary)",
    color: "var(--light)",
  });

  /************************************** DELETE BUTTON HANDLER ***********************************/
  $("#deleteBtn").on("click", activateDeleteMode);

  /************************************** CHECK STUDENT ID EXISTENCE ***********************************/
  $("#checkStudentBtn").on("click", function (e) {
    e.preventDefault();

    const studentIdToFind = $("#findstudentId").val().trim();
    const resultP = $("#checkStudentResult");
    const loader = $("#loader");
    const btnText = $(".btn-text");
    const checkBtn = $(this);
    const formContainer = $("#registrationForm"); // optional

    resultP.hide();
    loader.show(); // Show spinner
    btnText.hide(); // Hide text
    checkBtn.addClass("loading").prop("disabled", true);

    if (!studentIdToFind) {
      loader.hide();
      btnText.show();
      checkBtn.removeClass("loading").prop("disabled", false);
      resultP.text("Please enter a student ID.").show();
      formContainer.hide(); // optional
      return;
    }

    let exists = false;

    setTimeout(() => {
      const table = $("#myTable").DataTable();
      const allData = table.rows().data();

      allData.each(function (row) {
        const htmlString = row[1]; // Student ID column
        const tempDiv = document.createElement("div");
        tempDiv.innerHTML = htmlString;
        const studentIdInTable = tempDiv.textContent || tempDiv.innerText;

        if (studentIdInTable.includes(studentIdToFind)) {
          exists = true;
        }
      });

      loader.hide();
      btnText.show();
      checkBtn.removeClass("loading").prop("disabled", false);

      if (exists) {
        const existingID = encodeURIComponent(studentIdToFind);
        // Find the student name in the table
        let studentName = "";
        let studentField1 = "";
        let studentField2 = "";
        let studentField3 = "";
        let studentField4 = "";
        let studentField5 = "";
        let studentField6 = "";
        let studentField7 = "";
        let studentField8 = "";
        let studentField9 = "";
        let studentField10 = "";
        let studentField11 = "";
        let studentField12 = "";
        let studentField13 = "";

        allData.each(function (row) {
          const htmlString = row[1];
          const tempDiv = document.createElement("div");
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
            studentField7 = row[9];
            studentField8 = row[10];
            studentField9 = row[11];
            studentField10 = row[12];
            studentField11 = row[13];
            studentField12 = row[14];
            studentField13 = row[15];
          }
        });

        resultP.text("Student with this ID already exists.").show();
      } else {
        const session = window.userSession || {};

        $.ajax({
          url: "modules/student_info/processes/check_student.php",
          type: "POST",
          data: {
            mode: "section",
            studentNumber: studentIdToFind,
          },
          success: function (response) {
            let res = JSON.parse(response);
            if (res.status === "exists") {
              resultP.text("✅ Student number already exists.");
              console.log(response);

              loadFilteredTable();

            } else if (res.status === "not_exists") {
              console.log(response);
              // Show confirmation modal instead of window.confirm
              const confirmModal = document.getElementById(
                "confirmAddStudentModal"
              );
              const proceedBtn = document.getElementById("proceedAddStudent");
              const cancelBtn = document.getElementById("cancelAddStudent");

              if (confirmModal && proceedBtn && cancelBtn) {
                // Show modal over the Add Student modal
                confirmModal.classList.add("show");

                // Ensure any previous handlers are not duplicated by cloning
                const proceedClone = proceedBtn.cloneNode(true);
                const cancelClone = cancelBtn.cloneNode(true);
                proceedBtn.parentNode.replaceChild(proceedClone, proceedBtn);
                cancelBtn.parentNode.replaceChild(cancelClone, cancelBtn);

                proceedClone.addEventListener("click", function () {
                  const encodedId = encodeURIComponent(studentIdToFind);
                  confirmModal.classList.add("fade-out");
                  setTimeout(() => {
                    confirmModal.classList.remove("show", "fade-out");
                  }, 300);
                  NProgress.start();
                  setTimeout(() => {
                    NProgress.done();
                    window.location.href = `add-student-information?studentId=${encodedId}`;
                  }, 1000);
                });

                cancelClone.addEventListener("click", function () {
                  confirmModal.classList.add("fade-out");
                  setTimeout(() => {
                    confirmModal.classList.remove("show", "fade-out");
                  }, 300);
                  // Keep user on current modal; optionally show a note
                  resultP.text("").hide();
                });
              } else {
                // Fallback to confirm if modal not present
                if (
                  confirm(
                    "Student with this ID does not exists. Do you want to proceed to add the student?"
                  )
                ) {
                  const encodedId = encodeURIComponent(studentIdToFind);
                  NProgress.start();
                  setTimeout(() => {
                    NProgress.done();
                    window.location.href = `add-student-information?studentId=${encodedId}`;
                  }, 1000);
                }
              }
            } else {
              resultP.text("⚠️ Something went wrong.");
            }
          },
          error: function () {
            $("#result").text("⚠️ Error connecting to server.");
          },
        });

        resultP.hide();
        const encodedId = encodeURIComponent(studentIdToFind);
        NProgress.start();
        setTimeout(() => {
          NProgress.done();
          //window.location.href = `add_student_info.php?studentId=${encodedId}`;
        }, 1000);
      }
    }, 500); // fake short delay for UX
  });
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

/****************************BUTTON CHANGE AND MODALS***************************/
function activateDeleteMode() {
  const tableCheckboxes = document.querySelectorAll(".select-column");
  const buttonContainer = document.querySelector(".button-container");

  tableCheckboxes.forEach((col) => col.classList.remove("hidden"));

  buttonContainer.innerHTML = `
  <button class="button cancel-delete btn-clear" id="cancelDelete">Cancel</button>
  <button class="button delete-confirm" id="confirmDelete">Remove</button>
`;

  document.getElementById("cancelDelete").addEventListener("click", () => {
    tableCheckboxes.forEach((col) => col.classList.add("hidden"));
    document
      .querySelectorAll(".row-select")
      .forEach((box) => (box.checked = false));
    restoreOriginalButtons();
  });

  document.getElementById("confirmDelete").addEventListener("click", () => {
    const selected = document.querySelectorAll(".row-select:checked");
    if (selected.length === 0) {
      showToast("Please select at least one student to delete.");
      return;
    }
    // Store selected rows globally for use in modal confirmation
    window.selectedToDelete = selected;
    // Gather selected students' names from the table
    const selectedStudents = Array.from(window.selectedToDelete).map(
      (checkbox) => {
        const row = checkbox.closest("tr");
        // Student name is in the 3rd cell (index 2), adjust if needed
        const name = row ? row.cells[2].textContent.trim() : "Unknown";
        // Try to find student_number: prefer data attribute, otherwise cell 1
        let student_number = "";
        if (row && row.dataset && row.dataset.studentNumber) {
          student_number = row.dataset.studentNumber;
        } else if (row) {
          const maybeCell = row.cells[1];
          if (maybeCell) {
            const tmp = document.createElement("div");
            tmp.innerHTML = maybeCell.innerHTML;
            student_number = tmp.textContent.trim();
          }
        }
        return { name, student_number };
      }
    );
    openEnhancedDeleteModal(selectedStudents);
  });
}

function restoreOriginalButtons() {
  const buttonContainer = document.querySelector(".button-container");
  buttonContainer.innerHTML = `
  <button class="button" id="addStudentBtn">Add Student</button>
  <button class="button" id="deleteBtn">Remove Student</button>
`;

  document
    .getElementById("addStudentBtn")
    .addEventListener("click", openAddStudentModal);
  document
    .getElementById("deleteBtn")
    .addEventListener("click", activateDeleteMode);
}

function resetRemoveMode() {
  // Hide all checkboxes
  const tableCheckboxes = document.querySelectorAll(".select-column");
  tableCheckboxes.forEach((col) => col.classList.add("hidden"));
  
  // Uncheck all checkboxes
  document
    .querySelectorAll(".row-select")
    .forEach((box) => (box.checked = false));
  
  // Restore original buttons
  restoreOriginalButtons();
}

function openAddStudentModal() {
  document.getElementById("addStudentModal").classList.add("show");
  // Show initial options and hide other sections
  document.getElementById("initialOptions").style.display = "flex";
  document.getElementById("importFileSection").style.display = "none";
  document.getElementById("findStudentSection").style.display = "none";
}

function closeAddStudentModal() {
  const modal = document.getElementById("addStudentModal");
  modal.classList.add("fade-out");
  setTimeout(() => {
    modal.classList.remove("show", "fade-out");
  }, 300);
}

function openEnhancedDeleteModal(selectedStudents) {
  const modal = document.getElementById("enhancedDeleteModal");
  modal.classList.add("show");
  // Store the selected students globally for mode switching
  window._enhancedDeleteSelectedStudents = selectedStudents;
  renderEnhancedDeleteModal("single");
}

function renderEnhancedDeleteModal(mode) {
  const selectedStudents = window._enhancedDeleteSelectedStudents || [];
  // Set radio button
  document.querySelector(
    'input[name="deleteMode"][value="' + mode + '"]'
  ).checked = true;
  document.getElementById("singleReasonSection").style.display =
    mode === "single" ? "block" : "none";
  document.getElementById("multipleReasonSection").style.display =
    mode === "multiple" ? "block" : "none";

  // List students in single reason section
  const singleStudentList = document.getElementById("singleStudentList");
  singleStudentList.innerHTML = "";
  selectedStudents.forEach((student) => {
    const div = document.createElement("div");
    div.textContent = student.name;
    div.style.marginBottom = "2px";
    singleStudentList.appendChild(div);
  });

  // Populate per-student reason section
  const container = document.getElementById("multipleReasonSection");
  container.innerHTML = "";
  selectedStudents.forEach((student) => {
    const row = document.createElement("div");
    row.className = "student-reason-row";
    // attach the student_number so reasons can be mapped later
    if (student.student_number)
      row.dataset.studentNumber = student.student_number;
    row.innerHTML = `
    <span style="min-width: 80px;">${student.name}</span>
    <select class="custom-combobox">
      <option value="">Select</option>
      <option value="Incorrect or Incomplete Entry">Incorrect or Incomplete Entry</option>
      <option value="Transferred">The student transferred</option>
      <option value="Withdrawn">The student withdrawn or dropped out</option>
      <option value="Other">Other (please specify)</option>
    </select>
    <textarea placeholder="Please specify the reason"></textarea>
  `;
    // Show textarea if 'Other' is selected
    const select = row.querySelector("select");
    const textarea = row.querySelector("textarea");
    select.addEventListener("change", function () {
      textarea.style.display = this.value === "Other" ? "block" : "none";
    });
    container.appendChild(row);
  });
}

// Switch between single/multiple reason
Array.from(document.querySelectorAll('input[name="deleteMode"]')).forEach(
  (radio) => {
    radio.addEventListener("change", function () {
      renderEnhancedDeleteModal(this.value);
    });
  }
);

// Show textarea for 'Other' in single reason
const singleReasonSelect = document.getElementById("singleReasonSelect");
if (singleReasonSelect) {
  singleReasonSelect.addEventListener("change", function () {
    document.getElementById("singleOtherReason").style.display =
      this.value === "Other" ? "block" : "none";
  });
}

// Cancel button
const cancelDeleteWithReason = document.getElementById(
  "cancelDeleteWithReason"
);
if (cancelDeleteWithReason) {
  cancelDeleteWithReason.addEventListener("click", function () {
    const modal = document.getElementById("enhancedDeleteModal");
    modal.classList.add("fade-out");
    setTimeout(() => {
      modal.classList.remove("show", "fade-out");
    }, 300);
  });
}

// Confirm and Delete button
const confirmDeleteWithReason = document.getElementById(
  "confirmDeleteWithReason"
);
if (confirmDeleteWithReason) {
  confirmDeleteWithReason.addEventListener("click", function () {
    async function sendDeleteRequest() {
      // Collect selected checkboxes (stored earlier as window.selectedToDelete)
      const selectedCheckboxes =
        window.selectedToDelete ||
        document.querySelectorAll(".row-select:checked");
      if (!selectedCheckboxes || selectedCheckboxes.length === 0) {
        showToast("No students selected.");
        return;
      }

      // Build students array (student_number values should be in a data attribute on the row or cell)
      const students = Array.from(selectedCheckboxes)
        .map((cb) => {
          const row = cb.closest("tr");
          // you may store data-student-number on row or find column text
          const studentNumber =
            row.dataset.studentNumber ||
            row.querySelector(".student-number-cell")?.textContent?.trim() ||
            row.querySelector("td:nth-child(2)")?.textContent?.trim();
          return studentNumber;
        })
        .filter(Boolean);

      if (!students.length) {
        showToast("Could not find the student IDs for selected rows.");
        return;
      }

      // Gather reason(s) from your modal
      // If single reason mode:
      const singleReason =
        document.getElementById("singleReasonSelect")?.value || "";
      const singleOther =
        document.getElementById("singleOtherReason")?.value || "";
      const reasonText = singleReason === "Other" ? singleOther : singleReason;

      // If multiple mode: gather per-row reasons
      const perStudentReasons = {};
      document
        .querySelectorAll("#multipleReasonSection .student-reason-row")
        .forEach((row) => {
          const name = row.querySelector("span")?.textContent?.trim();
          const sel = row.querySelector("select")?.value;
          const text =
            sel === "Other" ? row.querySelector("textarea")?.value : "";
          // read dataset.studentNumber (set when modal was opened)
          const studentNumber = row.dataset
            ? row.dataset.studentNumber || ""
            : "";
          if (studentNumber)
            perStudentReasons[studentNumber] = sel === "Other" ? text : sel;
        });

      // Prepare payload
      const payload = new FormData();
      const storedData = localStorage.getItem("studentFilterData");
      const filterData = JSON.parse(storedData);
      const filter_type = filterData.filterType || "section"; // default to section if not set
      const academicYear =
        filterData.academicYear ||
        document.getElementById("filterAcademicYear").value;
      const semester =
        filterData.semester ||
        document.getElementById("filterSemester").value;
      const college =
        filterData.college ||
        document.getElementById("filterCollege").value;
      const program =
        filterData.program ||
        document.getElementById("filterProgram").value;
      const yearLevel =
        filterData.yearLevel ||
        document.getElementById("filterYearLevel").value;
      const section =
        filterData.section ||
        document.getElementById("filterSection").value;

      const collegeBatch =
        filterData.batchCollege || document.getElementById("batchCollege").value;
      const programBatch =
        filterData.batchProgram || document.getElementById("batchProgram").value;
      const yearBatch =
        filterData.yearBatch || document.getElementById("Year").value;
      const boardBatch =
        filterData.boardBatch ||
        document.getElementById("boardBatch").value;

      payload.append("students", JSON.stringify(students));
      payload.append("reason", reasonText);
      // include filter context
      payload.append("filter_type", filter_type);
      payload.append("academic_year", academicYear);
      payload.append("college", college);
      payload.append("program", program);
      payload.append("semester", semester);
      payload.append("year_level", yearLevel);
      payload.append("section", section);
      payload.append("filter_type", filter_type);
      payload.append("collegeBatch", collegeBatch);
      payload.append("programBatch", programBatch);
      payload.append("yearBatch", yearBatch);
      payload.append("boardBatch", boardBatch);

      // Attach reason_mode and per-student reasons when multiple-mode is selected
      const selectedMode =
        document.querySelector('input[name="deleteMode"]:checked')?.value ||
        "single";
      payload.append("reason_mode", selectedMode);
      if (selectedMode === "multiple") {
        const per_reasons_map = {};
        document
          .querySelectorAll("#multipleReasonSection .student-reason-row")
          .forEach((row) => {
            const sn = row.dataset ? row.dataset.studentNumber || "" : "";
            const sel = row.querySelector("select")?.value || "";
            let text = "";
            if (sel === "Other") {
              text = row.querySelector("textarea")?.value || "";
            } else {
              text = sel;
            }
            if (sn) per_reasons_map[sn] = text;
          });
        payload.append("per_reasons", JSON.stringify(per_reasons_map));
      }

      // Send debug=1 only once per page load to get diagnostic info; subsequent requests won't include debug
      if (!window.__deleteDebugSent) {
        payload.append("debug", "1");
        window.__deleteDebugSent = true;
      }

      // CSRF: append token if your app uses one (recommended)
      // payload.append('csrf_token', window.CSRF_TOKEN);

      // UI lock
      document.getElementById("confirmDeleteWithReason").disabled = true;
      showToast("Deleting...");

      try {
        const res = await fetch(
          "modules/student_info/processes/ajax_remove_students.php",
          {
            method: "POST",
            body: payload,
          }
        );
        const result = await res.json();
        if (!res.ok || result.success !== true) {
          showToast("Delete failed: " + (result.message || res.statusText));
          console.error(result);
          return;
        }

        // On success: remove rows from DataTable
        const table = window.studentInfoTable || $("#myTable").DataTable();
        students.forEach((sn) => {
          // find the row with student_number (assuming you have a cell or data attribute)
          const rowEl =
            document.querySelector(`tr[data-student-number="${sn}"]`) ||
            Array.from(document.querySelectorAll("#myTable tbody tr")).find(
              (tr) => {
                return (
                  tr
                    .querySelector(".student-number-cell")
                    ?.textContent?.trim() === sn
                );
              }
            );
          if (rowEl) {
            table.row(rowEl).remove();
          }
        });
        table.draw(false);
        showToast(`Deleted ${result.deleted_count} students.`);
        loadFilteredTable();
        resetRemoveMode();
        // close modal, re-enable button
        const modal = document.getElementById("enhancedDeleteModal");
        modal.classList.add("fade-out");
        setTimeout(() => {
          modal.classList.remove("show", "fade-out");
        }, 300);
      } catch (err) {
        console.error(err);
        showToast("Error deleting students. See console.");
      } finally {
        document.getElementById("confirmDeleteWithReason").disabled = false;
      }
    }
    // actually call the async function
    sendDeleteRequest();
  });
}

// --- Hook into existing delete modal flow ---
// Replace the confirmYes click handler to show the enhanced modal
const confirmYesBtn = document.getElementById("confirmYes");
if (confirmYesBtn) {
  confirmYesBtn.addEventListener("click", function () {
    if (window.selectedToDelete) {
      // Gather selected students' names and student_numbers from the table
      const selectedStudents = Array.from(window.selectedToDelete).map(
        (checkbox) => {
          const row = checkbox.closest("tr");
          // Student name is in the 3rd cell (index 2), adjust if needed
          const name = row
            ? (row.cells[2]?.textContent || "").trim()
            : "Unknown";
          // Try to find student_number: data attribute, a cell with a link, or cell index 1
          let student_number = "";
          if (row && row.dataset && row.dataset.studentNumber) {
            student_number = row.dataset.studentNumber;
          } else if (row) {
            // try cell 1 (second column)
            const maybeCell = row.cells[1];
            if (maybeCell) {
              // strip HTML if any
              const tmp = document.createElement("div");
              tmp.innerHTML = maybeCell.innerHTML;
              student_number = tmp.textContent.trim();
            }
          }
          return { name, student_number };
        }
      );
      closeDeleteModal();
      openEnhancedDeleteModal(selectedStudents);
    }
  });
}

/******************************* ADD STUDENT MODAL SELECTION AND IMPORT MODAL ***************************/
document.addEventListener("DOMContentLoaded", function () {
  // Import File button click handler
  document
    .getElementById("importFileBtn")
    .addEventListener("click", function () {
      document.getElementById("initialOptions").style.display = "none";
      document.getElementById("importFileSection").style.display = "block";
      document.getElementById("findStudentSection").style.display = "none";
    });

  // Manual Entry button click handler
  document
    .getElementById("manualEntryBtn")
    .addEventListener("click", function () {
      document.getElementById("initialOptions").style.display = "none";
      document.getElementById("importFileSection").style.display = "none";
      document.getElementById("findStudentSection").style.display = "block";
    });

  // Cancel Import button click handler
  document
    .getElementById("cancelImport")
    .addEventListener("click", function () {
      const modal = document.getElementById("addStudentModal");
      modal.classList.add("fade-out");
      setTimeout(() => {
        modal.classList.remove("show", "fade-out");
        document.body.classList.remove("modal-open");
        document.getElementById("findstudentId").value = "";
        document.getElementById("checkStudentResult").style.display = "none";
      }, 300);
    });

  // File input change handler
  document
    .getElementById("fileInput")
    .addEventListener("change", handleFileSelect);

  // Drag and drop handlers
  const dropZone = document.getElementById("dropZone");

  ["dragenter", "dragover", "dragleave", "drop"].forEach((eventName) => {
    dropZone.addEventListener(eventName, preventDefaults, false);
  });

  function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
  }

  ["dragenter", "dragover"].forEach((eventName) => {
    dropZone.addEventListener(eventName, highlight, false);
  });

  ["dragleave", "drop"].forEach((eventName) => {
    dropZone.addEventListener(eventName, unhighlight, false);
  });

  function highlight(e) {
    dropZone.classList.add("dragover");
  }

  function unhighlight(e) {
    dropZone.classList.remove("dragover");
  }

  dropZone.addEventListener("drop", handleDrop, false);

  function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    handleFiles(files);
  }
});

document.addEventListener("click", function (event) {
  const modal = document.getElementById("addStudentModal");
  const initialOptions = document.getElementById("initialOptions");

  // Only close if the modal is shown, initial options are visible, and clicked on the background
  if (
    modal.classList.contains("show") &&
    initialOptions.style.display === "flex" &&
    event.target === modal
  ) {
    modal.classList.add("fade-out");
    setTimeout(() => {
      modal.classList.remove("show", "fade-out");
      document.body.classList.remove("modal-open");
    }, 300);
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
      file.type ===
        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" ||
      file.type === "application/vnd.ms-excel" ||
      file.type === "text/csv" ||
      file.type === "application/csv"
    ) {
      // Create FormData to send the file
      const formData = new FormData();
      formData.append("file", file);
      formData.append("mode", "student_section");

      // Send using fetch API
      fetch("modules/student_info/processes/import_student_info.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.text()) // or .json() if PHP returns JSON
        .then((result) => {
          showToast("Server response: " + result);
          loadFilteredTable();
        })
        .catch((error) => {
          console.error("Error:", error);
          showToast("File upload failed.");
        });
    } else {
      showToast("Please select an Excel or CSV file (.xlsx, .xls, .csv)");
    }
  }
}

document.addEventListener("DOMContentLoaded", function () {
  const cancelEditBtn = document.getElementById("cancelEdit");

  if (cancelEditBtn) {
    cancelEditBtn.addEventListener("click", function () {
      const addStudentModal = document.getElementById("addStudentModal");
      const editStudentModal = document.getElementById("editStudentModal");
      if (addStudentModal) {
        addStudentModal.classList.add("fade-out");
        setTimeout(() => {
          addStudentModal.classList.remove("show", "fade-out");
        }, 300);
      }
      if (editStudentModal) {
        editStudentModal.classList.add("fade-out");
        setTimeout(() => {
          editStudentModal.classList.remove("show", "fade-out");
        }, 300);
      }
      document.body.classList.remove("modal-open");
      const findStudentId = document.getElementById("findstudentId");
      const checkStudentResult = document.getElementById("checkStudentResult");
      if (findStudentId) findStudentId.value = "";
      if (checkStudentResult) checkStudentResult.style.display = "none";
    });
  }
});

/*Manual Entry - Allow only numbers in age input field*/
function allowOnlyNumbers(event) {
  const key = event.key;

  // Allow only digits (0-9), Backspace, Delete, Arrow keys, and Tab
  if (
    !/^\d$/.test(key) && // Not a digit
    key !== "Backspace" &&
    key !== "Delete" &&
    key !== "ArrowLeft" &&
    key !== "ArrowRight" &&
    key !== "Tab"
  ) {
    event.preventDefault();
  }
}

/******************************* HANDLE INCOMING FILTER DATA ***************************/
function handleIncomingFilterData() {
  const urlParams = new URLSearchParams(window.location.search);
  const fromFilter = urlParams.get("from_filter");

  const stored = localStorage.getItem("studentFilterData");
  const parsed = stored ? JSON.parse(stored) : null;

  // Prefer human-readable "Text" fields when available, otherwise fallback to raw values
  const getValue = (obj, keyBase) =>
    obj?.[`${keyBase}Text`] ?? obj?.[keyBase] ?? "";

  const sessionFilterType =
    window.userSession && window.userSession.filter_type
      ? window.userSession.filter_type
      : "";
  const filterType =
    parsed && parsed.filterType
      ? parsed.filterType
      : sessionFilterType || "section";

  const showSectionFilters = () => {
    displayActiveFilters(
      getValue(parsed, "academicYear"),
      getValue(parsed, "semester"),
      getValue(parsed, "college"),
      getValue(parsed, "program"),
      getValue(parsed, "yearLevel"),
      getValue(parsed, "section")
    );
  };

  const showBatchFilters = () => {
    // Prefer explicitly stored batch labels if present
    const collegeText =
      getValue(parsed, "batchCollege") || getValue(parsed, "college");
    const programText =
      getValue(parsed, "batchProgram") || getValue(parsed, "program");
    const yearBatch =
      parsed?.yearBatch ?? window.userSession?.filter_year_batch ?? "";
    const boardBatch =
      parsed?.boardBatch ?? window.userSession?.filter_board_batch ?? "";
    displayActiveFiltersBatch(collegeText, programText, yearBatch, boardBatch);
  };

  if (parsed) {
    if (filterType === "batch") {
      showBatchFilters();
    } else {
      showSectionFilters();
    }
  } else if (sessionFilterType === "batch") {
    // Fallback: show batch from session when no localStorage yet
    const collegeText = window.userSession?.filter_college || "";
    const programText = window.userSession?.filter_program || "";
    const yearBatch = window.userSession?.filter_year_batch || "";
    const boardBatch = window.userSession?.filter_board_batch || "";
    displayActiveFiltersBatch(collegeText, programText, yearBatch, boardBatch);
  }
}

function displayActiveFilters(
  academicYear,
  semester,
  college,
  program,
  yearLevel,
  section
) {
  // Create filter display HTML
  let filterDisplayHTML = '<div class="form-container">';

  if (academicYear && academicYear !== "none") {
    filterDisplayHTML += `
          <div class="form-group">
              <label>Academic Year:</label>
              <span>${academicYear}</span>
          </div>`;
  }

  if (semester && semester !== "none") {
    filterDisplayHTML += `
        <div class="form-group">
            <label>Semester:</label>
            <span>${semester}</span>
        </div>`;
  }

  if (college && college !== "none") {
    filterDisplayHTML += `
          <div class="form-group">
              <label>College:</label>
              <span>${college}</span>
          </div>`;
  }

  if (program) {
    filterDisplayHTML += `
          <div class="form-group">
              <label>Program:</label>
              <span>${program}</span>
          </div>`;
  }

  if (yearLevel && yearLevel !== "none") {
    filterDisplayHTML += `
          <div class="form-group">
              <label>Year Level:</label>
              <span>${yearLevel}</span>
          </div>`;
  }

  if (section) {
    filterDisplayHTML += `
          <div class="form-group">
              <label>Section:</label>
              <span>${section}</span>
          </div>`;
  }

  filterDisplayHTML += "</div>";

  // Update the display
  const activeFiltersDisplay = document.getElementById("activeFiltersDisplay");
  if (activeFiltersDisplay) {
    activeFiltersDisplay.innerHTML = filterDisplayHTML;
  }
}

// Display for Batch filter view (College, Program, Year, Board Exam Batch)
function displayActiveFiltersBatch(college, program, yearBatch, boardBatch) {
  let filterDisplayHTML = '<div class="form-container">';

  if (college && college !== "none") {
    filterDisplayHTML += `
          <div class="form-group">
              <label>College:</label>
              <span>${college}</span>
          </div>`;
  }

  if (program && program !== "none") {
    filterDisplayHTML += `
          <div class="form-group">
              <label>Program:</label>
              <span>${program}</span>
          </div>`;
  }

  if (yearBatch && yearBatch !== "none") {
    filterDisplayHTML += `
          <div class="form-group">
              <label>Year:</label>
              <span>${yearBatch}</span>
          </div>`;
  }

  if (boardBatch && boardBatch !== "none") {
    filterDisplayHTML += `
          <div class="form-group">
              <label>Board Exam Batch:</label>
              <span>${boardBatch}</span>
          </div>`;
  }

  filterDisplayHTML += "</div>";

  const activeFiltersDisplay = document.getElementById("activeFiltersDisplay");
  if (activeFiltersDisplay) {
    activeFiltersDisplay.innerHTML = filterDisplayHTML;
  }
}

/************************************DATA TABLES FILTER CONNECTED TO DATABASE*************************************/
function applyFilters() {
  const academicYear = document.getElementById("filterAcademicYear").value;
  const academicYearText =
    document.getElementById("filterAcademicYear").options[
      document.getElementById("filterAcademicYear").selectedIndex
    ].text;
  const semester = document.getElementById("filterSemester").value;
  const semesterText =
    document.getElementById("filterSemester").options[
      document.getElementById("filterSemester").selectedIndex
    ].text;
  const college = document.getElementById("filterCollege").value;
  const collegeText =
    document.getElementById("filterCollege").options[
      document.getElementById("filterCollege").selectedIndex
    ].text;
  const program = document.getElementById("filterProgram").value;
  const programText =
    document.getElementById("filterProgram").options[
      document.getElementById("filterProgram").selectedIndex
    ].text;
  const yearLevel = document.getElementById("filterYearLevel").value;
  const yearLevelText =
    document.getElementById("filterYearLevel").options[
      document.getElementById("filterYearLevel").selectedIndex
    ].text;
  const section = document.getElementById("filterSection").value;
  const sectionText =
    document.getElementById("filterSection").options[
      document.getElementById("filterSection").selectedIndex
    ].text;

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

  // Check if all fields are selected (I put this here to ensure all fields are selected before proceeding)
  const batch = document.getElementById("batchFilterModal");
  if (batch.style.display === "none" || batch.style.display === "") {
    if (
      academicYear === "" ||
      semester === "" ||
      college === "" ||
      program === "" ||
      yearLevel === "" ||
      section === ""
    ) {
      showToast("Please select all filter options before proceeding.");
      return false;
    }
  } else {
    if (
      !collegeBatch ||
      collegeBatch === "none" ||
      !programBatch ||
      programBatch === "none" ||
      !yearBatch ||
      yearBatch === "none" ||
      !boardBatch ||
      boardBatch === "none"
    ) {
      showToast("Please select all filter options before proceeding.");
      return false;
    }
  }

  // Update the display and store updated filter data in localStorage
  if (batch.style.display === "none" || batch.style.display === "") {
    displayActiveFilters(
      academicYearText,
      semesterText,
      collegeText,
      programText,
      yearLevelText,
      sectionText
    );
    const filterData = {
      filterType: "section",
      academicYear: academicYear,
      academicYearText: academicYearText,
      semester: semester,
      semesterText: semesterText,
      college: college,
      collegeText: collegeText,
      program: program,
      programText: programText,
      yearLevel: yearLevel,
      yearLevelText: yearLevelText,
      section: section,
      sectionText: sectionText,
    };
    localStorage.setItem("studentFilterData", JSON.stringify(filterData));
  } else {
    displayActiveFiltersBatch(
      collegeBatchText,
      programBatchText,
      yearBatch,
      boardBatch
    );
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

  closeFilterModal();

  // Prepare form data
  const formData = new FormData();

  if (batch.style.display === "none" || batch.style.display === "") {
    formData.append("filter_type", "section");
    formData.append("academic_year", academicYear);
    formData.append("college", college);
    formData.append("program", program);
    formData.append("semester", semester);
    formData.append("year_level", yearLevel);
    formData.append("section", section);
  } else {
    formData.append("filter_type", "batch");
    formData.append("college", collegeBatch);
    formData.append("program", programBatch);
    formData.append("yearBatch", yearBatch);
    formData.append("boardBatch", boardBatch);
  }

  loadFilteredTable();
}

function closeFilterModal() {
  const modal = document.getElementById("filterModal");
  modal.classList.add("fade-out");
  setTimeout(() => {
    modal.style.display = "none";
    modal.classList.remove("fade-out");
  }, 300);
}

// Event Listeners for Filter Modal
document.addEventListener("DOMContentLoaded", function () {
  // Close modal when clicking outside
  window.onclick = function (event) {
    const modal = document.getElementById("filterModal");
    if (event.target === modal) {
      closeFilterModal();
    }
  };

  // Show filter modal on page load if URL contains showFilterModal=true
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get("showFilterModal") === "true") {
    document.getElementById("filterModal").style.display = "flex";
  }

  // Pre-populate filter modal with existing data when opened
  const filterButton = document.getElementById("filterButton");
  if (filterButton) {
    filterButton.addEventListener("click", function () {
      populateFilterModalWithExistingData();
    });
  }
});

function populateFilterModalWithExistingData() {
  // Get existing filter data from localStorage
  const storedData = localStorage.getItem("studentFilterData");
  const session = window.userSession || {};

  // Helper: show correct modal view
  const setModalView = (type) => {
    const section = document.getElementById("sectionFilterModal");
    const batch = document.getElementById("batchFilterModal");
    const toggleLabel = document.getElementById("toggleFilterModal");
    if (type === "batch") {
      if (batch && section) {
        batch.style.display = "block";
        section.style.display = "none";
      }
      if (toggleLabel) {
        const span = toggleLabel.querySelector("span");
        if (span) span.innerText = "Switch to Section Filter";
      }
    } else {
      if (batch && section) {
        batch.style.display = "none";
        section.style.display = "block";
      }
      if (toggleLabel) {
        const span = toggleLabel.querySelector("span");
        if (span) span.innerText = "Switch to Batch Filter";
      }
    }
  };

  if (storedData) {
    const filterData = JSON.parse(storedData);

    // Determine view
    setModalView(filterData.filterType === "batch" ? "batch" : "section");

    // Pre-populate the section filter fields
    if (filterData.academicYear) {
      document.getElementById("filterAcademicYear").value =
        filterData.academicYear;
    }
    if (filterData.semester) {
      document.getElementById("filterSemester").value = filterData.semester;
    }
    if (filterData.college) {
      document.getElementById("filterCollege").value = filterData.college;
      populateFilterPrograms();
    }
    if (filterData.program) {
      setTimeout(() => {
        document.getElementById("filterProgram").value = filterData.program;
        populateFilterYears();
      }, 100);
    }
    if (filterData.yearLevel) {
      setTimeout(() => {
        document.getElementById("filterYearLevel").value = filterData.yearLevel;
        populateFilterSections();
      }, 200);
    }
    if (filterData.section) {
      setTimeout(() => {
        document.getElementById("filterSection").value = filterData.section;
      }, 300);
    }

    // Pre-populate batch modal fields if available
    if (filterData.batchCollege) {
      document.getElementById("batchCollege").value = filterData.batchCollege;
      populateProgramsBatch();
    }
    if (filterData.batchProgram) {
      setTimeout(() => {
        document.getElementById("batchProgram").value = filterData.batchProgram;
      }, 100);
    }
    if (filterData.yearBatch) {
      document.getElementById("Year").value = filterData.yearBatch;
    }
    if (filterData.boardBatch) {
      document.getElementById("boardBatch").value = filterData.boardBatch;
    }
  } else {
    // Fallback to session when no localStorage exists yet
    const filterType = session.filter_type || "section";
    setModalView(filterType);

    // Section filter fields
    if (session.filter_academic_year) {
      document.getElementById("filterAcademicYear").value =
        session.filter_academic_year;
    }
    if (session.filter_semester) {
      document.getElementById("filterSemester").value = session.filter_semester;
    }
    if (session.filter_college) {
      document.getElementById("filterCollege").value = session.filter_college;
      populateFilterPrograms();
    }
    if (session.filter_program) {
      setTimeout(() => {
        document.getElementById("filterProgram").value = session.filter_program;
        populateFilterYears();
      }, 100);
    }
    if (session.filter_year_level) {
      setTimeout(() => {
        document.getElementById("filterYearLevel").value =
          session.filter_year_level;
        populateFilterSections();
      }, 200);
    }
    if (session.filter_section) {
      setTimeout(() => {
        document.getElementById("filterSection").value = session.filter_section;
      }, 300);
    }

    // Batch filter fields
    if (session.filter_college) {
      document.getElementById("batchCollege").value = session.filter_college;
      populateProgramsBatch();
    }
    if (session.filter_program) {
      setTimeout(() => {
        document.getElementById("batchProgram").value = session.filter_program;
      }, 100);
    }
    if (session.filter_year_batch) {
      document.getElementById("Year").value = session.filter_year_batch;
    }
    if (session.filter_board_batch) {
      document.getElementById("boardBatch").value = session.filter_board_batch;
    }
  }
}

//document.addEventListener('DOMContentLoaded', function () {
/******************************* FILTER BUTTON ***************************/
let programOptions = {};
let yearLevelOptions = {};
let collegeOptions = [];
let sectionOptions = {};
let arrangementOptions = [];
let languageOptions = [];

// Initial fetch of data from PHP
fetch("populate_filter.php")
  .then((res) => res.json())
  .then((data) => {
    collegeOptions = data.collegeOptions;
    programOptions = data.programOptions;
    yearLevelOptions = data.yearLevelOptions;
    sectionOptions = data.sectionOptions;
    arrangementOptions = data.arrangementOptions;
    languageOptions = data.languageOptions;

    // --- Session-based filter logic ---
    const session = window.userSession || {};
    const level = parseInt(session.level, 10);

    populateAY();
    populateFilterColleges();
    populateCollegesBatch();

    const collegeSelect = document.getElementById("filterCollege");
    const programSelect = document.getElementById("filterProgram");
    const yearSelect = document.getElementById("filterYearLevel");
    const collegeBatchSelect = document.getElementById("batchCollege");
    const programBatchSelect = document.getElementById("batchProgram");

    if (level === 0) {
      // Admin: All filters enabled
      collegeSelect.disabled = false;
      programSelect.disabled = false;
      collegeBatchSelect.disabled = false;
      programBatchSelect.disabled = false;
      yearSelect.disabled = false;
    } else if (level === 1 || level === 2) {
      // Dean/Administrative Assistant: College fixed, programs under that college
      collegeSelect.value = session.college;
      collegeSelect.disabled = true;
      populateFilterPrograms();
      collegeBatchSelect.value = session.college;
      collegeBatchSelect.disabled = true;
      populateProgramsBatch();
      programSelect.disabled = false;
      programBatchSelect.disabled = false;
      yearSelect.disabled = false;
    } else if (level === 3) {
      // Program Head: College and program fixed
      collegeSelect.value = session.college;
      collegeSelect.disabled = true;
      populateFilterPrograms();
      setTimeout(() => {
        console.log("Program Head session:", session);
        programSelect.value = session.program;
        programSelect.disabled = true;
        populateFilterYears();
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
      populateFilterYears();
    }
  });

function populateAY() {
    const minYear = 2000;
    const currentYear = new Date().getFullYear();
    const select = document.getElementById("filterAcademicYear");
    select.innerHTML = '<option value="none" disabled selected>Select</option>'; 

    // Loop downwards from the highest possible valid start year
    for (let year = currentYear; year > minYear; year--) {
        const option = document.createElement('option');
        option.value = `${year-1}-${year}`;
        option.textContent = `${year-1}-${year}`;
        select.appendChild(option);
    }
}

function populateFilterColleges() {
  const collegeSelect = document.getElementById("filterCollege");
  collegeSelect.innerHTML = '<option value="">Select College</option>';

  collegeOptions.forEach((college) => {
    const option = document.createElement("option");
    option.text = college.name;
    option.value = college.id;
    collegeSelect.add(option);
  });

  // Optionally reset the other dropdowns
  document.getElementById("filterProgram").innerHTML =
    '<option value="">Select Program</option>';
  document.getElementById("filterYearLevel").innerHTML =
    '<option value="">Select Year Level</option>';
  document.getElementById("filterSection").innerHTML =
    '<option value="">Select Section</option>';
}

function populateCollegesBatch() {
  const collegeSelect = document.getElementById("batchCollege");
  collegeSelect.innerHTML = '<option value="none">Select</option>';

  collegeOptions.forEach((college) => {
    const option = document.createElement("option");
    option.text = college.name;
    option.value = college.id;
    collegeSelect.add(option);
  });

  // Optionally reset the other dropdowns
  document.getElementById("batchProgram").innerHTML =
    '<option value="none">Select</option>';
  //document.getElementById("Year").innerHTML = '<option value="none">Select</option>';
  //document.getElementById("boardBatch").innerHTML = '<option value="none">Select</option>';
}

function populateFilterPrograms() {
  const college = document.getElementById("filterCollege").value;
  const programSelect = document.getElementById("filterProgram");
  programSelect.innerHTML = '<option value="">Select Program</option>';

  if (programOptions[college]) {
    programOptions[college].forEach((program) => {
      const option = document.createElement("option");
      option.text = program.name;
      option.value = program.id;
      programSelect.add(option);
    });
  }

  document.getElementById("filterYearLevel").innerHTML =
    '<option value="">Select Year Level</option>';
  document.getElementById("filterSection").innerHTML =
    '<option value="">Select Section</option>';
}

function populateProgramsBatch() {
  const college = document.getElementById("batchCollege").value.toUpperCase();
  const programSelect = document.getElementById("batchProgram");
  programSelect.innerHTML = '<option value="none">Select</option>';

  if (programOptions[college]) {
    programOptions[college].forEach((program) => {
      const option = document.createElement("option");
      option.text = program.name;
      option.value = program.id;
      programSelect.add(option);
    });
  }
}

function populateFilterYears() {
  const program = document.getElementById("filterProgram").value;
  const yearSelect = document.getElementById("filterYearLevel");
  yearSelect.innerHTML = '<option value="">Select Year Level</option>';

  if (yearLevelOptions[program]) {
    yearLevelOptions[program].forEach((year) => {
      const option = document.createElement("option");
      option.text = year.name;
      option.value = year.id;
      yearSelect.add(option);
    });
  }

  document.getElementById("filterSection").innerHTML =
    '<option value="">Select Section</option>';
}

function populateFilterSections() {
  const session = window.userSession || {};
  const level = parseInt(session.level, 10);

  const programId = document.getElementById("filterProgram").value;
  const yearLevel = document.getElementById("filterYearLevel").value; // use value, not text
  const semester = document.getElementById("filterSemester").value;
  const acadYear = document.getElementById("filterAcademicYear").value;

  const sectionSelect = document.getElementById("filterSection");
  sectionSelect.innerHTML = '<option value="">Select Section</option>';

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

function populateArrangements() {
  const arrangementSelect = document.getElementById("livingArrangement");
  arrangementOptions.forEach((arrangement) => {
    const option = document.createElement("option");
    option.text = arrangement.name;
    option.value = arrangement.id;
    arrangementSelect.add(option);
  });
}

function populateLanguages() {
  const languageSelect = document.getElementById("language");
  languageOptions.forEach((language) => {
    const option = document.createElement("option");
    option.text = language.name;
    option.value = language.id;
    languageSelect.add(option);
  });
}

function loadFilteredTable(){
  const payload = new FormData();
  const storedData = localStorage.getItem("studentFilterData");
  const filterData = JSON.parse(storedData);
  const filter_type = filterData.filterType || "section"; // default to section if not set
  const academicYear =
    filterData.academicYear ||
    document.getElementById("filterAcademicYear").value;
  const semester =
    filterData.semester ||
    document.getElementById("filterSemester").value;
  const college =
    filterData.college ||
    document.getElementById("filterCollege").value;
  const program =
    filterData.program ||
    document.getElementById("filterProgram").value;
  const yearLevel =
    filterData.yearLevel ||
    document.getElementById("filterYearLevel").value;
  const section =
    filterData.section ||
    document.getElementById("filterSection").value;

  const collegeBatch =
    filterData.batchCollege || document.getElementById("batchCollege").value;
  const programBatch =
    filterData.batchProgram || document.getElementById("batchProgram").value;
  const yearBatch =
    filterData.yearBatch || document.getElementById("Year").value;
  const boardBatch =
    filterData.boardBatch ||
    document.getElementById("boardBatch").value;

  // include filter context
  payload.append("filter_type", filter_type);
  payload.append("academic_year", academicYear);
  payload.append("college", college);
  payload.append("program", program);
  payload.append("semester", semester);
  payload.append("year_level", yearLevel);
  payload.append("section", section);
  payload.append("filter_type", filter_type);
  payload.append("collegeBatch", collegeBatch);
  payload.append("programBatch", programBatch);
  payload.append("yearBatch", yearBatch);
  payload.append("boardBatch", boardBatch);

  fetch("modules/student_info/processes/filter_table.php", {
      method: "POST",
      body: payload,
    })
      .then((res) => res.text()) // we expect HTML rows here
      .then((data) => {
        console.log(data);
        // Reset remove mode when table is refreshed
        resetRemoveMode();
        
        // Update rows using DataTables API to prevent header/body misalignment
        const table = window.studentInfoTable || $("#myTable").DataTable();
        table.clear();
        const $rows = $(data).filter("tr");
        console.log("Number of <tr> rows:", $rows.length);
        $rows.each(function () {
          table.row.add(this);
        });
        table.draw(false);
        table.columns.adjust().responsive.recalc();
      })
      .catch((err) => {
        console.error("Error loading table:", err);
      });
}

//});
/*
/******************* COMBOBOXES CUSTOM FUNCTIONALITY *******************
const programOptions = {
  "College of Medical Technology": ["BS Medical Technology", "BS Radiologic Technology"],
  "College of Nursing": ["BS Nursing"],
  "College of Dentistry": ["Doctor of Dental Medicine"],
  "School of Business and Management": ["BS Accountancy"],
  "College of Optometry": ["Doctor of Optometry"],
  "College of Arts and Sciences": ["BS Psychology"],
  "College of Pharmacy": ["BS Pharmacy"],
  "Institute of Education": ["BS Secondary Education"],
  "College of Physical Therapy": ["BS Physical Therapy"],
  "College of Medicine": ["Doctor of Medicine"]
};

const yearLevelOptions = {
  "BS Medical Technology": ["1st Year", "2nd Year", "3rd Year", "4th Year"],
  "BS Radiologic Technology": ["1st Year", "2nd Year", "3rd Year", "4th Year"],
  "BS Nursing": ["1st Year", "2nd Year", "3rd Year", "4th Year"],
  "Doctor of Dental Medicine": ["1st Year", "2nd Year", "3rd Year", "4th Year", "5th Year", "6th Year"],
  "BS Accountancy": ["1st Year", "2nd Year", "3rd Year", "4th Year"],
  "Doctor of Optometry": ["1st Year", "2nd Year", "3rd Year", "4th Year", "5th Year", "6th Year"],
  "BS Psychology": ["1st Year", "2nd Year", "3rd Year", "4th Year"],
  "BS Pharmacy": ["1st Year", "2nd Year", "3rd Year", "4th Year"],
  "BS Secondary Education": ["1st Year", "2nd Year", "3rd Year", "4th Year"],
  "BS Physical Therapy": ["1st Year", "2nd Year", "3rd Year", "4th Year"],
  "Doctor of Medicine": ["1st Year", "2nd Year", "3rd Year", "4th Year"]
};
*/

//ADD STUDENT INFO PHP FILE JS
//ADD STUDENT INFO PHP FILE JS
//ADD STUDENT INFO PHP FILE JS
//ADD STUDENT INFO PHP FILE JS
//ADD STUDENT INFO PHP FILE JS
//ADD STUDENT INFO PHP FILE JS
//ADD STUDENT INFO PHP FILE JS
//ADD STUDENT INFO PHP FILE JS
//ADD STUDENT INFO PHP FILE JS
//ADD STUDENT INFO PHP FILE JS
/******************************THIS IS FOR ADD STUDENT PHP FILE******************************/
document.addEventListener("DOMContentLoaded", function () {
  const params = new URLSearchParams(window.location.search);
  const studentId = params.get("studentId");
  if (studentId) {
    document.getElementById("studentId").value = studentId;
  }
});

/******************************* DATE INPUT TYPE CUSTOM CLICK ANYWHERE ***************************/
const dateInput = document.getElementById("birthdate");
if (dateInput) {
  dateInput.addEventListener("click", () => {
    if (dateInput.showPicker) {
      dateInput.showPicker();
    }
  });
}

/*******************************OTHER LANGUAGES TEXTBOX SHOW/HIDE***************************/
const languageSelect = document.getElementById("language");
const otherLanguageContainer = document.getElementById(
  "otherLanguageContainer"
);
if (languageSelect && otherLanguageContainer) {
  languageSelect.addEventListener("change", function () {
    if (this.value === "Others") {
      otherLanguageContainer.style.display = "block";
      const otherLang = document.getElementById("otherLanguage");
      if (otherLang) otherLang.setAttribute("required", "required");
    } else {
      otherLanguageContainer.style.display = "none";
      const otherLang = document.getElementById("otherLanguage");
      if (otherLang) otherLang.removeAttribute("required");
    }
  });
}

/****************************NOT USED BUT SOCIOECONOMIC RANGES ***************************/

// Set default values
//        document.getElementById("richInput").value = "₱219,140 and above";
//        document.getElementById("highIncomeInput").value = "₱131,483 to ₱219,139";
//        document.getElementById("upperMiddleInput").value = "₱76,669 to ₱131,482";
//        document.getElementById("middleClassInput").value = "₱43,828 to ₱76,668";
//        document.getElementById("lowerMiddleInput").value = "₱21,914 to ₱43,827";
//        document.getElementById("lowIncomeInput").value = "₱10,957 to ₱21,913";
//        document.getElementById("poorInput").value = "Below ₱10,957";

/*****************************ADD STUDENT INFO CONFIRMATION AND MODAL VALIDATION***************************/
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("viewStudentForm");
  const saveButton = document.getElementById("saveButton");
  const modal = document.getElementById("validationModal");
  const confirmSave = document.getElementById("confirmSave");
  const cancelSave = document.getElementById("cancelSave");
  const loader = document.getElementById("loader");

  // Prevent form submission on Enter (form should only proceed via buttons)
  form.addEventListener("submit", function (e) {
    e.preventDefault(); // Prevent actual form submission
  });

  saveButton.addEventListener("click", function (e) {
    e.preventDefault();

    if (form.checkValidity()) {
      modal.style.display = "block";
    } else {
      form.reportValidity();
    }
  });

  confirmSave.addEventListener("click", function () {
    // Check if warning note is present
    const warningNote = document.getElementById("academicYearWarningNote");
    if (warningNote) {
      if (
        !confirm(
          "Student information for this academic year already exists. Are you sure you want to overwrite the existing data?"
        )
      ) {
        loader.style.display = "none";
        confirmSave.disabled = false;
        return;
      }
    }

    // 1. Show spinner immediately
    loader.style.display = "inline-block";
    confirmSave.disabled = true;

    // 2. Wait for spinner to be visible
    setTimeout(function () {
      // 3. Show toast
      showToast("Student has been successfully recorded!");

      // 4. Wait for toast to be visible
      setTimeout(function () {
        // 5. Start NProgress
        NProgress.start();

        // 6. Animate NProgress to 70%
        setTimeout(function () {
          NProgress.set(0.7);
        }, 300);

        // 7. Finish NProgress and redirect
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
    modal.classList.add("fade-out");
    setTimeout(() => {
      modal.style.display = "none";
      modal.classList.remove("fade-out");
    }, 300);
  });

  window.addEventListener("click", function (event) {
    if (event.target === modal) {
      modal.classList.add("fade-out");
      setTimeout(() => {
        modal.style.display = "none";
        modal.classList.remove("fade-out");
      }, 300);
    }
  });
});

/////EDIT STUDENT INFORMATION MODAL FUNCTIONALITY
/////EDIT STUDENT INFORMATION MODAL FUNCTIONALITY
/////EDIT STUDENT INFORMATION MODAL FUNCTIONALITY
/////EDIT STUDENT INFORMATION MODAL FUNCTIONALITY
/////EDIT STUDENT INFORMATION MODAL FUNCTIONALITY
/****************************EDIT STUDENT INFORMATION***************************/
document.addEventListener("DOMContentLoaded", function () {
  const updateBtn = document.getElementById("updateBtn");
  const editLoader = document.getElementById("editLoader");
  const editModal = document.getElementById("editStudentModal");
  const form = document.getElementById("addStudentForm");

  if (updateBtn && form) {
    updateBtn.addEventListener("click", function () {
      if (!form.reportValidity()) {
        showToast("Please fill out the required information!");
        return;
      }

      if (editLoader) editLoader.style.display = "inline-block";
      updateBtn.disabled = true;

      setTimeout(() => {
        showToast("Student data has been successfully updated!");

        setTimeout(() => {
          NProgress.start();
          setTimeout(() => NProgress.set(0.7), 300);

          setTimeout(() => {
            NProgress.done();
            if (editLoader) editLoader.style.display = "none";
            updateBtn.disabled = false;
            closeEditModal();
            form.submit();
          }, 1200);
        }, 1000);
      }, 100);
    });
  }
});

/*
document.addEventListener("DOMContentLoaded", function () {
const form = document.getElementById("addStudentForm");
const updateButton = document.getElementById("updateButton");

// Prevent form submission on Enter (form should only proceed via buttons)
form.addEventListener("submit", function (e) {
  e.preventDefault(); // Prevent actual form submission
});

updateButton.addEventListener("click", function (e){
// 1. Show spinner immediately
confirmSave.disabled = true;
    setTimeout(function () {
      NProgress.done();
      //window.location.href = confirmSave.getAttribute("data-href");
  document.getElementById("addStudentForm").submit();
  closeEditModal();
    }, 1200); // NProgress duration
});
}); */

// Helper to open the edit modal and ensure input is editable
function openEditModal(data = {}, context = "") {
  const modal = document.getElementById("editStudentModal");

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
    modal.setAttribute("data-context", context);
  } else {
    modal.removeAttribute("data-context"); // Remove if no context needed
  }

  modal.classList.add("show");
  document.body.classList.add("modal-open");
  document
    .getElementById("cancelEdit")
    .addEventListener("click", closeEditModal);
}

function closeEditModal() {
  const modal = document.getElementById("editStudentModal");
  if (modal) {
    modal.classList.add("fade-out");
    setTimeout(() => {
      modal.classList.remove("show", "fade-out");
      document.body.classList.remove("modal-open");
    }, 300);
  }
}

/************************************OPTION IF THE USER HAS UNSAVED DATA MUST BE AT THE END OF THE SCRIPT*************************************/
document.addEventListener("DOMContentLoaded", function () {
  let isAddFormDirty = false;

  // 1. Mark ADD form as dirty when any input/select/textarea changes or types
  document
    .querySelectorAll(
      "#viewStudentForm input, #viewStudentForm select, #viewStudentForm textarea"
    )
    .forEach((el) => {
      const markAddDirty = () => {
        isAddFormDirty = true;
      };
      el.addEventListener("change", markAddDirty);
      el.addEventListener("input", markAddDirty);
    });

  // 2. Intercept navigation (like closing or reloading the tab)
  window.addEventListener("beforeunload", function (e) {
    if (isAddFormDirty) {
      e.preventDefault(); // Required for some browsers
      e.returnValue = ""; // Triggers browser confirmation dialog
      return "";
    }
  });

  // 3. If user confirms Save via modal, mark form as not dirty
  const _confirmSaveBtnDirty = document.getElementById("confirmSave");
  if (_confirmSaveBtnDirty) {
    _confirmSaveBtnDirty.addEventListener("click", function () {
      isAddFormDirty = false;
      // Do NOT redirect here! The main confirmSave handler will handle the redirect with UI feedback.
    });
  }

  // 4. Also mark not dirty if navigating back intentionally
  const backPageLink = document.querySelector(".back-page");
  if (backPageLink) {
    backPageLink.addEventListener("click", function (e) {
      // On add page, keep dirty state so the beforeunload prompt can show.
      // For pages without the add form, clear dirty to avoid unnecessary prompts.
      const addForm = document.getElementById("viewStudentForm");
      if (!addForm) {
        isAddFormDirty = false;
      }
      // Do not redirect here; global back-page handler manages navigation.
    });
  }
});

// Edit modal unsaved-changes handling: only active while modal is open
document.addEventListener("DOMContentLoaded", function () {
  const editModal = document.getElementById("editStudentModal");
  const editForm = document.getElementById("addStudentForm");
  let isEditModalDirty = false;

  if (editModal && editForm) {
    const watchEls = editForm.querySelectorAll("input, select, textarea");
    const markDirtyIfOpen = () => {
      if (editModal.classList.contains("show")) {
        isEditModalDirty = true;
      }
    };
    watchEls.forEach((el) => {
      el.addEventListener("input", markDirtyIfOpen);
      el.addEventListener("change", markDirtyIfOpen);
    });

    // Intercept reload/back only if modal is open and has unsaved changes
    window.addEventListener("beforeunload", function (e) {
      if (editModal.classList.contains("show") && isEditModalDirty) {
        e.preventDefault();
        e.returnValue = "";
        return "";
      }
    });

    // Intercept explicit navigation while modal is open
    const interceptNav = (el) => {
      if (!el) return;
      el.addEventListener(
        "click",
        function (e) {
          if (editModal.classList.contains("show") && isEditModalDirty) {
            const ok = confirm("Discard unsaved changes?");
            if (!ok) {
              // Block default and any other click handlers (like NProgress navigations)
              e.preventDefault();
              e.stopImmediatePropagation();
              return;
            }
            isEditModalDirty = false;
            closeEditModal();
          }
        },
        { capture: true }
      );
    };
    document
      .querySelectorAll("a.next-page, .back-page, a[href]")
      .forEach(interceptNav);

    // Cancel button should confirm if dirty
    const cancelEditBtn = document.getElementById("cancelEdit");
    if (cancelEditBtn) {
      cancelEditBtn.addEventListener("click", function (e) {
        if (isEditModalDirty) {
          const ok = confirm("Discard unsaved changes?");
          if (!ok) {
            e.preventDefault();
            return;
          }
        }
        isEditModalDirty = false;
        closeEditModal();
      });
    }

    // Save/update should clear dirty so no prompt appears
    const updateBtn = document.getElementById("updateBtn");
    if (updateBtn) {
      updateBtn.addEventListener("click", function () {
        isEditModalDirty = false;
      });
    }
    editForm.addEventListener("submit", function () {
      isEditModalDirty = false;
    });
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

// Helper to get academic year from filter display (student_info.php)
function getExistingAcademicYearForStudent(studentId) {
  // This function assumes you can pass the academic year via localStorage or URL param
  // For demo, try to get from localStorage
  const filterData = localStorage.getItem("studentFilterData");
  if (filterData) {
    const parsed = JSON.parse(filterData);
    return parsed.academicYear;
  }
  return null;
}

// Show/hide warning note below academic year select
function checkAcademicYearWarning() {
  const studentId = document.getElementById("studentId").value;
  const selectedAY = document.getElementById("AcademicYear").value;
  const warningNoteId = "academicYearWarningNote";

  // Remove old note if exists
  let note = document.getElementById(warningNoteId);
  if (note) note.remove();

  // Only check if studentId is present
  if (studentId) {
    const existingAY = getExistingAcademicYearForStudent(studentId);
    if (existingAY && selectedAY === existingAY) {
      // Insert warning note below the academic year row
      const aySelect = document.getElementById("AcademicYear");
      note = document.createElement("div");
      note.className = "note";
      note.id = warningNoteId;
      note.textContent =
        "Warning: Student information for this academic year already exists. Saving may overwrite existing data.";
      aySelect.parentNode.appendChild(note);
    }
  }
}

// Attach event listener to AcademicYear select
document.addEventListener("DOMContentLoaded", function () {
  const aySelect = document.getElementById("AcademicYear");
  if (aySelect) {
    aySelect.addEventListener("change", checkAcademicYearWarning);
    // Also check on load in case pre-filled
    checkAcademicYearWarning();
  }
});

// Helper to build a URL with only the needed student fields
function buildStudentUrl(targetUrl, params) {
  const urlParams = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== "") {
      urlParams.append(key, value);
    }
  });
  return `${targetUrl}?${urlParams.toString()}`;
}

// Intercept Student ID link clicks and redirect with data
$(document).ready(function () {
  $("#myTable").on("click", "a.next-page", function (e) {
    e.preventDefault();

    // Use data-href if present
    const targetUrl = $(this).data("href");

    NProgress.start();
    setTimeout(function () {
      NProgress.set(0.7);
    }, 300);
    setTimeout(function () {
      NProgress.done();
      window.location.href = targetUrl;
    }, 1200);
  });
});
