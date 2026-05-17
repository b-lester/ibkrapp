<?php
header('Content-Type: text/html; charset=utf-8');
$assetVersion = '1';
$localConfigPath = dirname(__DIR__) . '/localconfig.php';
if (file_exists($localConfigPath)) {
    require $localConfigPath;
    if (isset($config['cachebuster'])) {
        $assetVersion = (string)$config['cachebuster'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IBKR Charts</title>
    <script src="https://unpkg.com/lightweight-charts@4.2.3/dist/lightweight-charts.standalone.production.js"></script>
    <style>
        :root {
            --bg: #101417;
            --panel: #161c20;
            --panel-border: #2c363d;
            --text: #e6edf0;
            --muted: #91a1aa;
            --accent: #2f8f83;
            --danger: #c94d57;
            --input: #0d1114;
        }

        * {
            box-sizing: border-box;
        }

        body {
            height: 100vh;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            font-size: 14px;
        }

        button,
        input,
        select {
            font: inherit;
        }

        .app-shell {
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .topbar {
            min-height: 52px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 12px;
            border-bottom: 1px solid var(--panel-border);
            background: #12181c;
            flex-wrap: wrap;
        }

        .brand {
            font-weight: 700;
            letter-spacing: 0;
            margin-right: 8px;
        }

        .nav-link {
            color: var(--muted);
            text-decoration: none;
            border: 1px solid var(--panel-border);
            border-radius: 6px;
            padding: 7px 10px;
            line-height: 1;
        }

        .nav-link:hover {
            color: var(--text);
            border-color: #45545e;
        }

        .auth-status {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-left: auto;
            color: var(--muted);
            font-size: 12px;
            white-space: nowrap;
        }

        .auth-status a {
            color: #8fd2c8;
            text-decoration: none;
        }

        .auth-status a:hover {
            text-decoration: underline;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #7c8790;
            flex: 0 0 auto;
        }

        .status-dot.authenticated {
            background-color: #27ae60;
        }

        .status-dot.unauthenticated {
            background-color: #e74c3c;
        }

        .control-group {
            display: flex;
            align-items: center;
            gap: 6px;
            min-width: 0;
        }

        .control-group label {
            color: var(--muted);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        input,
        select {
            color: var(--text);
            background: var(--input);
            border: 1px solid var(--panel-border);
            border-radius: 6px;
            min-height: 34px;
            padding: 6px 8px;
            outline: none;
        }

        input:focus,
        select:focus {
            border-color: var(--accent);
        }

        #symbol-input {
            width: 110px;
            text-transform: uppercase;
        }

        .button {
            min-height: 34px;
            border: 1px solid var(--panel-border);
            border-radius: 6px;
            color: var(--text);
            background: #1d262b;
            padding: 6px 11px;
            cursor: pointer;
        }

        .button:hover {
            border-color: #45545e;
            background: #243039;
        }

        .button.primary {
            background: var(--accent);
            border-color: var(--accent);
            color: #f4fffb;
            font-weight: 700;
        }

        .status-bar {
            min-height: 28px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 5px 12px;
            color: var(--muted);
            border-bottom: 1px solid var(--panel-border);
            background: #0f1417;
            font-size: 12px;
        }

        .status-bar.error {
            color: #ff9aa2;
        }

        .workspace {
            flex: 1;
            min-height: 0;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 280px;
            overflow: hidden;
        }

        .workspace.watchlist-hidden {
            grid-template-columns: minmax(0, 1fr);
        }

        .charts-grid {
            min-height: 0;
            display: grid;
            grid-template-columns: repeat(var(--grid-cols, 1), minmax(0, 1fr));
            grid-template-rows: repeat(var(--grid-rows, 1), minmax(0, 1fr));
            grid-auto-rows: minmax(260px, 1fr);
            gap: 10px;
            padding: 10px;
            align-content: stretch;
            overflow: auto;
        }

        .watchlist-pane {
            min-height: 0;
            border-left: 1px solid var(--panel-border);
            background: #12181c;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .workspace.watchlist-hidden .watchlist-pane {
            display: none;
        }

        .watchlist-header {
            min-height: 42px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 8px 10px;
            border-bottom: 1px solid var(--panel-border);
            background: #141b1f;
        }

        .watchlist-title {
            font-weight: 800;
            font-size: 13px;
        }

        .watchlist-count {
            color: var(--muted);
            font-size: 12px;
            white-space: nowrap;
        }

        .watchlist-add {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 6px;
            padding: 8px 10px;
            border-bottom: 1px solid var(--panel-border);
        }

        #watchlist-symbol-input {
            width: 100%;
            min-height: 32px;
            text-transform: uppercase;
        }

        .watchlist-add .icon-button {
            width: 32px;
            height: 32px;
        }

        .watchlist-message {
            min-height: 20px;
            padding: 0 10px 7px;
            color: #ffb4ba;
            font-size: 12px;
        }

        .watchlist-items {
            flex: 1;
            min-height: 0;
            overflow: auto;
        }

        .watchlist-row {
            width: 100%;
            min-height: 38px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto auto;
            align-items: center;
            gap: 8px;
            padding: 7px 8px 7px 10px;
            border-bottom: 1px solid rgba(44, 54, 61, 0.72);
            outline: 2px solid transparent;
            outline-offset: -2px;
            cursor: pointer;
        }

        .watchlist-row.focused {
            border-color: #4597ff;
            box-shadow: 0 0 0 1px rgba(69, 151, 255, 0.45);
        }

        .watchlist-symbol {
            font-weight: 800;
            font-size: 13px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .watchlist-quote {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 1px;
            min-width: 86px;
            font-size: 12px;
        }

        .watchlist-price {
            color: var(--text);
            font-variant-numeric: tabular-nums;
        }

        .watchlist-change {
            color: var(--muted);
            font-variant-numeric: tabular-nums;
        }

        .watchlist-row.up .watchlist-change {
            color: #7bd9a8;
        }

        .watchlist-row.down .watchlist-change {
            color: #ff929a;
        }

        .watchlist-row.error .watchlist-change {
            color: #ffb4ba;
        }

        .watchlist-remove {
            width: 24px;
            height: 24px;
            border: none;
            border-radius: 4px;
            color: var(--muted);
            background: transparent;
            cursor: pointer;
        }

        .watchlist-remove:hover {
            color: var(--text);
            background: #1d262b;
        }

        .chart-panel {
            min-height: 0;
            border: 1px solid var(--panel-border);
            background: var(--panel);
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            outline: 2px solid transparent;
            outline-offset: -2px;
        }

        .chart-panel.focused {
            border-color: #4597ff;
            box-shadow: 0 0 0 1px rgba(69, 151, 255, 0.45);
        }

        .charts-grid:has(.chart-panel:only-child) {
            grid-template-columns: 1fr;
            grid-auto-rows: 1fr;
        }

        .layout-number {
            width: 58px;
            min-height: 32px;
        }

        .chart-header {
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 7px 9px;
            border-bottom: 1px solid var(--panel-border);
            background: #141b1f;
        }

        .chart-title {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        .chart-symbol-input {
            width: 96px;
            min-height: 28px;
            padding: 3px 6px;
            color: var(--text);
            background: var(--input);
            border: 1px solid transparent;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 800;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .chart-symbol-input:hover,
        .chart-symbol-input:focus {
            border-color: var(--panel-border);
        }

        .chart-meta {
            color: var(--muted);
            font-size: 12px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .chart-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }

        .chart-bar-select {
            min-height: 28px;
            padding: 3px 6px;
            color: var(--text);
            background: var(--input);
            border: 1px solid var(--panel-border);
            border-radius: 6px;
            font-size: 12px;
        }

        .icon-button {
            width: 28px;
            height: 28px;
            display: inline-grid;
            place-items: center;
            border-radius: 6px;
            border: 1px solid var(--panel-border);
            color: var(--muted);
            background: transparent;
            cursor: pointer;
        }

        .icon-button:hover {
            color: var(--text);
            border-color: #45545e;
            background: #1d262b;
        }

        .chart-area {
            position: relative;
            flex: 1;
            min-height: 0;
        }

        .chart-canvas {
            position: absolute;
            inset: 0;
        }

        .chart-message {
            position: absolute;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            color: var(--muted);
            text-align: center;
            white-space: pre-line;
            pointer-events: none;
            background: rgba(22, 28, 32, 0.72);
            z-index: 2;
        }

        .chart-panel.initial-loading .chart-message,
        .chart-panel.error .chart-message {
            display: flex;
        }

        .chart-panel.error .chart-message {
            color: #ff9aa2;
        }

        .chunk-loaders {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 3;
        }

        .chunk-loader {
            position: absolute;
            top: 12px;
            max-width: min(280px, calc(100% - 24px));
            border: 1px solid #45626a;
            border-radius: 6px;
            background: rgba(13, 17, 20, 0.9);
            color: #d5e7ed;
            padding: 5px 8px;
            font-size: 12px;
            line-height: 1.25;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.28);
        }

        .chart-footer {
            min-height: 38px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            padding: 5px 9px;
            border-top: 1px solid var(--panel-border);
            color: var(--muted);
            font-size: 12px;
        }

        .chart-footer-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cache-pill {
            border: 1px solid var(--panel-border);
            border-radius: 999px;
            padding: 2px 8px;
            white-space: nowrap;
        }

        .cache-pill.hit {
            color: #a7ddc7;
            border-color: #2f6f5c;
        }

        .cache-pill.miss {
            color: #f2c38f;
            border-color: #7f5b2c;
        }

        .footer-button {
            min-height: 26px;
            border: 1px solid var(--panel-border);
            border-radius: 6px;
            color: var(--text);
            background: transparent;
            padding: 3px 8px;
            cursor: pointer;
            white-space: nowrap;
        }

        .footer-button:hover {
            border-color: #45545e;
            background: #1d262b;
        }

        @media (max-width: 680px) {
            .topbar {
                align-items: stretch;
            }

            .control-group {
                flex: 1 1 140px;
            }

            .control-group input,
            .control-group select {
                width: 100%;
            }

            .button.primary {
                flex: 1 1 100%;
            }

            .workspace {
                grid-template-columns: minmax(0, 1fr);
            }

            .watchlist-pane {
                min-height: 260px;
                border-left: none;
                border-top: 1px solid var(--panel-border);
            }

            .chart-panel {
                min-height: 320px;
            }
        }
    </style>
</head>
<body>
<div class="app-shell">
    <div class="topbar">
        <div class="brand">IBKR Charts</div>
        <a class="nav-link" href="index.php">Positions</a>
        <div class="control-group">
            <label for="symbol-input">Symbol</label>
            <input id="symbol-input" autocomplete="off" spellcheck="false">
        </div>
        <div class="control-group">
            <label for="grid-rows-input">Rows</label>
            <input id="grid-rows-input" class="layout-number" type="number" min="1" max="12" step="1" value="1">
        </div>
        <div class="control-group">
            <label for="grid-cols-input">Cols</label>
            <input id="grid-cols-input" class="layout-number" type="number" min="1" max="12" step="1" value="1">
        </div>
        <button id="add-chart-button" class="button primary" type="button">Add Chart</button>
        <button id="toggle-watchlist-button" class="button" type="button">Watchlist</button>
        <div class="auth-status">
            <div id="auth-dot" class="status-dot"></div>
            <span id="auth-text">Checking session...</span>
        </div>
    </div>
    <div id="status-bar" class="status-bar">Ready</div>
    <div id="workspace" class="workspace">
        <div id="charts-grid" class="charts-grid"></div>
        <aside id="watchlist-pane" class="watchlist-pane">
            <div class="watchlist-header">
                <div class="watchlist-title">Watchlist</div>
                <div id="watchlist-count" class="watchlist-count">0 / 100</div>
            </div>
            <div class="watchlist-add">
                <input id="watchlist-symbol-input" autocomplete="off" spellcheck="false" placeholder="Symbol">
                <button id="add-watchlist-button" class="icon-button" type="button" title="Add ticker">+</button>
            </div>
            <div id="watchlist-message" class="watchlist-message"></div>
            <div id="watchlist-items" class="watchlist-items"></div>
        </aside>
    </div>
</div>

<script>
    const workspaceEl = document.getElementById('workspace');
    const chartGrid = document.getElementById('charts-grid');
    const statusBar = document.getElementById('status-bar');
    const addChartButton = document.getElementById('add-chart-button');
    const toggleWatchlistButton = document.getElementById('toggle-watchlist-button');
    const symbolInput = document.getElementById('symbol-input');
    const gridRowsInput = document.getElementById('grid-rows-input');
    const gridColsInput = document.getElementById('grid-cols-input');
    const watchlistSymbolInput = document.getElementById('watchlist-symbol-input');
    const addWatchlistButton = document.getElementById('add-watchlist-button');
    const watchlistItemsEl = document.getElementById('watchlist-items');
    const watchlistCountEl = document.getElementById('watchlist-count');
    const watchlistMessageEl = document.getElementById('watchlist-message');

    const upColor = '#1fa774';
    const downColor = '#dc4c5a';
    const watchlistLimit = 100;
    const defaultNewChartBar = '1d';
    const defaultNewChartPeriod = '1y';
    const maxConcurrentMarketDataRequests = 2;
    const chunkPeriodByBar = {
        '1min': '1d',
        '2min': '2d',
        '3min': '3d',
        '5min': '5d',
        '10min': '1w',
        '15min': '2w',
        '30min': '1m',
        '1h': '1m',
        '2h': '2m',
        '3h': '3m',
        '4h': '3m',
        '8h': '6m',
        '1d': '3m',
        '1w': '2y',
        '1m': '10y'
    };

    let nextChartId = 1;
    const charts = new Map();
    const workspaceStorageKey = 'ibkrChartWorkspace';
    let isRestoringWorkspace = false;
    let saveWorkspaceTimer = null;
    let watchlistVisible = true;
    let watchlistSymbols = [];
    const watchlistQuotes = new Map();
    let watchlistRefreshToken = 0;
    let focusedChartId = null;
    let focusedWatchlistSymbol = null;
    let activeMarketDataRequests = 0;
    const queuedMarketDataRequests = [];
    const barOptions = [
        ['1min', '1m'],
        ['2min', '2m'],
        ['3min', '3m'],
        ['5min', '5m'],
        ['10min', '10m'],
        ['15min', '15m'],
        ['30min', '30m'],
        ['1h', '1h'],
        ['2h', '2h'],
        ['4h', '4h'],
        ['1d', '1D'],
        ['1w', '1W'],
        ['1m', '1M']
    ];

    function clampGridNumber(value, fallback = 2) {
        const number = Number.parseInt(value, 10);
        if (!Number.isFinite(number)) return fallback;
        return Math.max(1, Math.min(12, number));
    }

    function applyGridDimensions() {
        const rows = clampGridNumber(gridRowsInput.value);
        const cols = clampGridNumber(gridColsInput.value);
        gridRowsInput.value = String(rows);
        gridColsInput.value = String(cols);
        chartGrid.style.setProperty('--grid-rows', String(rows));
        chartGrid.style.setProperty('--grid-cols', String(cols));
        for (const state of charts.values()) {
            state.chart.applyOptions({ autoSize: true });
            positionChunkLoaders(state);
        }
        saveWorkspace();
    }

    function setWatchlistVisible(isVisible, shouldSave = true) {
        watchlistVisible = Boolean(isVisible);
        workspaceEl.classList.toggle('watchlist-hidden', !watchlistVisible);
        toggleWatchlistButton.textContent = watchlistVisible ? 'Hide Watchlist' : 'Show Watchlist';
        for (const state of charts.values()) {
            state.chart.applyOptions({ autoSize: true });
            positionChunkLoaders(state);
        }
        if (shouldSave) saveWorkspace();
    }

    function serializeChartState(state) {
        return {
            symbol: state.symbol,
            conid: state.conid || null,
            bar: state.bar,
            period: state.targetPeriod,
            secType: state.secType,
            exchange: state.exchange,
            outsideRth: state.outsideRth,
            viewport: {
                timeRange: state.savedTimeRange || null,
                logicalRange: state.savedLogicalRange || null
            }
        };
    }

    function saveWorkspace() {
        if (isRestoringWorkspace) return;
        const workspace = {
            rows: clampGridNumber(gridRowsInput.value, 1),
            cols: clampGridNumber(gridColsInput.value, 1),
            charts: Array.from(charts.values()).map(serializeChartState),
            watchlist: {
                visible: watchlistVisible,
                symbols: watchlistSymbols
            }
        };
        if (saveWorkspaceTimer !== null) {
            window.clearTimeout(saveWorkspaceTimer);
        }
        saveWorkspaceTimer = window.setTimeout(async () => {
            saveWorkspaceTimer = null;
            try {
                await fetch('preferences.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ chartWorkspace: workspace })
                });
            } catch (error) {
                console.warn('Failed to save chart workspace', error);
            }
        }, 150);
    }

    async function loadWorkspace() {
        try {
            const response = await fetch('preferences.php');
            if (!response.ok) throw new Error(`Preferences fetch failed: ${response.status}`);
            const preferences = await response.json();
            const workspace = preferences.chartWorkspace;
            if (workspace && Array.isArray(workspace.charts)) return workspace;
        } catch (error) {
            console.warn('Failed to load chart workspace', error);
        }

        try {
            const raw = localStorage.getItem(workspaceStorageKey);
            if (!raw) return null;
            const legacyWorkspace = JSON.parse(raw);
            if (legacyWorkspace && Array.isArray(legacyWorkspace.charts)) return legacyWorkspace;
        } catch (error) {
            console.warn('Failed to load legacy chart workspace', error);
        }

        return null;
    }

    function formatPrice(value) {
        if (!Number.isFinite(value)) return '—';
        if (Math.abs(value) >= 1000) return value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (Math.abs(value) >= 10) return value.toFixed(2);
        return value.toFixed(4);
    }

    function formatPercent(value) {
        if (!Number.isFinite(value)) return '—';
        const sign = value > 0 ? '+' : '';
        return `${sign}${value.toFixed(2)}%`;
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setWatchlistMessage(message = '') {
        watchlistMessageEl.textContent = message;
    }

    function normalizeWatchlistSymbols(symbols) {
        if (!Array.isArray(symbols)) return [];
        const seen = new Set();
        const normalized = [];
        for (const symbol of symbols) {
            const value = normalizeSymbol(String(symbol || ''));
            if (!value || seen.has(value)) continue;
            seen.add(value);
            normalized.push(value);
            if (normalized.length >= watchlistLimit) break;
        }
        return normalized;
    }

    function runNextMarketDataRequest() {
        if (activeMarketDataRequests >= maxConcurrentMarketDataRequests) return;
        const next = queuedMarketDataRequests.shift();
        if (!next) return;

        activeMarketDataRequests++;
        fetch(next.url, next.options)
            .then(next.resolve)
            .catch(next.reject)
            .finally(() => {
                activeMarketDataRequests--;
                runNextMarketDataRequest();
            });
    }

    function queuedMarketDataFetch(url, options) {
        return new Promise((resolve, reject) => {
            queuedMarketDataRequests.push({ url, options, resolve, reject });
            runNextMarketDataRequest();
        });
    }

    function renderWatchlist() {
        watchlistCountEl.textContent = `${watchlistSymbols.length} / ${watchlistLimit}`;
        watchlistItemsEl.innerHTML = watchlistSymbols.map((symbol) => {
            const quote = watchlistQuotes.get(symbol) || { status: 'loading' };
            const price = quote.status === 'loading' ? 'Loading' : formatPrice(quote.price);
            const change = quote.status === 'error' ? 'Unavailable' : formatPercent(quote.percentChange);
            const direction = Number(quote.percentChange) > 0 ? 'up' : Number(quote.percentChange) < 0 ? 'down' : '';
            const rowClass = [
                'watchlist-row',
                direction,
                quote.status === 'error' ? 'error' : '',
                symbol === focusedWatchlistSymbol ? 'focused' : ''
            ].filter(Boolean).join(' ');
            const title = quote.error ? ` title="${escapeHtml(quote.error)}"` : '';
            return `
                <div class="${rowClass}" data-symbol="${symbol}"${title}>
                    <div class="watchlist-symbol">${escapeHtml(symbol)}</div>
                    <div class="watchlist-quote">
                        <div class="watchlist-price">${price}</div>
                        <div class="watchlist-change">${change}</div>
                    </div>
                    <button class="watchlist-remove" type="button" data-remove-symbol="${escapeHtml(symbol)}" title="Remove ${escapeHtml(symbol)}">×</button>
                </div>
            `;
        }).join('');
    }

    function setFocusedWatchlistSymbol(symbol) {
        const normalizedSymbol = normalizeSymbol(symbol || '');
        focusedWatchlistSymbol = watchlistSymbols.includes(normalizedSymbol) ? normalizedSymbol : null;
        renderWatchlist();
    }

    function buildWatchlistQuoteUrl(symbol) {
        const params = new URLSearchParams({
            symbol,
            secType: 'STK',
            bar: '1d',
            period: '1m',
            exchange: 'SMART',
            outsideRth: 'false'
        });
        return `marketdata.php?${params.toString()}`;
    }

    async function fetchWatchlistQuote(symbol) {
        const response = await queuedMarketDataFetch(buildWatchlistQuoteUrl(symbol));
        const data = await response.json();
        if (!response.ok) {
            if (response.status === 401 && window.showSessionExpired) {
                window.showSessionExpired();
            }
            throw new Error(data.error || `Quote failed: ${response.status}`);
        }

        const candles = validCandles(data.bars || []);
        if (candles.length < 2) {
            throw new Error('Not enough daily bars returned.');
        }

        const last = candles[candles.length - 1];
        const previous = candles[candles.length - 2];
        const percentChange = previous.close !== 0 ? ((last.close - previous.close) / previous.close) * 100 : 0;
        return {
            status: 'ready',
            price: last.close,
            percentChange,
            source: data.cache?.hit ? 'cache' : 'historical',
            updatedAt: Date.now()
        };
    }

    async function refreshWatchlistQuotes(symbols = watchlistSymbols) {
        const token = ++watchlistRefreshToken;
        const queue = normalizeWatchlistSymbols(symbols);
        for (const symbol of queue) {
            if (!watchlistQuotes.has(symbol)) {
                watchlistQuotes.set(symbol, { status: 'loading' });
            }
        }
        renderWatchlist();

        let index = 0;
        const worker = async () => {
            while (index < queue.length && token === watchlistRefreshToken) {
                const symbol = queue[index++];
                try {
                    watchlistQuotes.set(symbol, await fetchWatchlistQuote(symbol));
                } catch (error) {
                    console.warn(`Watchlist quote failed for ${symbol}`, error);
                    watchlistQuotes.set(symbol, {
                        status: 'error',
                        error: error.message,
                        price: null,
                        percentChange: null
                    });
                }
                if (token === watchlistRefreshToken) {
                    renderWatchlist();
                }
            }
        };

        await Promise.all(Array.from({ length: Math.min(4, queue.length) }, worker));
    }

    function addWatchlistSymbol() {
        const symbol = normalizeSymbol(watchlistSymbolInput.value);
        setWatchlistMessage('');
        if (!symbol) {
            watchlistSymbolInput.focus();
            return;
        }
        if (!/^[A-Z0-9._-]+$/.test(symbol)) {
            setWatchlistMessage('Use ticker symbols with letters, numbers, dots, underscores, or dashes.');
            return;
        }
        if (watchlistSymbols.includes(symbol)) {
            setWatchlistMessage(`${symbol} is already in the watchlist.`);
            watchlistSymbolInput.value = '';
            return;
        }
        if (watchlistSymbols.length >= watchlistLimit) {
            setWatchlistMessage(`Watchlist limit is ${watchlistLimit} tickers.`);
            return;
        }

        watchlistSymbols = [...watchlistSymbols, symbol];
        watchlistQuotes.set(symbol, { status: 'loading' });
        watchlistSymbolInput.value = '';
        renderWatchlist();
        saveWorkspace();
        refreshWatchlistQuotes([symbol]);
    }

    function removeWatchlistSymbol(symbol) {
        watchlistSymbols = watchlistSymbols.filter((item) => item !== symbol);
        watchlistQuotes.delete(symbol);
        if (focusedWatchlistSymbol === symbol) {
            focusedWatchlistSymbol = null;
        }
        setWatchlistMessage('');
        renderWatchlist();
        saveWorkspace();
    }

    function setStatus(message, isError = false) {
        statusBar.textContent = message;
        statusBar.classList.toggle('error', isError);
    }

    function normalizeSymbol(value) {
        return value.trim().toUpperCase();
    }

    function barSeconds(bar) {
        const match = String(bar).match(/^(\d+)(min|h|d|w|m)$/);
        if (!match) return 60;
        const value = Number(match[1]);
        const unit = match[2];
        if (unit === 'min') return value * 60;
        if (unit === 'h') return value * 3600;
        if (unit === 'd') return value * 86400;
        if (unit === 'w') return value * 604800;
        return value * 2592000;
    }

    function periodSeconds(period) {
        const match = String(period).match(/^(\d+)(min|h|d|w|m|y)$/);
        if (!match) return Number.MAX_SAFE_INTEGER;
        const value = Number(match[1]);
        const unit = match[2];
        if (unit === 'min') return value * 60;
        if (unit === 'h') return value * 3600;
        if (unit === 'd') return value * 86400;
        if (unit === 'w') return value * 604800;
        if (unit === 'm') return value * 2592000;
        return value * 31536000;
    }

    function chunkSecondsForPeriod(period) {
        const seconds = periodSeconds(period);
        return Number.isFinite(seconds) && seconds > 0 ? seconds : 86400;
    }

    function chunkPeriodForBar(bar) {
        return chunkPeriodByBar[bar] || '1m';
    }

    function requestPeriodForState(state) {
        const maxChunk = chunkPeriodForBar(state.bar);
        const targetSeconds = periodSeconds(state.targetPeriod);
        const maxChunkSeconds = periodSeconds(maxChunk);
        const minPeriodSeconds = barSeconds(state.bar) * 10;
        if (targetSeconds < minPeriodSeconds) return maxChunk;
        return targetSeconds < maxChunkSeconds ? state.targetPeriod : maxChunk;
    }

    function formatIbkrStartTime(unixSeconds) {
        const date = new Date(unixSeconds * 1000);
        const pad = (value) => String(value).padStart(2, '0');
        return `${date.getUTCFullYear()}${pad(date.getUTCMonth() + 1)}${pad(date.getUTCDate())}-${pad(date.getUTCHours())}:${pad(date.getUTCMinutes())}:${pad(date.getUTCSeconds())}`;
    }

    function chunkEndForTime(state, unixSeconds) {
        const chunkSeconds = chunkSecondsForPeriod(state.chunkPeriod || requestPeriodForState(state));
        return Math.floor(unixSeconds / chunkSeconds) * chunkSeconds;
    }

    function buildMarketDataUrl(state, options = {}) {
        const force = Boolean(options.force);
        const startTime = options.startTime || null;
        const params = new URLSearchParams({
            bar: state.bar,
            period: state.chunkPeriod,
            exchange: state.exchange,
            outsideRth: state.outsideRth ? 'true' : 'false'
        });

        if (state.conid) {
            params.set('conid', state.conid);
        } else if (/^\d+$/.test(state.symbol)) {
            params.set('conid', state.symbol);
        } else {
            params.set('symbol', state.symbol);
            params.set('secType', state.secType);
        }

        if (force) params.set('force', 'true');
        if (startTime) params.set('startTime', startTime);
        return `marketdata.php?${params.toString()}`;
    }

    function formatCandle(bar) {
        const timestampMs = Number(bar.time || bar.t || 0);
        return {
            time: Math.floor(timestampMs / 1000),
            open: Number(bar.open ?? bar.o),
            high: Number(bar.high ?? bar.h),
            low: Number(bar.low ?? bar.l),
            close: Number(bar.close ?? bar.c),
            volume: bar.volume ?? bar.v ?? null
        };
    }

    function validCandles(bars) {
        return bars
            .map(formatCandle)
            .filter((bar) => Number.isFinite(bar.time) &&
                Number.isFinite(bar.open) &&
                Number.isFinite(bar.high) &&
                Number.isFinite(bar.low) &&
                Number.isFinite(bar.close));
    }

    function candleSeriesData(candles) {
        return candles.map((bar) => ({
            time: bar.time,
            open: bar.open,
            high: bar.high,
            low: bar.low,
            close: bar.close
        }));
    }

    function volumeSeriesData(candles) {
        return candles
            .map((bar) => ({
                time: bar.time,
                value: Number(bar.volume),
                color: bar.close >= bar.open ? 'rgba(35, 176, 117, 0.45)' : 'rgba(238, 83, 80, 0.45)'
            }))
            .filter((bar) => Number.isFinite(bar.value) && bar.value >= 0);
    }

    function sanitizeTimeRange(range) {
        if (!range) return null;
        const from = Number(range.from);
        const to = Number(range.to);
        if (!Number.isFinite(from) || !Number.isFinite(to) || from >= to) return null;
        return { from, to };
    }

    function sanitizeLogicalRange(range) {
        if (!range) return null;
        const from = Number(range.from);
        const to = Number(range.to);
        if (!Number.isFinite(from) || !Number.isFinite(to) || from >= to) return null;
        return { from, to };
    }

    function timeRangeFromSavedChart(savedChart) {
        return sanitizeTimeRange(savedChart?.viewport?.timeRange || savedChart?.timeRange || null);
    }

    function logicalRangeFromSavedChart(savedChart) {
        return sanitizeLogicalRange(savedChart?.viewport?.logicalRange || savedChart?.logicalRange || null);
    }

    function captureViewportState(state) {
        if (!state.candles.length) return;
        if (state.chart.timeScale().getVisibleRange) {
            const timeRange = sanitizeTimeRange(state.chart.timeScale().getVisibleRange());
            if (timeRange) state.savedTimeRange = timeRange;
        }
        if (state.chart.timeScale().getVisibleLogicalRange) {
            const logicalRange = sanitizeLogicalRange(state.chart.timeScale().getVisibleLogicalRange());
            if (logicalRange) state.savedLogicalRange = logicalRange;
        }
    }

    function scheduleTimeRangeSave(state) {
        if (isRestoringWorkspace || state.isRestoringTimeRange || !state.candles.length) return;
        if (state.timeRangeSaveTimer !== null) {
            window.clearTimeout(state.timeRangeSaveTimer);
        }
        state.timeRangeSaveTimer = window.setTimeout(() => {
            state.timeRangeSaveTimer = null;
            captureViewportState(state);
            saveWorkspace();
        }, 250);
    }

    function restoreSavedViewport(state) {
        try {
            if (state.savedLogicalRange && state.chart.timeScale().setVisibleLogicalRange) {
                state.chart.timeScale().setVisibleLogicalRange(state.savedLogicalRange);
                return true;
            }
            if (state.savedTimeRange && state.chart.timeScale().setVisibleRange) {
                state.chart.timeScale().setVisibleRange(state.savedTimeRange);
                return true;
            }
        } catch (error) {
            console.warn('Failed to restore chart viewport', error);
        }
        return false;
    }

    function needsSavedTimeRangeBackfill(state) {
        return Boolean(
            state.savedTimeRange &&
            state.candles.length &&
            state.candles[0].time > state.savedTimeRange.from
        );
    }

    function ensureSavedTimeRangeLoaded(state) {
        if (!needsSavedTimeRangeBackfill(state)) return;
        if (state.isInitialLoading || state.loadingChunks.size >= 3) return;
        loadOlderChunk(state);
    }

    function finishTimeRangeRestore(state) {
        if (!state.isRestoringTimeRange) return;
        state.isRestoringTimeRange = false;
        window.setTimeout(() => {
            captureViewportState(state);
            saveWorkspace();
        }, 300);
    }

    function barOptionsHtml(selectedBar) {
        return barOptions
            .map(([value, label]) => `<option value="${value}"${value === selectedBar ? ' selected' : ''}>${label}</option>`)
            .join('');
    }

    function setFocusedChart(id) {
        if (!charts.has(id)) return;
        focusedChartId = id;
        for (const [chartId, chartState] of charts.entries()) {
            chartState.panel.classList.toggle('focused', chartId === focusedChartId);
        }
    }

    function focusSoleChart() {
        if (charts.size !== 1) return;
        const onlyChart = charts.values().next().value;
        if (onlyChart) setFocusedChart(onlyChart.id);
    }

    function createPanel(state) {
        const panel = document.createElement('section');
        panel.className = 'chart-panel initial-loading';
        panel.innerHTML = `
            <div class="chart-header">
                <div class="chart-title">
                    <input class="chart-symbol-input" autocomplete="off" spellcheck="false" title="Chart symbol">
                    <span class="chart-meta"></span>
                </div>
                <div class="chart-actions">
                    <select class="chart-bar-select" title="Bar time period">
                        ${barOptionsHtml(state.bar)}
                    </select>
                    <button class="icon-button refresh-chart" type="button" title="Refresh">↻</button>
                    <button class="icon-button close-chart" type="button" title="Close">×</button>
                </div>
            </div>
            <div class="chart-area">
                <div class="chart-canvas"></div>
                <div class="chunk-loaders"></div>
                <div class="chart-message">Loading…</div>
            </div>
            <div class="chart-footer">
                <div class="chart-footer-actions">
                    <button class="footer-button auto-fit-chart" type="button" title="Auto-fit price scale">Auto-fit</button>
                    <span class="cache-pill">Cache</span>
                </div>
            </div>
        `;

        const canvas = panel.querySelector('.chart-canvas');
        const chart = LightweightCharts.createChart(canvas, {
            autoSize: true,
            layout: {
                background: { type: 'solid', color: '#161c20' },
                textColor: '#b9c6cc',
                fontSize: 12
            },
            grid: {
                vertLines: { color: '#263038' },
                horzLines: { color: '#263038' }
            },
            rightPriceScale: {
                borderColor: '#34424a',
                autoScale: true,
                scaleMargins: { top: 0.08, bottom: 0.26 }
            },
            timeScale: {
                borderColor: '#34424a',
                timeVisible: true,
                secondsVisible: false,
                rightOffset: 8,
                barSpacing: 8,
                minBarSpacing: 1
            },
            crosshair: {
                mode: LightweightCharts.CrosshairMode.Normal
            },
            handleScale: {
                axisPressedMouseMove: {
                    time: true,
                    price: true
                },
                mouseWheel: true,
                pinch: true
            },
            handleScroll: {
                mouseWheel: true,
                pressedMouseMove: true,
                horzTouchDrag: true,
                vertTouchDrag: false
            }
        });

        const series = chart.addCandlestickSeries({
            upColor,
            downColor,
            borderUpColor: upColor,
            borderDownColor: downColor,
            wickUpColor: upColor,
            wickDownColor: downColor
        });
        const volumeSeries = chart.addHistogramSeries({
            priceFormat: { type: 'volume' },
            priceScaleId: 'volume',
            lastValueVisible: false,
            priceLineVisible: false
        });
        chart.priceScale('volume').applyOptions({
            scaleMargins: { top: 0.78, bottom: 0 },
            borderVisible: false
        });

        const chartState = {
            ...state,
            id: nextChartId++,
            panel,
            chart,
            series,
            volumeSeries,
            candles: [],
            isInitialLoading: false,
            loadingChunks: new Map(),
            requestedOlderEnds: new Set(),
            oldestRequestedTime: null,
            nextOlderChunkEnd: null,
            pendingOlderLoad: null,
            timeRangeSaveTimer: null,
            lastRequestUrl: '',
            lastLoadReason: 'initial',
            targetPeriod: state.period,
            chunkPeriod: null,
            conid: state.conid || null,
            secType: state.secType || 'STK',
            exchange: state.exchange || 'SMART',
            outsideRth: Boolean(state.outsideRth),
            savedTimeRange: sanitizeTimeRange(state.savedTimeRange),
            savedLogicalRange: sanitizeLogicalRange(state.savedLogicalRange),
            isRestoringTimeRange: Boolean(sanitizeLogicalRange(state.savedLogicalRange) || sanitizeTimeRange(state.savedTimeRange))
        };

        panel.querySelector('.close-chart').addEventListener('click', () => removeChart(chartState.id));
        panel.querySelector('.refresh-chart').addEventListener('click', () => loadInitialChunk(chartState, false));
        panel.querySelector('.auto-fit-chart').addEventListener('click', () => autoFitChart(chartState));
        panel.addEventListener('pointerdown', () => setFocusedChart(chartState.id));
        panel.querySelector('.chart-symbol-input').addEventListener('focus', (event) => event.target.select());
        panel.querySelector('.chart-symbol-input').addEventListener('click', (event) => event.target.select());
        panel.querySelector('.chart-symbol-input').addEventListener('blur', () => commitChartSymbolChange(chartState));
        panel.querySelector('.chart-symbol-input').addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                commitChartSymbolChange(chartState);
                event.target.blur();
            } else if (event.key === 'Escape') {
                event.preventDefault();
                event.target.value = chartState.symbol;
                event.target.blur();
            }
        });
        panel.querySelector('.chart-bar-select').addEventListener('change', (event) => {
            chartState.bar = event.target.value;
            chartState.savedTimeRange = null;
            chartState.savedLogicalRange = null;
            chartState.isRestoringTimeRange = false;
            saveWorkspace();
            loadInitialChunk(chartState, false);
        });
        chart.timeScale().subscribeVisibleLogicalRangeChange((range) => {
            positionChunkLoaders(chartState);
            handleVisibleRange(chartState, range);
            scheduleTimeRangeSave(chartState);
        });

        chartGrid.appendChild(panel);
        charts.set(chartState.id, chartState);
        if (!isRestoringWorkspace || charts.size === 1) {
            setFocusedChart(chartState.id);
        }
        updatePanelHeader(chartState);
        saveWorkspace();
        loadInitialChunk(chartState);
        return chartState;
    }

    function updatePanelHeader(state) {
        const symbolInputEl = state.panel.querySelector('.chart-symbol-input');
        if (symbolInputEl && symbolInputEl.value !== state.symbol) {
            symbolInputEl.value = state.symbol;
        }
        state.panel.querySelector('.chart-meta').textContent = `${state.bar} candles · ${state.chunkPeriod || requestPeriodForState(state)} chunks · initial ${state.targetPeriod}`;
        const barSelect = state.panel.querySelector('.chart-bar-select');
        if (barSelect && barSelect.value !== state.bar) {
            barSelect.value = state.bar;
        }
        updateRequestDebug(state);
    }

    function updateChartSymbol(state, symbolValue) {
        const symbol = normalizeSymbol(symbolValue);
        if (!symbol) {
            setStatus('Enter a symbol or numeric conid.', true);
            return false;
        }
        if (symbol === state.symbol) {
            updatePanelHeader(state);
            return false;
        }

        captureViewportState(state);
        state.symbol = symbol;
        state.conid = null;
        state.isRestoringTimeRange = Boolean(state.savedLogicalRange || state.savedTimeRange);
        saveWorkspace();
        updatePanelHeader(state);
        loadInitialChunk(state, false);
        return true;
    }

    function commitChartSymbolChange(state) {
        const input = state.panel.querySelector('.chart-symbol-input');
        if (!updateChartSymbol(state, input.value)) {
            input.value = state.symbol;
        }
    }

    function loadWatchlistSymbolInFocusedChart(symbol) {
        if (focusedChartId === null) {
            focusSoleChart();
        }

        const state = focusedChartId !== null ? charts.get(focusedChartId) : null;
        if (!state) {
            setStatus('Select a chart before loading a watchlist symbol.', true);
            return;
        }

        setFocusedWatchlistSymbol(symbol);
        updateChartSymbol(state, symbol);
    }

    function updateRequestDebug(state, text = '') {
        state.lastDebugText = text || (state.lastRequestUrl ? `${state.lastLoadReason}: ${state.lastRequestUrl}` : `${state.symbol} · ${state.bar} · chunk ${state.chunkPeriod}`);
    }

    function updateCachePill(state, cache) {
        const pill = state.panel.querySelector('.cache-pill');
        const hit = Boolean(cache && cache.hit);
        pill.classList.toggle('hit', hit);
        pill.classList.toggle('miss', !hit);
        pill.textContent = hit ? 'Cache hit' : 'IBKR fetch';
        if (cache && cache.cachedAtIso) {
            pill.title = `Cached at ${cache.cachedAtIso}`;
        } else {
            pill.title = '';
        }
    }

    function setPanelMessage(state, message, isError = false, showOverlay = false) {
        state.panel.classList.toggle('initial-loading', Boolean(message) && !isError && showOverlay);
        state.panel.classList.toggle('error', Boolean(message) && isError);
        state.panel.querySelector('.chart-message').textContent = message || '';
    }

    function autoFitChart(state) {
        state.chart.priceScale('right').applyOptions({
            autoScale: true,
            scaleMargins: { top: 0.08, bottom: 0.26 }
        });
        state.chart.priceScale('volume').applyOptions({
            scaleMargins: { top: 0.78, bottom: 0 },
            borderVisible: false
        });
        setStatus(`${state.symbol} price scale auto-fit`);
    }

    function describeRequest(state, force, reason) {
        const source = /^\d+$/.test(state.symbol) ? `conid ${state.symbol}` : state.symbol;
        const mode = force ? 'force refresh' : reason;
        return `${mode}: ${source} · ${state.bar} candles · ${state.chunkPeriod} chunk`;
    }

    function addChunkLoader(state, id, label, targetTime = null) {
        const loaders = state.panel.querySelector('.chunk-loaders');
        const loader = document.createElement('div');
        loader.className = 'chunk-loader';
        loader.textContent = label;
        loader.title = label;
        loaders.appendChild(loader);
        state.loadingChunks.set(id, { element: loader, targetTime });
        positionChunkLoaders(state);
    }

    function removeChunkLoader(state, id) {
        const loader = state.loadingChunks.get(id);
        if (!loader) return;
        loader.element.remove();
        state.loadingChunks.delete(id);
        positionChunkLoaders(state);
    }

    function positionChunkLoaders(state) {
        const chartArea = state.panel.querySelector('.chart-area');
        const width = chartArea.clientWidth || 0;
        let stackedIndex = 0;
        for (const loader of state.loadingChunks.values()) {
            let x = 12;
            if (loader.targetTime !== null && state.chart.timeScale().timeToCoordinate) {
                const coordinate = state.chart.timeScale().timeToCoordinate(loader.targetTime);
                if (Number.isFinite(coordinate)) {
                    x = Math.max(12, Math.min(width - 120, coordinate));
                }
            }
            loader.element.style.left = `${x}px`;
            loader.element.style.top = `${12 + stackedIndex * 30}px`;
            stackedIndex++;
        }
    }

    function mergeCandles(existingCandles, incomingCandles) {
        const byTime = new Map();
        for (const candle of existingCandles) byTime.set(candle.time, candle);
        for (const candle of incomingCandles) byTime.set(candle.time, candle);
        return Array.from(byTime.values()).sort((a, b) => a.time - b.time);
    }

    function visibleLogicalRange(state) {
        if (!state.chart.timeScale().getVisibleLogicalRange) return null;
        const range = state.chart.timeScale().getVisibleLogicalRange();
        if (!range) return null;
        const from = Number(range.from);
        const to = Number(range.to);
        if (!Number.isFinite(from) || !Number.isFinite(to) || from >= to) return null;
        return { from, to };
    }

    function prependShift(existingCandles, mergedCandles) {
        if (!existingCandles.length || !mergedCandles.length) return 0;
        const previousFirstTime = existingCandles[0].time;
        const index = mergedCandles.findIndex((bar) => bar.time === previousFirstTime);
        return index > 0 ? index : 0;
    }

    function restoreLogicalRangeAfterPrepend(state, range, shift) {
        if (!range || shift <= 0 || !state.chart.timeScale().setVisibleLogicalRange) return false;
        state.chart.timeScale().setVisibleLogicalRange({
            from: range.from + shift,
            to: range.to + shift
        });
        return true;
    }

    function unavailableChunkMessage(reason, startTime) {
        return startTime ? `${reason} unavailable for chunk ending before ${startTime}` : `${reason} unavailable`;
    }

    async function loadChunk(state, options = {}) {
        const force = Boolean(options.force);
        const reason = options.reason || 'load';
        const mode = options.mode || 'replace';
        const startTime = options.startTime || null;
        const chunkId = options.chunkId || `${reason}-${Date.now()}-${Math.random().toString(16).slice(2)}`;
        const targetTime = options.targetTime || null;
        let shouldBackfillSavedRange = false;
        let shouldFinishTimeRangeRestore = true;

        if (mode === 'replace' && state.isInitialLoading) {
            updateRequestDebug(state, `Ignored ${reason}: initial load already in progress`);
            return;
        }
        if (mode === 'replace') state.isInitialLoading = true;
        state.lastLoadReason = force ? 'force refresh' : reason;
        state.lastRequestUrl = buildMarketDataUrl(state, { force, startTime });
        const loadingText = describeRequest(state, force, reason);
        setPanelMessage(state, loadingText, false, mode === 'replace' && !state.candles.length);
        if (mode !== 'replace') {
            addChunkLoader(state, chunkId, loadingText, targetTime);
        }
        updateRequestDebug(state, `Requesting ${state.lastRequestUrl}`);
        setStatus(loadingText);
        updatePanelHeader(state);

        try {
            const startedAt = performance.now();
            const response = await queuedMarketDataFetch(state.lastRequestUrl);
            const data = await response.json();
            const elapsedMs = Math.round(performance.now() - startedAt);
            if (!response.ok) {
                if (response.status === 401 && window.showSessionExpired) {
                    window.showSessionExpired();
                }
                throw new Error(data.error || `Market data failed: ${response.status}`);
            }

            const candles = validCandles(data.bars || []);
            if (!candles.length) {
                throw new Error('No candles returned for this request.');
            }

            if (data.request && data.request.conid) {
                state.conid = String(data.request.conid);
                saveWorkspace();
            }

            const previousCandles = state.candles;
            const previousLogicalRange = mode === 'prepend' ? visibleLogicalRange(state) : null;
            state.candles = mode === 'prepend' ? mergeCandles(state.candles, candles) : candles;
            const logicalShift = mode === 'prepend' ? prependShift(previousCandles, state.candles) : 0;
            if (state.candles.length) {
                state.oldestRequestedTime = state.candles[0].time;
            }
            state.series.setData(candleSeriesData(state.candles));
            state.volumeSeries.setData(volumeSeriesData(state.candles));
            if (mode === 'replace') {
                if (!restoreSavedViewport(state)) {
                    state.chart.timeScale().fitContent();
                }
            } else {
                const restoredLogicalRange = restoreLogicalRangeAfterPrepend(state, previousLogicalRange, logicalShift);
                if (!restoredLogicalRange || state.isRestoringTimeRange) {
                    restoreSavedViewport(state);
                }
            }
            shouldBackfillSavedRange = needsSavedTimeRangeBackfill(state);
            updateCachePill(state, data.cache);
            setPanelMessage(state, '');
            updateRequestDebug(state, `${state.lastLoadReason}: ${state.lastRequestUrl} · ${elapsedMs}ms · ${candles.length} bars · ${data.cache?.hit ? 'cache hit' : 'IBKR fetch'}`);
            setStatus(`${state.symbol} ${state.bar} ${state.chunkPeriod} chunk loaded in ${elapsedMs}ms (${data.cache?.hit ? 'cache' : 'IBKR'})`);
        } catch (error) {
            if (mode !== 'replace') {
                console.warn(error);
                const message = unavailableChunkMessage(reason, startTime);
                updateRequestDebug(state, `${message}: ${state.lastRequestUrl}`);
                setStatus(`${state.symbol} ${state.bar}: ${message}`);
                if (state.candles.length) {
                    restoreSavedViewport(state);
                }
                shouldBackfillSavedRange = false;
                shouldFinishTimeRangeRestore = false;
                return;
            }
            console.error(error);
            state.isRestoringTimeRange = false;
            setPanelMessage(state, error.message, true);
            updateRequestDebug(state, `Failed ${state.lastLoadReason}: ${state.lastRequestUrl}`);
            setStatus(error.message, true);
        } finally {
            if (mode === 'replace') state.isInitialLoading = false;
            removeChunkLoader(state, chunkId);
            if (shouldBackfillSavedRange) {
                window.setTimeout(() => ensureSavedTimeRangeLoaded(state), 0);
            } else if (shouldFinishTimeRangeRestore) {
                finishTimeRangeRestore(state);
            }
        }
    }

    function loadInitialChunk(state, force = false) {
        state.chunkPeriod = requestPeriodForState(state);
        state.candles = [];
        state.oldestRequestedTime = null;
        state.nextOlderChunkEnd = null;
        state.requestedOlderEnds.clear();
        for (const id of Array.from(state.loadingChunks.keys())) {
            removeChunkLoader(state, id);
        }
        updatePanelHeader(state);
        return loadChunk(state, { force, reason: force ? 'force refresh' : 'initial chunk', mode: 'replace' });
    }

    function loadOlderChunk(state) {
        if (!state.candles.length || state.isInitialLoading || state.loadingChunks.size >= 3) return;
        const earliestLoaded = state.candles[0].time;
        if (state.nextOlderChunkEnd === null) {
            state.nextOlderChunkEnd = chunkEndForTime(state, earliestLoaded - barSeconds(state.bar));
        }

        let chunkEnd = state.nextOlderChunkEnd;
        while (state.requestedOlderEnds.has(chunkEnd)) {
            chunkEnd -= chunkSecondsForPeriod(state.chunkPeriod);
        }

        state.requestedOlderEnds.add(chunkEnd);
        state.nextOlderChunkEnd = chunkEnd - chunkSecondsForPeriod(state.chunkPeriod);
        state.oldestRequestedTime = Math.min(state.oldestRequestedTime || earliestLoaded, chunkEnd);
        const startTime = formatIbkrStartTime(chunkEnd);
        const chunkId = `older-${chunkEnd}`;
        updateRequestDebug(state, `Loading older ${state.chunkPeriod} chunk ending before ${startTime}`);
        setStatus(`Loading older ${state.symbol} ${state.bar} bars…`);
        return loadChunk(state, { reason: 'older chunk', mode: 'prepend', startTime, chunkId, targetTime: chunkEnd });
    }

    function handleVisibleRange(state, range) {
        if (!range || state.isInitialLoading || state.candles.length < 40) return;
        if (range.from > 25) return;

        if (state.pendingOlderLoad !== null) return;

        state.pendingOlderLoad = window.setTimeout(() => {
            state.pendingOlderLoad = null;
            if (state.isInitialLoading) return;
            loadOlderChunk(state);
        }, 450);
    }

    function addChartFromControls() {
        const symbol = normalizeSymbol(symbolInput.value);
        if (!symbol) {
            setStatus('Enter a symbol or numeric conid.', true);
            symbolInput.focus();
            return;
        }

        createPanel({
            symbol,
            bar: defaultNewChartBar,
            period: defaultNewChartPeriod,
            secType: 'STK',
            exchange: 'SMART',
            outsideRth: false
        });
    }

    function removeChart(id) {
        const state = charts.get(id);
        if (!state) return;
        if (state.timeRangeSaveTimer !== null) {
            window.clearTimeout(state.timeRangeSaveTimer);
        }
        state.chart.remove();
        state.panel.remove();
        charts.delete(id);
        if (focusedChartId === id) {
            focusedChartId = null;
            const nextChart = charts.values().next().value;
            if (nextChart) setFocusedChart(nextChart.id);
        }
        focusSoleChart();
        saveWorkspace();
        setStatus(charts.size ? 'Chart closed' : 'Ready');
    }

    addChartButton.addEventListener('click', addChartFromControls);
    toggleWatchlistButton.addEventListener('click', () => setWatchlistVisible(!watchlistVisible));
    addWatchlistButton.addEventListener('click', addWatchlistSymbol);
    watchlistSymbolInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') addWatchlistSymbol();
    });
    watchlistItemsEl.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-symbol]');
        if (removeButton) {
            removeWatchlistSymbol(removeButton.getAttribute('data-remove-symbol'));
            return;
        }

        const row = event.target.closest('[data-symbol]');
        if (!row) return;
        loadWatchlistSymbolInFocusedChart(row.getAttribute('data-symbol'));
    });
    gridRowsInput.addEventListener('change', applyGridDimensions);
    gridColsInput.addEventListener('change', applyGridDimensions);
    gridRowsInput.addEventListener('input', applyGridDimensions);
    gridColsInput.addEventListener('input', applyGridDimensions);
    symbolInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') addChartFromControls();
    });

    async function initializeCharts() {
        const savedWorkspace = await loadWorkspace();
        const shouldRestoreWorkspace = Boolean(savedWorkspace);
        if (shouldRestoreWorkspace) {
            isRestoringWorkspace = true;
        }

        gridRowsInput.value = savedWorkspace?.rows || '1';
        gridColsInput.value = savedWorkspace?.cols || '1';
        watchlistSymbols = normalizeWatchlistSymbols(savedWorkspace?.watchlist?.symbols || []);
        setWatchlistVisible(savedWorkspace?.watchlist?.visible !== false, false);
        renderWatchlist();
        applyGridDimensions();

        if (!window.LightweightCharts) {
            setStatus('Chart library failed to load.', true);
        } else if (savedWorkspace && savedWorkspace.charts.length > 0) {
            savedWorkspace.charts.forEach((savedChart) => {
                const savedSymbol = normalizeSymbol(savedChart.symbol || '');
                if (!savedSymbol) return;
                createPanel({
                    symbol: savedSymbol,
                    conid: savedChart.conid || null,
                    bar: savedChart.bar || '5min',
                    period: savedChart.period || '1d',
                    secType: savedChart.secType || 'STK',
                    exchange: savedChart.exchange || 'SMART',
                    outsideRth: Boolean(savedChart.outsideRth),
                    savedTimeRange: timeRangeFromSavedChart(savedChart),
                    savedLogicalRange: logicalRangeFromSavedChart(savedChart)
                });
            });
        } else {
            symbolInput.focus();
        }

        if (shouldRestoreWorkspace) {
            isRestoringWorkspace = false;
            focusSoleChart();
            saveWorkspace();
        }

        if (watchlistSymbols.length > 0) {
            refreshWatchlistQuotes();
        }
    }

</script>
<script src="auth_status.js?v=<?= htmlspecialchars($assetVersion, ENT_QUOTES) ?>"></script>
<script>
    initializeCharts();
    startAuthStatusPolling();
</script>
</body>
</html>
