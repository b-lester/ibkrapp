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
            padding: 20px;
            color: #333;
        }
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .auth-status {
            display: flex;
            align-items: center;
            font-size: 0.8rem;
            color: #666;
        }
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 6px;
            background-color: #999;
        }
        .status-dot.authenticated { background-color: #27ae60; }
        .status-dot.unauthenticated { background-color: #e74c3c; }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #2c3e50;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        th {
            background-color: #f8f9fa;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            color: #666;
        }
        .group-header {
            background-color: #f1f8ff;
            font-weight: bold;
        }
        .summary-row {
            background-color: #fafafa;
            font-size: 0.9rem;
            color: #555;
        }
        .summary-cell {
            text-align: right;
            padding-right: 20px;
            font-style: italic;
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
            padding: 20px;
            font-style: italic;
            color: #999;
        }
        .error {
            color: #e74c3c;
            text-align: center;
            padding: 20px;
            border: 1px solid #e74c3c;
            border-radius: 4px;
            background-color: #fdf2f2;
            margin-top: 20px;
        }
        .account-summary {
            margin-bottom: 30px;
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        .account-summary table {
            margin-top: 0;
        }
        .tag-input {
            width: 80px;
            padding: 4px;
            font-size: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .tag-badge {
            background-color: #e1f5fe;
            color: #0288d1;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 5px;
        }
        .tag-spacer {
            height: 40px;
        }
        .tag-spacer td {
            border: none;
            background-color: transparent;
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

    <div id="status"></div>
    <div id="table-container">
        <div class="loading">Loading positions...</div>
    </div>
</div>

<script>
    let currentTags = {};
    let currentNLV = 0;

    async function fetchPositions() {
        try {
            const [posRes, cashRes, tagsRes] = await Promise.all([
                fetch('list_positions.php'),
                fetch('list_cash.php'),
                fetch('tags.php')
            ]);

            if (!posRes.ok) throw new Error(`Positions fetch failed: ${posRes.status}`);
            if (!cashRes.ok) throw new Error(`Cash fetch failed: ${cashRes.status}`);
            if (!tagsRes.ok) throw new Error(`Tags fetch failed: ${tagsRes.status}`);

            const posData = await posRes.json();
            const cashData = await cashRes.json();
            currentTags = await tagsRes.json();

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
        if (positions) {
            positions.forEach(pos => {
                const ticker = getTicker(pos);
                const tag = currentTags[ticker] || '';
                if (tag !== 'safe') {
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
                        <th>Total Exposure</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>${formatCurrency(netLiquidation)}</strong></td>
                        <td><strong>${formatCurrency(positionsValue)}</strong></td>
                        <td>${formatCurrency(cashBalance)}</td>
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

    function renderPositions(positions) {
        if (!positions || positions.length === 0) {
            document.getElementById('table-container').innerHTML = '<div class="loading">No positions found.</div>';
            return;
        }

        // 1. Group by ticker
        const tickerGroups = {};
        positions.forEach(pos => {
            const ticker = getTicker(pos);
            if (!tickerGroups[ticker]) tickerGroups[ticker] = [];
            tickerGroups[ticker].push(pos);
        });

        // 2. Group ticker groups by tag
        const tagGroups = {};
        Object.keys(tickerGroups).forEach(ticker => {
            const tag = currentTags[ticker] || '';
            if (!tagGroups[tag]) tagGroups[tag] = [];
            tagGroups[tag].push(ticker);
        });

        // 3. Sort tags: empty first, then alphabetically
        const sortedTags = Object.keys(tagGroups).sort((a, b) => {
            if (a === '') return -1;
            if (b === '') return 1;
            return a.localeCompare(b);
        });

        let html = `
            <table>
                <thead>
                    <tr>
                        <th>Ticker</th>
                        <th>Position</th>
                        <th>Expires</th>
                        <th>Last</th>
                        <th>Value</th>
                        <th>PnL</th>
                        <th>Liability</th>
                    </tr>
                </thead>
                <tbody>
        `;

        sortedTags.forEach((tag, tagIndex) => {
            // Add spacing between tag groups
            if (tagIndex > 0) {
                html += '<tr class="tag-spacer"><td colspan="7"></td></tr>';
            }

            const tickersInTag = tagGroups[tag].sort();
            tickersInTag.forEach(ticker => {
                const group = tickerGroups[ticker];
                let groupPnL = 0;
                let groupExposure = 0;
                let groupCostBasis = 0;

                group.forEach((pos, index) => {
                    groupPnL += pos.unrealizedPnl;
                    const posExposure = calculatePositionExposure(pos);
                    groupExposure += posExposure;
                    const costBasis = Math.abs(pos.position * pos.avgCost);
                    groupCostBasis += costBasis;
                    const daysToExpiry = getDaysToExpiry(pos);

                    const pnlPercent = costBasis !== 0 ? (pos.unrealizedPnl / costBasis) * 100 : 0;
                    const pnlClass = pos.unrealizedPnl >= 0 ? 'pnl-positive' : 'pnl-negative';

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

                    html += `
                        <tr>
                            <td>
                                ${index === 0 ? `<strong>${ticker}</strong>` : ''}
                                ${index === 0 && currentTags[ticker] ? `<span class="tag-badge">${currentTags[ticker]}</span>` : ''}
                                ${index === 0 ? `
                                    <div style="margin-top: 4px;">
                                        <input type="text" class="tag-input" 
                                            placeholder="Add tag..." 
                                            value="${currentTags[ticker] || ''}" 
                                            onblur="saveTag('${ticker}', this.value)"
                                            onkeydown="if(event.key==='Enter') saveTag('${ticker}', this.value)">
                                    </div>
                                ` : ''}
                            </td>
                            <td>
                                ${pos.position} 
                                <small style="color: #888;">${pos.assetClass === 'OPT' ? '(OPT)' : ''}</small>
                                <div style="font-size: 0.75rem; color: #999;">${pos.contractDesc}</div>
                            </td>
                            <td>${daysToExpiry !== null ? daysToExpiry + 'd' : '-'}</td>
                            <td>${pos.mktPrice.toFixed(2)}</td>
                            <td>${formatCurrency(pos.mktValue)}</td>
                            <td class="${pnlClass}">
                                <div>${formatCurrency(pos.unrealizedPnl)}</div>
                                <div style="font-size: 0.75rem;">${formatPercent(pnlPercent)}</div>
                            </td>
                            <td>${liability > 0 ? formatCurrency(liability) : '-'}</td>
                        </tr>
                    `;
                });

                const groupPnlPercent = groupCostBasis !== 0 ? (groupPnL / groupCostBasis) * 100 : 0;
                const groupPnlClass = groupPnL >= 0 ? 'pnl-positive' : 'pnl-negative';

                html += `
                    <tr class="summary-row">
                        <td colspan="7" class="summary-cell">
                            <div class="pos-value">
                                Total PnL: <span class="${groupPnlClass}">${formatCurrency(groupPnL)} (${formatPercent(groupPnlPercent)})</span>
                            </div>
                            <div class="pos-value" style="margin-top: 4px;">Exposure: ${formatCurrency(groupExposure)}</div>
                            <div style="font-size: 0.75rem; color: #666; margin-top: 2px;">
                                ${currentNLV ? formatPercent((groupExposure / currentNLV) * 100) : '0.00%'} of NLV
                            </div>
                        </td>
                    </tr>
                `;
            });
        });

        html += `
                </tbody>
            </table>
        `;

        document.getElementById('table-container').innerHTML = html;
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

    // Tickle every 30 seconds
    tickleSession();
    setInterval(tickleSession, 30000);
</script>

</body>
</html>
