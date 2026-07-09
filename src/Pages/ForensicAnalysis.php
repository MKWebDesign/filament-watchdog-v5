<?php

namespace MKWebDesign\FilamentWatchdog\Pages;

use Filament\Pages\Page;
use MKWebDesign\FilamentWatchdog\Traits\ConfiguresWatchdogNavigation;

class ForensicAnalysis extends Page
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
        return 3;
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-magnifying-glass';

    protected string $view = 'filament-watchdog::pages.forensic-analysis';

    protected static string|\UnitEnum|null $navigationGroup = null; // Will be set in boot or we can just omit it and use the trait. But wait, we can't use __() in property definitions.

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'security/forensic-analysis';

    public static function getNavigationGroup(): ?string
    {
        return __('filament-watchdog-v5::messages.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-watchdog-v5::messages.page.forensic.title');
    }

    public function getTitle(): string
    {
        return __('filament-watchdog-v5::messages.page.forensic.title');
    }
}
