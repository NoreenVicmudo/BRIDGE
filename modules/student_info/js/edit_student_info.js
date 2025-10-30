/******************************UPON LOADING THE PAGE***********************************/
window.addEventListener("DOMContentLoaded", () => {
  const content = document.querySelector(".content");
  if (content) {
    content.classList.add("fade-in");
  }
});

/********** Loading animation for next pages **********/
document.addEventListener("DOMContentLoaded", function () {
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

/********** Loading animation for actions on the same page **********/
document.addEventListener("DOMContentLoaded", function () {
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

/****************************EDIT STUDENT INFORMATION***************************/
function openEditModal(studentData = null) {
  const modal = document.getElementById("editStudentModal");

  // Clear old data first
  clearEditForm();

  // Populate form if studentData is passed
  if (studentData) {
    document.getElementById("studentId").value = studentData.id || "";
    document.getElementById("lname").value = studentData.lname || "";
    document.getElementById("fname").value = studentData.fname || "";
    document.getElementById("mi").value = studentData.mi || "";
    document.getElementById("Suffix").value = studentData.suffix || "";
    document.getElementById("program").value = studentData.program || "";
  }

  // Change modal title and button text
  modal.querySelector("h2").textContent = "Update Student Information";
  modal.querySelector('.modal-buttons .button[type="submit"]').textContent =
    "Update";

  modal.classList.add("show");
  document.body.style.overflow = "hidden"; // Hide scroll
  document.documentElement.style.overflow = "hidden"; // Hide scroll on <html>

  const modalContent = modal.querySelector(".modal-content");
  if (modalContent) modalContent.scrollTop = 0;

  // Start tracking changes
  trackEditFormChanges();

  // Cancel button logic
  document.getElementById("cancelEdit").onclick = () => {
    attemptCloseModal();
  };
}

// Attempt to close modal with confirmation if form is dirty
function attemptCloseModal() {
  if (isEditFormDirty) {
    const confirmExit = confirm(
      "You have unsaved changes. Do you really want to exit?"
    );
    if (!confirmExit) return;
  }

  closeEditModal();
}

// Close modal and reset form state
function closeEditModal() {
  const modal = document.getElementById("editStudentModal");
  modal.classList.add("fade-out");
  setTimeout(() => {
    modal.classList.remove("show", "fade-out");
    document.body.style.overflow = ""; // Restore scroll
    document.documentElement.style.overflow = ""; // Restore scroll on <html>
    clearEditForm(); // clear after close
  }, 300);
}

// Click outside to close modal (with prompt)
window.addEventListener("click", function (e) {
  const modal = document.getElementById("editStudentModal");
  if (e.target === modal) {
    attemptCloseModal();
  }
});

/************************************OPTION IF THE USER HAS UNSAVED DATA MUST BE AT THE END OF THE SCRIPT*************************************/
let isEditFormDirty = false;

// Track changes in the Edit Modal form
function trackEditFormChanges() {
  const formInputs = document.querySelectorAll(
    "#addStudentForm input, #addStudentForm select, #addStudentForm textarea"
  );
  formInputs.forEach((el) => {
    el.addEventListener("input", () => {
      isEditFormDirty = true;
    });
  });
}

// Reset (clear) all fields in the Edit Modal form
function clearEditForm() {
  document.getElementById("addStudentForm").reset();

  // Reset selects manually if needed
  document.querySelectorAll("#addStudentForm select").forEach((select) => {
    select.selectedIndex = 0;
  });

  isEditFormDirty = false;
}
