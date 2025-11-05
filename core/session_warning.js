/********** SESSION TIMEOUT SYSTEM **********/
let sessionCheckInterval = null;
let lastActivity = Date.now();
let sessionTimeRemaining = null;
let serverLastActivity = null;
let isLoggingOut = false; // Flag to track intentional logout

// Session timeout settings (in milliseconds) 
const SESSION_TIMEOUT = 5 * 60 * 1000; // 5 minutes
const CHECK_INTERVAL = 30 * 1000; // Check every 30 seconds
const INACTIVE_THRESHOLD = 10 * 1000; // Consider inactive after 10 seconds of no activity

function initializeSessionWarning() {
    // Stop any existing intervals first (in case of navigation)
    stopSessionWarning();
    
    // Don't initialize on auth pages (login, logout, etc.)
    if (isAuthPage()) {
        return;
    }
    
    // Reset timers on any user activity
    document.addEventListener('click', resetSessionTimers);
    document.addEventListener('keypress', resetSessionTimers);
    document.addEventListener('mousemove', resetSessionTimers);
    document.addEventListener('scroll', resetSessionTimers);
    
    // Detect logout link clicks and stop session warning system
    document.addEventListener('click', handleLogoutClick);
    
    // Start the warning system
    startSessionWarning();
}

function resetSessionTimers() {
    // Don't update timers on auth pages
    if (isAuthPage()) {
        return;
    }
    
    lastActivity = Date.now();
    
    // Update server-side activity immediately when user interacts
    // This ensures the server knows the user is active right away
    updateServerActivity();
    
    // Clear existing interval
    if (sessionCheckInterval) {
        clearInterval(sessionCheckInterval);
    }
    
    // Restart the session check system
    startSessionWarning();
}

// Throttled function to update server-side activity
let activityUpdateThrottle = null;
let lastServerUpdate = 0;
const ACTIVITY_UPDATE_THROTTLE = 1000; // Throttle to maximum once per second

async function updateServerActivity() {
    const now = Date.now();
    const timeSinceLastUpdate = now - lastServerUpdate;
    
    // Clear any pending update - we'll reschedule it
    if (activityUpdateThrottle) {
        clearTimeout(activityUpdateThrottle);
        activityUpdateThrottle = null;
    }
    
    // Always update immediately on first call or after throttle period
    // This ensures server timeout resets immediately when user clicks
    const delay = (lastServerUpdate === 0 || timeSinceLastUpdate >= ACTIVITY_UPDATE_THROTTLE) 
        ? 0  // Immediate update
        : Math.min(100, ACTIVITY_UPDATE_THROTTLE - timeSinceLastUpdate); // Small delay for rapid clicks
    
    activityUpdateThrottle = setTimeout(async () => {
        activityUpdateThrottle = null;
        try {
            // Call the session timeout endpoint with update parameter to reset LAST_ACTIVITY
            const response = await fetch('core/get_session_timeout.php?update=1&_t=' + Date.now(), {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-cache'
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    lastServerUpdate = Date.now();
                    // Use server's actual timestamp (converted to milliseconds) for accurate countdown
                    serverLastActivity = data.lastActivity * 1000;
                    sessionTimeRemaining = data.timeRemaining;
                } else {
                    console.debug('Server activity update returned success=false:', data);
                }
            } else {
                console.debug('Server activity update failed with status:', response.status);
            }
        } catch (error) {
            // Silently fail - activity update is not critical
            console.debug('Activity update failed:', error);
        }
    }, delay);
}

function startSessionWarning() {
    // Check session status immediately
    checkSessionStatus();
    
    // Set up periodic checking
    sessionCheckInterval = setInterval(checkSessionStatus, CHECK_INTERVAL);
}

