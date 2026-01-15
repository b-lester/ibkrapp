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
    <div id="status"></div>
    <div id="table-container">
        <div class="loading">Loading positions...</div>
    </div>
</div>

<script>
    async function fetchPositions() {
        try {
            const response = await fetch('list_positions.php');
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            renderPositions(data.positions);
        } catch (error) {
            console.error('Fetch error:', error);
            document.getElementById('table-container').innerHTML = `
                <div class="error">
                    <strong>Error:</strong> ${error.message}
                    ${error.message.includes('authenticated') ? '<br><br><a href="https://localhost:5050/" target="_blank">Click here to log in to IBKR Gateway</a>' : ''}
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

    function formatCurrency(value) {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
    }

    function formatPercent(value) {
        return value.toFixed(2) + '%';
    }

    function renderPositions(positions) {
        if (!positions || positions.length === 0) {
            document.getElementById('table-container').innerHTML = '<div class="loading">No positions found.</div>';
            return;
        }

        // Group by ticker
        const groups = {};
        positions.forEach(pos => {
            const ticker = getTicker(pos);
            if (!groups[ticker]) groups[ticker] = [];
            groups[ticker].push(pos);
        });

        // Sort tickers alphabetically
        const tickers = Object.keys(groups).sort();

        let html = `
            <table>
                <thead>
                    <tr>
                        <th>Ticker</th>
                        <th>Position</th>
                        <th>Last</th>
                        <th>Value</th>
                        <th>Liability</th>
                    </tr>
                </thead>
                <tbody>
        `;

        tickers.forEach(ticker => {
            const group = groups[ticker];
            let groupPnL = 0;
            let groupExposure = 0;

            group.forEach((pos, index) => {
                groupPnL += pos.unrealizedPnl;

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
                
                if (pos.assetClass === 'STK') {
                    groupExposure += pos.mktValue;
                }
                groupExposure += liability;

                html += `
                    <tr>
                        <td>${index === 0 ? `<strong>${ticker}</strong>` : ''}</td>
                        <td>
                            ${pos.position} 
                            <small style="color: #888;">${pos.assetClass === 'OPT' ? '(OPT)' : ''}</small>
                            <div style="font-size: 0.75rem; color: #999;">${pos.contractDesc}</div>
                        </td>
                        <td>${pos.mktPrice.toFixed(2)}</td>
                        <td>${formatCurrency(pos.mktValue)}</td>
                        <td>${liability > 0 ? formatCurrency(liability) : '-'}</td>
                    </tr>
                `;
            });

            html += `
                <tr class="summary-row">
                    <td colspan="5" class="summary-cell">
                        <span class="pos-value">Exposure: ${formatCurrency(groupExposure)}</span>
                    </td>
                </tr>
            `;
        });

        html += `
                </tbody>
            </table>
        `;

        document.getElementById('table-container').innerHTML = html;
    }

    // Initial fetch
    fetchPositions();

    async function tickleSession() {
        const authDot = document.getElementById('auth-dot');
        const authText = document.getElementById('auth-text');
        
        try {
            // Using a proxy script to avoid CORS issues
            const response = await fetch('tickle_proxy.php', {
                method: 'GET'
            });
            
            // If CORS is an issue, we might need a small PHP helper. 
            // But following instructions to call the endpoint directly:
            const data = await response.json();
            
            const isAuthenticated = data.iserver && data.iserver.authStatus && data.iserver.authStatus.authenticated === true;
            
            if (isAuthenticated) {
                authDot.className = 'status-dot authenticated';
                authText.innerText = 'Session Active';
            } else {
                authDot.className = 'status-dot unauthenticated';
                authText.innerText = 'Session Expired';
            }
        } catch (error) {
            console.error('Tickle error:', error);
            authDot.className = 'status-dot unauthenticated';
            authText.innerText = 'Connection Error';
        }
    }

    // Tickle every 30 seconds
    tickleSession();
    setInterval(tickleSession, 30000);
</script>

</body>
</html>
