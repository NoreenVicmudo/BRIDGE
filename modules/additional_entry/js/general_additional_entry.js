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

    // Initialize save button state management
    initializeSaveButtonState();
    
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










/************************** ENHANCED MENU BAR UNDERLINE ANIMATION **************************/
/************************** ENHANCED MENU BAR UNDERLINE ANIMATION **************************/

// Debounce function to prevent excessive calls during resize
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

function moveUnderlineTo(el) {
  // With the new ::after pseudo-element design, we don't need to manually move underlines
  // The CSS handles the underline animation automatically on hover and active states
  // This function is kept for compatibility but doesn't need to do anything
}

function resetUnderlineToActive() {
  const activeLink = document.querySelector(".menu-bar .nav-link.active");
  if (activeLink) {
    // With the new ::after pseudo-element design, the active state is handled by CSS
    // No manual positioning needed - the CSS automatically shows the underline for .active class
  updateTabTitle(activeLink);
  }
}

function updateTabTitle(activeLink) {
  const tabTitle = document.getElementById("tab-title");
  if (window.innerWidth <= 768 && activeLink) {
    let label = activeLink.querySelector("span") 
               ? activeLink.querySelector("span").textContent.trim() 
               : activeLink.textContent.trim();
    if (tabTitle) {
    tabTitle.textContent = label;
    }
  } else if (tabTitle) {
    tabTitle.textContent = "";
  }
}

// Enhanced initialization with better error handling and touch support
function initializeMenuBar() {
  const links = document.querySelectorAll(".menu-bar .nav-link");
  
  if (!links.length) {
    console.warn("Menu bar elements not found");
    return;
  }
  
  // Initialize active state
  resetUnderlineToActive();

  // Detect if device supports touch
  const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
  
  // Add click handlers with enhanced feedback
  links.forEach(link => {
    // Use appropriate event type based on device capability
    const primaryEvent = isTouchDevice ? 'touchend' : 'click';
    const secondaryEvent = isTouchDevice ? 'click' : 'touchend';
    
    link.addEventListener(primaryEvent, (e) => {
      e.preventDefault();
      
      // Remove active class from all links
      links.forEach(l => l.classList.remove("active"));
      
      // Add active class to clicked link
      link.classList.add("active");
      
      // Update tab title (underline animation is handled by CSS)
      updateTabTitle(link);
      
      // Navigate to the link after animation
      setTimeout(() => {
        if (link.href) {
          window.location.href = link.href;
        }
      }, 150);
    });
    
    // Add secondary event listener for cross-device compatibility
    if (secondaryEvent !== primaryEvent) {
      link.addEventListener(secondaryEvent, (e) => {
        e.preventDefault();
      });
    }
    
    // Add hover effects only for non-touch devices
    if (!isTouchDevice) {
      link.addEventListener("mouseenter", () => {
        if (!link.classList.contains("active")) {
          link.style.transform = "translateY(-1px)";
          link.style.transition = "transform 0.2s ease";
        }
      });
      
      link.addEventListener("mouseleave", () => {
        if (!link.classList.contains("active")) {
          link.style.transform = "translateY(0)";
        }
      });
    }
    
    // Add touch feedback for touch devices
    if (isTouchDevice) {
      link.addEventListener("touchstart", () => {
        if (!link.classList.contains("active")) {
          link.style.transform = "scale(0.98)";
          link.style.transition = "transform 0.1s ease";
        }
      });
      
      link.addEventListener("touchend", () => {
        link.style.transform = "";
      });
      
      link.addEventListener("touchcancel", () => {
        link.style.transform = "";
      });
    }
  });
}

// Initialize on DOM ready
document.addEventListener("DOMContentLoaded", () => {
  // Small delay to ensure all elements are rendered
  setTimeout(initializeMenuBar, 100);
});

// Enhanced resize handler with debouncing
const debouncedResize = debounce(() => {
  resetUnderlineToActive();
}, 150);

window.addEventListener("resize", debouncedResize);

// Also handle orientation changes for mobile devices
window.addEventListener("orientationchange", () => {
  // Delay to allow for orientation change to complete
  setTimeout(() => {
    resetUnderlineToActive();
  }, 300);
});

// Handle window focus to ensure underline is positioned correctly
window.addEventListener("focus", () => {
  setTimeout(resetUnderlineToActive, 100);
});

/************************** SAVE BUTTON VISIBILITY LOGIC **************************/
/**
 * Unified save button visibility logic for all additional entry pages
 * Save button is initially hidden and only shows when reaching additional details
 * Exception: Socioeconomic Status shows save button immediately
 */
