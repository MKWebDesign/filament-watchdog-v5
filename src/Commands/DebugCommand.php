<?php

namespace MKWebDesign\FilamentWatchdog\Commands;

use Illuminate\Console\Command;
use MKWebDesign\FilamentWatchdog\Models\FileIntegrityCheck;
use MKWebDesign\FilamentWatchdog\Models\SecurityAlert;
use MKWebDesign\FilamentWatchdog\Models\MalwareDetection;
use MKWebDesign\FilamentWatchdog\Models\ActivityLog;

class DebugCommand extends Command
{
    protected $signature = 'watchdog:debug {--stats : Show database statistics} {--config : Show configuration} {--recent : Show recent activity}';
    protected $description = null;

    public function __construct()
    {
        $this->description = __('filament-watchdog-v5::messages.command.debug.description');
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info(__('filament-watchdog-v5::messages.command.debug.info_title'));
        $this->info(__('filament-watchdog-v5::messages.command.debug.info_divider'));

        if ($this->option('stats') || (!$this->option('config') && !$this->option('recent'))) {
            $this->showStats();
        }

        if ($this->option('config') || (!$this->option('stats') && !$this->option('recent'))) {
            $this->showConfig();
        }

        if ($this->option('recent') || (!$this->option('stats') && !$this->option('config'))) {
            $this->showRecent();
        }

        return 0;
    }

    private function showStats(): void
    {
        $this->info(__('filament-watchdog-v5::messages.command.debug.stats_title'));
        $this->info(__('filament-watchdog-v5::messages.command.debug.stats_divider'));

        $fileStats = [
            __('filament-watchdog-v5::messages.command.debug.total_files') => FileIntegrityCheck::count(),
            __('filament-watchdog-v5::messages.command.debug.clean_files') => FileIntegrityCheck::where('status', 'clean')->count(),
            __('filament-watchdog-v5::messages.command.debug.new_files') => FileIntegrityCheck::where('status', 'new')->count(),
            __('filament-watchdog-v5::messages.command.debug.modified_files') => FileIntegrityCheck::where('status', 'modified')->count(),
            __('filament-watchdog-v5::messages.command.debug.deleted_files') => FileIntegrityCheck::where('status', 'deleted')->count(),
        ];

        foreach ($fileStats as $label => $count) {
            $this->line(sprintf('%-20s: %d', $label, $count));
        }

        $this->newLine();

        $securityStats = [
            __('filament-watchdog-v5::messages.command.debug.total_alerts') => SecurityAlert::count(),
            __('filament-watchdog-v5::messages.command.debug.new_alerts') => SecurityAlert::where('status', 'new')->count(),
            __('filament-watchdog-v5::messages.command.debug.critical_alerts') => SecurityAlert::where('severity', 'critical')->count(),
            __('filament-watchdog-v5::messages.command.debug.malware_detections') => MalwareDetection::count(),
            __('filament-watchdog-v5::messages.command.debug.activity_logs') => ActivityLog::count(),
        ];

        foreach ($securityStats as $label => $count) {
            $this->line(sprintf('%-20s: %d', $label, $count));
        }

        $this->newLine();
    }

