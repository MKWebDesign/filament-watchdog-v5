<?php

namespace MKWebDesign\FilamentWatchdog\Commands;

use Illuminate\Console\Command;
use MKWebDesign\FilamentWatchdog\Services\EmergencyLockdownService;

class EmergencyLockdownCommand extends Command
{
    protected $signature = 'watchdog:emergency-lockdown 
                            {action : activate or deactivate}
                            {--maintenance-mode=1 : Enable maintenance mode (1 or 0)}
                            {--block-ips=1 : Block suspicious IPs (1 or 0)}
                            {--disable-users=0 : Disable non-admin users (1 or 0)}
                            {--clear-sessions=1 : Clear user sessions (1 or 0)}
                            {--htaccess-protection=1 : Add htaccess protection (1 or 0)}
                            {--notify-admins=1 : Notify administrators (1 or 0)}
                            {--emergency-backup=1 : Create emergency backup (1 or 0)}
                            {--force : Skip confirmation}';

    protected $description = null;

    public function __construct()
    {
        $this->description = __('filament-watchdog-v5::messages.command.emergency.description');
        parent::__construct();
    }

    public function handle(EmergencyLockdownService $lockdownService): int
    {
        $action = $this->argument('action');

        if (!in_array($action, ['activate', 'deactivate'])) {
        $this->error(__('filament-watchdog-v5::messages.command.emergency.action_error'));
            return 1;
        }

        if ($action === 'activate') {
        return $this->activateLockdown($lockdownService);
    } else {
        return $this->deactivateLockdown($lockdownService);
    }
    }

    private function activateLockdown(EmergencyLockdownService $lockdownService): int
    {
        if ($lockdownService->isLockdownActive()) {
            $this->warn(__('filament-watchdog-v5::messages.command.emergency.already_active'));
            
            $status = $lockdownService->getLockdownStatus();
            if ($status) {
                $this->info(__('filament-watchdog-v5::messages.command.emergency.lockdown_id', ['id' => $status['lockdown_id']]));
                $this->info(__('filament-watchdog-v5::messages.command.emergency.activated_by', ['by' => $status['activated_by']]));
                $this->info(__('filament-watchdog-v5::messages.command.emergency.activated_at', ['at' => $status['activated_at']->format('Y-m-d H:i:s')]));
            }
            
            return 0;
        }

        $this->warn(__('filament-watchdog-v5::messages.command.emergency.warning'));
        $this->warn(__('filament-watchdog-v5::messages.command.emergency.warning_desc'));

        if (!$this->option('force') && !$this->confirm(__('filament-watchdog-v5::messages.command.emergency.confirm_activate'))) {
        $this->info(__('filament-watchdog-v5::messages.command.emergency.activate_cancelled'));
            return 0;
        }

        $options = [
            'maintenance_mode' => (bool) $this->option('maintenance-mode'),
            'block_ips' => (bool) $this->option('block-ips'),
            'disable_users' => (bool) $this->option('disable-users'),
            'clear_sessions' => (bool) $this->option('clear-sessions'),
            'htaccess_protection' => (bool) $this->option('htaccess-protection'),
            'notify_admins' => (bool) $this->option('notify-admins'),
            'emergency_backup' => (bool) $this->option('emergency-backup'),
        ];

        $this->info(__('filament-watchdog-v5::messages.command.emergency.activating'));

        $results = $lockdownService->activateEmergencyLockdown($options);

        if ($results['status'] === 'success') {
        $this->info(__('filament-watchdog-v5::messages.command.emergency.activate_success'));
            $this->info(__('filament-watchdog-v5::messages.command.emergency.alert_id', ['id' => $results['alert_id']]));
            
            if (isset($results['blocked_ips']) && count($results['blocked_ips']) > 0) {
            $this->info(__('filament-watchdog-v5::messages.command.emergency.blocked_ips', ['ips' => implode(', ', $results['blocked_ips'])]));
            }
            
            $accessUrl = $lockdownService->getEmergencyAccessUrl();
            if ($accessUrl) {
                $this->warn(__('filament-watchdog-v5::messages.command.emergency.access_url', ['url' => $accessUrl]));
                $this->warn(__('filament-watchdog-v5::messages.command.emergency.access_url_desc'));
            }
            
            return 0;
        } else {
        $this->error(__('filament-watchdog-v5::messages.command.emergency.activate_failed', ['error' => $results['error'] ?? 'Unknown error']));
            return 1;
        }
    }

    private function deactivateLockdown(EmergencyLockdownService $lockdownService): int
    {
        if (!$lockdownService->isLockdownActive()) {
            $this->warn(__('filament-watchdog-v5::messages.command.emergency.not_active'));
            return 0;
        }

        $this->info(__('filament-watchdog-v5::messages.command.emergency.deactivating'));

        if (!$this->option('force') && !$this->confirm(__('filament-watchdog-v5::messages.command.emergency.confirm_deactivate'))) {
        $this->info(__('filament-watchdog-v5::messages.command.emergency.deactivate_cancelled'));
            return 0;
        }

        $results = $lockdownService->deactivateEmergencyLockdown();

        if ($results['status'] === 'success') {
        $this->info(__('filament-watchdog-v5::messages.command.emergency.deactivate_success'));
            $this->info(__('filament-watchdog-v5::messages.command.emergency.maintenance_disabled', ['status' => $results['maintenance_disabled'] ? __('filament-watchdog-v5::messages.command.debug.yes') : __('filament-watchdog-v5::messages.command.debug.no')]));
            $this->info(__('filament-watchdog-v5::messages.command.emergency.users_restored', ['count' => $results['users_restored'] ?? 0]));
            $this->info(__('filament-watchdog-v5::messages.command.emergency.normal_operations'));
            return 0;
        } else {
        $this->error(__('filament-watchdog-v5::messages.command.emergency.deactivate_failed', ['error' => $results['error'] ?? 'Unknown error']));
            return 1;
        }
    }
}
