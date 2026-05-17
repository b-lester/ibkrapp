let isReauthenticating = false;

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
    tickleSession();
    return window.setInterval(tickleSession, 30000);
}

window.manualReauthenticate = manualReauthenticate;
window.tickleSession = tickleSession;
window.startAuthStatusPolling = startAuthStatusPolling;
window.setAuthStatus = setAuthStatus;
window.showSessionExpired = showSessionExpired;
window.showConnectionError = showConnectionError;
