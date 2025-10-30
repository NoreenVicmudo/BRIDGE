/**
 * URL Helper for BRIDGE System
 * Provides JavaScript utilities for generating correct URLs
 */

// Get the base URL for the application
function getBaseUrl() {
    const protocol = window.location.protocol;
    const host = window.location.host;
    const pathname = window.location.pathname;
    
    // Extract the base path
    let basePath = pathname;
    if (basePath.includes('/bridge/')) {
        basePath = basePath.substring(0, basePath.indexOf('/bridge/') + 7);
    } else if (basePath.endsWith('/bridge')) {
        basePath = basePath + '/';
    } else if (!basePath.endsWith('/')) {
        basePath = basePath + '/';
    }
    
    return protocol + '//' + host + basePath;
}

// Generate a URL for a module or file
function url(path = '', useCleanUrl = true) {
    const baseUrl = getBaseUrl();
    
    if (!path) {
        return baseUrl;
    }
    
    // Remove leading slash if present
    path = path.replace(/^\//, '');
    
    if (useCleanUrl) {
        // Convert module paths to clean URLs
        const cleanPaths = {
            'modules/student_info/student_info_filter.php': 'student-info',
            'modules/academic_profile/academic_profile_filter.php': 'academic-profile',
            'modules/program_metrics/program_metrics_filter.php': 'program-metrics',
            'modules/generate_report/generate_report_filter.php': 'generate-report',
            'modules/additional_entry/student_info_data_entry.php': 'additional-entry',
            'modules/user_information/views/user_list.php': 'user-management',
            'modules/transaction_logs/transaction_logs.php': 'transaction-logs',
            'modules/settings/views/settings.php': 'settings',
            'user_auth/views/login.php': 'login',
            'public/main_page.php': '',
        };
        
        if (cleanPaths[path]) {
            return baseUrl + cleanPaths[path];
        }
    }
    
    return baseUrl + path;
}

// Generate a URL for static assets
function asset(path) {
    const baseUrl = getBaseUrl();
    return baseUrl + path.replace(/^\//, '');
}

// Enhanced fetch function that handles URL generation
function bridgeFetch(path, options = {}) {
    const fullUrl = url(path, true);
    return fetch(fullUrl, options);
}

// Enhanced fetch for direct file access (when you need to bypass clean URLs)
function bridgeFetchDirect(path, options = {}) {
    const fullUrl = url(path, false);
    return fetch(fullUrl, options);
}

// Make functions available globally
window.getBaseUrl = getBaseUrl;
window.url = url;
window.asset = asset;
window.bridgeFetch = bridgeFetch;
window.bridgeFetchDirect = bridgeFetchDirect;
