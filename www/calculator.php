<?php
declare(strict_types=1);
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
    <title>IBKR Calculator</title>
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

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            font-size: 14px;
        }

        button, input, select { font: inherit; }

        /* ── topbar ── */
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

        .nav-link:hover { color: var(--text); border-color: #45545e; }

        .nav-link.active {
            color: var(--text);
            border-color: var(--accent);
            background: rgba(47, 143, 131, 0.1);
        }

        .auth-status {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-left: auto;
            color: var(--muted);
            font-size: 12px;
        }

        .auth-status a { color: #8fd2c8; text-decoration: none; }
        .auth-status a:hover { text-decoration: underline; }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #7c8790;
            flex: 0 0 auto;
        }

        .status-dot.authenticated   { background: #27ae60; }
        .status-dot.unauthenticated { background: #e74c3c; }

        /* ── page layout ── */
        .page-body {
            display: flex;
            justify-content: center;
            padding: 32px 16px 64px;
        }

        .calc-card {
            width: 100%;
            max-width: 980px;
            display: flex;
            flex-direction: column;
            gap: 34px;
        }

        .tool-section {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        /* ── card header ── */
        .calc-card-header h1 {
            margin: 0 0 6px;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.3px;
        }

        .calc-card-header p {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.5;
        }

        /* ── panel blocks ── */
        .calc-panel {
            background: var(--panel);
            border: 1px solid var(--panel-border);
            border-radius: 10px;
            padding: 20px 22px;
        }

        .panel-title {
            margin: 0 0 16px;
            color: var(--text);
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* ── annual rate input ── */
        .input-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .input-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .number-input-wrap {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .number-input-wrap input[type="number"] {
            width: 110px;
            color: var(--text);
            background: var(--input);
            border: 1px solid var(--panel-border);
            border-radius: 6px;
            padding: 7px 10px;
            font-size: 18px;
            font-weight: 700;
            text-align: right;
            outline: none;
            -moz-appearance: textfield;
        }

        .comparison-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .field label {
            color: var(--muted);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .field input {
            width: 100%;
            color: var(--text);
            background: var(--input);
            border: 1px solid var(--panel-border);
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 15px;
            font-weight: 650;
            outline: none;
        }

        .field input:focus { border-color: var(--accent); }

        .field input[type="number"] {
            text-align: right;
            -moz-appearance: textfield;
        }

        .field input[type="number"]::-webkit-inner-spin-button,
        .field input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
        }

        .computed-note {
            margin-top: 14px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.45;
        }

        .decision-panel {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(0, 1.6fr);
            gap: 18px;
            align-items: stretch;
        }

        .decision-callout {
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 154px;
            border: 1px solid rgba(47, 143, 131, 0.4);
            border-radius: 8px;
            padding: 18px;
            background: rgba(47, 143, 131, 0.08);
        }

        .decision-callout.warning {
            border-color: rgba(201, 77, 87, 0.45);
            background: rgba(201, 77, 87, 0.08);
        }

        .decision-kicker {
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 8px;
        }

        .decision-title {
            font-size: 26px;
            font-weight: 850;
            line-height: 1.08;
            margin-bottom: 10px;
        }

        .decision-copy {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.5;
        }

        .metric-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .metric {
            min-height: 74px;
            border: 1px solid var(--panel-border);
            border-radius: 8px;
            padding: 12px;
            background: rgba(13, 17, 20, 0.45);
        }

        .metric-label {
            color: var(--muted);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
        }

        .metric-value {
            color: var(--text);
            font-size: 19px;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }

        .metric-value.positive { color: var(--accent); }
        .metric-value.negative { color: var(--danger); }

        .formula-box {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.5;
        }

        .formula-box strong { color: var(--text); }

        .number-input-wrap input[type="number"]::-webkit-inner-spin-button,
        .number-input-wrap input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
        }

        .number-input-wrap input[type="number"]:focus {
            border-color: var(--accent);
        }

        .input-suffix {
            font-size: 18px;
            font-weight: 700;
            color: var(--muted);
        }

        /* ── slider ── */
        .slider-label-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 14px;
        }

        .slider-label-row .input-label { margin: 0; }

        .period-display-wrap {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .period-stepper {
            display: flex;
            align-items: center;
            border: 1px solid var(--panel-border);
            border-radius: 5px;
            overflow: hidden;
        }

        .period-stepper:focus-within { border-color: var(--accent); }

        .stepper-btn {
            background: var(--input);
            border: none;
            color: var(--muted);
            font-size: 16px;
            line-height: 1;
            padding: 0 8px;
            height: 32px;
            cursor: pointer;
            user-select: none;
            transition: color 0.1s, background 0.1s;
        }

        .stepper-btn:hover { color: var(--text); background: #1a2228; }
        .stepper-btn:active { background: #222c33; }

        .period-days-input {
            width: 52px;
            font-size: 15px;
            font-weight: 700;
            color: var(--text);
            background: var(--input);
            border: none;
            border-left: 1px solid var(--panel-border);
            border-right: 1px solid var(--panel-border);
            padding: 3px 6px;
            text-align: center;
            outline: none;
            -moz-appearance: textfield;
        }

        .period-days-input::-webkit-inner-spin-button,
        .period-days-input::-webkit-outer-spin-button { -webkit-appearance: none; }

        .period-days-unit {
            font-size: 13px;
            font-weight: 600;
            color: var(--muted);
            white-space: nowrap;
        }

        .period-slider {
            -webkit-appearance: none;
            appearance: none;
            width: 100%;
            height: 5px;
            border-radius: 3px;
            outline: none;
            cursor: pointer;
            background: linear-gradient(
                to right,
                var(--accent) 0%,
                var(--accent) var(--fill, 8%),
                #2c363d var(--fill, 8%),
                #2c363d 100%
            );
        }

        .period-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--accent);
            cursor: pointer;
            border: 3px solid var(--panel);
            box-shadow: 0 0 0 1px var(--accent);
            transition: box-shadow 0.12s;
        }

        .period-slider::-webkit-slider-thumb:hover {
            box-shadow: 0 0 0 4px rgba(47, 143, 131, 0.25);
        }

        .period-slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--accent);
            cursor: pointer;
            border: 3px solid var(--panel);
        }

        .slider-ticks {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }

        .slider-tick {
            font-size: 11px;
            font-weight: 600;
            color: var(--muted);
            cursor: pointer;
            user-select: none;
            padding: 2px 0;
            transition: color 0.1s;
        }

        .slider-tick:hover { color: var(--text); }
        .slider-tick.active { color: var(--accent); }

        /* ── result box ── */
        .result-box {
            text-align: center;
            padding: 10px 0 4px;
        }

        .result-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--muted);
            margin-bottom: 10px;
        }

        .result-value {
            font-size: 56px;
            font-weight: 800;
            letter-spacing: -1.5px;
            line-height: 1;
            color: var(--accent);
            font-variant-numeric: tabular-nums;
            margin-bottom: 10px;
            transition: color 0.2s;
        }

        .result-value.negative { color: var(--danger); }

        .result-context {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.5;
        }

        /* ── reference table ── */
        .reference-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .reference-table th {
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--muted);
            padding: 0 10px 10px;
            border-bottom: 1px solid var(--panel-border);
        }

        .reference-table th.align-right,
        .reference-table td.align-right { text-align: right; }

        .reference-table td {
            padding: 9px 10px;
            border-bottom: 1px solid rgba(44, 54, 61, 0.6);
            font-variant-numeric: tabular-nums;
        }

        .reference-table tr:last-child td { border-bottom: none; }

        .reference-table tr.highlighted td {
            background: rgba(47, 143, 131, 0.09);
            color: var(--text);
        }

        .reference-table tr.highlighted td.return-value {
            color: var(--accent);
            font-weight: 700;
        }

        .reference-table tr.highlighted td.return-value.negative {
            color: var(--danger);
        }

        .reference-table td.return-value { color: var(--text); }
        .reference-table td.period-label { color: var(--muted); }
        .reference-table td.days-col { color: #6a7d88; font-size: 12px; }

        @media (max-width: 600px) {
            .page-body { padding: 20px 12px 48px; }
            .result-value { font-size: 42px; }
            .comparison-grid,
            .field-grid,
            .decision-panel,
            .metric-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="topbar">
    <div class="brand">IBKR Charts</div>
    <a class="nav-link" href="index.php">Positions</a>
    <a class="nav-link" href="charts.php">Charts</a>
    <span class="nav-link active">Calculator</span>
    <div class="auth-status">
        <div id="auth-dot" class="status-dot"></div>
        <span id="auth-text">Checking session...</span>
    </div>
</div>

<div class="page-body">
    <div class="calc-card">
        <section class="tool-section">
            <div class="calc-card-header">
                <h1>Annualized Return</h1>
                <p>Enter a target annual return and drag the slider to see exactly how much you need to gain in any shorter window to stay on pace — based on <strong>252 trading days per year</strong>, using compound math.</p>
            </div>

            <!-- input -->
            <div class="calc-panel">
                <div class="input-row">
                    <span class="input-label">Target Annual Return</span>
                    <div class="number-input-wrap">
                        <input type="number" id="annual-rate" value="10" min="-99" max="10000" step="0.1">
                        <span class="input-suffix">%</span>
                    </div>
                </div>
            </div>

            <!-- slider -->
            <div class="calc-panel">
                <div class="slider-label-row">
                    <span class="input-label">Time Period</span>
                    <div class="period-display-wrap">
                        <div class="period-stepper">
                            <button type="button" class="stepper-btn" id="period-decrement">−</button>
                            <input type="number" id="period-days-input" class="period-days-input" min="1" max="252" value="21">
                            <button type="button" class="stepper-btn" id="period-increment">+</button>
                        </div>
                        <span class="period-days-unit">trading days</span>
                    </div>
                </div>
                <input type="range" id="period-slider" min="1" max="252" value="21" class="period-slider">
                <div class="slider-ticks">
                    <span class="slider-tick" data-days="1">1d</span>
                    <span class="slider-tick" data-days="5">1w</span>
                    <span class="slider-tick" data-days="21">1m</span>
                    <span class="slider-tick" data-days="63">3m</span>
                    <span class="slider-tick" data-days="126">6m</span>
                    <span class="slider-tick" data-days="252">1y</span>
                </div>
            </div>

            <!-- result -->
            <div class="calc-panel result-box">
                <div class="result-label">Required return for this period</div>
                <div id="result-value" class="result-value">—</div>
                <div id="result-context" class="result-context"></div>
            </div>

            <!-- reference table -->
            <div class="calc-panel">
                <table class="reference-table">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th class="align-right">Trading Days</th>
                            <th class="align-right">Required Return</th>
                        </tr>
                    </thead>
                    <tbody id="reference-tbody"></tbody>
                </table>
            </div>
        </section>

        <section class="tool-section">
            <div class="calc-card-header">
                <h1>CSP Capital Switch</h1>
                <p>Compare the forward return from keeping an existing cash-secured put against closing it and redeploying the collateral into a new CSP. Assumes both options expire out of the money.</p>
            </div>

            <div class="comparison-grid">
                <div class="calc-panel">
                    <h2 class="panel-title">Current CSP</h2>
                    <div class="field-grid">
                        <div class="field">
                            <label for="current-contracts">Contracts</label>
                            <input type="number" id="current-contracts" value="1" min="1" step="1">
                        </div>
                        <div class="field">
                            <label for="current-strike">Strike</label>
                            <input type="number" id="current-strike" value="50" min="0" step="0.01">
                        </div>
                        <div class="field">
                            <label for="current-close">Buyback Cost</label>
                            <input type="number" id="current-close" value="0.20" min="0" step="0.01">
                        </div>
                        <div class="field">
                            <label for="current-dte">DTE</label>
                            <input type="number" id="current-dte" value="14" min="1" step="1">
                        </div>
                        <div class="field">
                            <label for="current-close-fees">Close Fees</label>
                            <input type="number" id="current-close-fees" value="1.40" min="0" step="0.01">
                        </div>
                    </div>
                    <div id="current-note" class="computed-note"></div>
                </div>

                <div class="calc-panel">
                    <h2 class="panel-title">New CSP</h2>
                    <div class="field-grid">
                        <div class="field">
                            <label for="new-contracts">Contracts</label>
                            <input type="number" id="new-contracts" value="1" min="1" step="1">
                        </div>
                        <div class="field">
                            <label for="new-strike">Strike</label>
                            <input type="number" id="new-strike" value="45" min="0" step="0.01">
                        </div>
                        <div class="field">
                            <label for="new-premium">Premium Credit</label>
                            <input type="number" id="new-premium" value="1.50" min="0" step="0.01">
                        </div>
                        <div class="field">
                            <label for="new-dte">DTE</label>
                            <input type="number" id="new-dte" value="30" min="1" step="1">
                        </div>
                        <div class="field">
                            <label for="new-open-fees">Open Fees</label>
                            <input type="number" id="new-open-fees" value="1.00" min="0" step="0.01">
                        </div>
                        <div class="field">
                            <label for="target-roc">Minimum ROC</label>
                            <input type="number" id="target-roc" value="3" min="-100" step="0.1">
                        </div>
                    </div>
                    <div id="new-note" class="computed-note"></div>
                </div>
            </div>

            <div class="calc-panel decision-panel">
                <div id="switch-callout" class="decision-callout">
                    <div class="decision-kicker">Decision</div>
                    <div id="switch-title" class="decision-title">—</div>
                    <div id="switch-copy" class="decision-copy"></div>
                </div>
                <div class="metric-grid">
                    <div class="metric">
                        <div class="metric-label">Keep ROC</div>
                        <div id="keep-roc" class="metric-value">—</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">Switch ROC</div>
                        <div id="switch-roc" class="metric-value">—</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">ROC Edge</div>
                        <div id="roc-edge" class="metric-value">—</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">Keep Annualized</div>
                        <div id="keep-annualized" class="metric-value">—</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">Switch Annualized</div>
                        <div id="switch-annualized" class="metric-value">—</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">Net Extra Profit</div>
                        <div id="net-extra-profit" class="metric-value">—</div>
                    </div>
                </div>
            </div>

            <div class="calc-panel formula-box">
                <strong>Keep ROC</strong> = current buyback value avoided / current collateral. <strong>Switch ROC</strong> = (new premium - current buyback cost - close/open fees) / new collateral. Annualized values use calendar DTE because option expirations are calendar dated.
            </div>
        </section>
    </div>
</div>

<script>
    const annualRateInput  = document.getElementById('annual-rate');
    const periodSlider     = document.getElementById('period-slider');
    const periodDaysInput  = document.getElementById('period-days-input');
    const periodDecrement  = document.getElementById('period-decrement');
    const periodIncrement  = document.getElementById('period-increment');
    const resultValueEl    = document.getElementById('result-value');
    const resultContextEl  = document.getElementById('result-context');
    const referenceTbody   = document.getElementById('reference-tbody');
    const ticks            = document.querySelectorAll('.slider-tick');
    const cspInputs        = {
        currentContracts: document.getElementById('current-contracts'),
        currentStrike:    document.getElementById('current-strike'),
        currentClose:     document.getElementById('current-close'),
        currentDte:       document.getElementById('current-dte'),
        currentCloseFees: document.getElementById('current-close-fees'),
        newContracts:     document.getElementById('new-contracts'),
        newStrike:        document.getElementById('new-strike'),
        newPremium:       document.getElementById('new-premium'),
        newDte:           document.getElementById('new-dte'),
        newOpenFees:      document.getElementById('new-open-fees'),
        targetRoc:        document.getElementById('target-roc'),
    };
    const currentNoteEl       = document.getElementById('current-note');
    const newNoteEl           = document.getElementById('new-note');
    const switchCalloutEl     = document.getElementById('switch-callout');
    const switchTitleEl       = document.getElementById('switch-title');
    const switchCopyEl        = document.getElementById('switch-copy');
    const keepRocEl           = document.getElementById('keep-roc');
    const switchRocEl         = document.getElementById('switch-roc');
    const rocEdgeEl           = document.getElementById('roc-edge');
    const keepAnnualizedEl    = document.getElementById('keep-annualized');
    const switchAnnualizedEl  = document.getElementById('switch-annualized');
    const netExtraProfitEl    = document.getElementById('net-extra-profit');

    const TRADING_DAYS_PER_YEAR = 252;
    const CALENDAR_DAYS_PER_YEAR = 365;
    const CONTRACT_MULTIPLIER = 100;

    const referencePeriods = [
        { label: '1 day',      days: 1   },
        { label: '1 week',     days: 5   },
        { label: '2 weeks',    days: 10  },
        { label: '1 month',    days: 21  },
        { label: '2 months',   days: 42  },
        { label: '3 months',   days: 63  },
        { label: '6 months',   days: 126 },
        { label: '9 months',   days: 189 },
        { label: '1 year',     days: 252 },
    ];

    function calcRequired(annualPct, days) {
        // compound: (1 + r_annual)^(trading_days / 252) − 1
        return (Math.pow(1 + annualPct / 100, days / TRADING_DAYS_PER_YEAR) - 1) * 100;
    }

    function formatPct(value, forDisplay = false) {
        const abs = Math.abs(value);
        let decimals;
        if (abs === 0)       decimals = 3;
        else if (abs < 0.01) decimals = forDisplay ? 5 : 5;
        else if (abs < 0.1)  decimals = forDisplay ? 4 : 4;
        else if (abs < 1)    decimals = 3;
        else                 decimals = 3;
        return (value >= 0 ? '' : '') + value.toFixed(decimals) + '%';
    }

    function formatSignedPct(value) {
        return `${value >= 0 ? '+' : ''}${formatPct(value)}`;
    }

    function formatMoney(value) {
        const sign = value < 0 ? '-' : '';
        return `${sign}$${Math.abs(value).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        })}`;
    }

    function readNumber(input, fallback = 0) {
        const value = Number(input.value);
        return Number.isFinite(value) ? value : fallback;
    }

    function calcAnnualized(simpleReturnPct, days) {
        if (days <= 0 || simpleReturnPct <= -100) return NaN;
        return (Math.pow(1 + simpleReturnPct / 100, CALENDAR_DAYS_PER_YEAR / days) - 1) * 100;
    }

    function setMetric(el, value, formatter) {
        el.textContent = Number.isFinite(value) ? formatter(value) : '—';
        el.classList.toggle('positive', Number.isFinite(value) && value > 0);
        el.classList.toggle('negative', Number.isFinite(value) && value < 0);
    }

    function formatDays(days) {
        if (days === 1)   return '1 trading day';
        if (days === 5)   return '1 week';
        if (days === 10)  return '2 weeks';
        if (days === 21)  return '1 month';
        if (days === 42)  return '2 months';
        if (days === 63)  return '3 months';
        if (days === 84)  return '4 months';
        if (days === 126) return '6 months';
        if (days === 189) return '9 months';
        if (days === 252) return '1 year';
        if (days % 21 === 0) return `${days / 21} months`;
        if (days % 5 === 0)  return `${days / 5} weeks`;
        return `${days} trading days`;
    }

    function updateSliderFill() {
        const min = Number(periodSlider.min);
        const max = Number(periodSlider.max);
        const val = Number(periodSlider.value);
        const pct = ((val - min) / (max - min)) * 100;
        periodSlider.style.setProperty('--fill', `${pct}%`);
    }

    function updateActiveTick(days) {
        ticks.forEach((tick) => {
            tick.classList.toggle('active', Number(tick.dataset.days) === days);
        });
    }

    function update() {
        const annualPct = Number(annualRateInput.value);
        const days      = Number(periodSlider.value);

        if (!Number.isFinite(annualPct)) return;

        const required = calcRequired(annualPct, days);
        const label    = formatDays(days);

        updateSliderFill();
        updateActiveTick(days);

        resultValueEl.textContent = formatPct(required, true);
        resultValueEl.classList.toggle('negative', required < 0);
        resultContextEl.textContent = `to achieve ${annualPct >= 0 ? '+' : ''}${annualPct}% annually over ${label}`;

        // reference table
        referenceTbody.innerHTML = referencePeriods.map(({ label: pLabel, days: pDays }) => {
            const req  = calcRequired(annualPct, pDays);
            const isCurrent = pDays === days;
            const negClass  = req < 0 ? ' negative' : '';
            return `<tr${isCurrent ? ' class="highlighted"' : ''}>
                <td class="period-label">${pLabel}</td>
                <td class="days-col align-right">${pDays}</td>
                <td class="return-value align-right${negClass}">${formatPct(req)}</td>
            </tr>`;
        }).join('');
    }

    function updateCspSwitch() {
        const currentContracts = Math.max(1, Math.floor(readNumber(cspInputs.currentContracts, 1)));
        const currentStrike = Math.max(0, readNumber(cspInputs.currentStrike));
        const currentClose = Math.max(0, readNumber(cspInputs.currentClose));
        const currentDte = Math.max(1, Math.floor(readNumber(cspInputs.currentDte, 1)));
        const currentCloseFees = Math.max(0, readNumber(cspInputs.currentCloseFees));
        const newContracts = Math.max(1, Math.floor(readNumber(cspInputs.newContracts, 1)));
        const newStrike = Math.max(0, readNumber(cspInputs.newStrike));
        const newPremium = Math.max(0, readNumber(cspInputs.newPremium));
        const newDte = Math.max(1, Math.floor(readNumber(cspInputs.newDte, 1)));
        const newOpenFees = Math.max(0, readNumber(cspInputs.newOpenFees));
        const targetRoc = readNumber(cspInputs.targetRoc, 3);

        const currentCollateral = currentStrike * CONTRACT_MULTIPLIER * currentContracts;
        const currentCloseValue = currentClose * CONTRACT_MULTIPLIER * currentContracts;
        const keepProfit = currentCloseValue;
        const keepRoc = currentCollateral > 0 ? (keepProfit / currentCollateral) * 100 : NaN;
        const keepAnnualized = calcAnnualized(keepRoc, currentDte);

        const newCollateral = newStrike * CONTRACT_MULTIPLIER * newContracts;
        const newPremiumValue = newPremium * CONTRACT_MULTIPLIER * newContracts;
        const newStandaloneProfit = newPremiumValue - newOpenFees;
        const newStandaloneRoc = newCollateral > 0 ? (newStandaloneProfit / newCollateral) * 100 : NaN;
        const switchProfit = newPremiumValue - currentCloseValue - currentCloseFees - newOpenFees;
        const switchRoc = newCollateral > 0 ? (switchProfit / newCollateral) * 100 : NaN;
        const switchAnnualized = calcAnnualized(switchRoc, newDte);
        const annualizedEdge = switchAnnualized - keepAnnualized;
        const netExtraProfit = switchProfit - keepProfit;
        const meetsTarget = switchRoc >= targetRoc;
        const beatsKeep = switchAnnualized > keepAnnualized;

        const standaloneText = Number.isFinite(newStandaloneRoc) ? formatSignedPct(newStandaloneRoc) : '—';
        currentNoteEl.textContent = `${formatMoney(currentCollateral)} collateral, ${formatMoney(currentCloseValue)} of remaining option value if the current CSP expires OTM.`;
        newNoteEl.textContent = `${formatMoney(newCollateral)} collateral, ${formatMoney(newPremiumValue)} gross premium, ${standaloneText} standalone ROC before the old close cost.`;

        setMetric(keepRocEl, keepRoc, formatPct);
        setMetric(switchRocEl, switchRoc, formatPct);
        setMetric(rocEdgeEl, annualizedEdge, formatSignedPct);
        setMetric(keepAnnualizedEl, keepAnnualized, formatPct);
        setMetric(switchAnnualizedEl, switchAnnualized, formatPct);
        setMetric(netExtraProfitEl, netExtraProfit, formatMoney);

        switchCalloutEl.classList.toggle('warning', !(beatsKeep && meetsTarget));
        if (!Number.isFinite(switchRoc) || !Number.isFinite(keepRoc)) {
            switchTitleEl.textContent = 'Enter strikes';
            switchCopyEl.textContent = 'Both CSPs need valid collateral before the comparison can be calculated.';
        } else if (beatsKeep && meetsTarget) {
            switchTitleEl.textContent = 'Switch is favored';
            switchCopyEl.textContent = `The new CSP beats the current annualized return by ${formatSignedPct(annualizedEdge)} and clears your ${formatPct(targetRoc)} ROC target after closing the current CSP.`;
        } else if (beatsKeep) {
            switchTitleEl.textContent = 'Better pace, below target';
            switchCopyEl.textContent = `The new CSP has the stronger annualized pace, but the net switch ROC is only ${formatPct(switchRoc)} versus your ${formatPct(targetRoc)} target.`;
        } else {
            switchTitleEl.textContent = 'Keep is favored';
            switchCopyEl.textContent = `The current CSP has the better forward annualized return after accounting for the cost to close it.`;
        }
    }

    // slider → days input
    periodSlider.addEventListener('input', () => {
        periodDaysInput.value = periodSlider.value;
        update();
    });

    // days input → slider
    periodDaysInput.addEventListener('input', () => {
        let val = parseInt(periodDaysInput.value, 10);
        if (!Number.isFinite(val) || val < 1) return;
        if (val > 252) val = 252;
        periodSlider.value = val;
        update();
    });

    // clamp on blur in case user typed something out of range
    periodDaysInput.addEventListener('blur', () => {
        let val = parseInt(periodDaysInput.value, 10);
        if (!Number.isFinite(val) || val < 1) val = 1;
        if (val > 252) val = 252;
        periodDaysInput.value = val;
        periodSlider.value = val;
        update();
    });

    // tick clicks
    ticks.forEach((tick) => {
        tick.addEventListener('click', () => {
            const days = Number(tick.dataset.days);
            periodSlider.value = days;
            periodDaysInput.value = days;
            update();
        });
    });

    function stepDays(delta) {
        let val = Math.max(1, Math.min(252, (parseInt(periodDaysInput.value, 10) || 1) + delta));
        periodDaysInput.value = val;
        periodSlider.value = val;
        update();
    }

    periodDecrement.addEventListener('click', () => stepDays(-1));
    periodIncrement.addEventListener('click', () => stepDays(+1));

    let savePrefsTimer = null;
    function savePrefs() {
        const annualPct = Number(annualRateInput.value);
        if (!Number.isFinite(annualPct)) return;
        if (savePrefsTimer !== null) window.clearTimeout(savePrefsTimer);
        savePrefsTimer = window.setTimeout(async () => {
            savePrefsTimer = null;
            const cspSwitchPrefs = Object.fromEntries(
                Object.entries(cspInputs).map(([key, input]) => [key, input.value])
            );
            try {
                await fetch('preferences.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ calculatorPrefs: { annualRate: annualPct, cspSwitch: cspSwitchPrefs } })
                });
            } catch (error) {
                console.warn('Failed to save calculator preferences', error);
            }
        }, 150);
    }

    async function loadPrefs() {
        try {
            const response = await fetch('preferences.php');
            if (!response.ok) return;
            const prefs = await response.json();
            const rate = prefs?.calculatorPrefs?.annualRate;
            if (Number.isFinite(rate)) {
                annualRateInput.value = rate;
            }
            const cspSwitchPrefs = prefs?.calculatorPrefs?.cspSwitch;
            if (cspSwitchPrefs && typeof cspSwitchPrefs === 'object') {
                Object.entries(cspInputs).forEach(([key, input]) => {
                    if (Object.prototype.hasOwnProperty.call(cspSwitchPrefs, key)) {
                        input.value = cspSwitchPrefs[key];
                    }
                });
            }
        } catch (error) {
            console.warn('Failed to load calculator preferences', error);
        }
    }

    annualRateInput.addEventListener('input', () => { update(); savePrefs(); });
    Object.values(cspInputs).forEach((input) => {
        input.addEventListener('input', () => { updateCspSwitch(); savePrefs(); });
    });

    // init
    loadPrefs().then(() => { update(); updateCspSwitch(); });
</script>
<script src="auth_status.js?v=<?= htmlspecialchars($assetVersion, ENT_QUOTES) ?>"></script>
<script>startAuthStatusPolling();</script>
</body>
</html>
