<?php

namespace MKWebDesign\FilamentWatchdog\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use MKWebDesign\FilamentWatchdog\Widgets\SecurityOverviewWidget;
use MKWebDesign\FilamentWatchdog\Widgets\ThreatLevelWidget;
use MKWebDesign\FilamentWatchdog\Widgets\RecentAlertsWidget;
use MKWebDesign\FilamentWatchdog\Models\SecurityAlert;
use MKWebDesign\FilamentWatchdog\Models\FileIntegrityCheck;
use MKWebDesign\FilamentWatchdog\Models\MalwareDetection;
use MKWebDesign\FilamentWatchdog\Services\FileIntegrityService;
use MKWebDesign\FilamentWatchdog\Services\MalwareDetectionService;
use MKWebDesign\FilamentWatchdog\Services\EmergencyLockdownService;
use Illuminate\Support\Facades\File;
use MKWebDesign\FilamentWatchdog\Traits\ConfiguresWatchdogNavigation;
use MKWebDesign\FilamentWatchdog\Services\SignatureUpdateService;
use MKWebDesign\FilamentWatchdog\Models\MalwareSignature;

class SecurityDashboard extends Page
{
    use ConfiguresWatchdogNavigation;

    protected static function getNavigationVisibility(): string
    {
        return 'always';
    }
    protected static function getDefaultSecuritySort(): int
    {
        return 1;
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';
    protected string $view = 'filament-watchdog::pages.security-dashboard';
    protected static string|\UnitEnum|null $navigationGroup = null;
    protected static ?int $navigationSort = 1;
    protected static ?string $slug = 'security/dashboard';

    public static function getNavigationGroup(): ?string
    {
        return __('filament-watchdog-v5::messages.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-watchdog-v5::messages.page.dashboard.title');
    }

    /**
     * Define the sort order for this security item
     * Lower numbers appear first in the menu
     */
    protected static function getSecurityItemSort(): int
    {
        return 1; // Dashboard should be first in the Security menu
    }

    protected function getHeaderActions(): array
    {
        $lockdownService = app(EmergencyLockdownService::class);
        $isLockdownActive = $lockdownService->isLockdownActive();

        return [
            Action::make('runScan')
                ->label(__('filament-watchdog-v5::messages.page.dashboard.actions.run_scan.label'))
                ->icon('heroicon-o-magnifying-glass')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading(__('filament-watchdog-v5::messages.page.dashboard.actions.run_scan.modal_heading'))
                ->modalDescription(__('filament-watchdog-v5::messages.page.dashboard.actions.run_scan.modal_description'))
                ->modalSubmitActionLabel(__('filament-watchdog-v5::messages.page.dashboard.actions.run_scan.submit_label'))
                ->action(function () {
                    try {
                        $fileIntegrityService = app(FileIntegrityService::class);
                        $malwareDetectionService = app(MalwareDetectionService::class);

                        $changes = $fileIntegrityService->scanForChanges();
                        $malwareDetections = $malwareDetectionService->scanUploads();

                        $changeCount = count($changes);
                        $malwareCount = count($malwareDetections);

                        Notification::make()
                            ->title(__('filament-watchdog-v5::messages.page.dashboard.notifications.scan_completed.title'))
                            ->body(__('filament-watchdog-v5::messages.page.dashboard.notifications.scan_completed.body', ['changes' => $changeCount, 'malware' => $malwareCount]))
                            ->success()
                            ->send();

                        $this->redirect(static::getUrl());
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('filament-watchdog-v5::messages.page.dashboard.notifications.scan_failed.title'))
                            ->body(__('filament-watchdog-v5::messages.page.dashboard.notifications.error_prefix') . ' ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('createBaseline')
                ->label(__('filament-watchdog-v5::messages.page.dashboard.actions.create_baseline.label'))
                ->icon('heroicon-o-document-duplicate')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('filament-watchdog-v5::messages.page.dashboard.actions.create_baseline.modal_heading'))
                ->modalDescription(__('filament-watchdog-v5::messages.page.dashboard.actions.create_baseline.modal_description'))
                ->modalSubmitActionLabel(__('filament-watchdog-v5::messages.page.dashboard.actions.create_baseline.submit_label'))
                ->action(function () {
                    try {
                        $fileIntegrityService = app(FileIntegrityService::class);
                        $fileIntegrityService->createBaseline();

                        $totalFiles = FileIntegrityCheck::count();

                        Notification::make()
                            ->title(__('filament-watchdog-v5::messages.page.dashboard.notifications.baseline_created.title'))
                            ->body(__('filament-watchdog-v5::messages.page.dashboard.notifications.baseline_created.body', ['files' => $totalFiles]))
                            ->success()
                            ->send();

                        $this->redirect(static::getUrl());
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('filament-watchdog-v5::messages.page.dashboard.notifications.baseline_failed.title'))
                            ->body(__('filament-watchdog-v5::messages.page.dashboard.notifications.error_prefix') . ' ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('updateSignatures')
                ->label(__('filament-watchdog-v5::messages.page.dashboard.actions.update_signatures.label'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading(__('filament-watchdog-v5::messages.page.dashboard.actions.update_signatures.modal_heading'))
                ->modalDescription(__('filament-watchdog-v5::messages.page.dashboard.actions.update_signatures.modal_description'))
                ->modalSubmitActionLabel(__('filament-watchdog-v5::messages.page.dashboard.actions.update_signatures.submit_label'))
                ->action(function () {
                    try {
                        $service = app(SignatureUpdateService::class);
                        $result = $service->update();

                        if ($result['success']) {
                            Notification::make()
                                ->title(__('filament-watchdog-v5::messages.page.dashboard.notifications.signatures_updated.title'))
                                ->body($result['message'] . ' (' . __('filament-watchdog-v5::messages.page.dashboard.notifications.signatures_updated.version') . ': ' . $result['version'] . '). ' . __('filament-watchdog-v5::messages.page.dashboard.notifications.signatures_updated.total_active') . ': ' . $service->getSignatureCount())
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title(__('filament-watchdog-v5::messages.page.dashboard.notifications.signatures_failed.title'))
                                ->body(__('filament-watchdog-v5::messages.page.dashboard.notifications.signatures_failed.body') . ' ' . $result['message'])
                                ->danger()
                                ->send();
                        }

                        $this->redirect(static::getUrl());
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('filament-watchdog-v5::messages.page.dashboard.notifications.signatures_failed.title'))
                            ->body(__('filament-watchdog-v5::messages.page.dashboard.notifications.error_prefix') . ' ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('viewQuarantine')
                ->label(__('filament-watchdog-v5::messages.page.dashboard.actions.view_quarantine.label'))
                ->icon('heroicon-o-archive-box')
                ->color('warning')
                ->action(function () {
                    $quarantinePath = config('filament-watchdog.malware_detection.quarantine_path');

                    if (!$quarantinePath) {
                        $quarantinePath = storage_path('app/quarantine');
                    }

                    $quarantineExists = File::exists($quarantinePath);

                    if ($quarantineExists) {
                        $files = File::files($quarantinePath);
                        $fileCount = count($files);

                        if ($fileCount > 0) {
                            $fileList = collect($files)->take(5)->map(function ($file) {
                                return basename($file);
                            })->join(', ');

                            Notification::make()
                                ->title(__('filament-watchdog-v5::messages.page.dashboard.notifications.quarantine_status.title'))
                                ->body(__('filament-watchdog-v5::messages.page.dashboard.notifications.quarantine_status.found', ['count' => $fileCount, 'files' => $fileList, 'more' => ($fileCount > 5 ? __('filament-watchdog-v5::messages.page.dashboard.notifications.quarantine_status.and_more', ['count' => $fileCount - 5]) : '')]))
                                ->warning()
                                ->send();
                        } else {
                            Notification::make()
                                ->title(__('filament-watchdog-v5::messages.page.dashboard.notifications.quarantine_empty.title'))
                                ->body(__('filament-watchdog-v5::messages.page.dashboard.notifications.quarantine_empty.body'))
                                ->success()
                                ->send();
                        }
                    } else {
                        Notification::make()
                            ->title(__('filament-watchdog-v5::messages.page.dashboard.notifications.quarantine_missing.title'))
                            ->body(__('filament-watchdog-v5::messages.page.dashboard.notifications.quarantine_missing.body') . ' ' . $quarantinePath)
                            ->info()
                            ->send();
                    }
                }),

            // Enhanced Emergency Lockdown Action
            Action::make($isLockdownActive ? 'deactivateLockdown' : 'emergencyLockdown')
                ->label($isLockdownActive ? __('filament-watchdog-v5::messages.page.dashboard.actions.lockdown.deactivate_label') : __('filament-watchdog-v5::messages.page.dashboard.actions.lockdown.activate_label'))
                ->icon($isLockdownActive ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                ->color($isLockdownActive ? 'success' : 'danger')
                ->requiresConfirmation()
                ->modalHeading($isLockdownActive ? __('filament-watchdog-v5::messages.page.dashboard.actions.lockdown.deactivate_heading') : __('filament-watchdog-v5::messages.page.dashboard.actions.lockdown.activate_heading'))
                ->modalDescription($isLockdownActive ?
                    __('filament-watchdog-v5::messages.page.dashboard.actions.lockdown.deactivate_description') :
                    new \Illuminate\Support\HtmlString(__('filament-watchdog-v5::messages.page.dashboard.actions.lockdown.activate_description'))
                )
                ->modalSubmitActionLabel($isLockdownActive ? __('filament-watchdog-v5::messages.page.dashboard.actions.lockdown.deactivate_submit') : __('filament-watchdog-v5::messages.page.dashboard.actions.lockdown.activate_submit'))
                ->action(function () use ($lockdownService, $isLockdownActive) {
                    try {
                        if ($isLockdownActive) {
                            // Deactivate lockdown
                            $results = $lockdownService->deactivateEmergencyLockdown();

                            if ($results['status'] === 'success') {
                                Notification::make()
                                    ->title(__('filament-watchdog-v5::messages.page.dashboard.notifications.lockdown_deactivated.title'))
                                    ->body(__('filament-watchdog-v5::messages.page.dashboard.notifications.lockdown_deactivated.body', ['users' => ($results['users_restored'] ?? 0)]))
                                    ->success()
                                    ->persistent()
                                    ->send();
                            } else {
                                throw new \Exception($results['error'] ?? 'Unknown error during deactivation');
                            }
                        } else {
                            // Activate lockdown with default options
                            $results = $lockdownService->activateEmergencyLockdown([
                                'maintenance_mode' => true,
                                'block_ips' => true,
                                'disable_users' => false, // Keep users active but they cant access due to maintenance
                                'clear_sessions' => true,
                                'htaccess_protection' => true,
                                'notify_admins' => true,
                                'emergency_backup' => true
                            ]);

                            if ($results['status'] === 'success') {
                                $accessUrl = $lockdownService->getEmergencyAccessUrl();
                                
                                $backupStatus = $results['emergency_backup'] ? 
                                    __('filament-watchdog-v5::messages.page.dashboard.notifications.lockdown_activated.backup_created') : 
                                    __('filament-watchdog-v5::messages.page.dashboard.notifications.lockdown_activated.backup_failed');

                                Notification::make()
                                    ->title(__('filament-watchdog-v5::messages.page.dashboard.notifications.lockdown_activated.title'))
                                    ->body(__('filament-watchdog-v5::messages.page.dashboard.notifications.lockdown_activated.body', [
                                        'alert_id' => $results['alert_id'],
                                        'url' => $accessUrl,
                                        'admins' => ($results['admin_notifications'] ?? 0),
                                        'ips' => count($results['blocked_ips'] ?? []),
                                        'backup' => $backupStatus
                                    ]))
                                    ->danger()
                                    ->persistent()
                                    ->send();
                            } else {
                                throw new \Exception($results['error'] ?? 'Unknown error during activation');
                            }
                        }

                        $this->redirect(static::getUrl());
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title($isLockdownActive ? __('filament-watchdog-v5::messages.page.dashboard.notifications.deactivation_failed.title') : __('filament-watchdog-v5::messages.page.dashboard.notifications.activation_failed.title'))
                            ->body(__('filament-watchdog-v5::messages.page.dashboard.notifications.error_prefix') . ' ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('lockdownStatus')
                ->label(__('filament-watchdog-v5::messages.page.dashboard.actions.lockdown_status.label'))
                ->icon('heroicon-o-information-circle')
                ->color('gray')
                ->visible($isLockdownActive)
                ->action(function () use ($lockdownService) {
                    $status = $lockdownService->getLockdownStatus();
                    $accessUrl = $lockdownService->getEmergencyAccessUrl();

                    if ($status) {
                        Notification::make()
                            ->title(__('filament-watchdog-v5::messages.page.dashboard.notifications.lockdown_status.title'))
                            ->body(__('filament-watchdog-v5::messages.page.dashboard.notifications.lockdown_status.body', [
                                'id' => $status['lockdown_id'],
                                'by' => $status['activated_by'],
                                'at' => $status['activated_at']->format('Y-m-d H:i:s'),
                                'url' => $accessUrl
                            ]))
                            ->info()
                            ->persistent()
                            ->send();
                    }
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SecurityOverviewWidget::class,
            ThreatLevelWidget::class,
            RecentAlertsWidget::class,
        ];
    }

    public function getTitle(): string
    {
        return __('filament-watchdog-v5::messages.page.dashboard.title');
    }

    public function getHeading(): string
    {
        $lockdownService = app(EmergencyLockdownService::class);
        $isLockdownActive = $lockdownService->isLockdownActive();

        if ($isLockdownActive) {
            return __('filament-watchdog-v5::messages.page.dashboard.heading_lockdown');
        }

        return __('filament-watchdog-v5::messages.page.dashboard.title');
    }

    protected function getViewData(): array
    {
        $lockdownService = app(EmergencyLockdownService::class);

        return [
            'systemStatus' => [
                'fileMonitoring' => config('filament-watchdog.monitoring.enabled', true),
                'malwareDetection' => config('filament-watchdog.malware_detection.enabled', true),
                'activityMonitoring' => config('filament-watchdog.activity_monitoring.enabled', true),
                'alertSystem' => config('filament-watchdog.alerts.enabled', true),
                'emergencyLockdown' => $lockdownService->isLockdownActive(),
            ],
            'stats' => [
                'totalFiles' => FileIntegrityCheck::count(),
                'modifiedFiles' => FileIntegrityCheck::where('status', 'modified')->count(),
                'malwareDetections' => MalwareDetection::count(),
                'unresolvedAlerts' => SecurityAlert::whereIn('status', ['new', 'acknowledged'])->count(),
            ],
            'lockdownStatus' => $lockdownService->getLockdownStatus(),
        ];
    }
}
