/******************************* SIMPLE LOADING SCREEN ***************************/
window.addEventListener('load', function() {
  const loadingScreen = document.getElementById('loadingScreen');
  
  if (loadingScreen) {
    // Wait a bit to show the loading screen
    setTimeout(function() {
      loadingScreen.classList.add('fade-out');
      
      // Remove from DOM after fade out
      setTimeout(function() {
        loadingScreen.remove();
      }, 300);
    }, 1000);
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

/********** Loading animation for actions on the same page **********/
document.addEventListener('DOMContentLoaded', function () {
    const samePageButtons = document.querySelectorAll(".same-page");
  
    samePageButtons.forEach(function (button) {
      button.addEventListener("click", function (e) {
        e.preventDefault(); // Stop the default link
  
        NProgress.start();
  
        setTimeout(() => {
          NProgress.set(0.7);
        }, 300);
  
        setTimeout(() => {
          NProgress.done();
          // Redirect to the same href after animation
          window.location.href = button.getAttribute("href");
        }, 1200);
      });
    });
  });
  

/******************************* STICKY HEADER SCROLL BEHAVIOR ***************************/
let lastScrollTop = 0;
const header = document.querySelector('header');
const scrollThreshold = 100; // Minimum scroll before hiding header

function handleHeaderScroll() {
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    
    // Only start hiding/showing after scrolling past the threshold
    if (scrollTop > scrollThreshold) {
        if (scrollTop > lastScrollTop) {
            // Scrolling down - hide header
            header.classList.add('header-hidden');
        } else {
            // Scrolling up - show header
            header.classList.remove('header-hidden');
        }
    } else {
        // At the top - always show header
        header.classList.remove('header-hidden');
    }
    
    lastScrollTop = scrollTop;
}

// Throttle the scroll event for better performance
let ticking = false;
function updateHeader() {
    handleHeaderScroll();
    ticking = false;
}

function requestTick() {
    if (!ticking) {
        requestAnimationFrame(updateHeader);
        ticking = true;
    }
}

// Add scroll event listener
window.addEventListener('scroll', requestTick);

// Show header when hovering near the top of the page
document.addEventListener('mousemove', function(e) {
    if (e.clientY < 100) {
        header.classList.remove('header-hidden');
    }
});

/******************************* SIDE BAR TOGGLE CUSTOMIZATION ***************************/
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const icon = document.getElementById('toggleIcon');

    // Always start collapsed
    sidebar.classList.remove('open');
    icon.className = 'bi bi-list';
});

// Modify toggleSidebar to handle overlay for small screens
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const icon = document.getElementById('toggleIcon');
    const content = document.querySelector('.content');

    const isOpen = sidebar.classList.toggle('open');
    icon.className = isOpen ? 'bi bi-chevron-double-left' : 'bi bi-list';

    if (window.innerWidth <= 768) {
        if (isOpen) {
            sidebarOverlay.classList.add('active');
        } else {
            sidebarOverlay.classList.remove('active');
        }
        if(content) content.style.paddingLeft = '';
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

// Create sidebar overlay (for smaller screens)
const sidebarOverlay = document.createElement('div');
sidebarOverlay.className = 'sidebar-overlay';
document.body.appendChild(sidebarOverlay);

// Close sidebar when clicking outside (only for small screens)
sidebarOverlay.addEventListener('click', function () {
    const sidebar = document.getElementById('sidebar');
    const icon = document.getElementById('toggleIcon');
    if (sidebar.classList.contains('open')) {
        sidebar.classList.remove('open');
        icon.className = 'bi bi-list';
        sidebarOverlay.classList.remove('active');
    }
});


/*************************** Dynamically match subtitle width to BRIDGE and scale hero texts responsively ***************************/
function matchSubtitleToBridge() {
  const bridge = document.querySelector('.hero-bridge');
  const subtitle = document.querySelector('.hero-subtitle');
  const welcome = document.querySelector('.hero-welcome');
  if (!bridge || !subtitle || !welcome) return;

  // Responsive font sizes for welcome and bridge
  const vw = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
  // Welcome: always smaller than BRIDGE
  let welcomeFont = Math.max(Math.min(vw * 0.035, 32), 18); // px
  let bridgeFont = Math.max(Math.min(vw * 0.09, 120), 32); // px
  welcome.style.fontSize = welcomeFont + 'px';
  bridge.style.fontSize = bridgeFont + 'px';

  // Subtitle: match width to BRIDGE, but keep readable
  subtitle.style.fontSize = '';
  let fontSize = bridgeFont * 0.15 + 10; // initial guess
  subtitle.style.fontSize = fontSize + 'px';
  let bridgeWidth = bridge.offsetWidth;
  let minFont = 10;
  let maxFont = bridgeFont * 0.5;
  // Decrease subtitle font size until it fits within bridge width
  while (subtitle.offsetWidth > bridgeWidth && fontSize > minFont) {
    fontSize -= 0.5;
    subtitle.style.fontSize = fontSize + 'px';
  }
  // If it's much shorter, increase a bit (but not above maxFont)
  while (subtitle.offsetWidth < bridgeWidth - 10 && fontSize < maxFont) {
    fontSize += 0.5;
    subtitle.style.fontSize = fontSize + 'px';
    if (subtitle.offsetWidth > bridgeWidth) {
      fontSize -= 0.5;
      subtitle.style.fontSize = fontSize + 'px';
      break;
    }
  }

  // On small screens, if subtitle wraps, reduce font size further
  if (window.innerWidth <= 600) {
    let subtitleHeight = subtitle.offsetHeight;
    let bridgeHeight = bridge.offsetHeight;
    let lineHeight = parseFloat(window.getComputedStyle(subtitle).lineHeight);
    // If subtitle is more than one line, reduce font size
    while (subtitleHeight > lineHeight * 1.5 && fontSize > minFont) {
      fontSize -= 0.5;
      subtitle.style.fontSize = fontSize + 'px';
      subtitleHeight = subtitle.offsetHeight;
    }
  }
}

window.addEventListener('DOMContentLoaded', matchSubtitleToBridge);
window.addEventListener('resize', matchSubtitleToBridge);

/********** Scroll to top on page load **********/
window.onbeforeunload = function () {
  window.scrollTo(0, 0);
};
window.addEventListener('pageshow', function() {
  window.scrollTo(0, 0);
});
