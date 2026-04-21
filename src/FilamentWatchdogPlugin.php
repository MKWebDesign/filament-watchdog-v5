<?php

namespace MKWebDesign\FilamentWatchdog;

use Filament\Contracts\Plugin;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use MKWebDesign\FilamentWatchdog\Pages\ForensicAnalysis;
use MKWebDesign\FilamentWatchdog\Pages\SecurityDashboard;
use MKWebDesign\FilamentWatchdog\Pages\ThreatTimeline;
use MKWebDesign\FilamentWatchdog\Resources\ActivityLogResource;
use MKWebDesign\FilamentWatchdog\Resources\FileIntegrityResource;
use MKWebDesign\FilamentWatchdog\Resources\MalwareDetectionResource;
use MKWebDesign\FilamentWatchdog\Resources\SecurityAlertResource;
use MKWebDesign\FilamentWatchdog\Widgets\RecentAlertsWidget;
use MKWebDesign\FilamentWatchdog\Widgets\SecurityOverviewWidget;
use MKWebDesign\FilamentWatchdog\Widgets\ThreatLevelWidget;

class FilamentWatchdogPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'filament-watchdog';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->pages([
                SecurityDashboard::class,
                ThreatTimeline::class,
                ForensicAnalysis::class,
            ])
            ->resources([
                FileIntegrityResource::class,
                MalwareDetectionResource::class,
                ActivityLogResource::class,
                SecurityAlertResource::class,
            ])
            ->widgets([
                SecurityOverviewWidget::class,
                ThreatLevelWidget::class,
                RecentAlertsWidget::class,
            ]);

        // In Filament v5 the Panel is available directly in register(),
        // so no Filament::serving() callback is needed.
        $this->registerSecurityNavigationGroup($panel);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Register the Security navigation group based on config settings.
     */
    protected function registerSecurityNavigationGroup(Panel $panel): void
    {
        $navigationConfig = config('filament-watchdog.navigation.security', []);

        // Don't register anything if completely hidden
        if ($navigationConfig['hidden'] ?? false) {
            return;
        }

        $collapsed = $navigationConfig['collapsed'] ?? true;

        // Avoid duplicate registration: check the groups already registered on this panel.
        // Note: Panel::navigationGroups() merges internally, so we must NOT spread
        // existing groups ourselves — just pass the new group and let Filament merge it.
        $alreadyRegistered = collect($panel->getNavigationGroups())->contains(
            fn ($group) => $group instanceof NavigationGroup && $group->getLabel() === 'Security'
        );

        if ($alreadyRegistered) {
            return;
        }

        $panel->navigationGroups([
            NavigationGroup::make('Security')
                ->collapsed($collapsed)
                ->collapsible(true),
        ]);
    }
}