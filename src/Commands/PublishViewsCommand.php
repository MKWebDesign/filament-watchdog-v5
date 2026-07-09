<?php

namespace MKWebDesign\FilamentWatchdog\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishViewsCommand extends Command
{
    protected $signature = 'watchdog:publish-views 
                            {--force : Overwrite existing views}
                            {--only-emergency : Only publish emergency views}
                            {--preview : Preview what will be published}';

    protected $description = null;

    public function __construct()
    {
        $this->description = __('filament-watchdog-v5::messages.command.publish.description');
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info(__('filament-watchdog-v5::messages.command.publish.publishing'));
        $this->newLine();

        $onlyEmergency = $this->option('only-emergency');
        $force = $this->option('force');
        $preview = $this->option('preview');

        if ($preview) {
            return $this->showPreview();
        }

        if ($onlyEmergency) {
            return $this->publishEmergencyViews($force);
        }

        // Publish all views
        $this->publishAllViews($force);
        $this->publishEmergencyViews($force);

        $this->displaySuccessMessage();

        return 0;
    }

    private function publishAllViews(bool $force): void
    {
        $this->info(__('filament-watchdog-v5::messages.command.publish.package_views'));

        $this->call('vendor:publish', [
            '--provider' => 'MKWebDesign\\FilamentWatchdog\\FilamentWatchdogServiceProvider',
            '--tag' => 'filament-watchdog-views',
            '--force' => $force,
        ]);

        $this->info(__('filament-watchdog-v5::messages.command.publish.package_views_success'));
    }

    private function publishEmergencyViews(bool $force): int
    {
        $this->info(__('filament-watchdog-v5::messages.command.publish.emergency_views'));

        $sourceDir = __DIR__ . '/../../resources/views/errors';
        $targetDir = resource_path('views/errors');
        $emergencyView = 'emergency-lockdown.blade.php';

        // Ensure target directory exists
        if (!File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
            $this->info(__('filament-watchdog-v5::messages.command.publish.created_dir', ['dir' => $targetDir]));
        }

        $sourcePath = $sourceDir . '/' . $emergencyView;
        $targetPath = $targetDir . '/' . $emergencyView;

        // Check if source exists
        if (!File::exists($sourcePath)) {
            $this->warn(__('filament-watchdog-v5::messages.command.publish.source_not_found'));
            return $this->createFallbackEmergencyView($targetPath, $force);
        }

        // Check if target exists and handle force option
        if (File::exists($targetPath) && !$force) {
            if (!$this->confirm(__('filament-watchdog-v5::messages.command.publish.confirm_overwrite'))) {
                $this->warn(__('filament-watchdog-v5::messages.command.publish.skipped', ['file' => $emergencyView]));
                return 0;
            }
        }

        // Copy the file
        try {
            File::copy($sourcePath, $targetPath);
            $this->info(__('filament-watchdog-v5::messages.command.publish.published', ['file' => $emergencyView]));

            // Verify the file was created correctly
            if (File::exists($targetPath)) {
                $size = File::size($targetPath);
                $this->info(__('filament-watchdog-v5::messages.command.publish.file_size', ['size' => number_format($size)]));
            }

            return 0;
        } catch (\Exception $e) {
            $this->error(__('filament-watchdog-v5::messages.command.publish.publish_failed', ['error' => $e->getMessage()]));
            return 1;
        }
    }

    private function createFallbackEmergencyView(string $targetPath, bool $force): int
    {
        if (File::exists($targetPath) && !$force) {
            if (!$this->confirm(__('filament-watchdog-v5::messages.command.publish.confirm_fallback'))) {
                $this->warn(__('filament-watchdog-v5::messages.command.publish.skipped_fallback'));
                return 0;
            }
        }

        try {
            $fallbackContent = $this->getFallbackEmergencyTemplate();
            File::put($targetPath, $fallbackContent);
            $this->info(__('filament-watchdog-v5::messages.command.publish.fallback_success'));

            $size = File::size($targetPath);
            $this->info(__('filament-watchdog-v5::messages.command.publish.file_size', ['size' => number_format($size)]));

            return 0;
        } catch (\Exception $e) {
            $this->error(__('filament-watchdog-v5::messages.command.publish.fallback_failed', ['error' => $e->getMessage()]));
            return 1;
        }
    }

    private function showPreview(): int
    {
        $this->info(__('filament-watchdog-v5::messages.command.publish.preview_title'));
        $this->newLine();

        $files = [
            'Emergency View' => 'resources/views/errors/emergency-lockdown.blade.php',
            'Package Views' => 'resources/views/vendor/filament-watchdog/',
            'Config File' => 'config/filament-watchdog.php (use --tag=filament-watchdog-config)',
        ];

        foreach ($files as $type => $path) {
            $status = __('filament-watchdog-v5::messages.command.publish.status_missing');
            if (File::exists(base_path($path))) {
                $status = __('filament-watchdog-v5::messages.command.publish.status_exists');
            }
            $this->line("  {$status} {$type}: {$path}");
        }

        $this->newLine();
        $this->info(__('filament-watchdog-v5::messages.command.publish.tip_force'));
        $this->info(__('filament-watchdog-v5::messages.command.publish.tip_only_emergency'));

        return 0;
    }

