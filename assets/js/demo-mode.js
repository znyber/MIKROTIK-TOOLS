/**
 * H4N5VS Mikrotik System Security
 * Demo Mode JavaScript
 * This file handles the demo mode functionality
 */

// Check if we're running in demo mode
const inDemoMode = () => {
    return localStorage.getItem('h4n5vs_demo_mode') === 'true';
};

// Set demo mode status
const setDemoMode = (status) => {
    localStorage.setItem('h4n5vs_demo_mode', status ? 'true' : 'false');
    console.log(`H4N5VS Mikrotik System Security running in ${status ? 'DEMO MODE' : 'REAL MODE'}`);
    
    // Add visual indicator for demo mode
    if (status) {
        if (!document.querySelector('.demo-indicator')) {
            const demoIndicator = document.createElement('div');
            demoIndicator.className = 'demo-indicator';
            demoIndicator.innerHTML = '<span>DEMO MODE</span>';
            document.body.appendChild(demoIndicator);
        }
    } else {
        const indicator = document.querySelector('.demo-indicator');
        if (indicator) {
            indicator.remove();
        }
    }
};

// Initialize demo mode from session
document.addEventListener('DOMContentLoaded', function() {
    // Check PHP session through a cookie or custom header
    const demoMode = document.body.getAttribute('data-demo-mode') === 'true';
    setDemoMode(demoMode);
    
    // Add CSS for demo indicator
    if (!document.getElementById('demo-indicator-style')) {
        const style = document.createElement('style');
        style.id = 'demo-indicator-style';
        style.innerHTML = `
            .demo-indicator {
                position: fixed;
                top: 0;
                right: 0;
                background: linear-gradient(135deg, #FFC107, #FF9800);
                color: #000;
                padding: 5px 10px;
                z-index: 9999;
                border-bottom-left-radius: 5px;
                font-weight: bold;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                font-size: 12px;
            }
        `;
        document.head.appendChild(style);
    }
});

// Redirect API calls in demo mode
const redirectApiCall = (originalUrl) => {
    if (!inDemoMode()) {
        return originalUrl;
    }
    
    // List of API endpoints that should be redirected in demo mode
    const redirectMap = {
        'api/connection_status.php': 'api/connection_status_demo.php',
        'api/system_info.php': 'api/system_info_demo.php',
        'api/security_status.php': 'api/security_status_demo.php',
        'api/logs.php': 'api/logs_demo.php',
        'api/active_connections.php': 'api/active_connections_demo.php',
        'api/network_stats.php': 'api/network_stats_demo.php',
        'api/threats.php': 'api/threats_demo.php',
        'api/get_routers.php': 'api/get_routers_demo.php',
        'api/set_active_router.php': 'api/set_active_router_demo.php'
    };
    
    // Check if the URL matches any of the API endpoints
    for (const [original, demo] of Object.entries(redirectMap)) {
        if (originalUrl.includes(original)) {
            console.log(`Demo mode: redirecting ${original} to ${demo}`);
            return originalUrl.replace(original, demo);
        }
    }
    
    return originalUrl;
};

// Override fetch in demo mode
const originalFetch = window.fetch;
window.fetch = function(url, options) {
    const newUrl = redirectApiCall(url);
    return originalFetch(newUrl, options);
};

// Override XMLHttpRequest in demo mode
const originalXhrOpen = XMLHttpRequest.prototype.open;
XMLHttpRequest.prototype.open = function(method, url, async, user, password) {
    const newUrl = redirectApiCall(url);
    return originalXhrOpen.call(this, method, newUrl, async, user, password);
};

// Helper function to create demo data
const generateDemoData = (type) => {
    switch(type) {
        case 'cpu':
            return Math.floor(Math.random() * 50) + 10; // 10-60%
        case 'memory':
            return Math.floor(Math.random() * 40) + 20; // 20-60%
        case 'disk':
            return Math.floor(Math.random() * 30) + 40; // 40-70%
        case 'connections':
            return Math.floor(Math.random() * 100) + 50; // 50-150
        case 'bandwidth': 
            return {
                download: Math.floor(Math.random() * 50) + 10, // 10-60 Mbps
                upload: Math.floor(Math.random() * 20) + 5 // 5-25 Mbps
            };
        default:
            return null;
    }
};

// Expose demo functions globally
window.h4n5vsDemo = {
    inDemoMode,
    setDemoMode,
    generateDemoData
};