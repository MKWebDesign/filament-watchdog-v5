<?php

namespace MKWebDesign\FilamentWatchdog\Commands;

use Illuminate\Console\Command;
use MKWebDesign\FilamentWatchdog\Services\SignatureUpdateService;

class UpdateSignaturesCommand extends Command
{
    protected $signature = 'watchdog:update-signatures';

    protected $description = 'Update malware signatures from the remote signatures database';

    public function handle(SignatureUpdateService $service): int
    {
        $this->info('Updating malware signatures...');

        $result = $service->update();

        if ($result['success']) {
            $this->info("✅ {$result['message']} (version: {$result['version']})");
            $this->info('Total active signatures: ' . $service->getSignatureCount());
            return self::SUCCESS;
        }

        $this->error('❌ Signature update failed: ' . $result['message']);
        $this->warn('Falling back to built-in signatures.');
        return self::FAILURE;
    }
}
