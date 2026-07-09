<?php

namespace MKWebDesign\FilamentWatchdog\Widgets;

use Filament\Widgets\Widget;
use MKWebDesign\FilamentWatchdog\Models\SecurityAlert;
use MKWebDesign\FilamentWatchdog\Models\MalwareDetection;

class ThreatLevelWidget extends Widget
{
    protected string $view = 'filament-watchdog::widgets.threat-level';
    protected int | string | array $columnSpan = 1;
    protected static ?int $sort = 2;

    protected function getViewData(): array
    {
        // Calculate threat level based on recent alerts
        $criticalAlerts = SecurityAlert::where('severity', 'critical')
            ->where('status', 'new')
            ->count();

        $highAlerts = SecurityAlert::where('severity', 'high')
            ->where('status', 'new')
            ->count();

        $recentMalware = MalwareDetection::where('created_at', '>=', now()->subDay())
            ->where('status', 'detected')
            ->count();

        // Determine threat level
        if ($criticalAlerts > 0 || $recentMalware > 0) {
            $threatLevel = __('filament-watchdog-v5::messages.widget.threat_level.critical.level');
            $color = 'red';
            $description = __('filament-watchdog-v5::messages.widget.threat_level.critical.desc');
        } elseif ($highAlerts > 0) {
            $threatLevel = __('filament-watchdog-v5::messages.widget.threat_level.high.level');
            $color = 'orange';
            $description = __('filament-watchdog-v5::messages.widget.threat_level.high.desc');
        } elseif ($highAlerts > 0 || SecurityAlert::where('status', 'new')->count() > 0) {
            $threatLevel = __('filament-watchdog-v5::messages.widget.threat_level.medium.level');
            $color = 'yellow';
            $description = __('filament-watchdog-v5::messages.widget.threat_level.medium.desc');
        } else {
            $threatLevel = __('filament-watchdog-v5::messages.widget.threat_level.low.level');
            $color = 'green';
            $description = __('filament-watchdog-v5::messages.widget.threat_level.low.desc');
        }

        return [
            'threat_level' => $threatLevel,
            'color' => $color,
            'description' => $description,
            'critical_count' => $criticalAlerts,
            'high_count' => $highAlerts,
            'malware_count' => $recentMalware,
        ];
    }
}
