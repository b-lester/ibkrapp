let isReauthenticating = false;
let isLoggingOut = false;

function authStatusElements() {
    return {
        dot: document.getElementById('auth-dot'),
        text: document.getElementById('auth-text')
    };
}

function setAuthStatus(isAuthenticated, html) {
    const { dot, text } = authStatusElements();
    if (!dot || !text) return;

    dot.className = isAuthenticated ? 'status-dot authenticated' : 'status-dot unauthenticated';
    if (html) {
        text.innerHTML = html;
    } else {
        text.innerText = isAuthenticated ? 'Session Active' : 'Session Expired';
    }
}

function ensureLogoutButton() {
    const { text } = authStatusElements();
    if (!text) return;
    const container = text.closest('.auth-status');
    if (!container || container.querySelector('.auth-logout-button')) return;

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'auth-logout-button';
    button.textContent = 'Log out';
    button.title = 'Log out of the IBKR Client Portal Gateway session';
    button.addEventListener('click', logoutGatewaySession);
    Object.assign(button.style, {
        color: '#8fd2c8',
        background: 'transparent',
        border: '0',
        padding: '0',
        cursor: 'pointer',
        font: 'inherit'
    });
    container.appendChild(button);
}

function showSessionExpired() {
    setAuthStatus(false, 'Session Expired (<a href="#" onclick="manualReauthenticate(); return false;">Re-authenticate</a>)');
}

function showConnectionError() {
    setAuthStatus(false, 'Connection Error (<a href="#" onclick="manualReauthenticate(); return false;">Re-authenticate</a>)');
}

async function manualReauthenticate() {
    if (isReauthenticating) return;
    isReauthenticating = true;

    const { dot, text } = authStatusElements();
    if (!dot || !text) return;

    text.innerText = 'Re-authenticating...';
    try {
        await fetch('reauthenticate_proxy.php', { method: 'POST' });
        await tickleSession(true);

        if (!dot.classList.contains('authenticated')) {
            text.innerHTML = 'Re-auth failed. (<a href="https://localhost:5050/" target="_blank">Login</a>)';
        }
    } catch (error) {
        console.error('Manual re-auth error:', error);
        text.innerHTML = 'Re-auth failed. (<a href="https://localhost:5050/" target="_blank">Login</a>)';
    } finally {
        isReauthenticating = false;
    }
}

async function logoutGatewaySession() {
    if (isLoggingOut) return;
    isLoggingOut = true;

    const { text } = authStatusElements();
    if (text) text.innerText = 'Logging out...';

    try {
        const response = await fetch('logout_proxy.php', { method: 'POST' });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(data.error || `Logout failed: ${response.status}`);
        }
        document.cookie = 'api=; Max-Age=0; path=/; SameSite=Lax';
        setAuthStatus(false, 'Logged out (<a href="https://localhost:5050/" target="_blank">Login</a>)');
    } catch (error) {
        console.error('Logout error:', error);
        if (text) text.innerHTML = 'Logout failed';
    } finally {
        isLoggingOut = false;
        ensureLogoutButton();
    }
}

async function tickleSession(isAfterManualReauth = false) {
    if (isReauthenticating && !isAfterManualReauth) return;

    const { dot, text } = authStatusElements();
    if (!dot || !text) return;

    try {
        const response = await fetch('tickle_proxy.php', { method: 'GET' });
        const data = await response.json();
        if (response.status === 401) {
            showSessionExpired();
            return;
        }
        if (!response.ok) {
            throw new Error(data.error || `Tickle failed: ${response.status}`);
        }
        const isAuthenticated = data.iserver && data.iserver.authStatus && data.iserver.authStatus.authenticated === true;

        if (isAuthenticated) {
            setAuthStatus(true);
            return;
        }

        if (!isAfterManualReauth && !text.innerHTML.includes('localhost:5050')) {
            showSessionExpired();
        }
    } catch (error) {
        console.warn('Tickle error:', error);
        if (!isAfterManualReauth && !text.innerHTML.includes('localhost:5050')) {
            showConnectionError();
        }
    }
}

function startAuthStatusPolling() {
    ensureLogoutButton();
    tickleSession();
    return window.setInterval(tickleSession, 30000);
}

window.manualReauthenticate = manualReauthenticate;
window.logoutGatewaySession = logoutGatewaySession;
window.tickleSession = tickleSession;
window.startAuthStatusPolling = startAuthStatusPolling;
window.setAuthStatus = setAuthStatus;
window.showSessionExpired = showSessionExpired;
window.showConnectionError = showConnectionError;