function toggleSaveButtonVisibility(show, reason = '') {
    const saveButtons = document.querySelectorAll('.entry-buttons');
    saveButtons.forEach(btn => {
        btn.style.display = show ? 'block' : 'none';
        
        // If showing the button, also initialize the disabled state
        if (show) {
            const button = btn.querySelector('button');
            if (button) {
                button.disabled = true;
                button.style.opacity = '0.5';
                button.style.cursor = 'not-allowed';
            }
        }
    });
    
}

/**
 * Enhanced save button state management with change detection
 * Disables save button by default and enables only when user makes changes
 */
function initializeSaveButtonState() {
    // Set up change detection for all form inputs
    setupFormChangeDetection();
    
    // Set up a mutation observer to watch for when save buttons become visible
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                const target = mutation.target;
                if (target.classList.contains('entry-buttons')) {
                    const isVisible = target.style.display !== 'none';
                    if (isVisible) {
                        // When buttons become visible, ensure they start disabled
                        const buttons = target.querySelectorAll('button');
                        buttons.forEach(btn => {
                            btn.disabled = true;
                            btn.style.opacity = '0.5';
                            btn.style.cursor = 'not-allowed';
                        });
                    }
                }
            }
        });
    });
    
    // Observe all entry-buttons elements
    const entryButtons = document.querySelectorAll('.entry-buttons');
    entryButtons.forEach(btn => {
        observer.observe(btn, { attributes: true, attributeFilter: ['style'] });
    });
}

/**
 * Set up change detection for all form inputs to enable/disable save button
 */
function setupFormChangeDetection() {
    // Prevent multiple event listener setups
    if (window.formChangeDetectionSetup) {
        // If already set up, just re-store original values for new elements
        if (window.storeOriginalValues) {
            window.storeOriginalValues();
        }
        return;
    }
    window.formChangeDetectionSetup = true;
    
    // Store original values for comparison
    window.formChangeOriginalValues = window.formChangeOriginalValues || new Map();
    const originalValues = window.formChangeOriginalValues;
    
    // Function to check if any field has changed
    function checkForChanges() {
        let hasChanges = false;
        
        // Check all text inputs
        const textInputs = document.querySelectorAll('input[type="text"], textarea');
        textInputs.forEach(input => {
            const originalValue = originalValues.get(input) || '';
            // Case-insensitive comparison for text inputs
            if (input.value.trim().toLowerCase() !== originalValue.trim().toLowerCase()) {
                hasChanges = true;
            }
        });
        
        // Check all select elements
        const selectElements = document.querySelectorAll('select');
        selectElements.forEach(select => {
            const originalValue = originalValues.get(select) || '';
            if (select.value !== originalValue) {
                hasChanges = true;
            }
        });
        
        // Check all checkboxes
        const checkboxes = document.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            const originalValue = originalValues.get(checkbox) || false;
            if (checkbox.checked !== originalValue) {
                hasChanges = true;
            }
        });
        
        // Update save button state
        updateSaveButtonState(hasChanges);
    }
    
    // Function to update save button state
    function updateSaveButtonState(hasChanges) {
        const saveButtons = document.querySelectorAll('.entry-buttons button');
        saveButtons.forEach(btn => {
            if (hasChanges) {
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.style.cursor = 'pointer';
            } else {
                btn.disabled = true;
                btn.style.opacity = '0.5';
                btn.style.cursor = 'not-allowed';
            }
        });
    }
    
    // Store original values when inputs are first populated
    window.storeOriginalValues = function() {
        const allInputs = document.querySelectorAll('input, select, textarea');
        allInputs.forEach(input => {
            if (input.type === 'checkbox') {
                originalValues.set(input, input.checked);
            } else {
                originalValues.set(input, input.value);
            }
        });
    };
    
    // Set up event listeners for all form inputs
    function setupEventListeners() {
        // Create a single event handler to avoid multiple listeners
        const handleFormChange = function(e) {
            if (e.target.matches('input, select, textarea')) {
                checkForChanges();
            }
        };
        
        // Remove any existing listeners first
        document.removeEventListener('input', handleFormChange);
        document.removeEventListener('change', handleFormChange);
        
        // Add the listeners
        document.addEventListener('input', handleFormChange);
        document.addEventListener('change', handleFormChange);
    }
    
    // Initialize
    setupEventListeners();
    
    // Force disable all save buttons initially
    forceDisableAllSaveButtons();
    
    // Store original values after a short delay to ensure form is populated
    setTimeout(() => {
        if (window.storeOriginalValues) {
            window.storeOriginalValues();
        }
    }, 500);
    
    // Re-store values when form content changes (for dynamic forms)
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                // New elements added, store their original values
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        const inputs = node.querySelectorAll ? node.querySelectorAll('input, select, textarea') : [];
                        inputs.forEach(input => {
                            if (input.type === 'checkbox') {
                                window.formChangeOriginalValues.set(input, input.checked);
                            } else {
                                window.formChangeOriginalValues.set(input, input.value);
                            }
                        });
                    }
                });
            }
        });
    });
    
    // Observe the form container for changes
    const formContainer = document.querySelector('.entry-content');
    if (formContainer) {
        observer.observe(formContainer, {
            childList: true,
            subtree: true
        });
    }
}

