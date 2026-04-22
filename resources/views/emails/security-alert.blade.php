<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Alert</title>
    <style>
        body { margin: 0; padding: 0; background: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .header { padding: 32px 40px; background: {{ $headerBg }}; }
        .header-icon { font-size: 32px; margin-bottom: 12px; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 700; color: #ffffff; }
        .header p { margin: 6px 0 0; font-size: 14px; color: rgba(255,255,255,0.85); }
        .severity-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; letter-spacing: 0.5px; background: rgba(255,255,255,0.2); color: #ffffff; margin-top: 12px; }
        .body { padding: 32px 40px; }
        .field { margin-bottom: 20px; }
        .field-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; color: #94a3b8; margin-bottom: 4px; }
        .field-value { font-size: 15px; color: #1e293b; line-height: 1.5; }
        .metadata { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 16px; margin-top: 8px; }
        .metadata pre { margin: 0; font-family: 'Courier New', monospace; font-size: 12px; color: #475569; white-space: pre-wrap; word-break: break-all; }
        .divider { border: none; border-top: 1px solid #e2e8f0; margin: 24px 0; }
        .footer { padding: 20px 40px 32px; }
        .footer p { margin: 0; font-size: 13px; color: #94a3b8; line-height: 1.6; }
        .footer a { color: #6366f1; text-decoration: none; }
        .action-btn { display: inline-block; margin-top: 24px; padding: 12px 24px; background: {{ $headerBg }}; color: #ffffff; border-radius: 6px; font-size: 14px; font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrapper">

    <div class="header">
        <div class="header-icon">🛡️</div>
        <h1>Security Alert</h1>
        <p>{{ config('app.name') }} — FilamentWatchdog</p>
        <span class="severity-badge">{{ strtoupper($alert->severity) }}</span>
    </div>

    <div class="body">

        <div class="field">
            <div class="field-label">Alert</div>
            <div class="field-value" style="font-size:18px;font-weight:600;color:#0f172a;">{{ $alert->title }}</div>
        </div>

        <div class="field">
            <div class="field-label">Description</div>
            <div class="field-value">{{ $alert->description }}</div>
        </div>

        <div class="field">
            <div class="field-label">Alert Type</div>
            <div class="field-value">{{ str_replace('_', ' ', ucfirst($alert->alert_type)) }}</div>
        </div>

        <div class="field">
            <div class="field-label">Detected at</div>
            <div class="field-value">{{ $alert->created_at->format('D, d M Y — H:i:s T') }}</div>
        </div>

        @if(!empty($alert->metadata))
            <div class="field">
                <div class="field-label">Details</div>
                <div class="metadata">
                    <pre>{{ json_encode($alert->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
        @endif

        @if($dashboardUrl)
            <a href="{{ $dashboardUrl }}" class="action-btn">View Security Dashboard</a>
        @endif

    </div>

    <hr class="divider">

    <div class="footer">
        <p>
            This is an automated alert from <strong>FilamentWatchdog</strong>.<br>
            To adjust alert settings, update your <code>config/filament-watchdog.php</code>.<br>
            To stop receiving these emails, set <code>alerts.email_enabled</code> to <code>false</code>.
        </p>
    </div>

</div>
</body>
</html>
