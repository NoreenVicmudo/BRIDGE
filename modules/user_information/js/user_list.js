/******************************UPON LOADING THE PAGE***********************************/
window.addEventListener('DOMContentLoaded', () => {
    const content = document.querySelector('.content');
    if (content) {
      content.classList.add('fade-in');
    }
    
  });

//////////for loading animation on next pages
document.addEventListener('DOMContentLoaded', function () {
  // Global delegation for ALL .next-page links
  document.addEventListener('click', function (e) {
    const link = e.target.closest('.next-page');
    if (link) {
      e.preventDefault();
      NProgress.start();

      setTimeout(() => {
        NProgress.set(0.7);
      }, 300);

      setTimeout(() => {
        window.location.href = link.href;
      }, 1200);
    }
  });

  // Finish progress bar when the new page loads
  window.addEventListener('load', function () {
    NProgress.done();
  });
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
    dom: '<"top-controls"f>t<"bottom-controls"ip>',
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
      $(".dataTables_filter input").attr("placeholder", "Search users...");
      
      
    }
  });

  window.userInfoTable = dataTable;

  // Customize table header colors
  $('#myTable thead th').css({
    'background-color': 'var(--primary)',
    'color': 'var(--light)'
  });

  // Enhanced pagination responsiveness
  function adjustPaginationForScreenSize() {
    const screenWidth = window.innerWidth;
    const paginateContainer = $('.dataTables_paginate');
    
    if (screenWidth <= 400) {
      // Very small screens - show only essential buttons
      paginateContainer.find('.paginate_button').each(function() {
        const $btn = $(this);
        if (!$btn.hasClass('first') && !$btn.hasClass('previous') && 
            !$btn.hasClass('next') && !$btn.hasClass('last') && 
            !$btn.hasClass('current')) {
          $btn.hide();
        }
      });
    } else if (screenWidth <= 600) {
      // Small screens - show fewer page numbers
      paginateContainer.find('.paginate_button').each(function() {
        const $btn = $(this);
        if (!$btn.hasClass('first') && !$btn.hasClass('previous') && 
            !$btn.hasClass('next') && !$btn.hasClass('last') && 
            !$btn.hasClass('current')) {
          // Show only current page and adjacent pages
          const currentIndex = paginateContainer.find('.current').index();
          const btnIndex = $btn.index();
          if (Math.abs(btnIndex - currentIndex) > 1) {
            $btn.hide();
          } else {
            $btn.show();
          }
        }
      });
    } else {
      // Normal screens - show all buttons
      paginateContainer.find('.paginate_button').show();
    }
  }

  // Call on initial load and window resize
  adjustPaginationForScreenSize();
  $(window).on('resize', adjustPaginationForScreenSize);

  // Re-adjust pagination when table redraws
  dataTable.on('draw', function() {
    setTimeout(adjustPaginationForScreenSize, 100);
  });

  // Enhanced search bar responsiveness
  function adjustSearchBarForScreenSize() {
    const screenWidth = window.innerWidth;
    const searchInput = $('.dataTables_filter input');
    
    if (screenWidth <= 400) {
      // Very small screens - use shorter placeholder
      searchInput.attr('placeholder', 'Search...');
    } else if (screenWidth <= 600) {
      // Small screens - use medium placeholder
      searchInput.attr('placeholder', 'Search users...');
    } else {
      // Normal screens - use full placeholder
      searchInput.attr('placeholder', 'Search users...');
    }
  }

  // Call on initial load and window resize
  adjustSearchBarForScreenSize();
  $(window).on('resize', adjustSearchBarForScreenSize);

    /************************************** DELETE BUTTON HANDLER ***********************************/
  $('#deleteBtn').on('click', activateDeleteMode);
});




