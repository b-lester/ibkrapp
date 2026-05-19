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

        .watchlist-sort-buttons {
            display: flex;
            gap: 4px;
        }

        .watchlist-sort-btn {
            min-height: 22px;
            border: 1px solid var(--panel-border);
            border-radius: 4px;
            color: var(--muted);
            background: transparent;
            padding: 2px 6px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
        }

        .watchlist-sort-btn:hover {
            color: var(--text);
            border-color: #45545e;
        }

        .watchlist-sort-btn.active {
            color: #8fd2c8;
            border-color: var(--accent);
            background: rgba(47, 143, 131, 0.12);
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

        .chart-contract-name {
            color: var(--text);
            font-size: 12px;
            font-weight: 650;
            max-width: min(360px, 34vw);
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
            justify-content: space-between;
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

        .ohlc-readout {
            min-width: 0;
            color: #c9d4d9;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ohlc-readout .up {
            color: #7fd0a3;
        }

        .ohlc-readout .down {
            color: #f18b8f;
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

        .realtime-pill {
            border: 1px solid var(--panel-border);
            border-radius: 999px;
            padding: 2px 8px;
            white-space: nowrap;
            color: var(--muted);
        }

        .realtime-pill.online {
            color: #a7ddc7;
            border-color: #2f6f5c;
        }

        .realtime-pill.error {
            color: #ffb4ba;
            border-color: #8d3f48;
        }

        .backfill-pill {
            border: 1px solid var(--panel-border);
            border-radius: 999px;
            padding: 2px 8px;
            white-space: nowrap;
            color: var(--muted);
        }

        .backfill-pill.loading {
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

        .footer-button.active {
            border-color: #4e8cff;
            color: #d9e7ff;
            background: rgba(78, 140, 255, 0.14);
        }

        .drag-handle {
            cursor: grab;
            color: var(--muted);
            padding: 2px 5px 2px 2px;
            font-size: 13px;
            line-height: 1;
            user-select: none;
            flex-shrink: 0;
            opacity: 0.6;
        }

        .drag-handle:hover {
            opacity: 1;
            color: var(--text);
        }

        .drag-handle:active {
            cursor: grabbing;
        }

        .chart-panel.dragging {
            opacity: 0.35;
        }

        .chart-panel.drop-before {
            box-shadow: -3px 0 0 0 var(--accent), 0 0 0 1px var(--accent);
        }

        .chart-panel.drop-after {
            box-shadow: 3px 0 0 0 var(--accent), 0 0 0 1px var(--accent);
        }

        #max-panel-host {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 200;
            background: var(--bg);
        }

        #max-panel-host .chart-panel {
            height: 100%;
            border-radius: 0;
            border: none;
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
                <div class="watchlist-sort-buttons">
                    <button id="sort-alpha-button" class="watchlist-sort-btn" type="button">A–Z</button>
                    <button id="sort-change-button" class="watchlist-sort-btn" type="button">%</button>
                </div>
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
<div id="max-panel-host"></div>

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
    const sortAlphaButton = document.getElementById('sort-alpha-button');
    const sortChangeButton = document.getElementById('sort-change-button');

    const upColor = '#1fa774';
    const downColor = '#dc4c5a';
    const watchlistLimit = 100;
    const defaultNewChartBar = '1d';
    const defaultNewChartPeriod = '1y';
    const maxConcurrentMarketDataRequests = 2;
    const realtimeWebsocketUrl = 'wss://localhost:5050/v1/api/ws';
    const realtimeFields = ['31', '70', '71', '82', '83', '84', '86', '6509', '7295', '7296'];
    const realtimeRenewMs = 9 * 60 * 1000;
    const realtimeInitialSubscribeDelayMs = 3000;
    const realtimeSessionCookieName = 'api';
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

    const maxPanelHost = document.getElementById('max-panel-host');
    let maximizedChartId = null;
    let draggingChartId = null;

    let nextChartId = 1;
    const charts = new Map();
    const workspaceStorageKey = 'ibkrChartWorkspace';
    let isRestoringWorkspace = false;
    let saveWorkspaceTimer = null;
    let watchlistVisible = true;
    let watchlistSymbols = [];
    let watchlistSort = 'default';
    const watchlistQuotes = new Map();
    let watchlistRefreshToken = 0;
    let watchlistClickTimer = null;
    const contractInfoPromises = new Map();
    let focusedChartId = null;
    let focusedWatchlistSymbol = null;
    let activeMarketDataRequests = 0;
    const queuedMarketDataRequests = [];
    let realtimeSocket = null;
    let realtimeReconnectTimer = null;
    let realtimeRenewTimer = null;
    let realtimeInitialSyncTimer = null;
    let realtimeStatus = 'offline';
    let realtimeDetail = '';
    let realtimeSessionPromise = null;
    const realtimeSubscribedConids = new Set();
    const pendingRealtimeResubscribeConids = new Set();
    const realtimeConidStats = new Map();
    const realtimeSeededCharts = new Set();
    const realtimeStats = {
        messages: 0,
        marketMessages: 0,
        ticks: 0,
        lastAt: null,
        lastTickAt: null,
        lastTopic: '',
        lastKeys: '',
        lastError: ''
    };
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
            logScale: Boolean(state.logScale),
            contractInfo: state.contractInfo || null,
            viewport: {
                timeRange: state.savedTimeRange || null
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
                symbols: watchlistSymbols,
                sort: watchlistSort
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

    function firstNonEmptyString(...values) {
        for (const value of values) {
            if (value === null || value === undefined) continue;
            const text = String(value).trim();
            if (text) return text;
        }
        return '';
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

    function parseMarketNumber(value) {
        if (value === null || value === undefined) return null;
        if (typeof value === 'number') return Number.isFinite(value) ? value : null;
        const normalized = String(value)
            .replace(/^[A-Z]\s*/i, '')
            .replace(/[%,$,\s]/g, '');
        const number = Number(normalized);
        return Number.isFinite(number) ? number : null;
    }

    function marketNumberPrefix(value) {
        if (value === null || value === undefined || typeof value === 'number') return '';
        const match = String(value).trim().match(/^([A-Z]+)/i);
        return match ? match[1].toUpperCase() : '';
    }

    function setRealtimeStatus(status, detail = '') {
        realtimeStatus = status;
        realtimeDetail = detail;
        updateRealtimePills();
        if (detail) {
            setStatus(`Realtime ${status}: ${detail}`, status === 'error');
        }
    }

    function updateRealtimePills() {
        const desiredCount = desiredRealtimeConids().size;
        const subscribedCount = realtimeSubscribedConids.size;
        const globalLabel = realtimeStatus === 'online'
            ? `RT ${subscribedCount}/${desiredCount} · ${realtimeStats.ticks} ticks total`
            : realtimeStatus === 'connecting'
                ? 'RT connecting'
                : realtimeStatus === 'error'
                    ? 'RT error'
                    : 'RT offline';
        const titleParts = [
            `${subscribedCount} realtime subscriptions active out of ${desiredCount}.`,
            `${realtimeStats.messages} websocket messages, ${realtimeStats.marketMessages} market-data messages, ${realtimeStats.ticks} parsed ticks.`
        ];
        if (realtimeStats.lastTopic) titleParts.push(`Last topic: ${realtimeStats.lastTopic}.`);
        if (realtimeStats.lastKeys) titleParts.push(`Last keys: ${realtimeStats.lastKeys}.`);
        if (realtimeStats.lastError) titleParts.push(`Last parser note: ${realtimeStats.lastError}.`);
        for (const pill of document.querySelectorAll('.realtime-pill')) {
            const state = chartStateForElement(pill);
            const conid = state?.conid ? String(state.conid) : '';
            const conidStats = conid ? realtimeConidStats.get(conid) : null;
            const isSubscribed = conid ? realtimeSubscribedConids.has(conid) : subscribedCount > 0;
            pill.textContent = conid && realtimeStatus === 'online'
                ? `RT ${conidStats?.ticks || 0} ticks`
                : globalLabel;
            pill.title = realtimeDetail || realtimePillTitle(conid, conidStats, titleParts.join(' '));
            pill.classList.toggle('online', realtimeStatus === 'online' && isSubscribed);
            pill.classList.toggle('error', realtimeStatus === 'error');
        }
    }

    function chartStateForElement(element) {
        const panel = element.closest('.chart-panel');
        if (!panel) return null;
        for (const state of charts.values()) {
            if (state.panel === panel) return state;
        }
        return null;
    }

    function realtimePillTitle(conid, stats, fallbackTitle) {
        if (!conid) return fallbackTitle;
        const parts = [
            `conid ${conid}`,
            `${stats?.messages || 0} realtime messages`,
            `${stats?.ticks || 0} parsed ticks`
        ];
        if (stats?.price !== null && stats?.price !== undefined) parts.push(`last rt price ${formatPrice(stats.price)}`);
        if (stats?.fields) parts.push(`fields ${stats.fields}`);
        return parts.join(' · ');
    }

    async function ensureRealtimeSessionCookie() {
        if (realtimeSessionPromise !== null) return realtimeSessionPromise;

        realtimeSessionPromise = fetch('realtime_session.php')
            .then(async (response) => {
                const data = await response.json();
                if (!response.ok) throw new Error(data.error || `Realtime session failed: ${response.status}`);
                if (!data.apiCookie) throw new Error('Realtime session cookie missing.');
                document.cookie = `${realtimeSessionCookieName}=${encodeURIComponent(data.apiCookie)}; path=/; SameSite=Lax`;
                return data.apiCookie;
            })
            .catch((error) => {
                realtimeSessionPromise = null;
                throw error;
            });

        return realtimeSessionPromise;
    }

    function desiredRealtimeConids() {
        const conids = new Set();
        for (const state of charts.values()) {
            if (state.conid) conids.add(String(state.conid));
        }
        for (const quote of watchlistQuotes.values()) {
            if (quote.conid) conids.add(String(quote.conid));
        }
        return conids;
    }

    function realtimeTopic(conid) {
        return `smd+${conid}+${JSON.stringify({ fields: realtimeFields, snapshot: true, tempo: 1000 })}`;
    }

    function sendRealtimeSubscription(conid) {
        if (!realtimeSocket || realtimeSocket.readyState !== WebSocket.OPEN) return;
        realtimeSocket.send(realtimeTopic(conid));
        realtimeSubscribedConids.add(String(conid));
        updateRealtimePills();
    }

    function forceRealtimeResubscribe(conid) {
        if (!conid) return;
        const key = String(conid);
        if (!realtimeSocket || realtimeSocket.readyState !== WebSocket.OPEN) {
            pendingRealtimeResubscribeConids.add(key);
            return;
        }
        if (realtimeSubscribedConids.has(key)) {
            realtimeSocket.send(`umd+${key}+{}`);
            realtimeSubscribedConids.delete(key);
            updateRealtimePills();
        }
        window.setTimeout(() => sendRealtimeSubscription(key), 350);
    }

    function flushPendingRealtimeResubscribes() {
        if (!pendingRealtimeResubscribeConids.size || !realtimeSocket || realtimeSocket.readyState !== WebSocket.OPEN) return;
        const conids = Array.from(pendingRealtimeResubscribeConids);
        pendingRealtimeResubscribeConids.clear();
        for (const conid of conids) {
            forceRealtimeResubscribe(conid);
        }
    }

    function syncRealtimeSubscriptions() {
        if (!realtimeSocket || realtimeSocket.readyState !== WebSocket.OPEN) return;
        const desired = desiredRealtimeConids();

        for (const conid of Array.from(realtimeSubscribedConids)) {
            if (!desired.has(conid)) {
                realtimeSocket.send(`umd+${conid}+{}`);
                realtimeSubscribedConids.delete(conid);
                updateRealtimePills();
            }
        }

        for (const conid of desired) {
            if (!realtimeSubscribedConids.has(conid)) {
                sendRealtimeSubscription(conid);
            }
        }
    }

    function scheduleRealtimeSubscriptionSync(delayMs = 0) {
        if (realtimeInitialSyncTimer !== null) {
            window.clearTimeout(realtimeInitialSyncTimer);
        }
        realtimeInitialSyncTimer = window.setTimeout(() => {
            realtimeInitialSyncTimer = null;
            syncRealtimeSubscriptions();
            flushPendingRealtimeResubscribes();
            if (realtimeStatus === 'online') setRealtimeStatus('online');
        }, delayMs);
    }

    function scheduleRealtimeReconnect() {
        if (realtimeReconnectTimer !== null) return;
        realtimeReconnectTimer = window.setTimeout(() => {
            realtimeReconnectTimer = null;
            connectRealtime();
        }, 5000);
    }

    function scheduleRealtimeRenewal() {
        if (realtimeRenewTimer !== null) {
            window.clearTimeout(realtimeRenewTimer);
        }
        realtimeRenewTimer = window.setTimeout(() => {
            realtimeSubscribedConids.clear();
            syncRealtimeSubscriptions();
            scheduleRealtimeRenewal();
        }, realtimeRenewMs);
    }

    async function connectRealtime() {
        if (realtimeSocket && (realtimeSocket.readyState === WebSocket.OPEN || realtimeSocket.readyState === WebSocket.CONNECTING)) {
            if (realtimeSocket.readyState === WebSocket.OPEN && realtimeInitialSyncTimer === null) {
                syncRealtimeSubscriptions();
            }
            return;
        }

        realtimeSocket = null;
        setRealtimeStatus('connecting', 'getting gateway session');
        try {
            await ensureRealtimeSessionCookie();
        } catch (error) {
            setRealtimeStatus('error', error.message);
            scheduleRealtimeReconnect();
            return;
        }

        try {
            realtimeSocket = new WebSocket(realtimeWebsocketUrl);
        } catch (error) {
            setRealtimeStatus('error', error.message);
            scheduleRealtimeReconnect();
            return;
        }

        setRealtimeStatus('connecting');
        const connectTimeout = window.setTimeout(() => {
            if (realtimeSocket && realtimeSocket.readyState === WebSocket.CONNECTING) {
                setRealtimeStatus('error', 'websocket did not open. Open https://localhost:5050 once in this browser and accept the gateway certificate.');
                realtimeSocket.close();
            }
        }, 7000);
        realtimeSocket.addEventListener('open', () => {
            window.clearTimeout(connectTimeout);
            realtimeSubscribedConids.clear();
            setRealtimeStatus('online', 'connected; waiting for gateway stream');
            scheduleRealtimeSubscriptionSync(realtimeInitialSubscribeDelayMs);
            scheduleRealtimeRenewal();
        });
        realtimeSocket.addEventListener('message', (event) => {
            if (typeof event.data === 'string') {
                handleRealtimeMessage(event.data);
                return;
            }
            if (event.data && typeof event.data.text === 'function') {
                event.data.text()
                    .then(handleRealtimeMessage)
                    .catch((error) => {
                        realtimeStats.lastError = `Could not read websocket message: ${error.message}`;
                        updateRealtimePills();
                    });
            }
        });
        realtimeSocket.addEventListener('error', () => {
            setRealtimeStatus('error', 'websocket connection failed');
        });
        realtimeSocket.addEventListener('close', () => {
            window.clearTimeout(connectTimeout);
            realtimeSubscribedConids.clear();
            if (realtimeRenewTimer !== null) {
                window.clearTimeout(realtimeRenewTimer);
                realtimeRenewTimer = null;
            }
            if (realtimeInitialSyncTimer !== null) {
                window.clearTimeout(realtimeInitialSyncTimer);
                realtimeInitialSyncTimer = null;
            }
            if (realtimeStatus !== 'error') {
                setRealtimeStatus('offline');
            } else {
                updateRealtimePills();
            }
            if (desiredRealtimeConids().size > 0) scheduleRealtimeReconnect();
        });
    }

    function ensureRealtime() {
        if (desiredRealtimeConids().size === 0) return;
        connectRealtime();
        if (realtimeInitialSyncTimer === null) {
            syncRealtimeSubscriptions();
            flushPendingRealtimeResubscribes();
        }
        updateRealtimePills();
    }

    function realtimeMessagePayload(message) {
        if (!message || typeof message !== 'object') return {};
        const args = message.args && typeof message.args === 'object' && !Array.isArray(message.args)
            ? message.args
            : {};
        return { ...message, ...args };
    }

    function handleRealtimeMessage(rawMessage) {
        let message;
        try {
            message = JSON.parse(rawMessage);
        } catch (error) {
            realtimeStats.lastError = 'Received a non-JSON websocket message.';
            updateRealtimePills();
            return;
        }
        const payload = realtimeMessagePayload(message);
        const topic = String(payload.topic || message.topic || '');
        realtimeStats.messages++;
        realtimeStats.lastAt = Date.now();
        realtimeStats.lastTopic = topic || '(none)';
        realtimeStats.lastKeys = Object.keys(payload).slice(0, 10).join(', ');

        if (!topic.startsWith('smd+')) {
            if (topic === 'sts') scheduleRealtimeSubscriptionSync(0);
            updateRealtimePills();
            return;
        }

        realtimeStats.marketMessages++;
        const conid = String(payload.conid || topic.split('+')[1] || '');
        const price = realtimePayloadPrice(payload);
        const lastPrice = parseMarketNumber(payload['31']);
        const percentChange = parseMarketNumber(payload['83']);
        const absoluteChange = parseMarketNumber(payload['82']);
        const updatedMs = Number(payload._updated || Date.now());
        if (!conid) return;

        updateRealtimeConidStats(conid, payload, price, updatedMs, price !== null);
        updateRealtimeWatchlist(conid, price, percentChange, absoluteChange, updatedMs);
        if (price !== null) {
            realtimeStats.ticks++;
            realtimeStats.lastTickAt = Date.now();
            realtimeStats.lastError = lastPrice === null ? `Using bid/ask-derived price ${formatPrice(price)} for ${conid}.` : '';
            for (const state of charts.values()) {
                if (String(state.conid || '') === conid) {
                    applyRealtimeTickToChart(state, price, payload, updatedMs);
                }
            }
        } else {
            realtimeStats.lastError = `Market-data message for ${conid} did not include a usable price.`;
        }
        updateRealtimePills();
    }

    function realtimePayloadPrice(payload) {
        const last = parseMarketNumber(payload['31']);
        const lastPrefix = marketNumberPrefix(payload['31']);
        const bid = parseMarketNumber(payload['84']);
        const ask = parseMarketNumber(payload['86']);
        const currentClose = parseMarketNumber(payload['7296']);
        const bookPrice = bid !== null && ask !== null && bid > 0 && ask > 0
            ? (bid + ask) / 2
            : bid !== null && bid > 0
                ? bid
                : ask !== null && ask > 0
                    ? ask
                    : null;
        if (lastPrefix === 'C' && bookPrice !== null) return bookPrice;
        if (last !== null) return last;
        if (currentClose !== null) return currentClose;
        if (bookPrice !== null) return bookPrice;
        return null;
    }

    function realtimeDailyCandlePatch(payload, fallbackPrice, existingCandle = null) {
        const open = parseMarketNumber(payload['7295']);
        const high = parseMarketNumber(payload['70']);
        const low = parseMarketNumber(payload['71']);
        const lastPrefix = marketNumberPrefix(payload['31']);
        const last = parseMarketNumber(payload['31']);
        const close = last !== null && lastPrefix !== 'C'
            ? last
            : fallbackPrice;
        return {
            open: open ?? existingCandle?.open ?? fallbackPrice,
            high: high ?? existingCandle?.high ?? fallbackPrice,
            low: low ?? existingCandle?.low ?? fallbackPrice,
            close: close ?? existingCandle?.close ?? fallbackPrice
        };
    }

    function updateRealtimeConidStats(conid, payload, price, updatedMs, hasUsablePrice) {
        const key = String(conid);
        const current = realtimeConidStats.get(key) || { messages: 0, ticks: 0 };
        realtimeConidStats.set(key, {
            ...current,
            messages: current.messages + 1,
            ticks: current.ticks + (hasUsablePrice ? 1 : 0),
            lastAt: Date.now(),
            updatedMs,
            price,
            fields: Object.keys(payload).slice(0, 16).join(', ')
        });
    }

    function watchlistRowTitle(symbol, quote) {
        const parts = [];
        if (quote.error) parts.push(quote.error);
        if (quote.conid) parts.push(`conid ${quote.conid}`);
        if (quote.source) parts.push(`source ${quote.source}`);
        if (quote.updatedAt) parts.push(`quote ${new Date(quote.updatedAt).toLocaleTimeString()}`);
        const realtime = quote.conid ? realtimeConidStats.get(String(quote.conid)) : null;
        if (realtime) {
            parts.push(`rt messages ${realtime.messages}`);
            parts.push(`rt ticks ${realtime.ticks || 0}`);
            if (realtime.price !== null && realtime.price !== undefined) parts.push(`rt price ${formatPrice(realtime.price)}`);
            if (realtime.fields) parts.push(`fields ${realtime.fields}`);
        } else if (quote.conid) {
            parts.push('rt messages 0');
        }
        return parts.join(' · ');
    }

    function updateRealtimeWatchlist(conid, price, percentChange, absoluteChange, updatedMs) {
        let changed = false;
        for (const [symbol, quote] of watchlistQuotes.entries()) {
            if (String(quote.conid || '') !== conid) continue;
            let nextPercentChange = percentChange !== null ? percentChange : quote.percentChange;
            if (nextPercentChange === null && price !== null && Number.isFinite(quote.previousClose) && quote.previousClose !== 0) {
                nextPercentChange = ((price - quote.previousClose) / quote.previousClose) * 100;
            } else if (nextPercentChange === null && absoluteChange !== null && Number.isFinite(quote.previousClose) && quote.previousClose !== 0) {
                nextPercentChange = (absoluteChange / quote.previousClose) * 100;
            }
            watchlistQuotes.set(symbol, {
                ...quote,
                status: 'ready',
                price: price !== null ? price : quote.price,
                percentChange: nextPercentChange,
                source: 'realtime',
                updatedAt: updatedMs
            });
            changed = true;
        }
        if (changed) renderWatchlist();
    }

    function realtimeCandleTime(state, updatedMs) {
        const updatedSeconds = Math.floor(updatedMs / 1000);
        const seconds = barSeconds(state.bar);
        if (seconds < 86400) return Math.floor(updatedSeconds / seconds) * seconds;

        const updatedDate = new Date(updatedSeconds * 1000);
        const last = state.candles[state.candles.length - 1];
        if (last) {
            const lastDate = new Date(last.time * 1000);
            if (
                lastDate.getUTCFullYear() === updatedDate.getUTCFullYear() &&
                lastDate.getUTCMonth() === updatedDate.getUTCMonth() &&
                lastDate.getUTCDate() === updatedDate.getUTCDate()
            ) {
                return last.time;
            }
        }
        if (seconds === 86400 && last) {
            const lastDate = new Date(last.time * 1000);
            const alignedDate = new Date(Date.UTC(
                updatedDate.getUTCFullYear(),
                updatedDate.getUTCMonth(),
                updatedDate.getUTCDate(),
                lastDate.getUTCHours(),
                lastDate.getUTCMinutes(),
                lastDate.getUTCSeconds()
            ));
            return Math.floor(alignedDate.getTime() / 1000);
        }
        return updatedSeconds;
    }

    function applyRealtimeTickToChart(state, price, payload, updatedMs) {
        if (!state.candles.length) return;
        if (state.bar === '1d') {
            seedRealtimeChartFromLiveHistory(state, updatedMs);
        }
        const candleTime = realtimeCandleTime(state, updatedMs);
        const candles = [...state.candles];
        const last = candles[candles.length - 1];

        if (candleTime > last.time) {
            const dailyPatch = barSeconds(state.bar) >= 86400
                ? realtimeDailyCandlePatch(payload, price)
                : null;
            candles.push({
                time: candleTime,
                open: dailyPatch ? dailyPatch.open : price,
                high: dailyPatch ? dailyPatch.high : price,
                low: dailyPatch ? dailyPatch.low : price,
                close: dailyPatch ? dailyPatch.close : price,
                volume: null
            });
        } else {
            const index = candleTime === last.time ? candles.length - 1 : candles.findIndex((bar) => bar.time === candleTime);
            if (index < 0) return;
            const candle = candles[index];
            const dailyPatch = barSeconds(state.bar) >= 86400
                ? realtimeDailyCandlePatch(payload, price, candle)
                : null;
            if (dailyPatch) {
                candles[index] = {
                    ...candle,
                    open: dailyPatch.open,
                    high: Math.max(candle.high, dailyPatch.high, price),
                    low: Math.min(candle.low, dailyPatch.low, price),
                    close: dailyPatch.close
                };
            } else {
                candles[index] = {
                    ...candle,
                    high: Math.max(candle.high, price),
                    low: Math.min(candle.low, price),
                    close: price
                };
            }
        }

        state.candles = candles;
        state.series.setData(candleSeriesData(state.candles));
        state.volumeSeries.setData(volumeSeriesData(state.candles));
    }

    async function seedRealtimeChartFromLiveHistory(state, updatedMs) {
        if (barSeconds(state.bar) > 86400 || !state.conid || !state.candles.length) return;
        const updatedDate = new Date(updatedMs);
        const seedKey = `${state.id}:${state.conid}:${state.bar}:${updatedDate.toISOString().slice(0, 10)}`;
        if (realtimeSeededCharts.has(seedKey)) return;
        realtimeSeededCharts.add(seedKey);

        const previousStatus = state.lastDebugText;
        try {
            const url = buildMarketDataUrl(state, {
                force: true,
                period: state.bar === '1d' ? '1m' : state.chunkPeriod
            });
            updateRequestDebug(state, `Realtime seed: ${url}`);
            const response = await queuedMarketDataFetch(url);
            const data = await response.json();
            if (!response.ok) throw new Error(data.error || `Realtime seed failed: ${response.status}`);
            const candles = validCandles(data.bars || []);
            if (!candles.length) return;
            state.candles = mergeCandles(state.candles, candles);
            state.series.setData(candleSeriesData(state.candles));
            state.volumeSeries.setData(volumeSeriesData(state.candles));
            updateCachePill(state, data.cache);
        } catch (error) {
            console.warn('Realtime seed failed', error);
        } finally {
            if (previousStatus) updateRequestDebug(state, previousStatus);
        }
    }

    function sortedWatchlistSymbols() {
        if (watchlistSort === 'default') return watchlistSymbols;
        const copy = [...watchlistSymbols];
        if (watchlistSort === 'alpha-asc') {
            copy.sort((a, b) => a.localeCompare(b));
        } else if (watchlistSort === 'alpha-desc') {
            copy.sort((a, b) => b.localeCompare(a));
        } else {
            const descending = watchlistSort === 'change-desc';
            copy.sort((a, b) => {
                const qa = watchlistQuotes.get(a);
                const qb = watchlistQuotes.get(b);
                const ca = qa?.status === 'ready' ? Number(qa.percentChange) : null;
                const cb = qb?.status === 'ready' ? Number(qb.percentChange) : null;
                if (ca === null && cb === null) return 0;
                if (ca === null) return 1;
                if (cb === null) return -1;
                return descending ? cb - ca : ca - cb;
            });
        }
        return copy;
    }

    function setWatchlistSort(key) {
        if (key === 'alpha') {
            watchlistSort = watchlistSort === 'alpha-asc' ? 'alpha-desc'
                : watchlistSort === 'alpha-desc' ? 'default'
                : 'alpha-asc';
        } else if (key === 'change') {
            watchlistSort = watchlistSort === 'change-desc' ? 'change-asc'
                : watchlistSort === 'change-asc' ? 'default'
                : 'change-desc';
        }
        renderWatchlist();
        saveWorkspace();
    }

    function renderWatchlist() {
        watchlistCountEl.textContent = `${watchlistSymbols.length} / ${watchlistLimit}`;

        const alphaActive = watchlistSort.startsWith('alpha');
        const changeActive = watchlistSort.startsWith('change');
        sortAlphaButton.classList.toggle('active', alphaActive);
        sortChangeButton.classList.toggle('active', changeActive);
        sortAlphaButton.textContent = watchlistSort === 'alpha-desc' ? 'Z–A' : 'A–Z';
        sortAlphaButton.title = alphaActive
            ? `Sorting ${watchlistSort === 'alpha-asc' ? 'A→Z' : 'Z→A'} — click to ${watchlistSort === 'alpha-asc' ? 'reverse' : 'clear'}`
            : 'Sort alphabetically A→Z';
        sortChangeButton.textContent = watchlistSort === 'change-asc' ? '% ↑' : '% ↓';
        sortChangeButton.title = changeActive
            ? `Sorting ${watchlistSort === 'change-desc' ? 'gainers first' : 'losers first'} — click to ${watchlistSort === 'change-desc' ? 'reverse' : 'clear'}`
            : 'Sort by % change (gainers first)';

        watchlistItemsEl.innerHTML = sortedWatchlistSymbols().map((symbol) => {
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
            const titleText = watchlistRowTitle(symbol, quote);
            const title = titleText ? ` title="${escapeHtml(titleText)}"` : '';
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
            conid: data.request?.conid ? String(data.request.conid) : null,
            price: last.close,
            previousClose: previous.close,
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
                    const quote = await fetchWatchlistQuote(symbol);
                    watchlistQuotes.set(symbol, quote);
                    ensureRealtime();
                    if (quote.conid) {
                        window.setTimeout(() => forceRealtimeResubscribe(quote.conid), 0);
                    }
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
        syncRealtimeSubscriptions();
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
        const minPeriodSeconds = barSeconds(state.bar) * 80;
        if (targetSeconds < minPeriodSeconds) return maxChunk;
        return targetSeconds < maxChunkSeconds ? state.targetPeriod : maxChunk;
    }

    function formatIbkrStartTime(unixSeconds) {
        const date = new Date(unixSeconds * 1000);
        const pad = (value) => String(value).padStart(2, '0');
        return `${date.getUTCFullYear()}${pad(date.getUTCMonth() + 1)}${pad(date.getUTCDate())}-${pad(date.getUTCHours())}:${pad(date.getUTCMinutes())}:${pad(date.getUTCSeconds())}`;
    }

    function contractInfoFromMarketData(data, state) {
        const request = data?.request || {};
        const resolved = request.resolvedContract || {};
        const metadata = data?.metadata || {};
        const symbol = firstNonEmptyString(request.symbol, resolved.symbol, metadata.symbol, state.symbol).toUpperCase();
        const name = firstNonEmptyString(
            metadata.text,
            resolved.companyName,
            resolved.company,
            resolved.name,
            resolved.description,
            resolved.text,
            resolved.localSymbol
        );

        return {
            symbol,
            name: name && name.toUpperCase() !== symbol ? name : '',
            conid: firstNonEmptyString(request.conid, resolved.conid, state.conid),
            exchange: firstNonEmptyString(request.exchange, state.exchange),
            secType: firstNonEmptyString(request.secType, state.secType)
        };
    }

    function contractInfoLookupKey(state) {
        return [
            normalizeSymbol(state.symbol),
            state.conid || '',
            state.secType || '',
            state.exchange || ''
        ].join('|');
    }

    async function fetchContractInfo(state) {
        const key = contractInfoLookupKey(state);
        if (contractInfoPromises.has(key)) return contractInfoPromises.get(key);

        const params = new URLSearchParams({
            symbol: state.symbol,
            secType: state.secType || 'STK',
            exchange: state.exchange || 'SMART'
        });
        if (state.conid) params.set('conid', state.conid);

        const promise = fetch(`contract.php?${params.toString()}`)
            .then(async (response) => {
                const data = await response.json();
                if (!response.ok) throw new Error(data.error || `Contract lookup failed: ${response.status}`);
                return data.contract || null;
            })
            .finally(() => {
                contractInfoPromises.delete(key);
            });

        contractInfoPromises.set(key, promise);
        return promise;
    }

    function ensureChartContractInfo(state) {
        if (state.contractInfo?.name || !state.symbol) return;
        const key = contractInfoLookupKey(state);
        if (state.contractInfoAttemptedKey === key) return;
        state.contractInfoAttemptedKey = key;
        const expectedSymbol = state.symbol;
        const expectedConid = state.conid || null;

        fetchContractInfo(state)
            .then((contract) => {
                if (!contract || !contract.name) return;
                if (state.symbol !== expectedSymbol) return;
                if (expectedConid !== null && (state.conid || null) !== expectedConid) return;
                state.contractInfo = {
                    ...(state.contractInfo || {}),
                    ...contract
                };
                saveWorkspace();
                updatePanelHeader(state);
            })
            .catch((error) => {
                console.warn(`Contract lookup failed for ${state.symbol}`, error);
            });
    }

    function buildMarketDataUrl(state, options = {}) {
        const force = Boolean(options.force);
        const startTime = options.startTime || null;
        const period = options.period || state.chunkPeriod;
        const params = new URLSearchParams({
            bar: state.bar,
            period,
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

    function formatOhlcDate(time) {
        const seconds = Number(time);
        if (!Number.isFinite(seconds)) return '';
        const date = new Date(seconds * 1000);
        return date.toLocaleString(undefined, {
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        });
    }

    function ohlcReadoutHtml(bar) {
        if (!bar) return 'O -- H -- L -- C --';
        const directionClass = bar.close >= bar.open ? 'up' : 'down';
        const change = bar.close - bar.open;
        const changePct = bar.open !== 0 ? (change / bar.open) * 100 : 0;
        const range = bar.high - bar.low;
        const rangePct = bar.low !== 0 ? (range / bar.low) * 100 : 0;
        const sign = change > 0 ? '+' : '';
        const volume = Number(bar.volume);
        const volumeText = Number.isFinite(volume) ? ` V ${formatPrice(volume)}` : '';
        return [
            `<span>${formatOhlcDate(bar.time)}</span>`,
            `O ${formatPrice(bar.open)}`,
            `H ${formatPrice(bar.high)}`,
            `L ${formatPrice(bar.low)}`,
            `C <span class="${directionClass}">${formatPrice(bar.close)}</span>`,
            `<span class="${directionClass}">${sign}${formatPrice(change)} / ${sign}${changePct.toFixed(2)}%</span>`,
            `R ${formatPrice(range)} / ${rangePct.toFixed(2)}%${volumeText}`
        ].join(' · ');
    }

    function updateOhlcReadout(state, bar = null) {
        const readout = state.panel.querySelector('.ohlc-readout');
        if (!readout) return;
        readout.innerHTML = ohlcReadoutHtml(bar);
    }

    function sanitizeTimeRange(range) {
        if (!range) return null;
        const from = Number(range.from);
        const to = Number(range.to);
        if (!Number.isFinite(from) || !Number.isFinite(to) || from >= to) return null;
        return { from, to };
    }

    function timeRangeFromSavedChart(savedChart) {
        return sanitizeTimeRange(savedChart?.viewport?.timeRange || savedChart?.timeRange || null);
    }

    function captureTimeRange(state) {
        if (!state.candles.length || !state.chart.timeScale().getVisibleRange) return;
        const timeRange = sanitizeTimeRange(state.chart.timeScale().getVisibleRange());
        if (timeRange) state.savedTimeRange = timeRange;
    }

    function scheduleTimeRangeSave(state) {
        if (isRestoringWorkspace || state.isRestoringTimeRange || !state.candles.length) return;
        if (state.timeRangeSaveTimer !== null) {
            window.clearTimeout(state.timeRangeSaveTimer);
        }
        state.timeRangeSaveTimer = window.setTimeout(() => {
            state.timeRangeSaveTimer = null;
            captureTimeRange(state);
            saveWorkspace();
        }, 250);
    }

    function restoreSavedTimeRange(state) {
        if (!state.savedTimeRange) return false;
        try {
            state.chart.timeScale().setVisibleRange(state.savedTimeRange);
            return true;
        } catch (error) {
            console.warn('Failed to restore chart time range', error);
            return false;
        }
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
            captureTimeRange(state);
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
                    <span class="drag-handle" draggable="true" title="Drag to reorder">⠿</span>
                    <input class="chart-symbol-input" autocomplete="off" spellcheck="false" title="Chart symbol">
                    <span class="chart-contract-name"></span>
                    <span class="chart-meta"></span>
                </div>
                <div class="chart-actions">
                    <select class="chart-bar-select" title="Bar time period">
                        ${barOptionsHtml(state.bar)}
                    </select>
                    <button class="icon-button maximize-chart" type="button" title="Maximize">⤢</button>
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
                <div class="ohlc-readout">O -- H -- L -- C --</div>
                <div class="chart-footer-actions">
                    <button class="footer-button auto-fit-chart" type="button" title="Auto-fit price scale">Auto-fit</button>
                    <button class="footer-button log-scale-chart" type="button" title="Toggle logarithmic price scale">Log</button>
                    <span class="realtime-pill">RT offline</span>
                    <span class="backfill-pill">Hist idle</span>
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
            lastVisibleRange: null,
            lastBackfillDecision: 'idle',
            targetPeriod: state.period,
            chunkPeriod: null,
            conid: state.conid || null,
            secType: state.secType || 'STK',
            exchange: state.exchange || 'SMART',
            outsideRth: Boolean(state.outsideRth),
            logScale: Boolean(state.logScale),
            contractInfo: state.contractInfo || null,
            savedTimeRange: sanitizeTimeRange(state.savedTimeRange),
            isRestoringTimeRange: Boolean(sanitizeTimeRange(state.savedTimeRange))
        };

        applyPriceScaleMode(chartState);
        panel.querySelector('.close-chart').addEventListener('click', () => removeChart(chartState.id));
        panel.querySelector('.maximize-chart').addEventListener('click', () => toggleMaximizeChart(chartState));
        panel.querySelector('.refresh-chart').addEventListener('click', () => loadInitialChunk(chartState, false));
        panel.querySelector('.auto-fit-chart').addEventListener('click', () => autoFitChart(chartState));
        panel.querySelector('.log-scale-chart').addEventListener('click', () => toggleLogScale(chartState));
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
            chartState.isRestoringTimeRange = false;
            saveWorkspace();
            loadInitialChunk(chartState, false);
        });
        chart.subscribeCrosshairMove((param) => {
            const data = param?.seriesData?.get(series);
            updateOhlcReadout(chartState, data || null);
        });
        chart.timeScale().subscribeVisibleLogicalRangeChange((range) => {
            positionChunkLoaders(chartState);
            handleVisibleRange(chartState, range);
            scheduleTimeRangeSave(chartState);
        });

        const handle = panel.querySelector('.drag-handle');
        handle.addEventListener('dragstart', (e) => {
            draggingChartId = chartState.id;
            panel.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', String(chartState.id));
        });
        handle.addEventListener('dragend', () => {
            draggingChartId = null;
            panel.classList.remove('dragging');
            for (const s of charts.values()) {
                s.panel.classList.remove('drop-before', 'drop-after');
            }
        });
        panel.addEventListener('dragover', (e) => {
            if (draggingChartId === null || draggingChartId === chartState.id) return;
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            const rect = panel.getBoundingClientRect();
            const isBefore = e.clientX < rect.left + rect.width / 2;
            panel.classList.toggle('drop-before', isBefore);
            panel.classList.toggle('drop-after', !isBefore);
        });
        panel.addEventListener('dragleave', (e) => {
            if (!panel.contains(e.relatedTarget)) {
                panel.classList.remove('drop-before', 'drop-after');
            }
        });
        panel.addEventListener('drop', (e) => {
            e.preventDefault();
            panel.classList.remove('drop-before', 'drop-after');
            if (draggingChartId === null || draggingChartId === chartState.id) return;
            const draggingState = charts.get(draggingChartId);
            if (!draggingState || draggingState.panel.parentNode !== chartGrid) return;
            const rect = panel.getBoundingClientRect();
            const isBefore = e.clientX < rect.left + rect.width / 2;
            chartGrid.insertBefore(draggingState.panel, isBefore ? panel : panel.nextSibling);
            saveWorkspace();
        });

        chartGrid.appendChild(panel);
        charts.set(chartState.id, chartState);
        if (!isRestoringWorkspace || charts.size === 1) {
            setFocusedChart(chartState.id);
        }
        updatePanelHeader(chartState);
        saveWorkspace();
        loadInitialChunk(chartState);
        if (chartState.conid) {
            window.setTimeout(ensureRealtime, 0);
        }
        return chartState;
    }

    function updatePanelHeader(state) {
        const symbolInputEl = state.panel.querySelector('.chart-symbol-input');
        if (symbolInputEl && symbolInputEl.value !== state.symbol) {
            symbolInputEl.value = state.symbol;
        }
        const contractNameEl = state.panel.querySelector('.chart-contract-name');
        const contractName = firstNonEmptyString(state.contractInfo?.name);
        if (contractNameEl) {
            contractNameEl.textContent = contractName;
            contractNameEl.title = contractName || 'Looking up company name';
        }
        const metaParts = [
            `${state.bar} candles`,
            `${state.chunkPeriod || requestPeriodForState(state)} chunks`,
            `initial ${state.targetPeriod}`
        ];
        if (state.contractInfo?.exchange) metaParts.push(state.contractInfo.exchange);
        if (state.contractInfo?.secType) metaParts.push(state.contractInfo.secType);
        if (state.contractInfo?.conid) metaParts.push(`conid ${state.contractInfo.conid}`);
        state.panel.querySelector('.chart-meta').textContent = metaParts.join(' · ');
        if (!contractName) ensureChartContractInfo(state);
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

        captureTimeRange(state);
        state.symbol = symbol;
        state.conid = null;
        state.contractInfo = null;
        state.contractInfoAttemptedKey = null;
        state.isRestoringTimeRange = Boolean(state.savedTimeRange);
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

    function addChartForWatchlistSymbol(symbol) {
        const normalizedSymbol = normalizeSymbol(symbol);
        if (!normalizedSymbol) return;
        setFocusedWatchlistSymbol(normalizedSymbol);
        const chartState = createPanel({
            symbol: normalizedSymbol,
            bar: defaultNewChartBar,
            period: defaultNewChartPeriod,
            secType: 'STK',
            exchange: 'SMART',
            outsideRth: false
        });
        setFocusedChart(chartState.id);
        setStatus(`Added ${normalizedSymbol} chart`);
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

    function updateBackfillPill(state, text = null, title = null, isLoading = false) {
        const pill = state.panel.querySelector('.backfill-pill');
        if (!pill) return;
        if (text !== null) pill.textContent = text;
        if (title !== null) pill.title = title;
        pill.classList.toggle('loading', Boolean(isLoading));
    }

    function setPanelMessage(state, message, isError = false, showOverlay = false) {
        state.panel.classList.toggle('initial-loading', Boolean(message) && !isError && showOverlay);
        state.panel.classList.toggle('error', Boolean(message) && isError);
        state.panel.querySelector('.chart-message').textContent = message || '';
    }

    function priceScaleMode(logScale) {
        const modes = LightweightCharts.PriceScaleMode || {};
        return logScale
            ? (modes.Logarithmic ?? 1)
            : (modes.Normal ?? 0);
    }

    function updateLogScaleButton(state) {
        const button = state.panel.querySelector('.log-scale-chart');
        if (!button) return;
        button.classList.toggle('active', Boolean(state.logScale));
        button.title = state.logScale ? 'Use linear price scale' : 'Use logarithmic price scale';
    }

    function applyPriceScaleMode(state) {
        state.chart.priceScale('right').applyOptions({
            mode: priceScaleMode(state.logScale),
            autoScale: true,
            scaleMargins: { top: 0.08, bottom: 0.26 }
        });
        updateLogScaleButton(state);
    }

    function toggleLogScale(state) {
        state.logScale = !state.logScale;
        applyPriceScaleMode(state);
        saveWorkspace();
        setStatus(`${state.symbol} ${state.logScale ? 'log' : 'linear'} scale`);
    }

    function autoFitChart(state) {
        applyPriceScaleMode(state);
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

    function preserveVisibleRangeAfterPrepend(state, range, shift) {
        if (!range) return;
        if (shift <= 0) return;
        window.requestAnimationFrame(() => {
            restoreLogicalRangeAfterPrepend(state, range, shift);
        });
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
        const loadGeneration = state.loadGeneration || 0;
        const requestBar = state.bar;
        const requestChunkPeriod = state.chunkPeriod;
        const requestSymbol = state.symbol;
        const requestConid = state.conid || null;
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
            if (
                loadGeneration !== state.loadGeneration ||
                requestBar !== state.bar ||
                requestChunkPeriod !== state.chunkPeriod ||
                requestSymbol !== state.symbol ||
                (requestConid !== null && requestConid !== (state.conid || null))
            ) {
                updateRequestDebug(state, `Ignored stale ${reason}: ${state.lastRequestUrl}`);
                return;
            }
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
                state.contractInfo = contractInfoFromMarketData(data, state);
                saveWorkspace();
                ensureRealtime();
                window.setTimeout(() => forceRealtimeResubscribe(state.conid), 0);
                updatePanelHeader(state);
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
                if (!restoreSavedTimeRange(state)) {
                    state.chart.timeScale().fitContent();
                }
            } else {
                preserveVisibleRangeAfterPrepend(state, previousLogicalRange, logicalShift);
            }
            shouldBackfillSavedRange = needsSavedTimeRangeBackfill(state);
            updateCachePill(state, data.cache);
            setPanelMessage(state, '');
            updateRequestDebug(state, `${state.lastLoadReason}: ${state.lastRequestUrl} · ${elapsedMs}ms · ${candles.length} bars · ${data.cache?.hit ? 'cache hit' : 'IBKR fetch'}`);
            if (mode === 'prepend') {
                updateBackfillPill(
                    state,
                    'Hist loaded',
                    `${candles.length} older bars loaded in ${elapsedMs}ms from ${state.lastRequestUrl}`
                );
            } else {
                updateBackfillPill(
                    state,
                    'Hist idle',
                    `${candles.length} bars loaded in ${elapsedMs}ms from ${state.lastRequestUrl}`
                );
            }
            setStatus(`${state.symbol} ${state.bar} ${state.chunkPeriod} chunk loaded in ${elapsedMs}ms (${data.cache?.hit ? 'cache' : 'IBKR'})`);
        } catch (error) {
            if (mode !== 'replace') {
                console.warn(error);
                const message = unavailableChunkMessage(reason, startTime);
                updateRequestDebug(state, `${message}: ${state.lastRequestUrl}`);
                updateBackfillPill(state, 'Hist miss', `${message}: ${state.lastRequestUrl}`);
                setStatus(`${state.symbol} ${state.bar}: ${message}`);
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
        state.loadGeneration = (state.loadGeneration || 0) + 1;
        state.chunkPeriod = requestPeriodForState(state);
        state.candles = [];
        state.oldestRequestedTime = null;
        state.nextOlderChunkEnd = null;
        state.requestedOlderEnds.clear();
        if (state.pendingOlderLoad !== null) {
            window.clearTimeout(state.pendingOlderLoad);
            state.pendingOlderLoad = null;
        }
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
            // Anchor the first older chunk to exactly where loaded data starts.
            // Snapping to an epoch-aligned boundary (the previous approach) left a gap
            // between the snap point and the initial chunk's first bar, which caused
            // entire months to never be requested.
            state.nextOlderChunkEnd = earliestLoaded - barSeconds(state.bar);
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
        updateBackfillPill(
            state,
            'Hist loading',
            `Loading older ${state.symbol} ${state.bar} chunk ending before ${startTime}`,
            true
        );
        setStatus(`Loading older ${state.symbol} ${state.bar} bars…`);
        return loadChunk(state, { reason: 'older chunk', mode: 'prepend', startTime, chunkId, targetTime: chunkEnd });
    }

    function handleVisibleRange(state, range) {
        if (!range) return;
        const from = Number(range.from);
        const to = Number(range.to);
        const visibleBars = to - from;
        const barsBeforeLeftEdge = from;
        state.lastVisibleRange = { from, to };

        if (state.isInitialLoading || state.candles.length < 1) {
            updateBackfillPill(
                state,
                'Hist wait',
                `range ${from.toFixed(1)}..${to.toFixed(1)} visible ${visibleBars.toFixed(1)} loaded ${state.candles.length}; initial=${state.isInitialLoading}`
            );
            return;
        }

        const nearLoadedStart = barsBeforeLeftEdge <= Math.max(25, state.candles.length * 0.18);
        const scaledBeyondLoadedData = visibleBars >= state.candles.length * 0.82;
        state.lastBackfillDecision = `range ${from.toFixed(1)}..${to.toFixed(1)} visible ${visibleBars.toFixed(1)} loaded ${state.candles.length} near=${nearLoadedStart} scaled=${scaledBeyondLoadedData}`;
        updateBackfillPill(state, 'Hist idle', state.lastBackfillDecision);
        if (!nearLoadedStart && !scaledBeyondLoadedData) return;

        if (state.pendingOlderLoad !== null) return;

        updateBackfillPill(state, 'Hist queued', state.lastBackfillDecision, true);
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

    function toggleMaximizeChart(state) {
        if (maximizedChartId === state.id) {
            // Restore: put panel back into the grid before its saved next sibling
            if (state._maxNextSibling && state._maxNextSibling.parentNode === chartGrid) {
                chartGrid.insertBefore(state.panel, state._maxNextSibling);
            } else {
                chartGrid.appendChild(state.panel);
            }
            state._maxNextSibling = null;
            maxPanelHost.style.display = 'none';
            maximizedChartId = null;
            const btn = state.panel.querySelector('.maximize-chart');
            if (btn) { btn.title = 'Maximize'; btn.textContent = '⤢'; }
        } else {
            // Maximize: remember grid position, move panel to host
            state._maxNextSibling = state.panel.nextElementSibling;
            maxPanelHost.appendChild(state.panel);
            maxPanelHost.style.display = 'block';
            maximizedChartId = state.id;
            const btn = state.panel.querySelector('.maximize-chart');
            if (btn) { btn.title = 'Restore'; btn.textContent = '⤡'; }
        }
        state.chart.applyOptions({ autoSize: true });
        positionChunkLoaders(state);
    }

    function removeChart(id) {
        const state = charts.get(id);
        if (!state) return;
        if (maximizedChartId === id) {
            maxPanelHost.style.display = 'none';
            maximizedChartId = null;
        }
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
        syncRealtimeSubscriptions();
        saveWorkspace();
        setStatus(charts.size ? 'Chart closed' : 'Ready');
    }

    addChartButton.addEventListener('click', addChartFromControls);
    toggleWatchlistButton.addEventListener('click', () => setWatchlistVisible(!watchlistVisible));
    addWatchlistButton.addEventListener('click', addWatchlistSymbol);
    sortAlphaButton.addEventListener('click', () => setWatchlistSort('alpha'));
    sortChangeButton.addEventListener('click', () => setWatchlistSort('change'));
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
        const symbol = row.getAttribute('data-symbol');
        if (watchlistClickTimer !== null) {
            window.clearTimeout(watchlistClickTimer);
        }
        watchlistClickTimer = window.setTimeout(() => {
            watchlistClickTimer = null;
            loadWatchlistSymbolInFocusedChart(symbol);
        }, 220);
    });
    watchlistItemsEl.addEventListener('dblclick', (event) => {
        const removeButton = event.target.closest('[data-remove-symbol]');
        if (removeButton) return;

        const row = event.target.closest('[data-symbol]');
        if (!row) return;
        if (watchlistClickTimer !== null) {
            window.clearTimeout(watchlistClickTimer);
            watchlistClickTimer = null;
        }
        addChartForWatchlistSymbol(row.getAttribute('data-symbol'));
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
        watchlistSort = ['alpha-asc', 'alpha-desc', 'change-desc', 'change-asc'].includes(savedWorkspace?.watchlist?.sort)
            ? savedWorkspace.watchlist.sort : 'default';
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
                    logScale: Boolean(savedChart.logScale),
                    contractInfo: savedChart.contractInfo || null,
                    savedTimeRange: timeRangeFromSavedChart(savedChart)
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
        window.setTimeout(ensureRealtime, 0);
    }

</script>
<script src="auth_status.js?v=<?= htmlspecialchars($assetVersion, ENT_QUOTES) ?>"></script>
<script>
    initializeCharts();
    startAuthStatusPolling();
</script>
</body>
</html>
