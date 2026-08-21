const API_BASE = '/blog-app/api/';

async function fetchAPI(endpoint, options = {}) {
    const url = API_BASE + endpoint;
    const config = {
        headers: { 'Content-Type': 'application/json' },
        signal: AbortSignal.timeout(15000), // ✅ 15 second timeout
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

function updateNav() {
    const user = getCurrentUser();
    const authLinks = document.getElementById('authLinks');
    const userInfo = document.getElementById('userInfo');
    const usernameDisplay = document.getElementById('usernameDisplay');
    const newPostLink = document.getElementById('newPostLink');

    if (user) {
        if (authLinks) authLinks.style.display = 'none';
        if (userInfo) {
            userInfo.style.display = 'inline';
            if (usernameDisplay) usernameDisplay.textContent = user.username;
        }
        if (newPostLink) newPostLink.style.display = 'inline';
    } else {
        if (authLinks) authLinks.style.display = 'inline';
        if (userInfo) userInfo.style.display = 'none';
        if (newPostLink) newPostLink.style.display = 'none';
    }
}

async function logoutUser() {
    try {
        await fetchAPI('auth.php?action=logout', { method: 'POST' });
        clearUser();
        updateNav();
        window.location.href = 'index.html';
    } catch (err) {
        console.error('Logout error:', err);
        clearUser();
        updateNav();
        window.location.href = 'index.html';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    updateNav();
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            logoutUser();
        });
    }
    const deleteButtons = document.querySelectorAll('.delete-blog-btn');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            if (confirm('Are you sure you want to delete this blog post? This action cannot be undone.')) {
                window.location.href = url;
            }
        });
    });
});


function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