/****************************BUTTON CHANGE AND MODALS***************************/
function activateDeleteMode() {
  const tableCheckboxes = document.querySelectorAll('.select-column');
  const buttonContainer = document.querySelector('.button-container');

  tableCheckboxes.forEach(col => col.classList.remove('hidden'));

  buttonContainer.innerHTML = `
    <button class="button cancel-delete btn-back" id="cancelDelete">Cancel</button>
    <button class="button delete-confirm" id="confirmDelete">Remove</button>
  `;

  document.getElementById('cancelDelete').addEventListener('click', () => {
    tableCheckboxes.forEach(col => col.classList.add('hidden'));
    document.querySelectorAll('.row-select').forEach(box => box.checked = false);
    restoreOriginalButtons();
  });

  document.getElementById('confirmDelete').addEventListener('click', () => {
    const selected = document.querySelectorAll('.row-select:checked');

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
        // Try to get a student_number from a data attribute or from the 2nd cell
        let username = "";
        if (row && row.dataset && row.dataset.studentNumber) {
          username = row.dataset.studentNumber;
        } else if (row) {
          const maybeCell = row.cells[1];
          if (maybeCell) {
            const tmp = document.createElement("div");
            tmp.innerHTML = maybeCell.innerHTML;
            username = tmp.textContent.trim();
          }
        }
        return { name, username };
      }
    );
    openEnhancedDeleteModal(selectedStudents);
  });
}

function restoreOriginalButtons() {
  const buttonContainer = document.querySelector('.button-container');
  buttonContainer.innerHTML = `
    <button class="button" id="deleteBtn">Remove User</button>
  `;

  
  document.getElementById('deleteBtn').addEventListener('click', activateDeleteMode);
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

function openEnhancedDeleteModal(selectedStudents) {
  const modal = document.getElementById('enhancedDeleteModal');
  modal.classList.add('show');
  // Store the selected students globally for mode switching
  window._enhancedDeleteSelectedStudents = selectedStudents;
  renderEnhancedDeleteModal('single');
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

// Add event listeners for modal closing
document.addEventListener('DOMContentLoaded', function() {
  const modal = document.getElementById('enhancedDeleteModal');
  
  // Close modal when clicking outside of it
  modal.addEventListener('click', function(event) {
    if (event.target === modal) {
      closeModalWithAnimation('enhancedDeleteModal');
    }
  });
  
  // Close modal when pressing Escape key
  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && modal.classList.contains('show')) {
      closeModalWithAnimation('enhancedDeleteModal');
    }
  });
});

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
        <option value="">Select</option>
          <option value="Incorrect or Incomplete Entry">User resigned</option>
          <option value="Transferred">User moved to other department</option>
          <option value="Withdrawn">User's position changed</option>
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
      payload.append("students", JSON.stringify(students));
      payload.append("reason", reasonText);

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
          "modules/user_information/processes/ajax_delete_users.php",
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
        showToast(`Deleted ${result.deleted_count} users.`);
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
        showToast("Error deleting users. See console.");
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
          let username = "";
          if (row && row.dataset && row.dataset.studentNumber) {
            username = row.dataset.studentNumber;
          } else if (row) {
            // try cell 1 (second column)
            const maybeCell = row.cells[1];
            if (maybeCell) {
              // strip HTML if any
              const tmp = document.createElement("div");
              tmp.innerHTML = maybeCell.innerHTML;
              username = tmp.textContent.trim();
            }
          }
          return { name, username };
        }
      );
      closeDeleteModal();
      openEnhancedDeleteModal(selectedStudents);
    }
  });
}



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

/***************** LOAD TABLE ****************/
function loadFilteredTable(){
  const payload = new FormData();
  const session = window.userSession || {};
  const college =
    session.college;
  const program =
    session.program;

  // include filter context
  payload.append("college", college);
  payload.append("program", program);

  fetch("/bridge/modules/user_information/processes/process_populate_user_list.php", {
    method: "POST",
    body: payload
  })
  .then(res => res.text())
  .then(data => {
    const table = window.userInfoTable || $('#myTable').DataTable();
    table.clear();
    const $rows = $(data).filter('tr');
    $rows.each(function () { table.row.add(this); });
    table.draw(false);
})
  .catch(err => console.error("Error loading user table:", err));
}