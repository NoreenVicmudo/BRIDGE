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
  // Initialize DataTable
  const dataTable = $('#myTable').DataTable({
    scrollX: true,
    responsive: true,
    ordering: false,
    dom: '<"top-controls d-flex justify-content-between align-items-center"f<"btn-group ms-2">>t<"bottom-controls"ip>',
    language: {
      search: "",
      lengthMenu: "Show _MENU_ entries",
      info: "",
      infoEmpty: "",  // 👈 hides "Showing 0 to 0 of 0 entries"
      emptyTable: "No current requests", // 👈 hide "No data available in table"
      paginate: {
        previous: "Previous",
        next: "Next"
      }
    },
    initComplete: function () {
      // Set initial placeholder and add responsive functionality
      adjustSearchBarForScreenSize();
      $(window).on('resize', adjustSearchBarForScreenSize);
    }
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
      searchInput.attr('placeholder', 'Search requests...');
    } else {
      // Normal screens - use full placeholder
      searchInput.attr('placeholder', 'Search requests...');
    }
  }

  // Function to load requests
  function loadApprovalRequests() {
    fetch("/bridge/modules/user_information/processes/fetch_approval_request.php")
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          dataTable.clear(); // remove old rows

          if (data.data.length > 0) {
            data.data.forEach(user => {
              const fullName = `${user.user_firstname} ${user.user_lastname}`;
              const row = `
                <tr>
                  <td>${user.user_username}</td>
                  <td>${fullName}</td>
                  <td>${user.college_name || 'N/A'}</td>
                  <td>${user.position}</td>
                  <td>${timeSince(user.signup_completed_at)}</td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn-plain btn-reject" data-id="${user.user_id}">Reject</button>
                      <button class="btn-plain btn-accept" data-id="${user.user_id}">Accept</button>
                    </div>
                  </td>
                </tr>
              `;
              dataTable.row.add($(row));
            });
          }

          dataTable.draw(false);
        } else {
          console.error("Error loading requests:", data.message);
        }
      })
      .catch(err => console.error("Fetch error:", err));
  }

  // Load on page load
  loadApprovalRequests();

  // Auto-refresh every 1 second
  setInterval(() => {
    loadApprovalRequests();
  }, 2000);

  // Store modal state
  let currentAction = null;
  let currentUserId = null;

  // Open modal with dynamic text
  function openValidationModal(action, userId) {
    currentAction = action;
    currentUserId = userId;

    const modal = document.getElementById("validateModal");
    const title = modal.querySelector("h2");
    const body = modal.querySelector("p");

    title.textContent = action === "accept" ? "Accept User" : "Reject User";
    body.textContent = `Are you sure you want to ${action} this user?`;

    // Ensure previous closing state is cleared
    modal.classList.remove("closing");
    // Use class to show (CSS handles centering)
    modal.classList.add("show");
    // Remove any inline display so CSS can take effect
    modal.style.display = "";
  }

  // Close modal
  function cancelValidationModal() {
    const modal = document.getElementById("validateModal");
    if (!modal) return;

    // keep visible, trigger fadeOut animation
    modal.classList.add("closing");

    // match fadeOut duration (0.5s)
    setTimeout(() => {
      modal.classList.remove("closing", "show");
      modal.style.display = "none";
      currentAction = null;
      currentUserId = null;
    }, 500);
  }

  // Confirm action
  function goToValidationModal(buttonEl) {
    if (!currentAction || !currentUserId) return;

    const modal = document.getElementById("validateModal");
    const buttons = modal.querySelectorAll(".modal-buttons button");

    // Add fade out effect and disable all buttons
    buttons.forEach(btn => {
      btn.disabled = true;
      btn.style.opacity = "0.5";
      btn.style.transition = "opacity 0.3s ease";
      btn.style.pointerEvents = "none";
    });

    // Save & clear text, add loader
    const originalText = buttonEl.textContent;
    buttonEl.setAttribute("data-original-text", originalText);
    buttonEl.textContent = "";

    let loader = buttonEl.querySelector(".loader");
    if (!loader) {
      loader = document.createElement("span");
      loader.className = "loader";
      buttonEl.appendChild(loader);
    }
    loader.style.display = "inline-block";

    fetch("/bridge/modules/user_information/processes/process_approval.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `action=${currentAction}&user_id=${currentUserId}`
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showToast(data.message);

          // Remove row from table
          const row = $(`#myTable button[data-id='${currentUserId}']`).parents("tr");
          dataTable.row(row).remove().draw();
        } else {
          showToast("Error: " + data.message);
        }
      })
      .catch(err => showToast("Request failed: " + err))
      .finally(() => {
        setTimeout(() => {
          cancelValidationModal();

          // Reset buttons after modal closes
          buttons.forEach(btn => {
            btn.disabled = false;
            btn.style.opacity = "1";
            btn.style.pointerEvents = "auto";

            const savedText = btn.getAttribute("data-original-text");
            if (savedText) {
              btn.textContent = savedText;
              btn.removeAttribute("data-original-text");
            }

            const loader = btn.querySelector(".loader");
            if (loader) loader.remove();
          });
        }, 500);
      });
  }

  // Attach handlers to Accept/Reject buttons
  $('#myTable').on('click', '.btn-accept, .btn-reject', function () {
    const userId = $(this).data('id');
    const action = $(this).hasClass('btn-accept') ? 'accept' : 'reject';
    openValidationModal(action, userId);
  });

  // Close modal if click outside content
  window.addEventListener("click", function (event) {
    const modal = document.getElementById("validateModal");
    if (event.target === modal) {
      cancelValidationModal();
    }
  });

  // Expose cancel/confirm for modal buttons
  window.cancelValidationModal = cancelValidationModal;
  window.goToValidationModal = goToValidationModal;

  // Style header
  $('#myTable thead th').css({
    'background-color': 'var(--primary)',
    'color': 'var(--light)'
  });
});

//Time computation for "Requested" column
function timeSince(dateString) {
    const now = new Date();
    const then = new Date(dateString);
    const seconds = Math.floor((now - then) / 1000);

    if (seconds < 60) return `${seconds} s`;
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes} min`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours} ${hours !== 1 ? 'hrs' : 'hr'}`;
    const days = Math.floor(hours / 24);
    return `${days} d ago`;
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



