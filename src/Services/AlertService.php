<?php

namespace MKWebDesign\FilamentWatchdog\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use MKWebDesign\FilamentWatchdog\Mail\SecurityAlertMail;
use MKWebDesign\FilamentWatchdog\Models\SecurityAlert;

class AlertService
{
    private const SEVERITY_LEVELS = [
        'low'      => 1,
        'medium'   => 2,
        'high'     => 3,
        'critical' => 4,
    ];

    public function createAlert(
        string $alertType,
        string $title,
        string $description,
        string $severity = 'medium',
        array $metadata = []
    ): SecurityAlert {
        $alert = SecurityAlert::create([
            'alert_type'  => $alertType,
            'title'       => $title,
            'description' => $description,
            'severity'    => $severity,
            'status'      => 'new',
            'metadata'    => $metadata,
        ]);

        if (config('filament-watchdog.alerts.enabled') && $this->meetsThreshold($severity)) {
            $this->sendEmailAlert($alert);
        }

        return $alert;
    }

    private function meetsThreshold(string $severity): bool
    {
        $minSeverity = config('filament-watchdog.alerts.min_severity', 'medium');
        $alertLevel = self::SEVERITY_LEVELS[$severity] ?? 0;
        $minLevel = self::SEVERITY_LEVELS[$minSeverity] ?? 2;

        return $alertLevel >= $minLevel;
    }

    private function sendEmailAlert(SecurityAlert $alert): void
    {
        if (! config('filament-watchdog.alerts.email_enabled')) {
            return;
        }

        if ($this->isRateLimited()) {
            Log::warning('FilamentWatchdog: Alert rate limit reached, skipping email for: ' . $alert->title);
            return;
        }

        $recipients = config('filament-watchdog.alerts.email_recipients', []);

        if (empty($recipients)) {
            return;
        }

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient)->send(new SecurityAlertMail($alert));
            } catch (\Exception $e) {
                Log::error('FilamentWatchdog: Failed to send alert email to ' . $recipient . ': ' . $e->getMessage());
            }
        }

        $this->incrementRateLimit();
    }

    private function isRateLimited(): bool
    {
        if (! config('filament-watchdog.alerts.rate_limiting.enabled')) {
            return false;
        }

        $maxAlerts = config('filament-watchdog.alerts.rate_limiting.max_alerts_per_hour', 10);

        return Cache::get('watchdog_alerts_sent', 0) >= $maxAlerts;
    }

    private function incrementRateLimit(): void
    {
        $current = Cache::get('watchdog_alerts_sent', 0);
        Cache::put('watchdog_alerts_sent', $current + 1, now()->addHour());
    }

    public function acknowledgeAlert(int $alertId, string $acknowledgedBy): bool
    {
        $alert = SecurityAlert::find($alertId);

        if (! $alert) {
            return false;
        }

        $alert->update([
            'status'           => 'acknowledged',
            'acknowledged_at'  => now(),
            'acknowledged_by'  => $acknowledgedBy,
        ]);

        return true;
    }

    public function resolveAlert(int $alertId, string $resolvedBy): bool
    {
        $alert = SecurityAlert::find($alertId);

        if (! $alert) {
            return false;
        }

        $alert->update([
            'status'      => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $resolvedBy,
        ]);

        return true;
    }

    public function getAlertStats(): array
    {
        return [
            'total_alerts'        => SecurityAlert::count(),
            'new_alerts'          => SecurityAlert::where('status', 'new')->count(),
            'acknowledged_alerts' => SecurityAlert::where('status', 'acknowledged')->count(),
            'resolved_alerts'     => SecurityAlert::where('status', 'resolved')->count(),
            'critical_alerts'     => SecurityAlert::where('severity', 'critical')->count(),
            'recent_alerts'       => SecurityAlert::where('created_at', '>=', now()->subDay())->count(),
        ];
    }
}
