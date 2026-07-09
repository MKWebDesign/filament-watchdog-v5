<?php

namespace MKWebDesign\FilamentWatchdog\Pages;

use Filament\Pages\Page;
use MKWebDesign\FilamentWatchdog\Traits\ConfiguresWatchdogNavigation;

class ThreatTimeline extends Page
{
    use ConfiguresWatchdogNavigation;

    protected static function getNavigationVisibility(): string
    {
        return 'conditional';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static function getDefaultSecuritySort(): int
    {
        return 2;
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected string $view = 'filament-watchdog::pages.threat-timeline';

    protected static string|\UnitEnum|null $navigationGroup = null; // Overridden by getNavigationGroup

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'security/threat-timeline';

    public static function getNavigationGroup(): ?string
    {
        return __('filament-watchdog-v5::messages.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-watchdog-v5::messages.page.timeline.title');
    }

    public function getTitle(): string
    {
        return __('filament-watchdog-v5::messages.page.timeline.title');
    }
}
