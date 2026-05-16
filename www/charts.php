<?php
header('Content-Type: text/html; charset=utf-8');
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

        .charts-grid {
            flex: 1;
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

        .chart-panel {
            min-height: 0;
            border: 1px solid var(--panel-border);
            background: var(--panel);
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
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
            align-items: baseline;
            gap: 8px;
            min-width: 0;
        }

        .chart-symbol {
            font-size: 15px;
            font-weight: 800;
            white-space: nowrap;
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
            min-height: 48px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: 8px;
            padding: 5px 9px;
            border-top: 1px solid var(--panel-border);
            color: var(--muted);
            font-size: 12px;
        }

        .chart-debug {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .chart-range,
        .chart-request {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .chart-request {
            color: #a8b6bd;
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
            <input id="symbol-input" value="NOW" autocomplete="off" spellcheck="false">
        </div>
        <div class="control-group">
            <label for="bar-select">Bar</label>
            <select id="bar-select">
                <option value="1min">1m</option>
                <option value="2min">2m</option>
                <option value="3min">3m</option>
                <option value="5min" selected>5m</option>
                <option value="10min">10m</option>
                <option value="15min">15m</option>
                <option value="30min">30m</option>
                <option value="1h">1h</option>
                <option value="2h">2h</option>
                <option value="4h">4h</option>
                <option value="1d">1D</option>
                <option value="1w">1W</option>
                <option value="1m">1M</option>
            </select>
        </div>
        <div class="control-group">
            <label for="range-select">Initial</label>
            <select id="range-select">
                <option value="1d" selected>1D</option>
                <option value="1w">1W</option>
                <option value="1m">1M</option>
                <option value="3m">3M</option>
                <option value="6m">6M</option>
                <option value="1y">1Y</option>
            </select>
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
    </div>
    <div id="status-bar" class="status-bar">Ready</div>
    <div id="charts-grid" class="charts-grid"></div>
</div>

<script>
    const chartGrid = document.getElementById('charts-grid');
    const statusBar = document.getElementById('status-bar');
    const addChartButton = document.getElementById('add-chart-button');
    const symbolInput = document.getElementById('symbol-input');
    const barSelect = document.getElementById('bar-select');
    const rangeSelect = document.getElementById('range-select');
    const gridRowsInput = document.getElementById('grid-rows-input');
    const gridColsInput = document.getElementById('grid-cols-input');

    const upColor = '#1fa774';
    const downColor = '#dc4c5a';
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
                timeRange: state.savedTimeRange || null
            }
        };
    }

    function saveWorkspace() {
        if (isRestoringWorkspace) return;
        const workspace = {
            rows: clampGridNumber(gridRowsInput.value, 1),
            cols: clampGridNumber(gridColsInput.value, 1),
            charts: Array.from(charts.values()).map(serializeChartState)
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
            close: Number(bar.close ?? bar.c)
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

    function createPanel(state) {
        const panel = document.createElement('section');
        panel.className = 'chart-panel initial-loading';
        panel.innerHTML = `
            <div class="chart-header">
                <div class="chart-title">
                    <span class="chart-symbol"></span>
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
                <div class="chart-debug">
                    <span class="chart-range"></span>
                    <span class="chart-request"></span>
                </div>
                <span class="cache-pill">Cache</span>
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
                scaleMargins: { top: 0.12, bottom: 0.12 }
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

        const chartState = {
            ...state,
            id: nextChartId++,
            panel,
            chart,
            series,
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
            isRestoringTimeRange: Boolean(sanitizeTimeRange(state.savedTimeRange))
        };

        panel.querySelector('.close-chart').addEventListener('click', () => removeChart(chartState.id));
        panel.querySelector('.refresh-chart').addEventListener('click', () => loadInitialChunk(chartState, false));
        panel.querySelector('.chart-bar-select').addEventListener('change', (event) => {
            chartState.bar = event.target.value;
            chartState.savedTimeRange = null;
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
        updatePanelHeader(chartState);
        saveWorkspace();
        loadInitialChunk(chartState);
        return chartState;
    }

    function updatePanelHeader(state) {
        state.panel.querySelector('.chart-symbol').textContent = state.symbol;
        state.panel.querySelector('.chart-meta').textContent = `${state.bar} candles · ${state.chunkPeriod || requestPeriodForState(state)} chunks · initial ${state.targetPeriod}`;
        const barSelect = state.panel.querySelector('.chart-bar-select');
        if (barSelect && barSelect.value !== state.bar) {
            barSelect.value = state.bar;
        }
        if (!state.candles.length) {
            state.panel.querySelector('.chart-range').textContent = 'No loaded bars yet';
        }
        updateRequestDebug(state);
    }

    function updateRequestDebug(state, text = '') {
        const requestEl = state.panel.querySelector('.chart-request');
        const fallback = state.lastRequestUrl ? `${state.lastLoadReason}: ${state.lastRequestUrl}` : `${state.symbol} · ${state.bar} · chunk ${state.chunkPeriod}`;
        requestEl.textContent = text || fallback;
        requestEl.title = requestEl.textContent;
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

    function describeRange(state) {
        if (!state.candles.length) return '';
        const first = new Date(state.candles[0].time * 1000).toLocaleString();
        const last = new Date(state.candles[state.candles.length - 1].time * 1000).toLocaleString();
        return `${state.candles.length} bars · ${first} → ${last}`;
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

    async function loadChunk(state, options = {}) {
        const force = Boolean(options.force);
        const reason = options.reason || 'load';
        const mode = options.mode || 'replace';
        const startTime = options.startTime || null;
        const chunkId = options.chunkId || `${reason}-${Date.now()}-${Math.random().toString(16).slice(2)}`;
        const targetTime = options.targetTime || null;
        let shouldBackfillSavedRange = false;

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
            const response = await fetch(state.lastRequestUrl);
            const data = await response.json();
            const elapsedMs = Math.round(performance.now() - startedAt);
            if (!response.ok) {
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

            state.candles = mode === 'prepend' ? mergeCandles(state.candles, candles) : candles;
            if (state.candles.length) {
                state.oldestRequestedTime = state.candles[0].time;
            }
            state.series.setData(state.candles);
            if (mode === 'replace') {
                if (!restoreSavedTimeRange(state)) {
                    state.chart.timeScale().fitContent();
                }
            } else {
                restoreSavedTimeRange(state);
            }
            shouldBackfillSavedRange = needsSavedTimeRangeBackfill(state);
            state.panel.querySelector('.chart-range').textContent = describeRange(state);
            updateCachePill(state, data.cache);
            setPanelMessage(state, '');
            updateRequestDebug(state, `${state.lastLoadReason}: ${state.lastRequestUrl} · ${elapsedMs}ms · ${candles.length} bars · ${data.cache?.hit ? 'cache hit' : 'IBKR fetch'}`);
            setStatus(`${state.symbol} ${state.bar} ${state.chunkPeriod} chunk loaded in ${elapsedMs}ms (${data.cache?.hit ? 'cache' : 'IBKR'})`);
        } catch (error) {
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
            } else {
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
            bar: barSelect.value,
            period: rangeSelect.value,
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
        saveWorkspace();
        setStatus(charts.size ? 'Chart closed' : 'Ready');
    }

    addChartButton.addEventListener('click', addChartFromControls);
    gridRowsInput.addEventListener('change', applyGridDimensions);
    gridColsInput.addEventListener('change', applyGridDimensions);
    gridRowsInput.addEventListener('input', applyGridDimensions);
    gridColsInput.addEventListener('input', applyGridDimensions);
    symbolInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') addChartFromControls();
    });

    async function initializeCharts() {
        const savedWorkspace = await loadWorkspace();
        gridRowsInput.value = savedWorkspace?.rows || '1';
        gridColsInput.value = savedWorkspace?.cols || '1';
        applyGridDimensions();

        if (!window.LightweightCharts) {
            setStatus('Chart library failed to load.', true);
        } else if (savedWorkspace && savedWorkspace.charts.length > 0) {
            isRestoringWorkspace = true;
            savedWorkspace.charts.forEach((savedChart) => createPanel({
                symbol: normalizeSymbol(savedChart.symbol || 'NOW'),
                conid: savedChart.conid || null,
                bar: savedChart.bar || '5min',
                period: savedChart.period || '1d',
                secType: savedChart.secType || 'STK',
                exchange: savedChart.exchange || 'SMART',
                outsideRth: Boolean(savedChart.outsideRth),
                savedTimeRange: timeRangeFromSavedChart(savedChart)
            }));
            isRestoringWorkspace = false;
            saveWorkspace();
        } else {
            addChartFromControls();
        }
    }

    initializeCharts();
</script>
</body>
</html>
