<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribed — Uptime Kita</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f9fafb;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #1f2937;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .card {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 40px 32px;
            max-width: 440px;
            width: 90%;
            text-align: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid #e5e7eb;
        }
        .icon-circle {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background-color: #f3f4f6;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 24px;
        }
        h1 {
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 12px 0;
            color: #111827;
        }
        p {
            font-size: 14px;
            color: #6b7280;
            margin: 0 0 28px 0;
            line-height: 1.5;
        }
        .btn {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff;
            font-weight: 600;
            font-size: 14px;
            padding: 10px 24px;
            border-radius: 6px;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-circle">✉️</div>
        <h1>Unsubscribed Successfully</h1>
        <p>You have been unsubscribed from the weekly uptime summary report for <strong>{{ $user->email }}</strong>. You can re-enable it anytime from your account settings.</p>
        <a href="{{ url('/settings/notifications') }}" class="btn">Notification Settings</a>
    </div>
</body>
</html>
