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
    protected static function getDefaultSecuritySort(): int
    {
        return 3;
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-magnifying-glass';
    protected string $view = 'filament-watchdog::pages.forensic-analysis';
    protected static string|\UnitEnum|null $navigationGroup = 'Security';
    protected static ?int $navigationSort = 3;
    protected static ?string $title = 'Forensic Analysis';
    protected static ?string $slug = 'security/forensic-analysis';
}
