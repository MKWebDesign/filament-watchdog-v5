<?php

namespace MKWebDesign\FilamentWatchdog\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class CreateBaselineCommand extends Command
{
    protected $signature = 'watchdog:baseline';
    protected $description = null;

    public function __construct()
    {
        $this->description = __('filament-watchdog-v5::messages.command.baseline.description');
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info(__('filament-watchdog-v5::messages.command.baseline.creating'));

        try {
            // Call the scan command with baseline option
            Artisan::call('watchdog:scan', ['--baseline' => true]);

            $this->info(__('filament-watchdog-v5::messages.command.baseline.success'));
            return 0;
        } catch (\Exception $e) {
            $this->error(__('filament-watchdog-v5::messages.command.baseline.failed', ['error' => $e->getMessage()]));
            return 1;
        }
    }
}
