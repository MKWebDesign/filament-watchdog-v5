<?php

namespace MKWebDesign\FilamentWatchdog\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use MKWebDesign\FilamentWatchdog\Models\MalwareSignature;

class SignatureUpdateService
{
    private const SIGNATURES_URL = 'https://raw.githubusercontent.com/MKWebDesign/filament-watchdog-v5/main/signatures.json';

    public function update(): array
    {
        $url = config('filament-watchdog.malware_detection.signatures_url', self::SIGNATURES_URL);

        try {
            $response = Http::timeout(30)->get($url);

            if (! $response->successful()) {
                Log::warning('FilamentWatchdog: Failed to fetch signatures, HTTP ' . $response->status());
                return ['success' => false, 'message' => 'HTTP ' . $response->status(), 'updated' => 0];
            }

            $data = $response->json();

            if (empty($data['signatures']) || ! is_array($data['signatures'])) {
                Log::warning('FilamentWatchdog: Invalid signatures format received');
                return ['success' => false, 'message' => 'Invalid format', 'updated' => 0];
            }

            $count = $this->storeSignatures($data['signatures'], $data['version'] ?? 'unknown');

            Log::info("FilamentWatchdog: Updated {$count} malware signatures (version: " . ($data['version'] ?? 'unknown') . ")");

            return [
                'success'  => true,
                'message'  => "Updated {$count} signatures",
                'updated'  => $count,
                'version'  => $data['version'] ?? 'unknown',
            ];
        } catch (\Exception $e) {
            Log::error('FilamentWatchdog: Signature update failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'updated' => 0];
        }
    }

    private function storeSignatures(array $signatures, string $version): int
    {
        $count = 0;

        foreach ($signatures as $signature) {
            if (empty($signature['name']) || empty($signature['pattern'])) {
                continue;
            }

            MalwareSignature::updateOrCreate(
                ['name' => $signature['name']],
                [
                    'pattern'    => $signature['pattern'],
                    'risk_level' => $signature['risk_level'] ?? 'medium',
                    'category'   => $signature['category'] ?? 'unknown',
                    'description' => $signature['description'] ?? null,
                    'source'     => 'github-v' . $version,
                    'active'     => true,
                ]
            );

            $count++;
        }

        return $count;
    }

    public function getSignatureCount(): int
    {
        return MalwareSignature::active()->count();
    }

    public function hasSignatures(): bool
    {
        return MalwareSignature::active()->exists();
    }
}
