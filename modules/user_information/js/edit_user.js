/******************************UPON LOADING THE PAGE***********************************/
window.addEventListener('DOMContentLoaded', () => {
  const content = document.querySelector('.content');
  if (content) {
    content.classList.add('fade-in');
  }
});

/********** Loading animation for next pages **********/
document.addEventListener('DOMContentLoaded', function () {
  const nextPageButtons = document.querySelectorAll(".next-page");

  nextPageButtons.forEach(function (button) {
    button.addEventListener("click", function (e) {
      e.preventDefault();
      NProgress.start();

      setTimeout(() => {
        NProgress.set(0.7);
      }, 300);

      setTimeout(() => {
        NProgress.done();
        window.location.href = button.href || button.dataset.href;
      }, 1200);
    });
  });

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





/****************************EDIT STUDENT INFORMATION***************************/
/* COMMETED OUT
function openEditModal(studentData = null) {
  const modal = document.getElementById('editUserModal');

  // Clear old data first
  clearEditForm();

  // Populate form if studentData is passed
  if (studentData) {
    document.getElementById('studentId').value = studentData.id || '';
    document.getElementById('lname').value = studentData.lname || '';
    document.getElementById('fname').value = studentData.fname || '';
    document.getElementById('mi').value = studentData.mi || '';
    document.getElementById('Suffix').value = studentData.suffix || '';
    document.getElementById('program').value = studentData.program || '';
  }

  // Change modal title and button text
  modal.querySelector('h2').textContent = 'Update User Information';
  modal.querySelector('.modal-buttons .button[type="submit"]').textContent = 'Update';

  modal.classList.add('show');
  document.body.style.overflow = 'hidden'; // Hide scroll
  document.documentElement.style.overflow = 'hidden'; // Hide scroll on <html>

  const modalContent = modal.querySelector('.modal-content');
  if (modalContent) modalContent.scrollTop = 0;

  // Start tracking changes
  trackEditFormChanges();

  // Cancel button logic
  document.getElementById('cancelEdit').onclick = () => {
    attemptCloseModal();
  };
}

// Attempt to close modal with confirmation if form is dirty
function attemptCloseModal() {
  if (isEditFormDirty) {
    const confirmExit = confirm("You have unsaved changes. Do you really want to exit?");
    if (!confirmExit) return;
  }

  closeEditModal();
}

// Close modal and reset form state
function closeEditModal() {
  const modal = document.getElementById('editUserModal');
  modal.classList.remove('show');
  document.body.style.overflow = ''; // Restore scroll
  document.documentElement.style.overflow = ''; // Restore scroll on <html>
  clearEditForm(); // clear after close
}

// Click outside to close modal (with prompt)
window.addEventListener('click', function(e) {
  const modal = document.getElementById('editUserModal');
  if (e.target === modal) {
    attemptCloseModal();
  }
}); COMMENTED OUT*/

function openEditModal(userId) {
  if (!userId) {
    alert("Missing user ID");
    return;
  }

  clearEditForm();

  Promise.all([
    fetch("/bridge/modules/user_information/processes/populate_filter_college.php").then(r => r.json()),
    fetch(`/bridge/modules/user_information/processes/get_user.php?id=${userId}`).then(r => r.json())
  ])
  .then(([options, user]) => {
    if (user.error) {
      alert(user.error);
      return;
    }

    // --- Save user_id into hidden field ---
    document.getElementById("userId").value = user.user_id;

    // --- Populate Colleges ---
    const collegeSelect = document.getElementById("filterCollege");
    collegeSelect.innerHTML = '<option value="" disabled>Select</option>';
    options.collegeOptions.forEach(c => {
      const opt = document.createElement("option");
      opt.value = c.id;
      opt.textContent = c.name;
      if (user.college_id == c.id) opt.selected = true;
      collegeSelect.appendChild(opt);
    });

    // --- Populate Positions (Step 2 logic goes here) ---
    const posSelect = document.getElementById("filterPosition");

    function updatePositionOptions() {
      const selectedCollege = parseInt(collegeSelect.value, 10);
      posSelect.innerHTML = '<option value="" disabled>Select</option>';

      // Always include Dean & Assistant
      options.positionOptions.forEach(p => {
        if (p.id !== 3) { // skip Program Head initially
          const opt = document.createElement("option");
          opt.value = p.id;
          opt.textContent = p.name;
          if (user.user_level == p.id) opt.selected = true;
          posSelect.appendChild(opt);
        }
      });

      // Add Program Head only if college has programs
      if (options.programOptions[selectedCollege]) {
        const opt = document.createElement("option");
        opt.value = 3;
        opt.textContent = "Program Head";
        if (user.user_level == 3) opt.selected = true;
        posSelect.appendChild(opt);
      }
    }

    updatePositionOptions();
    collegeSelect.addEventListener("change", () => {
      updatePositionOptions();
      updateProgramOptions();
    });

    // --- Populate Programs dynamically ---
    const progGroup = document.getElementById("programGroup");
    const progSelect = document.getElementById("filterProgram");

    function updateProgramOptions() {
      progSelect.innerHTML = '<option value="" disabled>Select</option>';
      progGroup.style.display = "none";

      const selectedCollege = parseInt(collegeSelect.value, 10);
      const selectedPosition = parseInt(posSelect.value, 10);

      // Only Program Head + college with programs
      if (selectedPosition === 3 && options.programOptions[selectedCollege]) {
        progGroup.style.display = "block";
        options.programOptions[selectedCollege].forEach(pr => {
          const opt = document.createElement("option");
          opt.value = pr.id;
          opt.textContent = pr.name;
          if (user.program_id == pr.id) opt.selected = true;
          progSelect.appendChild(opt);
        });
      }
    }

    updateProgramOptions();
    posSelect.addEventListener("change", updateProgramOptions);

    // --- Fill Names ---
    document.getElementById('lname').value = user.lname || "";
    document.getElementById('fname').value = user.fname || "";

    // --- Show Modal ---
    const modal = document.getElementById('editUserModal');
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    document.documentElement.style.overflow = 'hidden';

    trackEditFormChanges();
    document.getElementById('cancelEdit').onclick = attemptCloseModal;
  })
  .catch(err => console.error("Error loading edit modal:", err));
}





/**************************** MODAL CLOSE LOGIC ****************************/
function attemptCloseModal() {
  if (isEditFormDirty) {
    const confirmExit = confirm("You have unsaved changes. Do you really want to exit?");
    if (!confirmExit) return;
  }
  closeEditModal();
}

function closeEditModal() {
  const modal = document.getElementById('editUserModal');
  console.log('Adding closing class to modal');
  modal.classList.add('closing');
  
  // Force a reflow to ensure the class is applied
  modal.offsetHeight;
  
  // Remove the modal after animation completes
  setTimeout(() => {
    console.log('Removing modal classes');
    modal.classList.remove('show', 'closing');
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';
    clearEditForm();
  }, 500); // Match the animation duration
}

// Close when clicking outside
window.addEventListener('click', function(e) {
  const modal = document.getElementById('editUserModal');
  if (e.target === modal) {
    attemptCloseModal();
  }
});



function loadPrograms(collegeId, selectedProgramId = null) {
  fetch("/bridge/modules/user_information/processes/populate_filter_college.php")
    .then(res => res.json())
    .then(data => {
      const programSelect = document.getElementById("filterProgram");
      programSelect.innerHTML = '<option value="">Select Program</option>';

      if (data.programOptions[collegeId]) {
        data.programOptions[collegeId].forEach(program => {
          const opt = document.createElement("option");
          opt.value = program.id;
          opt.textContent = program.name;
          if (selectedProgramId && program.id == selectedProgramId) {
            opt.selected = true;
          }
          programSelect.appendChild(opt);
        });
      }
    });
}



/************************************OPTION IF THE USER HAS UNSAVED DATA MUST BE AT THE END OF THE SCRIPT*************************************/
let isEditFormDirty = false;

// Track changes in the Edit Modal form
function trackEditFormChanges() {
  const formInputs = document.querySelectorAll("#editUser input, #editUser select, #editUser textarea");
  formInputs.forEach(el => {
    el.addEventListener("input", () => {
      isEditFormDirty = true;
    });
  });
}

// Reset (clear) all fields in the Edit Modal form
function clearEditForm() {
  document.getElementById('editUser').reset();

  // Reset selects manually if needed
  document.querySelectorAll('#editUser select').forEach(select => {
    select.selectedIndex = 0;
  });

  isEditFormDirty = false;
}

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("editUser");
    const updateBtn = document.getElementById("updateUserBtn"); 
    const formButtons = document.querySelectorAll(".form-btn");

    if (!form || !updateBtn || formButtons.length === 0) return;

    form.addEventListener("submit", function(e) {
        e.preventDefault();
        console.log("Form submission event triggered. Default behavior prevented.");

        // Add loading class to the update button and disable all buttons with fade effect
        updateBtn.classList.add("loading");
        formButtons.forEach(btn => {
            btn.disabled = true;
            btn.style.opacity = "0.5";
            btn.style.transition = "opacity 0.3s ease";
            btn.style.pointerEvents = "none";
        });

        const formData = new FormData();
        formData.append('id', document.getElementById("userId").value);
        formData.append('lname', document.getElementById("lname").value);
        formData.append('fname', document.getElementById("fname").value);
        formData.append('filterCollege', document.getElementById("filterCollege").value);
        formData.append('filterPosition', document.getElementById("filterPosition").value);
        
        const programGroup = document.getElementById("programGroup");
        const programSelect = document.getElementById("filterProgram");
        if (programGroup.style.display !== 'none' && programSelect) {
            formData.append('filterProgram', programSelect.value);
        }

        fetch("/bridge/modules/user_information/processes/update_user.php", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
          setTimeout(() => {
            console.log("Response from server:", data);
            
            // Remove loading class and re-enable buttons
            updateBtn.classList.remove("loading");
            formButtons.forEach(btn => {
                btn.disabled = false;
                btn.style.opacity = "1";
                btn.style.pointerEvents = "auto";
            });

            if (data.success) {
                showToast("User updated successfully!");
                // Wait for toast to hide (1000ms) then reload without NProgress
                setTimeout(() => {
                    location.reload(); 
                }, 2000); // 1000ms toast display + 1000ms buffer
            } else {
                showToast("Update failed: " + data.error);
            }
          }, 2000) // Increased loading time to 2 seconds
        })
        .catch(err => {
            // Re-enable buttons and remove loading class on error
            updateBtn.classList.remove("loading");
            formButtons.forEach(btn => {
                btn.disabled = false;
                btn.style.opacity = "1";
                btn.style.pointerEvents = "auto";
            });
            console.error("Error:", err);
            showToast("An error occurred. Please try again.");
        });
    });
});

////////// TOAST HELPER (Responsive) //////////
function showToast(message) {
    let toast = document.getElementById("customToast");
    if (!toast) {
        toast = document.createElement("div");
        toast.id = "customToast";
        toast.style.position = "fixed";
        toast.style.top = "20px";
        toast.style.left = "50%";
        toast.style.transform = "translateX(-50%) translateY(-100px)";
        toast.style.background = "#60357a";
        toast.style.color = "#fff";
        toast.style.padding = "12px 20px";
        toast.style.borderRadius = "8px";
        toast.style.zIndex = "99999";
        toast.style.maxWidth = "90%";
        toast.style.wordWrap = "break-word";
        toast.style.textAlign = "center";
        toast.style.fontSize = "clamp(14px, 2vw, 18px)";
        toast.style.opacity = "0";
        toast.style.transition = "all 0.5s ease";
        document.body.appendChild(toast);
    }

    toast.textContent = message;

    requestAnimationFrame(() => {
        toast.style.opacity = "1";
        toast.style.transform = "translateX(-50%) translateY(0)";
    });

    setTimeout(() => {
        toast.style.opacity = "0";
        toast.style.transform = "translateX(-50%) translateY(-100px)";
    }, 1000);
}