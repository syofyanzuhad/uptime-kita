<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Weekly Uptime Report</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f3f4f6;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #1f2937;
            -webkit-text-size-adjust: 100%;
        }
        .container {
            max-width: 680px;
            margin: 30px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .header {
            background-color: #111827;
            color: #ffffff;
            padding: 28px 32px;
            text-align: left;
        }
        .header h1 {
            margin: 0 0 6px 0;
            font-size: 22px;
            font-weight: 700;
        }
        .header p {
            margin: 0;
            font-size: 14px;
            color: #9ca3af;
        }
        .content {
            padding: 32px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 24px;
            color: #374151;
        }
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 28px;
        }
        .stat-card {
            display: table-cell;
            width: 33.33%;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 16px;
            text-align: center;
        }
        .stat-card:not(:last-child) {
            margin-right: 12px;
        }
        .stat-number {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            margin-top: 4px;
        }
        .stat-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
            font-weight: 600;
        }
        .callout-box {
            padding: 16px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            background-color: #f9fafb;
        }
        .callout-best {
            border-left: 4px solid #10b981;
            background-color: #ecfdf5;
        }
        .callout-worst {
            border-left: 4px solid #ef4444;
            background-color: #fef2f2;
        }
        .callout-title {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .callout-best .callout-title {
            color: #065f46;
        }
        .callout-worst .callout-title {
            color: #991b1b;
        }
        .callout-desc {
            font-size: 13px;
            color: #4b5563;
            margin: 0;
        }
        .table-container {
            width: 100%;
            margin: 28px 0;
            border-collapse: collapse;
        }
        .table-container th {
            background-color: #f9fafb;
            color: #4b5563;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            padding: 12px 14px;
            border-bottom: 2px solid #e5e7eb;
            text-align: left;
        }
        .table-container td {
            padding: 12px 14px;
            font-size: 14px;
            border-bottom: 1px solid #f3f4f6;
            color: #1f2937;
        }
        .table-container tr:hover td {
            background-color: #f9fafb;
        }
        .trend-up {
            color: #10b981;
            font-weight: bold;
        }
        .trend-down {
            color: #ef4444;
            font-weight: bold;
        }
        .trend-same {
            color: #6b7280;
            font-weight: bold;
        }
        .status-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
        }
        .status-up { background-color: #10b981; }
        .status-down { background-color: #ef4444; }
        .btn-container {
            text-align: center;
            margin: 32px 0 20px 0;
        }
        .btn {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff !important;
            font-weight: 600;
            font-size: 14px;
            padding: 12px 28px;
            border-radius: 6px;
            text-decoration: none;
        }
        .footer {
            border-top: 1px solid #e5e7eb;
            padding: 24px 32px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            background-color: #f9fafb;
        }
        .footer a {
            color: #6b7280;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Your Weekly Uptime Report</h1>
            <p>Week of {{ $period ?? now()->startOfWeek()->format('M d, Y') }}</p>
        </div>

        <div class="content">
            <p class="greeting">
                Hello <strong>{{ $user->name ?? 'there' }}</strong>, here is your fleet uptime performance summary for the past week:
            </p>

            <table class="stats-grid" cellpadding="0" cellspacing="8">
                <tr>
                    <td class="stat-card">
                        <div class="stat-label">Fleet Health</div>
                        <div class="stat-number">{{ $fleetTotals['up_monitors'] ?? 0 }} / {{ $fleetTotals['total_monitors'] ?? count($summaries) }} Up</div>
                    </td>
                    <td class="stat-card">
                        <div class="stat-label">Average Uptime</div>
                        <div class="stat-number">{{ number_format($fleetTotals['average_uptime'] ?? 100, 2) }}%</div>
                    </td>
                    <td class="stat-card">
                        <div class="stat-label">Total Downtime</div>
                        <div class="stat-number">{{ $fleetTotals['total_downtime_mins'] ?? 0 }}m</div>
                    </td>
                </tr>
            </table>

            @if(!empty($fleetTotals['best_performer']))
                <div class="callout-box callout-best">
                    <div class="callout-title">🏆 Best performer of the week</div>
                    <p class="callout-desc">
                        <strong>{{ $fleetTotals['best_performer']['display_name'] }}</strong> maintained
                        <strong>{{ number_format($fleetTotals['best_performer']['uptime_7d'], 2) }}%</strong> uptime
                        with an average response time of <strong>{{ $fleetTotals['best_performer']['avg_response_ms'] }}ms</strong>.
                    </p>
                </div>
            @endif

            @if(!empty($fleetTotals['worst_performer']) && (($fleetTotals['worst_performer']['uptime_7d'] < 100) || ($fleetTotals['worst_performer']['total_downtime_mins'] > 0)))
                <div class="callout-box callout-worst">
                    <div class="callout-title">⚠️ Worst performer of the week</div>
                    <p class="callout-desc">
                        <strong>{{ $fleetTotals['worst_performer']['display_name'] }}</strong> experienced
                        <strong>{{ $fleetTotals['worst_performer']['total_downtime_mins'] }} mins</strong> of downtime
                        across <strong>{{ $fleetTotals['worst_performer']['incidents_7d'] }} incident(s)</strong>
                        ({{ number_format($fleetTotals['worst_performer']['uptime_7d'], 2) }}% uptime).
                    </p>
                </div>
            @endif

            <table class="table-container">
                <thead>
                    <tr>
                        <th>Monitor Name</th>
                        <th style="text-align: right;">Uptime %</th>
                        <th style="text-align: right;">Avg ms</th>
                        <th style="text-align: right;">Incidents</th>
                        <th style="text-align: center;">Trend</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summaries as $summary)
                        <tr>
                            <td>
                                <span class="status-dot {{ ($summary['is_up_now'] ?? true) ? 'status-up' : 'status-down' }}"></span>
                                <a href="{{ $summary['monitor_url'] ?? url('/monitors/' . $summary['monitor_id']) }}" style="color: #2563eb; text-decoration: none; font-weight: 500;">
                                    {{ $summary['display_name'] }}
                                </a>
                            </td>
                            <td style="text-align: right; font-weight: 600;">
                                {{ number_format($summary['uptime_7d'] ?? 100, 2) }}%
                            </td>
                            <td style="text-align: right;">
                                {{ $summary['avg_response_ms'] ?? 0 }}ms
                            </td>
                            <td style="text-align: right;">
                                {{ $summary['incidents_7d'] ?? 0 }}
                            </td>
                            <td style="text-align: center;">
                                @php
                                    $diff = round(($summary['uptime_7d'] ?? 100) - ($summary['uptime_7d_prev'] ?? ($summary['uptime_7d'] ?? 100)), 2);
                                @endphp
                                @if($diff > 0)
                                    <span class="trend-up" title="Improved by +{{ $diff }}%">↑</span>
                                @elseif($diff < 0)
                                    <span class="trend-down" title="Dropped by {{ $diff }}%">↓</span>
                                @else
                                    <span class="trend-same" title="Unchanged">=</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="btn-container">
                <a href="{{ $dashboardUrl ?? url('/dashboard') }}" class="btn">Go to Dashboard</a>
            </div>
        </div>

        <div class="footer">
            <p style="margin: 0 0 8px 0;">
                You are receiving this automated weekly report because weekly summaries are enabled in your account.
            </p>
            <p style="margin: 0;">
                <a href="{{ $unsubscribeUrl }}">Unsubscribe from weekly reports</a> &bull;
                <a href="{{ url('/settings/notifications') }}">Manage notification preferences</a>
            </p>
        </div>
    </div>
</body>
</html>
