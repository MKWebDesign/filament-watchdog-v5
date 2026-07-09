<?php

namespace MKWebDesign\FilamentWatchdog\Commands;

use Illuminate\Console\Command;
use MKWebDesign\FilamentWatchdog\Services\SignatureUpdateService;

class UpdateSignaturesCommand extends Command
{
    protected $signature = 'watchdog:update-signatures';

    protected $description = null;

    public function __construct()
    {
        $this->description = __('filament-watchdog-v5::messages.command.update.description');
        parent::__construct();
    }

    public function handle(SignatureUpdateService $service): int
    {
        $this->info(__('filament-watchdog-v5::messages.command.update.updating'));

        $result = $service->update();

        if ($result['success']) {
            $this->info(__('filament-watchdog-v5::messages.command.update.success', ['message' => $result['message'], 'version' => $result['version']]));
            $this->info(__('filament-watchdog-v5::messages.command.update.total_active', ['count' => $service->getSignatureCount()]));
            return self::SUCCESS;
        }

        $this->error(__('filament-watchdog-v5::messages.command.update.failed', ['message' => $result['message']]));
        $this->warn(__('filament-watchdog-v5::messages.command.update.fallback'));
        return self::FAILURE;
    }
}
