// General polling functionality for D&D Manager
// This file contains shared polling utilities

// Polling configuration
const POLL_INTERVAL = 3000; // 3 seconds

// Generic polling function
async function pollEndpoint(url, callback, errorCallback = null) {
    try {
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success) {
            callback(result);
        } else if (errorCallback) {
            errorCallback(result);
        }
    } catch (error) {
        console.error('Polling error:', error);
        if (errorCallback) {
            errorCallback({ error: error.message });
        }
    }
}

// Start polling with interval
function startPolling(url, callback, interval = POLL_INTERVAL) {
    // Initial poll
    pollEndpoint(url, callback);
    
    // Set up interval
    return setInterval(() => {
        pollEndpoint(url, callback);
    }, interval);
}

// Stop polling
function stopPolling(intervalId) {
    if (intervalId) {
        clearInterval(intervalId);
    }
}

// Visual feedback for updates
function flashElement(elementId, color = 'text-primary') {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.add(color);
        setTimeout(() => {
            element.classList.remove(color);
        }, 500);
    }
}

// Helper to make API calls
async function apiCall(action, data = {}) {
    const formData = new FormData();
    formData.append('action', action);
    
    for (const key in data) {
        formData.append(key, data[key]);
    }
    
    try {
        const response = await fetch('/admin/api.php', {
            method: 'POST',
            body: formData
        });
        
        return await response.json();
    } catch (error) {
        console.error('API call error:', error);
        return { success: false, error: error.message };
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        pollEndpoint,
        startPolling,
        stopPolling,
        flashElement,
        apiCall,
        POLL_INTERVAL
    };
}