/**
 * Reset save button state (useful when form is reset or new data is loaded)
 */
function resetSaveButtonState() {
    // Force disable all save buttons
    forceDisableAllSaveButtons();
    
    // Reset the setup flag to allow re-initialization
    window.formChangeDetectionSetup = false;
    
    // Re-initialize change detection
    setupFormChangeDetection();
}

/**
 * Manually trigger change detection (useful when form values are programmatically changed)
 */
function triggerChangeDetection() {
    if (window.formChangeOriginalValues) {
        let hasChanges = false;
        
        // Check all text inputs
        const textInputs = document.querySelectorAll('input[type="text"], textarea');
        textInputs.forEach(input => {
            const originalValue = window.formChangeOriginalValues.get(input) || '';
            // Case-insensitive comparison for text inputs
            if (input.value.trim().toLowerCase() !== originalValue.trim().toLowerCase()) {
                hasChanges = true;
            }
        });
        
        // Check all select elements
        const selectElements = document.querySelectorAll('select');
        selectElements.forEach(select => {
            const originalValue = window.formChangeOriginalValues.get(select) || '';
            if (select.value !== originalValue) {
                hasChanges = true;
            }
        });
        
        // Check all checkboxes
        const checkboxes = document.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            const originalValue = window.formChangeOriginalValues.get(checkbox) || false;
            if (checkbox.checked !== originalValue) {
                hasChanges = true;
            }
        });
        
        // Update save button state
        const saveButtons = document.querySelectorAll('.entry-buttons button');
        saveButtons.forEach(btn => {
            if (hasChanges) {
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.style.cursor = 'pointer';
            } else {
                btn.disabled = true;
                btn.style.opacity = '0.5';
                btn.style.cursor = 'not-allowed';
            }
        });
    }
}

/**
 * Force disable all visible save buttons (useful for resetting state)
 */
function forceDisableAllSaveButtons() {
    const saveButtons = document.querySelectorAll('.entry-buttons button');
    saveButtons.forEach(btn => {
        btn.disabled = true;
        btn.style.opacity = '0.5';
        btn.style.cursor = 'not-allowed';
    });
}

/**
 * Store original values and reset save button state (useful when form is populated)
 */
function storeOriginalValuesAndReset() {
    // Store original values
    if (window.storeOriginalValues) {
        window.storeOriginalValues();
    }
    
    // Force disable all save buttons
    forceDisableAllSaveButtons();
}

/**
 * Check if save button should be visible based on current form state
 * @param {string} metric - The selected metric
 * @param {Object} formState - Current form state object
 */
function checkSaveButtonVisibility(metric, formState = {}) {
    // Exception: Socioeconomic Status always shows save button
    if (metric === "SocioeconomicStatus") {
        toggleSaveButtonVisibility(true, 'Socioeconomic Status - immediate visibility');
        // Set up change detection for socioeconomic status
        setTimeout(() => {
            if (typeof setupFormChangeDetection === 'function') {
                setupFormChangeDetection();
            }
        }, 100);
        return;
    }
    
    // For all other metrics, check if additional details are reached
    const textboxGroup = document.getElementById("textboxGroup");
    const defaultTextbox = document.getElementById("defaultTextbox");
    
    if (textboxGroup && defaultTextbox) {
        const isTextboxVisible = textboxGroup.style.display === "block" && 
                                defaultTextbox.style.display === "block";
        
        if (isTextboxVisible) {
            toggleSaveButtonVisibility(true, 'Additional details reached');
        } else {
            toggleSaveButtonVisibility(false, 'Additional details not reached');
        }
    } else {
        toggleSaveButtonVisibility(false, 'Form elements not found');
    }
}
