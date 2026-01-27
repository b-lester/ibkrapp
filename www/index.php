<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IBKR Positions</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 10px;
            color: #333;
            font-size: 0.85rem;
        }
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        .auth-status {
            display: flex;
            align-items: center;
            font-size: 0.75rem;
            color: #666;
        }
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
            background-color: #999;
        }
        .status-dot.authenticated { background-color: #27ae60; }
        .status-dot.unauthenticated { background-color: #e74c3c; }
        .container {
            max-width: 1100px;
            margin: 0 auto;
            background: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            font-size: 1.4rem;
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            text-align: left;
            padding: 6px 8px;
            border-bottom: 1px solid #eee;
        }
        th {
            background-color: #f8f9fa;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.7rem;
            color: #666;
        }
        .group-header {
            background-color: #f1f8ff;
            font-weight: bold;
        }
        .summary-row {
            background-color: #fafafa;
            font-size: 0.8rem;
            color: #555;
        }
        .summary-cell {
            text-align: right;
            padding-right: 10px;
            font-style: italic;
        }
        .summary-content {
            display: flex;
            justify-content: flex-end;
            gap: 20px;
            align-items: center;
        }
        .pos-value {
            font-weight: bold;
        }
        .pnl-positive {
            color: #27ae60;
        }
        .pnl-negative {
            color: #e74c3c;
        }
        .loading {
            text-align: center;
            padding: 10px;
            font-style: italic;
            color: #999;
        }
        .error {
            color: #e74c3c;
            text-align: center;
            padding: 10px;
            border: 1px solid #e74c3c;
            border-radius: 4px;
            background-color: #fdf2f2;
            margin-top: 10px;
        }
        .account-summary {
            margin-bottom: 15px;
            background-color: #fff;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        .account-summary table {
            margin-top: 0;
        }
        .tag-badge {
            background-color: #e1f5fe;
            color: #0288d1;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-left: 5px;
        }
        .tag-spacer {
            height: 20px;
        }
        .tag-spacer td {
            border: none;
            background-color: transparent;
        }
        .sorting-controls {
            margin-top: 10px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.8rem;
        }
        .sorting-controls label {
            font-weight: 600;
            color: #666;
        }
        .sorting-controls select {
            padding: 2px 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #fff;
            font-size: 0.8rem;
        }
        .ticker-name {
            cursor: pointer;
            text-decoration: underline dotted #ccc;
        }
        .ticker-name:hover {
            color: #0288d1;
            text-decoration-color: #0288d1;
        }
        .open-date {
            cursor: pointer;
            color: #666;
            text-decoration: underline dotted #ccc;
            font-size: 0.75rem;
        }
        .open-date:hover {
            color: #0288d1;
            text-decoration-color: #0288d1;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-top">
        <h1>My Positions</h1>
        <div class="auth-status">
            <div id="auth-dot" class="status-dot"></div>
            <span id="auth-text">Checking session...</span>
        </div>
    </div>

    <div id="account-container" class="account-summary">
        <div class="loading">Loading account info...</div>
    </div>

    <div class="sorting-controls">
        <div style="display: flex; align-items: center; gap: 5px;">
            <label for="sort-select">Sort:</label>
            <select id="sort-select" onchange="updateSort(this.value)">
                <option value="ticker">Ticker</option>
                <option value="expires">Expires (Earliest)</option>
                <option value="pnl">Total PnL (Lowest)</option>
                <option value="exposure">Aggregated Exposure (Highest)</option>
            </select>
        </div>
        <div style="display: flex; align-items: center; gap: 5px;">
            <label for="filter-select">Filter:</label>
            <select id="filter-select" onchange="updateFilter(this.value)">
                <option value="all">All</option>
                <option value="STK">Stocks</option>
                <option value="OPT">Options</option>
            </select>
        </div>
        <div style="display: flex; align-items: center; gap: 5px; margin-left: 10px;">
            <input type="checkbox" id="group-ticker-toggle" onchange="updateGroupByTicker(this.checked)">
            <label for="group-ticker-toggle">Group by Ticker</label>
        </div>
    </div>

    <div id="status"></div>
    <div id="table-container">
        <div class="loading">Loading positions...</div>
    </div>
</div>

<script>
    let currentTags = {};
    let currentOpenDates = {};
    let currentNLV = 0;
    let currentSort = 'ticker';
    let currentFilter = 'all';
    let currentGroupByTicker = true;
    let lastPositionsData = [];

    async function fetchPositions() {
        try {
            const [posRes, cashRes, tagsRes, prefsRes, openDatesRes, tradesRes] = await Promise.all([
                fetch('list_positions.php'),
                fetch('list_cash.php'),
                fetch('tags.php'),
                fetch('preferences.php'),
                fetch('open_dates.php'),
                fetch('list_trades.php?days=7')
            ]);

            if (!posRes.ok) throw new Error(`Positions fetch failed: ${posRes.status}`);
            if (!cashRes.ok) throw new Error(`Cash fetch failed: ${cashRes.status}`);
            if (!tagsRes.ok) throw new Error(`Tags fetch failed: ${tagsRes.status}`);
            if (!prefsRes.ok) throw new Error(`Preferences fetch failed: ${prefsRes.status}`);
            if (!openDatesRes.ok) throw new Error(`Open dates fetch failed: ${openDatesRes.status}`);
            if (!tradesRes.ok) throw new Error(`Trades fetch failed: ${tradesRes.status}`);

            const posData = await posRes.json();
            const cashData = await cashRes.json();
            currentTags = await tagsRes.json();
            const prefs = await prefsRes.json();
            currentOpenDates = await openDatesRes.json();
            const tradesData = await tradesRes.json();

            if (prefs.sort) {
                currentSort = prefs.sort;
                document.getElementById('sort-select').value = currentSort;
            }
            if (prefs.filter) {
                currentFilter = prefs.filter;
                document.getElementById('filter-select').value = currentFilter;
            }
            if (prefs.hasOwnProperty('groupByTicker')) {
                currentGroupByTicker = prefs.groupByTicker;
                document.getElementById('group-ticker-toggle').checked = currentGroupByTicker;
            } else {
                // Default to checked
                document.getElementById('group-ticker-toggle').checked = true;
            }

            // Auto-fill open dates from trades if missing
            let openDatesChanged = false;
            if (posData.positions && tradesData.trades) {
                for (const pos of posData.positions) {
                    if (!currentOpenDates[pos.conid]) {
                        // Find most recent trade for this conid
                        // Trades are typically returned in reverse chronological order or we can just find any
                        const match = tradesData.trades.find(t => String(t.conid) === String(pos.conid));
                        if (match) {
                            // trade_time: "20260126-17:24:42" -> "2026-01-26"
                            const rawDate = match.trade_time.split('-')[0];
                            const formattedDate = `${rawDate.substring(0, 4)}-${rawDate.substring(4, 6)}-${rawDate.substring(6, 8)}`;
                            
                            // Save it locally first
                            currentOpenDates[pos.conid] = formattedDate;
                            
                            // Save to backend (async, don't wait for each)
                            saveOpenDate(pos.conid, formattedDate, false); // Pass false to skip re-render for each
                            openDatesChanged = true;
                        }
                    }
                }
            }

            lastPositionsData = posData.positions;
            renderAccountSummary(posData.positions, cashData);
            renderPositions(posData.positions);
        } catch (error) {
            console.error('Fetch error:', error);
            document.getElementById('table-container').innerHTML = `
                <div class="error">
                    <strong>Error:</strong> ${error.message}
                </div>
            `;
        }
    }

    function getTicker(pos) {
        // Ticker is derived by parsing the contractDesc field.
        // For options, it's usually the first part before spaces.
        // Examples: 
        // "NFLX" -> "NFLX"
        // "IREN   FEB2026 40 P [IREN  260213P00040000 100]" -> "IREN"
        return pos.contractDesc.trim().split(/\s+/)[0];
    }

    function getDaysToExpiry(pos) {
        if (pos.assetClass !== 'OPT') return null;
        // Match 6 digits followed by P or C inside the brackets [TICKER YYMMDDP...]
        const match = pos.contractDesc.match(/\[.*?\s+(\d{6})[CP]/);
        if (match) {
            const d = match[1];
            const year = 2000 + parseInt(d.substring(0, 2));
            const month = parseInt(d.substring(2, 4)) - 1;
            const day = parseInt(d.substring(4, 6));
            const expiry = new Date(year, month, day);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            expiry.setHours(0, 0, 0, 0);
            return Math.ceil((expiry - today) / 86400000);
        }
        return null;
    }

    function calculatePositionExposure(pos) {
        let exposure = 0;
        if (pos.assetClass === 'STK') {
            exposure = pos.mktValue;
        }
        
        let liability = 0;
        if (pos.assetClass === 'OPT' && pos.position < 0) {
            const desc = pos.contractDesc.split('[')[0].trim();
            const parts = desc.split(/\s+/);
            if (parts.length >= 4) {
                const strike = parseFloat(parts[parts.length - 2]);
                const isPut = parts[parts.length - 1] === 'P';
                if (isPut && !isNaN(strike)) {
                    liability = Math.abs(pos.position * strike * 100);
                }
            }
        }
        exposure += liability;
        return exposure;
    }

    function renderAccountSummary(positions, cashData) {
        // Extract account level info from the BASE currency ledger of the first account found
        let positionsValue = 0;
        let cashBalance = 0;
        let netLiquidation = 0;

        if (cashData.accounts) {
            const accountIds = Object.keys(cashData.accounts);
            if (accountIds.length > 0) {
                const firstAcc = cashData.accounts[accountIds[0]];
                const ledger = firstAcc.BASE || firstAcc.USD || Object.values(firstAcc)[0];
                
                if (ledger) {
                    positionsValue = (ledger.stockmarketvalue || 0) + (ledger.stockoptionmarketvalue || 0);
                    cashBalance = ledger.cashbalance || 0;
                    netLiquidation = ledger.netliquidationvalue || 0;
                    currentNLV = netLiquidation;
                }
            }
        }

        let totalExposure = 0;
        let stocksExposure = 0;
        if (positions) {
            positions.forEach(pos => {
                const ticker = getTicker(pos);
                const tag = currentTags[ticker] || '';
                if (tag !== 'safe') {
                    if (pos.assetClass === 'STK') {
                        stocksExposure += pos.mktValue;
                    }
                    totalExposure += calculatePositionExposure(pos);
                }
            });
        }

        document.getElementById('account-container').innerHTML = `
            <table>
                <thead>
                    <tr>
                        <th>Net Liquidation</th>
                        <th>Positions Value</th>
                        <th>Cash Balance</th>
                        <th>Stocks Exposure</th>
                        <th>Total Exposure</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>${formatCurrency(netLiquidation)}</strong></td>
                        <td><strong>${formatCurrency(positionsValue)}</strong></td>
                        <td>${formatCurrency(cashBalance)}</td>
                        <td>
                            <strong>${formatCurrency(stocksExposure)}</strong>
                            <div style="font-size: 0.8rem; color: #666;">
                                ${netLiquidation ? formatPercent((stocksExposure / netLiquidation) * 100) : '0.00%'} of NLV
                            </div>
                        </td>
                        <td>
                            <strong>${formatCurrency(totalExposure)}</strong>
                            <div style="font-size: 0.8rem; color: #666;">
                                ${netLiquidation ? formatPercent((totalExposure / netLiquidation) * 100) : '0.00%'} of NLV
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        `;
    }

    function formatCurrency(value) {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
    }

    function formatPercent(value) {
        return value.toFixed(2) + '%';
    }

    async function saveTag(ticker, tag) {
        try {
            const res = await fetch('tags.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ticker, tag })
            });
            if (res.ok) {
                const data = await res.json();
                currentTags = data.tags;
                fetchPositions(); // Refresh UI
            }
        } catch (error) {
            console.error('Save tag error:', error);
        }
    }

    async function saveOpenDate(conid, open_date, triggerRender = true) {
        try {
            const res = await fetch('open_dates.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ conid, open_date })
            });
            if (res.ok) {
                const data = await res.json();
                currentOpenDates = data.open_dates;
                if (triggerRender) {
                    renderPositions(lastPositionsData); // Just re-render
                }
            }
        } catch (error) {
            console.error('Save open date error:', error);
        }
    }

    async function updateSort(sortValue) {
        currentSort = sortValue;
        renderPositions(lastPositionsData);
        try {
            await fetch('preferences.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ sort: sortValue })
            });
        } catch (error) {
            console.error('Save sort preference error:', error);
        }
    }

    async function updateFilter(filterValue) {
        currentFilter = filterValue;
        renderPositions(lastPositionsData);
        try {
            await fetch('preferences.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ filter: filterValue })
            });
        } catch (error) {
            console.error('Save filter preference error:', error);
        }
    }

    async function updateGroupByTicker(enabled) {
        currentGroupByTicker = enabled;
        renderPositions(lastPositionsData);
        try {
            await fetch('preferences.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ groupByTicker: enabled })
            });
        } catch (error) {
            console.error('Save group-by preference error:', error);
        }
    }

    function editTag(ticker) {
        const oldTag = currentTags[ticker] || '';
        const newTag = prompt(`Enter tag for ${ticker}:`, oldTag);
        if (newTag !== null && newTag !== oldTag) {
            saveTag(ticker, newTag.trim());
        }
    }

    function editOpenDate(conid, ticker) {
        const oldDate = currentOpenDates[conid] || '';
        let newDate = prompt(`Enter open date for ${ticker} (YYYY-MM-DD):`, oldDate);
        
        if (newDate === null) return; // User cancelled
        
        newDate = newDate.trim();
        if (newDate !== '' && !/^\d{4}-\d{2}-\d{2}$/.test(newDate)) {
            alert('Invalid date format. Please use YYYY-MM-DD.');
            return;
        }

        if (newDate !== oldDate) {
            saveOpenDate(conid, newDate);
        }
    }

    function renderPositions(positions) {
        if (!positions || positions.length === 0) {
            document.getElementById('table-container').innerHTML = '<div class="loading">No positions found.</div>';
            return;
        }

        // 0. Filter positions
        const filteredPositions = positions.filter(pos => {
            if (currentFilter === 'all') return true;
            return pos.assetClass === currentFilter;
        });

        if (filteredPositions.length === 0) {
            document.getElementById('table-container').innerHTML = '<div class="loading">No positions match the current filter.</div>';
            return;
        }

        let html = `
            <table>
                <thead>
                    <tr>
                        <th>Ticker</th>
                        <th>Position</th>
                        <th>Open Date</th>
                        <th>Age</th>
                        <th>Target Return</th>
                        <th>ROC</th>
                        <th>Expires</th>
                        <th>Avg</th>
                        <th>Last</th>
                        <th>Value</th>
                        <th>PnL</th>
                        <th>Liability</th>
                    </tr>
                </thead>
                <tbody>
        `;

        if (currentGroupByTicker) {
            // 1. Group by ticker and pre-calculate group-level stats for sorting
            const tickerGroups = {};
            filteredPositions.forEach(pos => {
                const ticker = getTicker(pos);
                if (!tickerGroups[ticker]) {
                    tickerGroups[ticker] = {
                        positions: [],
                        totalPnL: 0,
                        minDaysToExpiry: Infinity,
                        totalExposure: 0,
                        costBasis: 0
                    };
                }
                const group = tickerGroups[ticker];
                group.positions.push(pos);
                group.totalPnL += pos.unrealizedPnl;
                group.totalExposure += calculatePositionExposure(pos);
                group.costBasis += Math.abs(pos.position * pos.avgCost);
                
                const days = getDaysToExpiry(pos);
                if (days !== null && days < group.minDaysToExpiry) {
                    group.minDaysToExpiry = days;
                }
            });

            // 2. Sort tickers based on selected option
            const sortedTickers = Object.keys(tickerGroups).sort((a, b) => {
                const groupA = tickerGroups[a];
                const groupB = tickerGroups[b];
                
                switch (currentSort) {
                    case 'expires':
                        const expiryA = groupA.minDaysToExpiry === Infinity ? 99999 : groupA.minDaysToExpiry;
                        const expiryB = groupB.minDaysToExpiry === Infinity ? 99999 : groupB.minDaysToExpiry;
                        return expiryA - expiryB;
                    case 'pnl':
                        return groupA.totalPnL - groupB.totalPnL;
                    case 'exposure':
                        return groupB.totalExposure - groupA.totalExposure;
                    case 'ticker':
                    default:
                        return a.localeCompare(b);
                }
            });

            // 3. Group sorted tickers by tag
            const tagGroups = {};
            sortedTickers.forEach(ticker => {
                const tag = currentTags[ticker] || '';
                if (!tagGroups[tag]) tagGroups[tag] = [];
                tagGroups[tag].push(ticker);
            });

            // 4. Sort tags: empty first, then alphabetically
            const sortedTags = Object.keys(tagGroups).sort((a, b) => {
                if (a === '') return -1;
                if (b === '') return 1;
                return a.localeCompare(b);
            });

            sortedTags.forEach((tag, tagIndex) => {
                if (tagIndex > 0) html += '<tr class="tag-spacer"><td colspan="12"></td></tr>';

                const tickersInTag = tagGroups[tag];
                tickersInTag.forEach(ticker => {
                    const groupData = tickerGroups[ticker];
                    const group = groupData.positions;
                    
                    group.forEach((pos, index) => {
                        html += renderPositionRow(pos, ticker, index === 0);
                    });

                    const groupPnL = groupData.totalPnL;
                    const groupExposure = groupData.totalExposure;
                    const groupPnlClass = groupPnL >= 0 ? 'pnl-positive' : 'pnl-negative';

                    html += `
                        <tr class="summary-row">
                            <td colspan="12" class="summary-cell">
                                <div class="summary-content">
                                    <div>Total PnL: <span class="${groupPnlClass}">${formatCurrency(groupPnL)}</span></div>
                                    <div class="pos-value">Exposure: ${formatCurrency(groupExposure)}</div>
                                    <div style="font-size: 0.75rem; color: #666;">
                                        ${currentNLV ? formatPercent((groupExposure / currentNLV) * 100) : '0.00%'} of NLV
                                    </div>
                                </div>
                            </td>
                        </tr>
                    `;
                });
            });
        } else {
            // Flat list logic
            const sortedPositions = [...filteredPositions].sort((a, b) => {
                const tickerA = getTicker(a);
                const tickerB = getTicker(b);
                switch (currentSort) {
                    case 'expires':
                        const daysA = getDaysToExpiry(a) ?? 99999;
                        const daysB = getDaysToExpiry(b) ?? 99999;
                        return daysA - daysB;
                    case 'pnl':
                        return a.unrealizedPnl - b.unrealizedPnl;
                    case 'exposure':
                        return calculatePositionExposure(b) - calculatePositionExposure(a);
                    case 'ticker':
                    default:
                        return tickerA.localeCompare(tickerB);
                }
            });

            const tagGroups = {};
            sortedPositions.forEach(pos => {
                const ticker = getTicker(pos);
                const tag = currentTags[ticker] || '';
                if (!tagGroups[tag]) tagGroups[tag] = [];
                tagGroups[tag].push(pos);
            });

            const sortedTags = Object.keys(tagGroups).sort((a, b) => {
                if (a === '') return -1;
                if (b === '') return 1;
                return a.localeCompare(b);
            });

            sortedTags.forEach((tag, tagIndex) => {
                if (tagIndex > 0) html += '<tr class="tag-spacer"><td colspan="12"></td></tr>';
                tagGroups[tag].forEach(pos => {
                    html += renderPositionRow(pos, getTicker(pos), true);
                });
            });
        }

        html += `</tbody></table>`;
        document.getElementById('table-container').innerHTML = html;
    }

    function renderPositionRow(pos, ticker, showTicker) {
        const costBasis = Math.abs(pos.position * pos.avgCost);
        const daysToExpiry = getDaysToExpiry(pos);
        const pnlClass = pos.unrealizedPnl >= 0 ? 'pnl-positive' : 'pnl-negative';
        const openDateStr = currentOpenDates[pos.conid];
        const openDateDisplay = openDateStr || '-';

        let ageDays = null;
        let targetReturn = null;
        if (openDateStr) {
            const openD = new Date(openDateStr);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            openD.setHours(0, 0, 0, 0);
            ageDays = Math.floor((today - openD) / (1000 * 60 * 60 * 24));
            targetReturn = requiredReturnPercent(ageDays);
        }

        let liability = 0;
        let strikeBasis = 0;
        let roc = null;
        if (pos.assetClass === 'OPT') {
            const desc = pos.contractDesc.split('[')[0].trim();
            const parts = desc.split(/\s+/);
            if (parts.length >= 4) {
                const strike = parseFloat(parts[parts.length - 2]);
                const isPut = parts[parts.length - 1] === 'P';
                if (!isNaN(strike)) {
                    strikeBasis = Math.abs(pos.position * strike * 100);
                    // The "Liability" column still only displays for short Puts
                    if (pos.position < 0 && isPut) {
                        liability = strikeBasis;
                    }
                    
                    // Calculate ROC: (Premium Collected / Capital at Risk)
                    // Simplified: avgPrice / strike
                    if (strike > 0) {
                        roc = (pos.avgPrice / strike) * 100;
                    }
                }
            }
        }

        let pnlPercent = 0;
        if (pos.assetClass === 'OPT' && strikeBasis > 0) {
            pnlPercent = (pos.unrealizedPnl / strikeBasis) * 100;
        } else if (costBasis !== 0) {
            pnlPercent = (pos.unrealizedPnl / costBasis) * 100;
        }

        return `
            <tr>
                <td>
                    ${showTicker ? `<strong class="ticker-name" onclick="editTag('${ticker}')">${ticker}</strong>` : ''}
                    ${showTicker && currentTags[ticker] ? `<span class="tag-badge">${currentTags[ticker]}</span>` : ''}
                </td>
                <td>
                    ${pos.position} 
                    <small style="color: #888;">${pos.assetClass === 'OPT' ? '(OPT)' : ''}</small>
                    <div style="font-size: 0.75rem; color: #999;">${pos.contractDesc}</div>
                </td>
                <td>
                    <span class="open-date" onclick="editOpenDate('${pos.conid}', '${ticker}')">${openDateDisplay}</span>
                </td>
                <td>${ageDays !== null ? ageDays + 'd' : '-'}</td>
                <td>${targetReturn !== null ? formatPercent(targetReturn) : '-'}</td>
                <td>${roc !== null ? formatPercent(roc) : '-'}</td>
                <td>${daysToExpiry !== null ? daysToExpiry + 'd' : '-'}</td>
                <td>${pos.avgPrice.toFixed(2)}</td>
                <td>${pos.mktPrice.toFixed(2)}</td>
                <td>${formatCurrency(pos.mktValue)}</td>
                <td class="${pnlClass}">
                    <div>${formatCurrency(pos.unrealizedPnl)}</div>
                    <div style="font-size: 0.75rem;">${formatPercent(pnlPercent)}</div>
                </td>
                <td>${liability > 0 ? formatCurrency(liability) : '-'}</td>
            </tr>
        `;
    }

    // Initial fetch
    fetchPositions();

    let isReauthenticating = false;

    async function manualReauthenticate() {
        if (isReauthenticating) return;
        isReauthenticating = true;

        const authText = document.getElementById('auth-text');
        authText.innerText = 'Re-authenticating...';
        try {
            const response = await fetch('reauthenticate_proxy.php', { method: 'POST' });
            // Even if response is OK, we need to check if we are actually authenticated now
            await tickleSession(true); // Pass true to indicate this is a check after re-auth
            
            const isAuthenticated = document.getElementById('auth-dot').classList.contains('authenticated');
            if (!isAuthenticated) {
                // If still not authenticated after re-auth attempt, show Login link
                authText.innerHTML = 'Re-auth failed. (<a href="https://localhost:5050/" target="_blank">Login</a>)';
            }
        } catch (error) {
            console.error('Manual re-auth error:', error);
            authText.innerHTML = 'Re-auth failed. (<a href="https://localhost:5050/" target="_blank">Login</a>)';
        } finally {
            isReauthenticating = false;
        }
    }

    async function tickleSession(isAfterManualReauth = false) {
        // Only skip if we are in the middle of re-authenticating (unless this IS the check after re-auth)
        if (isReauthenticating && !isAfterManualReauth) return;

        const authDot = document.getElementById('auth-dot');
        const authText = document.getElementById('auth-text');
        
        try {
            const response = await fetch('tickle_proxy.php', {
                method: 'GET'
            });
            
            const data = await response.json();
            const isAuthenticated = data.iserver && data.iserver.authStatus && data.iserver.authStatus.authenticated === true;
            
            if (isAuthenticated) {
                authDot.className = 'status-dot authenticated';
                authText.innerText = 'Session Active';
            } else {
                authDot.className = 'status-dot unauthenticated';
                // Only show Re-authenticate if we haven't just tried it and failed, 
                // and if we aren't already showing a Login link.
                if (!isAfterManualReauth && !authText.innerHTML.includes('localhost:5050')) {
                    authText.innerHTML = 'Session Expired (<a href="#" onclick="manualReauthenticate(); return false;">Re-authenticate</a>)';
                }
            }
        } catch (error) {
            console.error('Tickle error:', error);
            authDot.className = 'status-dot unauthenticated';
            if (!isAfterManualReauth && !authText.innerHTML.includes('localhost:5050')) {
                authText.innerHTML = 'Connection Error (<a href="#" onclick="manualReauthenticate(); return false;">Re-authenticate</a>)';
            }
        }
    }

    function requiredReturnPercent(days, monthlyTargetPercent = 3) {
        const monthlyFactor = 1 + (monthlyTargetPercent / 100);
        const periodFactor = Math.pow(monthlyFactor, days / 30);
        return (periodFactor - 1) * 100;
    }

    // Tickle every 30 seconds
    tickleSession();
    setInterval(tickleSession, 30000);
</script>

</body>
</html>
