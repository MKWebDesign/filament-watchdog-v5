<?php

namespace MKWebDesign\FilamentWatchdog;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use MKWebDesign\FilamentWatchdog\Commands\CleanupLogsCommand;
use MKWebDesign\FilamentWatchdog\Commands\CreateBaselineCommand;
use MKWebDesign\FilamentWatchdog\Commands\DebugCommand;
use MKWebDesign\FilamentWatchdog\Commands\EmergencyLockdownCommand;
use MKWebDesign\FilamentWatchdog\Commands\PublishViewsCommand;
use MKWebDesign\FilamentWatchdog\Commands\ScanFilesCommand;
use MKWebDesign\FilamentWatchdog\Commands\UpdateSignaturesCommand;
use MKWebDesign\FilamentWatchdog\Services\ActivityMonitoringService;
use MKWebDesign\FilamentWatchdog\Services\AlertService;
use MKWebDesign\FilamentWatchdog\Services\EmergencyLockdownService;
use MKWebDesign\FilamentWatchdog\Services\FileIntegrityService;
use MKWebDesign\FilamentWatchdog\Services\MalwareDetectionService;

class FilamentWatchdogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/filament-watchdog.php', 'filament-watchdog');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'filament-watchdog-v5');

        // Register services as singletons
        $this->app->singleton(FileIntegrityService::class);
        $this->app->singleton(MalwareDetectionService::class);
        $this->app->singleton(ActivityMonitoringService::class);
        $this->app->singleton(AlertService::class);
        $this->app->singleton(EmergencyLockdownService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-watchdog');

        // Publish config
        $this->publishes([
            __DIR__.'/../config/filament-watchdog.php' => config_path('filament-watchdog.php'),
        ], 'filament-watchdog-config');

        // Publish package views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/filament-watchdog'),
        ], 'filament-watchdog-views');

        // Publish emergency error views directly to Laravel's error views
        $this->publishes([
            __DIR__.'/../resources/views/errors' => resource_path('views/errors'),
        ], 'filament-watchdog-errors');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                UpdateSignaturesCommand::class,
                ScanFilesCommand::class,
                CreateBaselineCommand::class,
                CleanupLogsCommand::class,
                EmergencyLockdownCommand::class,
                PublishViewsCommand::class,
                DebugCommand::class,
            ]);
        }

        // Auto-publish emergency views on installation (if enabled in config)
        if (config('filament-watchdog.emergency.auto_publish_views', true)) {
            $this->autoPublishEmergencyViews();
        }

        // Register scheduled commands
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Run scan every minute if enabled
            if (config('filament-watchdog.monitoring.enabled', true)) {
                $schedule->command('watchdog:scan')->everyMinute();
            }

            // Cleanup logs daily
            $schedule->command('watchdog:cleanup', ['--force'])->daily();
            $schedule->command('watchdog:update-signatures')->weekly();

        });
    }

    /**
     * Automatically publish emergency views during package installation
     */
    private function autoPublishEmergencyViews(): void
    {
        // Only auto-publish if views don't exist yet
        $errorViewsPath = resource_path('views/errors');
        $emergencyViewPath = $errorViewsPath.'/emergency-lockdown.blade.php';

        if (! File::exists($emergencyViewPath)) {
            try {
                // Ensure errors directory exists
                if (! File::exists($errorViewsPath)) {
                    File::makeDirectory($errorViewsPath, 0755, true);
                }

                // Copy emergency view template from package
                $sourceView = __DIR__.'/../resources/views/errors/emergency-lockdown.blade.php';

                if (File::exists($sourceView)) {
                    File::copy($sourceView, $emergencyViewPath);

                    // Log successful auto-publishing
                    if (function_exists('info')) {
                        info('FilamentWatchdog: Emergency maintenance view auto-published successfully');
                    }
                } else {
                    // Fallback: create basic emergency view
                    $this->createFallbackEmergencyView($emergencyViewPath);
                }
            } catch (\Exception $e) {
                // Silent fail - don't break installation
                if (function_exists('error_log')) {
                    error_log('FilamentWatchdog: Failed to auto-publish emergency view: '.$e->getMessage());
                }
            }
        }
    }

    /**
     * Create fallback emergency view if package view is not available
     */
    private function createFallbackEmergencyView(string $targetPath): void
    {
        $emergencyContent = $this->getBasicEmergencyTemplate();
        File::put($targetPath, $emergencyContent);
    }

    /**
     * Get basic emergency template as fallback
     */
    private function getBasicEmergencyTemplate(): string
    {
        return '<!DOCTYPE html>
<html lang="{{ str_replace(\'_\', \'-\', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __("filament-watchdog-v5::messages.service.emergency.view.title") }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            overflow: hidden;
        }

        .stars {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .star {
            position: absolute;
            width: 2px;
            height: 2px;
            background: white;
            border-radius: 50%;
            animation: twinkle 2s infinite;
        }

        @keyframes twinkle {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 1; }
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            text-align: center;
            position: relative;
            z-index: 10;
        }

        .lockdown-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            animation: pulse 2s infinite;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .main-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            line-height: 1.2;
        }

        .subtitle {
            font-size: 1.25rem;
            font-weight: 400;
            margin-bottom: 2rem;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .status-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .status-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #ffd700;
        }

        .status-list {
            list-style: none;
            text-align: left;
            max-width: 500px;
            margin: 0 auto;
            margin-bottom: 1rem;
        }

        .status-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
        }

        .status-list li:last-child {
            border-bottom: none;
        }

        .status-icon {
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }

        .admin-section {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .admin-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #ffd700;
        }

        .admin-text {
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .timeline {
            margin-top: 2rem;
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .footer {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 0.9rem;
            opacity: 0.7;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(45deg, #ffd700, #ff6b6b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        @media (max-width: 768px) {
            .main-title {
                font-size: 2rem;
            }
            
            .lockdown-icon {
                font-size: 3rem;
            }
            
            .container {
                padding: 1rem;
            }
            
            .status-card {
                padding: 1.5rem;
            }
        }

        .security-pattern {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0.03;
            background-image: 
                radial-gradient(circle at 25% 25%, #fff 2px, transparent 2px),
                radial-gradient(circle at 75% 75%, #fff 2px, transparent 2px);
            background-size: 50px 50px;
            animation: drift 20s linear infinite;
        }

        @keyframes drift {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }
    </style>
</head>
<body>
    <div class="stars" id="stars"></div>
    <div class="security-pattern"></div>
    
    <div class="container">
        <div class="lockdown-icon">🚨</div>
        
        <h1 class="main-title">{{ __("filament-watchdog-v5::messages.service.emergency.view.main_title") }}</h1>
        
        <p class="subtitle">
            {{ __("filament-watchdog-v5::messages.service.emergency.view.subtitle") }}
        </p>

        <div class="status-card">
            <h2 class="status-title">{{ __("filament-watchdog-v5::messages.service.emergency.view.status_title") }}</h2>
            <ul class="status-list">
                <li>
                    <span class="status-icon">🔒</span>
                    <span>{{ __("filament-watchdog-v5::messages.service.emergency.view.status_1") }}</span>
                </li>
                <li>
                    <span class="status-icon">🧹</span>
                    <span>{{ __("filament-watchdog-v5::messages.service.emergency.view.status_2") }}</span>
                </li>
                <li>
                    <span class="status-icon">💾</span>
                    <span>{{ __("filament-watchdog-v5::messages.service.emergency.view.status_3") }}</span>
                </li>
                <li>
                    <span class="status-icon">📧</span>
                    <span>{{ __("filament-watchdog-v5::messages.service.emergency.view.status_4") }}</span>
                </li>
                <li>
                    <span class="status-icon">🔍</span>
                    <span>{{ __("filament-watchdog-v5::messages.service.emergency.view.status_5") }}</span>
                </li>
            </ul>
        </div>

        <div class="admin-section">
            <h3 class="admin-title">{{ __("filament-watchdog-v5::messages.service.emergency.view.admin_title") }}</h3>
            <p class="admin-text">
                {{ __("filament-watchdog-v5::messages.service.emergency.view.admin_text") }}
            </p>
        </div>

        <div class="timeline">
            <p><strong>{{ __("filament-watchdog-v5::messages.service.emergency.view.timeline_activated") }}</strong> <span id="lockdown-time"></span></p>
            <p><strong>{{ __("filament-watchdog-v5::messages.service.emergency.view.timeline_expected") }}</strong> {{ __("filament-watchdog-v5::messages.service.emergency.view.timeline_resolution") }}</p>
        </div>

        <div class="footer">
            <div class="logo">{{ __("filament-watchdog-v5::messages.service.emergency.view.logo") }}</div>
            <p>{{ __("filament-watchdog-v5::messages.service.emergency.view.footer_1") }}</p>
            <p>{{ __("filament-watchdog-v5::messages.service.emergency.view.footer_2") }}</p>
        </div>
    </div>

    <script>
        // Create animated stars
        function createStars() {
            const starsContainer = document.getElementById("stars");
            const numberOfStars = 50;

            for (let i = 0; i < numberOfStars; i++) {
                const star = document.createElement("div");
                star.className = "star";
                star.style.left = Math.random() * 100 + "%";
                star.style.top = Math.random() * 100 + "%";
                star.style.animationDelay = Math.random() * 2 + "s";
                starsContainer.appendChild(star);
            }
        }

        // Set lockdown time
        function setLockdownTime() {
            const now = new Date();
            const timeString = now.toLocaleString("en-US", {
                year: "numeric",
                month: "long",
                day: "numeric",
                hour: "2-digit",
                minute: "2-digit",
                timeZoneName: "short"
            });
            document.getElementById("lockdown-time").textContent = timeString;
        }

        // Initialize
        document.addEventListener("DOMContentLoaded", function() {
            createStars();
            setLockdownTime();
        });

        // Add subtle page refresh every 5 minutes to check if lockdown is lifted
        setTimeout(function() {
            window.location.reload();
        }, 300000); // 5 minutes
    </script>
</body>
</html>';
    }

    /**
     * Get the services provided by the provider
     */
    public function provides(): array
    {
        return [
            FileIntegrityService::class,
            MalwareDetectionService::class,
            ActivityMonitoringService::class,
            AlertService::class,
            EmergencyLockdownService::class,
            ScanFilesCommand::class,
            CreateBaselineCommand::class,
            CleanupLogsCommand::class,
            EmergencyLockdownCommand::class,
            PublishViewsCommand::class,
            DebugCommand::class,
        ];
    }
}
