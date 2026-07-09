<?php

namespace MKWebDesign\FilamentWatchdog\Commands;

use Illuminate\Console\Command;
use MKWebDesign\FilamentWatchdog\Models\ActivityLog;
use MKWebDesign\FilamentWatchdog\Models\FileIntegrityCheck;
use MKWebDesign\FilamentWatchdog\Models\MalwareDetection;
use MKWebDesign\FilamentWatchdog\Models\SecurityAlert;

class CleanupLogsCommand extends Command
{
    protected $signature = 'watchdog:cleanup {--days=30 : Number of days to keep logs} {--force : Force cleanup without confirmation}';
    protected $description = null;

    public function __construct()
    {
        $this->description = __('filament-watchdog-v5::messages.command.cleanup.description');
        parent::__construct();
    }

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $retentionDays = $days ?: config('filament-watchdog.database.log_retention_days', 30);
        $cutoffDate = now()->subDays($retentionDays);

        $this->info(__('filament-watchdog-v5::messages.command.cleanup.cleaning_up', ['days' => $retentionDays]));

        if (!$this->option('force')) {
            if (!$this->confirm(__('filament-watchdog-v5::messages.command.cleanup.confirm'))) {
                $this->info(__('filament-watchdog-v5::messages.command.cleanup.cancelled'));
                return 0;
            }
        }

        try {
            $deletedActivity = ActivityLog::where('created_at', '<', $cutoffDate)->delete();
            $deletedIntegrity = FileIntegrityCheck::where('created_at', '<', $cutoffDate)
                ->where('status', '!=', 'modified') // Keep modified files
                ->delete();
            $deletedMalware = MalwareDetection::where('created_at', '<', $cutoffDate)
                ->where('status', 'cleaned') // Only delete cleaned malware
                ->delete();
            $deletedAlerts = SecurityAlert::where('created_at', '<', $cutoffDate)
                ->where('status', 'resolved') // Only delete resolved alerts
                ->delete();

            $this->info(__('filament-watchdog-v5::messages.command.cleanup.completed'));
            $this->line(__('filament-watchdog-v5::messages.command.cleanup.activity_logs', ['count' => $deletedActivity]));
            $this->line(__('filament-watchdog-v5::messages.command.cleanup.integrity_checks', ['count' => $deletedIntegrity]));
            $this->line(__('filament-watchdog-v5::messages.command.cleanup.malware_detections', ['count' => $deletedMalware]));
            $this->line(__('filament-watchdog-v5::messages.command.cleanup.security_alerts', ['count' => $deletedAlerts]));

            return 0;
        } catch (\Exception $e) {
            $this->error(__('filament-watchdog-v5::messages.command.cleanup.failed', ['error' => $e->getMessage()]));
            return 1;
        }
    }
}
