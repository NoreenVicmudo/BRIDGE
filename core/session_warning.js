/********** SESSION TIMEOUT WARNING SYSTEM **********/
let sessionCheckInterval = null;
let isWarningShown = false;
let lastActivity = Date.now();
let warningShownTime = 0; // Track when warning was last shown

// Session timeout settings (in milliseconds) 
const SESSION_TIMEOUT = 5 * 60 * 1000; // 5 minutes
const WARNING_TIME = 3 * 60 * 1000; // 3 minutes before timeout
const CHECK_INTERVAL = 30 * 1000; // Check every 30 seconds
const WARNING_COOLDOWN = 2 * 60 * 1000; // 2 minutes cooldown before showing warning again

function initializeSessionWarning() {
    // Reset timers on any user activity
    document.addEventListener('click', resetSessionTimers);
    document.addEventListener('keypress', resetSessionTimers);
    document.addEventListener('mousemove', resetSessionTimers);
    document.addEventListener('scroll', resetSessionTimers);
    
    // Start the warning system
    startSessionWarning();
}

function resetSessionTimers() {
    lastActivity = Date.now();
    
    // Only reset warning flag if enough time has passed since last warning
    const timeSinceLastWarning = Date.now() - warningShownTime;
    if (timeSinceLastWarning > WARNING_COOLDOWN) {
        isWarningShown = false;
    }
    
    // Clear existing interval
    if (sessionCheckInterval) {
        clearInterval(sessionCheckInterval);
    }
    
    // Restart the warning system
    startSessionWarning();
}

function startSessionWarning() {
    // Check session status immediately
    checkSessionStatus();
    
    // Set up periodic checking
    sessionCheckInterval = setInterval(checkSessionStatus, CHECK_INTERVAL);
}

async function checkSessionStatus() {
    try {
        const response = await fetch('core/get_session_timeout.php', {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-cache'
        });
        
        if (!response.ok) {
            console.error('Failed to check session status');
            return;
        }
        
        const data = await response.json();
        
        if (!data.success || data.isExpired) {
            // Session expired, redirect immediately
            showFinalWarning();
            return;
        }
        
        const timeRemaining = data.timeRemaining * 1000; // Convert to milliseconds
        
        // Show warning if we have 3 minutes or less remaining
        if (timeRemaining <= WARNING_TIME && !isWarningShown) {
            showSessionWarning();
        }
        
    } catch (error) {
        console.error('Error checking session status:', error);
    }
}

function showSessionWarning() {
    isWarningShown = true;
    warningShownTime = Date.now(); // Track when warning was shown
    sessionToast("⚠️ You will be logged out in 3 minutes due to inactivity. Click anywhere to stay logged in.", 10000);
}

function showFinalWarning() {
    // Clear the interval to prevent multiple calls
    if (sessionCheckInterval) {
        clearInterval(sessionCheckInterval);
        sessionCheckInterval = null;
    }
    
    // Set a flag to bypass navigation guards during session expiration
    window.sessionExpired = true;
    
    sessionToast("Session expired. Logging out...", 2000);
    
    // Auto-redirect to timeout landing page with NProgress
    setTimeout(() => {
        if (typeof NProgress !== "undefined") {
            NProgress.start();
            setTimeout(() => NProgress.set(0.7), 300);
            setTimeout(() => {
                NProgress.done();
                // Force redirect without triggering navigation guards
                window.location.replace("session-expired?timeout=1");
            }, 1200);
        } else {
            // Fallback without NProgress - use replace to bypass navigation guards
            window.location.replace("session-expired?timeout=1");
        }
    }, 2000); // Wait for toast to show briefly
}


////////// TOAST HELPER (Responsive) //////////
function sessionToast(message, duration = 5000) {
    let toast = document.getElementById("sessionWarningToast");
    if (!toast) {
        toast = document.createElement("div");
        toast.id = "sessionWarningToast";
        toast.style.position = "fixed";
        toast.style.top = "20px";
        toast.style.left = "50%";
        toast.style.transform = "translateX(-50%) translateY(-100px)";
        toast.style.background = "#dc3545";
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
        toast.style.boxShadow = "0 4px 12px rgba(0,0,0,0.3)";
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
    }, duration);
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeSessionWarning();
});