// Handle logout link clicks to prevent session warning during intentional logout
function handleLogoutClick(event) {
    const target = event.target.closest('a');
    if (target && target.href) {
        const href = target.href.toLowerCase();
        // Check if the link points to logout
        if (href.includes('/logout') || href.includes('core/logout')) {
            isLoggingOut = true;
            stopSessionWarning();
            // Don't prevent default - let the logout proceed normally
        }
    }
    
    // Also check for buttons or elements that might trigger logout
    const button = event.target.closest('button');
    if (button) {
        const onclick = button.getAttribute('onclick') || '';
        const dataAction = button.getAttribute('data-action') || '';
        if (onclick.toLowerCase().includes('logout') || 
            dataAction.toLowerCase().includes('logout') ||
            button.textContent.toLowerCase().includes('logout')) {
            // Check if this button is in a form or has a logout-related action
            const form = button.closest('form');
            if (form && (form.action.includes('logout') || form.action.includes('core/logout'))) {
                isLoggingOut = true;
                stopSessionWarning();
            }
        }
    }
}

// Monitor for programmatic logout (e.g., via AJAX calls to logout endpoint)
// Override fetch to detect logout calls
(function() {
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        let url = '';
        let method = 'GET';
        
        // Handle different fetch call patterns
        if (typeof args[0] === 'string') {
            url = args[0];
            method = args[1]?.method || 'GET';
        } else if (args[0] instanceof Request) {
            url = args[0].url;
            method = args[0].method || 'GET';
            // If options are provided, they override Request method
            if (args[1]?.method) {
                method = args[1].method;
            }
        } else if (args[0]?.url) {
            url = args[0].url;
            method = args[0].method || args[1]?.method || 'GET';
        }
        
        // Detect POST/PUT/DELETE requests to logout endpoint
        if (url && (url.toLowerCase().includes('/logout') || url.toLowerCase().includes('core/logout'))) {
            if (method.toUpperCase() === 'POST' || method.toUpperCase() === 'PUT' || method.toUpperCase() === 'DELETE') {
                isLoggingOut = true;
                stopSessionWarning();
            }
        }
        
        return originalFetch.apply(this, args);
    };
})();

// Check if we're on a page where session warnings shouldn't show
function isAuthPage() {
    const path = window.location.pathname.toLowerCase();
    return path.includes('/login') || 
           path.includes('/logout') || 
           path.includes('/session-expired') ||
           path.includes('/forget-password') ||
           path.includes('/verify-email');
}

// Stop all session checking activities (useful when navigating to auth pages)
function stopSessionWarning() {
    if (sessionCheckInterval) {
        clearInterval(sessionCheckInterval);
        sessionCheckInterval = null;
    }
    if (activityUpdateThrottle) {
        clearTimeout(activityUpdateThrottle);
        activityUpdateThrottle = null;
    }
}

// Reset logout flag (useful when user logs back in)
function resetLogoutFlag() {
    isLoggingOut = false;
}

async function checkSessionStatus() {
    // Don't check session status on login/logout pages
    // Also stop any running intervals if we detect we're on an auth page
    if (isAuthPage()) {
        stopSessionWarning();
        return;
    }
    
    // Don't check or show warnings if user is intentionally logging out
    if (isLoggingOut) {
        stopSessionWarning();
        return;
    }
    
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
            // Only show warning if we're not already on an auth page
            // and user is not intentionally logging out
            if (!isAuthPage() && !isLoggingOut) {
                showFinalWarning();
            }
            return;
        }
        
        // Update session info
        sessionTimeRemaining = data.timeRemaining;
        // Update serverLastActivity from server response
        serverLastActivity = data.lastActivity * 1000; // Convert to milliseconds
        
    } catch (error) {
        console.error('Error checking session status:', error);
    }
}

function showFinalWarning() {
    // Don't show warning if user is intentionally logging out
    if (isLoggingOut) {
        return;
    }
    
    // Clear the interval to prevent multiple calls
    if (sessionCheckInterval) {
        clearInterval(sessionCheckInterval);
        sessionCheckInterval = null;
    }
    
    // Set a flag to bypass navigation guards during session expiration
    window.sessionExpired = true;
    
    // Auto-redirect to timeout landing page with NProgress
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
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeSessionWarning();
});
