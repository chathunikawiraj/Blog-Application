const API_BASE = '../api/';  // adjust if needed

async function fetchAPI(endpoint, options = {}) {
    const url = API_BASE + endpoint;
    const config = {
        headers: { 'Content-Type': 'application/json' },
        ...options
    };
    if (options.body && typeof options.body === 'object') {
        config.body = JSON.stringify(options.body);
    }
    const response = await fetch(url, config);
    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.error || `HTTP ${response.status}`);
    }
    return response.json();
}

// Check login status by trying to fetch user info (we can use session)
// Since we use PHP sessions, we can have a /api/auth.php?action=check endpoint.
// For simplicity, we'll store user in localStorage after login.
function getCurrentUser() {
    const user = localStorage.getItem('user');
    return user ? JSON.parse(user) : null;
}

function setUser(user) {
    localStorage.setItem('user', JSON.stringify(user));
}

function clearUser() {
    localStorage.removeItem('user');
}

// UI helpers to show/hide auth links
function updateNav() {
    const user = getCurrentUser();
    const authLinks = document.getElementById('authLinks');
    const userInfo = document.getElementById('userInfo');
    const usernameDisplay = document.getElementById('usernameDisplay');
    if (user) {
        authLinks.style.display = 'none';
        userInfo.style.display = 'inline';
        usernameDisplay.textContent = user.username;
    } else {
        authLinks.style.display = 'inline';
        userInfo.style.display = 'none';
    }
}

// Logout
document.addEventListener('DOMContentLoaded', () => {
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async () => {
            await fetchAPI('auth.php?action=logout', { method: 'POST' });
            clearUser();
            updateNav();
            window.location.href = 'index.html';
        });
    }
    updateNav();
});