    private function showConfig(): void
    {
        $this->info(__('filament-watchdog-v5::messages.command.debug.config_title'));
        $this->info(__('filament-watchdog-v5::messages.command.debug.config_divider'));

        $config = [
            __('filament-watchdog-v5::messages.command.debug.monitoring_enabled') => config('filament-watchdog.monitoring.enabled') ? __('filament-watchdog-v5::messages.command.debug.yes') : __('filament-watchdog-v5::messages.command.debug.no'),
            __('filament-watchdog-v5::messages.command.debug.malware_enabled') => config('filament-watchdog.malware_detection.enabled') ? __('filament-watchdog-v5::messages.command.debug.yes') : __('filament-watchdog-v5::messages.command.debug.no'),
            __('filament-watchdog-v5::messages.command.debug.activity_enabled') => config('filament-watchdog.activity_monitoring.enabled') ? __('filament-watchdog-v5::messages.command.debug.yes') : __('filament-watchdog-v5::messages.command.debug.no'),
            __('filament-watchdog-v5::messages.command.debug.alerts_enabled') => config('filament-watchdog.alerts.enabled') ? __('filament-watchdog-v5::messages.command.debug.yes') : __('filament-watchdog-v5::messages.command.debug.no'),
            __('filament-watchdog-v5::messages.command.debug.scan_interval') => config('filament-watchdog.monitoring.scan_interval') . ' ' . __('filament-watchdog-v5::messages.command.debug.seconds'),
            __('filament-watchdog-v5::messages.command.debug.hash_algorithm') => config('filament-watchdog.file_integrity.hash_algorithm'),
            __('filament-watchdog-v5::messages.command.debug.max_file_size') => number_format(config('filament-watchdog.file_integrity.max_file_size') / 1024 / 1024, 2) . ' ' . __('filament-watchdog-v5::messages.command.debug.mb'),
        ];

        foreach ($config as $label => $value) {
            $this->line(sprintf('%-20s: %s', $label, $value));
        }

        $this->newLine();
        $this->info(__('filament-watchdog-v5::messages.command.debug.monitored_paths'));
        $paths = config('filament-watchdog.monitoring.monitored_paths', []);
        foreach ($paths as $path) {
            $this->line('  - ' . $path);
        }

        $this->newLine();
        $this->info(__('filament-watchdog-v5::messages.command.debug.excluded_paths'));
        $excluded = config('filament-watchdog.monitoring.excluded_paths', []);
        foreach ($excluded as $path) {
            $this->line('  - ' . $path);
        }

        $this->newLine();
    }

    private function showRecent(): void
    {
        $this->info(__('filament-watchdog-v5::messages.command.debug.recent_activity'));
        $this->info(__('filament-watchdog-v5::messages.command.debug.recent_activity_divider'));

        $this->info(__('filament-watchdog-v5::messages.command.debug.recent_file_changes'));
        $recentFiles = FileIntegrityCheck::where('updated_at', '>=', now()->subDay())
            ->whereIn('status', ['new', 'modified'])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        if ($recentFiles->count() > 0) {
            foreach ($recentFiles as $file) {
                $this->line(sprintf(
                    '  %s: %s (%s)',
                    $file->updated_at->format('H:i:s'),
                    $file->file_path,
                    strtoupper($file->status)
                ));
            }
        } else {
            $this->line(__('filament-watchdog-v5::messages.command.debug.no_recent_changes'));
        }

        $this->newLine();
        $this->info(__('filament-watchdog-v5::messages.command.debug.recent_alerts'));
        $recentAlerts = SecurityAlert::where('created_at', '>=', now()->subDay())
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($recentAlerts->count() > 0) {
            foreach ($recentAlerts as $alert) {
                $this->line(sprintf(
                    '  %s: %s [%s]',
                    $alert->created_at->format('H:i:s'),
                    $alert->title,
                    strtoupper($alert->severity)
                ));
            }
        } else {
            $this->line(__('filament-watchdog-v5::messages.command.debug.no_recent_alerts'));
        }

        $this->newLine();
        $this->info(__('filament-watchdog-v5::messages.command.debug.recent_malware'));
        $recentMalware = MalwareDetection::where('created_at', '>=', now()->subDay())
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($recentMalware->count() > 0) {
            foreach ($recentMalware as $malware) {
                $this->line(sprintf(
                    '  %s: %s (%s - %s)',
                    $malware->created_at->format('H:i:s'),
                    $malware->file_path,
                    $malware->threat_type,
                    strtoupper($malware->risk_level)
                ));
            }
        } else {
            $this->line(__('filament-watchdog-v5::messages.command.debug.no_recent_malware'));
        }

        $this->newLine();
    }
}
