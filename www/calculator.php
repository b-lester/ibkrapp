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
            max-width: 560px;
            display: flex;
            flex-direction: column;
            gap: 28px;
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

        .period-display {
            font-size: 15px;
            font-weight: 700;
            color: var(--text);
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
                <span id="period-display" class="period-display">1 month</span>
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
    </div>
</div>

<script>
    const annualRateInput = document.getElementById('annual-rate');
    const periodSlider    = document.getElementById('period-slider');
    const periodDisplay   = document.getElementById('period-display');
    const resultValueEl   = document.getElementById('result-value');
    const resultContextEl = document.getElementById('result-context');
    const referenceTbody  = document.getElementById('reference-tbody');
    const ticks           = document.querySelectorAll('.slider-tick');

    const TRADING_DAYS_PER_YEAR = 252;

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

        periodDisplay.textContent = formatDays(days);
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

    // tick clicks
    ticks.forEach((tick) => {
        tick.addEventListener('click', () => {
            periodSlider.value = tick.dataset.days;
            update();
        });
    });

    annualRateInput.addEventListener('input', update);
    periodSlider.addEventListener('input', update);

    // init
    update();
</script>
<script src="auth_status.js?v=<?= htmlspecialchars($assetVersion, ENT_QUOTES) ?>"></script>
<script>startAuthStatusPolling();</script>
</body>
</html>