    private function displaySuccessMessage(): void
    {
        $this->newLine();
        $this->info(__('filament-watchdog-v5::messages.command.publish.success_all'));
        $this->newLine();

        $this->info(__('filament-watchdog-v5::messages.command.publish.published_files'));
        $this->line('  - resources/views/errors/emergency-lockdown.blade.php');
        $this->line('  - resources/views/vendor/filament-watchdog/');

        $this->newLine();
        $this->info(__('filament-watchdog-v5::messages.command.publish.customization'));
        $this->line(__('filament-watchdog-v5::messages.command.publish.edit_emergency'));
        $this->line(__('filament-watchdog-v5::messages.command.publish.configure_colors'));
        $this->line(__('filament-watchdog-v5::messages.command.publish.publish_config'));

        $this->newLine();
        $this->info(__('filament-watchdog-v5::messages.command.publish.test_emergency'));
        $this->line(__('filament-watchdog-v5::messages.command.publish.test_activate'));
        $this->line(__('filament-watchdog-v5::messages.command.publish.test_deactivate'));

        $this->newLine();
        $this->info(__('filament-watchdog-v5::messages.command.publish.dont_forget'));
    }

    private function getFallbackEmergencyTemplate(): string
    {
        return '{{-- 
    FilamentWatchdog Emergency Lockdown Maintenance Page (Fallback)
    Auto-generated by: php artisan watchdog:publish-views
    
    Customize this view or publish the full template with:
    php artisan vendor:publish --tag=filament-watchdog-errors
--}}
<!DOCTYPE html>
<html lang="{{ str_replace(\'_\', \'-\', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚨 Emergency Security Lockdown - {{ config(\'app.name\', \'FilamentWatchdog\') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: \'Inter\', -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            padding: 20px;
        }
        .container {
            text-align: center;
            max-width: 700px;
            background: rgba(255, 255, 255, 0.1);
            padding: 50px 40px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .icon {
            font-size: 5rem;
            margin-bottom: 25px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            font-weight: 700;
        }
        .subtitle {
            font-size: 1.1rem;
            line-height: 1.6;
            opacity: 0.9;
            margin-bottom: 30px;
        }
        .status {
            background: rgba(255, 255, 255, 0.1);
            padding: 25px;
            border-radius: 15px;
            margin: 30px 0;
            text-align: left;
        }
        .status h3 {
            color: #ffd700;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        .status ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .status li {
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .status li:last-child {
            border-bottom: none;
        }
        .admin-info {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            padding: 20px;
            border-radius: 12px;
            margin: 30px 0;
        }
        .admin-info h3 {
            color: #ffd700;
            margin-bottom: 10px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 0.9rem;
            opacity: 0.8;
        }
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            background: linear-gradient(45deg, #ffd700, #ff6b6b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        @media (max-width: 768px) {
            .container {
                padding: 30px 20px;
            }
            h1 {
                font-size: 2rem;
            }
            .icon {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🚨</div>
        <h1>Emergency Security Lockdown</h1>
        <p class="subtitle">
            Our security system has temporarily restricted access to protect your data. 
            We are working to resolve this situation as quickly as possible.
        </p>

        <div class="status">
            <h3>🛡️ Security Measures Active</h3>
            <ul>
                <li>🔒 Site access restricted to authorized administrators</li>
                <li>🧹 All user sessions have been cleared</li>
                <li>💾 Emergency backup created and secured</li>
                <li>📧 Administrators have been notified</li>
                <li>🔍 Security analysis in progress</li>
            </ul>
        </div>

        <div class="admin-info">
            <h3>👨‍💻 Administrator Access</h3>
            <p>
                If you are a system administrator, check your email for the emergency access link 
                or use the secret key provided during lockdown activation.
            </p>
        </div>

        <div class="footer">
            <div class="logo">🐕 FilamentWatchdog</div>
            <p>Advanced Security Monitoring & Protection</p>
            <p><strong>Expected resolution:</strong> Within 1-2 hours</p>
            <p style="margin-top: 15px;">
                <strong>Lockdown activated:</strong> <span id="lockdown-time"></span>
            </p>
        </div>
    </div>

    <script>
        // Set lockdown time
        document.getElementById(\'lockdown-time\').textContent = new Date().toLocaleString();
        
        // Auto-refresh every 5 minutes to check if lockdown is lifted
        setTimeout(function() {
            window.location.reload();
        }, 300000);
    </script>
</body>
</html>';
    }
}