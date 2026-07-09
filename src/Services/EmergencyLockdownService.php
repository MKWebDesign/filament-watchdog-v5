<?php

namespace MKWebDesign\FilamentWatchdog\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use MKWebDesign\FilamentWatchdog\Models\SecurityAlert;

class EmergencyLockdownService
{
    public function __construct(
        private AlertService $alertService
    ) {}

/**
 * Activate comprehensive emergency lockdown
 */
public function activateEmergencyLockdown(array $options = []): array
{
    $lockdownId = 'emergency_' . time();
    $results = [];

    try {
        // 1. Create critical alert with full audit trail
        $alert = $this->alertService->createAlert(
            'emergency_lockdown',
            __('filament-watchdog-v5::messages.service.emergency.alert_title'),
            __('filament-watchdog-v5::messages.service.emergency.alert_body'),
            'critical',
            [
                'lockdown_id' => $lockdownId,
                'activated_by' => Auth::user()?->name ?? 'System',
                'activated_at' => now()->toISOString(),
                'ip_address' => $this->getRealIpAddress(),
                'user_agent' => request()->userAgent(),
                'actions_taken' => [],
                'options' => $options
            ]
        );

        $results['alert_created'] = true;
        $results['alert_id'] = $alert->id;

        // 2. Enable maintenance mode (prevents most access)
        if ($options['maintenance_mode'] ?? true) {
            $maintenanceResult = $this->enableMaintenanceMode($lockdownId);
            $results['maintenance_mode'] = $maintenanceResult;
        }

        // 3. Block suspicious IP addresses
        if ($options['block_ips'] ?? true) {
            $ipBlockResult = $this->blockSuspiciousIPs();
            $results['blocked_ips'] = $ipBlockResult;
        }

        // 4. Disable non-admin users temporarily
        if ($options['disable_users'] ?? false) {
            $userDisableResult = $this->disableNonAdminUsers();
            $results['disabled_users'] = $userDisableResult;
        }

        // 5. Clear all user sessions except current admin
        if ($options['clear_sessions'] ?? true) {
            $sessionResult = $this->clearUserSessions();
            $results['sessions_cleared'] = $sessionResult;
        }

        // 6. Create .htaccess protection (IPv6 compatible)
        if ($options['htaccess_protection'] ?? true) {
            $htaccessResult = $this->createEmergencyHtaccess();
            $results['htaccess_protection'] = $htaccessResult;
        }

        // 7. Notify administrators immediately
        if ($options['notify_admins'] ?? true) {
            $notificationResult = $this->notifyAllAdministrators($lockdownId, $alert->id);
            $results['admin_notifications'] = $notificationResult;
        }

        // 8. Create emergency backup of critical files
        if ($options['emergency_backup'] ?? true) {
            $backupResult = $this->createEmergencyBackup();
            $results['emergency_backup'] = $backupResult;
        }

        // 9. Log all activities
        $this->logLockdownActivation($lockdownId, $results);

        // 10. Set lockdown cache flag
        Cache::put('emergency_lockdown_active', [
            'lockdown_id' => $lockdownId,
            'activated_at' => now(),
            'activated_by' => Auth::user()?->name ?? 'System',
            'alert_id' => $alert->id,
            'options' => $options
        ], now()->addHours(24));

        $results['lockdown_active'] = true;
        $results['status'] = 'success';

    } catch (\Exception $e) {
        Log::critical('Emergency lockdown failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        $results['status'] = 'failed';
        $results['error'] = $e->getMessage();
    }

    return $results;
}

/**
 * Enable Laravel maintenance mode with custom emergency page
 */
private function enableMaintenanceMode(string $lockdownId): bool
{
    try {
        $secret = 'admin_' . substr($lockdownId, -8);

        // Ensure emergency view exists
        $this->ensureEmergencyViewExists();

        // Create maintenance mode with admin bypass and custom template
        $artisanPath = base_path('artisan');
        $customView = config('filament-watchdog.emergency.maintenance.custom_view', 'errors::emergency-lockdown');
        $statusCode = config('filament-watchdog.emergency.maintenance.status_code', 503);
        $retryAfter = config('filament-watchdog.emergency.maintenance.retry_after', 3600);

        $command = "php {$artisanPath} down --secret={$secret} --status={$statusCode} --retry={$retryAfter} --render=\"{$customView}\"";
        exec($command, $output, $returnCode);

        // Store secret for admin access
        Cache::put('emergency_lockdown_secret', $secret, now()->addHours(24));

        Log::info('Maintenance mode enabled with custom emergency page', [
            'secret' => $secret,
            'view' => $customView,
            'status_code' => $statusCode,
            'command_output' => $output,
            'return_code' => $returnCode
        ]);

        return $returnCode === 0;
    } catch (\Exception $e) {
        Log::error('Failed to enable maintenance mode: ' . $e->getMessage());
        return false;
    }
}

/**
 * Ensure emergency view exists (auto-publish if needed)
 */
private function ensureEmergencyViewExists(): void
{
    $emergencyViewPath = resource_path('views/errors/emergency-lockdown.blade.php');

    if (!File::exists($emergencyViewPath)) {
        Log::info('Emergency view not found, auto-publishing...');

        try {
            // Auto-publish emergency view
            $this->publishEmergencyView();
            Log::info('Emergency view auto-published successfully');
        } catch (\Exception $e) {
            Log::error('Failed to auto-publish emergency view: ' . $e->getMessage());
        }
    }
}

/**
 * Publish emergency view from package
 */
private function publishEmergencyView(): void
{
    $sourceDir = __DIR__ . '/../../resources/views/errors';
    $targetDir = resource_path('views/errors');
    $emergencyView = 'emergency-lockdown.blade.php';

    // Ensure target directory exists
    if (!File::exists($targetDir)) {
        File::makeDirectory($targetDir, 0755, true);
    }

    $sourcePath = $sourceDir . '/' . $emergencyView;
    $targetPath = $targetDir . '/' . $emergencyView;

    if (File::exists($sourcePath)) {
        File::copy($sourcePath, $targetPath);
    } else {
        // Fallback: create basic emergency view
        $this->createFallbackEmergencyView($targetPath);
    }
}

/**
 * Create fallback emergency view if package view is not available
 */
private function createFallbackEmergencyView(string $targetPath): void
{
    $emergencyContent = $this->getEmergencyPageTemplate();
    File::put($targetPath, $emergencyContent);
}

/**
 * Get emergency page template content
 */
private function getEmergencyPageTemplate(): string
{
    return '<!DOCTYPE html>
<html lang="en">
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
 * Block suspicious IP addresses
 */
private function blockSuspiciousIPs(): array
{
    $blockedIPs = [];

    try {
        // Get IPs with recent failed login attempts or high-risk activity
        $suspiciousIPs = DB::table('activity_logs')
            ->where('event_type', 'failed_login')
            ->where('created_at', '>=', now()->subHours(24))
            ->groupBy('ip_address')
            ->havingRaw('COUNT(*) >= ?', [3])
            ->pluck('ip_address');

        // Also get IPs from high-risk activities
        $highRiskIPs = DB::table('activity_logs')
            ->whereIn('risk_level', ['high', 'critical'])
            ->where('created_at', '>=', now()->subHours(24))
            ->pluck('ip_address');

        $allSuspiciousIPs = $suspiciousIPs->merge($highRiskIPs)->unique();

        foreach ($allSuspiciousIPs as $ip) {
            $currentIP = $this->getRealIpAddress();
            if ($ip && $ip !== $currentIP && $this->blockIPAddress($ip)) {
                $blockedIPs[] = $ip;
            }
        }

        return $blockedIPs;
    } catch (\Exception $e) {
        Log::error('Failed to block suspicious IPs: ' . $e->getMessage());
        return [];
    }
}

/**
 * Block specific IP address via .htaccess
 */
private function blockIPAddress(string $ip): bool
{
    try {
        $htaccessPath = public_path('.htaccess');

        // Handle IPv6 addresses properly
        $isIPv6 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);

        if ($isIPv6) {
            $blockRule = "\n# Emergency Lockdown - Block IPv6 {$ip}\nRequire not ip \"{$ip}\"\n";
        } else {
            $blockRule = "\n# Emergency Lockdown - Block IPv4 {$ip}\nRequire not ip {$ip}\n";
        }

        File::append($htaccessPath, $blockRule);

        return true;
    } catch (\Exception $e) {
        Log::error("Failed to block IP {$ip}: " . $e->getMessage());
        return false;
    }
}

/**
 * Temporarily disable non-admin users
 */
private function disableNonAdminUsers(): int
{
    try {
        $currentUserEmail = Auth::user()?->email ?? '';
        $adminEmails = $this->getAdminEmails();

        // Get users to disable (exclude current user and admin emails)
        $usersToDisable = DB::table('users')
            ->where('email', '!=', $currentUserEmail)
            ->whereNotIn('email', $adminEmails)
            ->where('active', true) // Only disable active users
            ->get();

        // Store original user states for restoration
        Cache::put('emergency_disabled_users', $usersToDisable->toArray(), now()->addDays(7));

        // Disable users
        $count = DB::table('users')
            ->where('email', '!=', $currentUserEmail)
            ->whereNotIn('email', $adminEmails)
            ->where('active', true)
            ->update([
                'active' => false,
                'disabled_reason' => 'Emergency Lockdown - ' . now()->toDateTimeString()
            ]);

        return $count;
    } catch (\Exception $e) {
        Log::error('Failed to disable users: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Clear all user sessions except current admin
 */
private function clearUserSessions(): bool
{
    try {
        $currentSessionId = session()->getId();

        // Clear Laravel sessions (except current admin)
        $cleared = DB::table('sessions')
            ->where('id', '!=', $currentSessionId)
            ->delete();

        return $cleared >= 0; // Even 0 deletions is success
    } catch (\Exception $e) {
        Log::error('Failed to clear sessions: ' . $e->getMessage());
        return false;
    }
}

/**
 * Create emergency security protection (No .htaccess modification)
 */
private function createEmergencyHtaccess(): bool
{
    try {
        $currentIP = $this->getRealIpAddress();
        $isIPv6 = filter_var($currentIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);

        Log::info('Emergency security: Skipping htaccess modification', [
            'ip' => $currentIP,
            'is_ipv6' => $isIPv6,
            'reason' => 'Using maintenance mode + session clearing instead',
            'user_agent' => request()->userAgent()
        ]);

        // Create IP whitelist file for manual server configuration
        $ipWhitelistPath = storage_path('app/emergency-ip-whitelist.txt');
        $ipData = "# Emergency IP Whitelist - " . now()->toDateTimeString() . "\n";
        $ipData .= "# Generated during emergency lockdown activation\n";
        $ipData .= "# Use these IPs for server-level whitelisting if needed\n\n";
        $ipData .= "# Admin IP (detected):\n";
        $ipData .= $currentIP . "\n\n";
        $ipData .= "# Standard localhost IPs:\n";
        $ipData .= "127.0.0.1\n";
        if ($isIPv6) {
            $ipData .= "::1\n";
        }
        $ipData .= "\n# Common private network ranges (if applicable):\n";
        $ipData .= "# 192.168.1.0/24\n";
        $ipData .= "# 10.0.0.0/8\n";
        $ipData .= "# 172.16.0.0/12\n\n";

        // Add Apache/nginx config examples
        $ipData .= "# Apache .htaccess example (if manual implementation needed):\n";
        $ipData .= "# Order Deny,Allow\n";
        $ipData .= "# Deny from all\n";
        if ($isIPv6) {
            $ipData .= "# Allow from 127.0.0.1\n";
            $ipData .= "# Allow from ::1\n";
            $ipData .= "# # IPv6 requires server-level config\n";
        } else {
            $ipData .= "# Allow from {$currentIP}\n";
            $ipData .= "# Allow from 127.0.0.1\n";
        }

        $ipData .= "\n# Nginx config example:\n";
        $ipData .= "# location / {\n";
        if ($isIPv6) {
            $ipData .= "#     allow 127.0.0.1;\n";
            $ipData .= "#     allow ::1;\n";
            $ipData .= "#     # IPv6 requires careful configuration\n";
        } else {
            $ipData .= "#     allow {$currentIP};\n";
            $ipData .= "#     allow 127.0.0.1;\n";
        }
        $ipData .= "#     deny all;\n";
        $ipData .= "# }\n";

        File::put($ipWhitelistPath, $ipData);

        // Log critical information for manual intervention
        Log::critical('EMERGENCY LOCKDOWN: Manual server configuration may be needed', [
            'admin_ip' => $currentIP,
            'is_ipv6' => $isIPv6,
            'whitelist_file' => $ipWhitelistPath,
            'maintenance_mode' => 'ACTIVE - Site is offline',
            'sessions_cleared' => 'All user sessions cleared',
            'manual_action' => 'Add IP whitelist to server config if additional security needed'
        ]);

        // Create a simple security status file
        $securityStatusPath = storage_path('app/emergency-security-status.json');
        $securityStatus = [
            'lockdown_active' => true,
            'timestamp' => now()->toISOString(),
            'admin_ip' => $currentIP,
            'ip_type' => $isIPv6 ? 'IPv6' : 'IPv4',
            'security_measures' => [
                'maintenance_mode' => 'ACTIVE',
                'sessions_cleared' => 'YES',
                'htaccess_protection' => 'SKIPPED (compatibility)',
                'ip_whitelist_file' => $ipWhitelistPath,
                'server_level_blocking' => 'Manual configuration available'
            ],
            'emergency_access' => [
                'method' => 'Maintenance mode secret key',
                'admin_ip_recorded' => $currentIP
            ]
        ];

        File::put($securityStatusPath, json_encode($securityStatus, JSON_PRETTY_PRINT));

        Log::info('Emergency security files created successfully', [
            'ip_whitelist' => $ipWhitelistPath,
            'security_status' => $securityStatusPath,
            'admin_ip' => $currentIP
        ]);

        return true;

    } catch (\Exception $e) {
        Log::error('Failed to create emergency security configuration', [
            'error' => $e->getMessage(),
            'ip' => $this->getRealIpAddress(),
            'trace' => $e->getTraceAsString()
        ]);
        return false;
    }
}

/**
 * Validate .htaccess syntax before writing
 */
private function validateHtaccessSyntax(string $rules): bool
{
    try {
        // Basic syntax checks
        $openTags = substr_count($rules, '<');
        $closeTags = substr_count($rules, '>');

        if ($openTags !== $closeTags) {
            Log::error('htaccess validation failed: unmatched tags', [
                'open_tags' => $openTags,
                'close_tags' => $closeTags
            ]);
            return false;
        }

        // Check for RequireAll closure
        if (strpos($rules, '<RequireAll>') !== false && strpos($rules, '</RequireAll>') === false) {
            Log::error('htaccess validation failed: unclosed RequireAll');
            return false;
        }

        // Check for IfModule closure
        preg_match_all('/<IfModule[^>]*>/', $rules, $openModules);
        $closeModules = substr_count($rules, '</IfModule>');

        if (count($openModules[0]) !== $closeModules) {
            Log::error('htaccess validation failed: unmatched IfModule tags');
            return false;
        }

        return true;

    } catch (\Exception $e) {
        Log::error('htaccess validation error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Check if mod_evasive is available
 */
private function isModEvasiveAvailable(): bool
{
    if (!function_exists('apache_get_modules')) {
        return false;
    }

    $modules = apache_get_modules();
    return in_array('mod_evasive24', $modules) || in_array('mod_evasive20', $modules);
}

/**
 * Get real IP address (handles proxies and IPv6)
 */
private function getRealIpAddress(): string
{
    $ipKeys = [
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_X_FORWARDED_FOR',      // Proxy
        'HTTP_X_FORWARDED',          // Proxy
        'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
        'HTTP_FORWARDED_FOR',        // Proxy
        'HTTP_FORWARDED',            // Proxy
        'HTTP_CLIENT_IP',            // Proxy
        'REMOTE_ADDR'                // Standard
    ];

    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) && !empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);

            // Validate IP (allow both public and private for emergency access)
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return request()->ip();
}

/**
 * Notify all administrators
 */
private function notifyAllAdministrators(string $lockdownId, int $alertId): int
{
    $notifiedCount = 0;

    try {
        $adminEmails = config('filament-watchdog.alerts.email_recipients', []);
        $lockdownDetails = [
            'lockdown_id' => $lockdownId,
            'alert_id' => $alertId,
            'activated_by' => auth()->user()->name ?? 'System',
            'activated_at' => now()->toDateTimeString(),
            'ip_address' => $this->getRealIpAddress(),
            'secret_key' => Cache::get('emergency_lockdown_secret', 'N/A')
        ];

        foreach ($adminEmails as $email) {
            $this->sendLockdownNotification($email, $lockdownDetails);
            $notifiedCount++;
        }

        return $notifiedCount;
    } catch (\Exception $e) {
        Log::error('Failed to notify administrators: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Create emergency backup of critical files
 */
private function createEmergencyBackup(): bool
{
    try {
        $backupPath = storage_path('app/emergency-backups/' . date('Y-m-d-H-i-s'));
        File::makeDirectory($backupPath, 0755, true);

        // Backup critical directories
        $criticalPaths = [
            'app' => app_path(),
            'config' => config_path(),
            'database' => database_path(),
            'routes' => base_path('routes')
        ];

        foreach ($criticalPaths as $name => $path) {
            if (File::exists($path)) {
                $destinationPath = $backupPath . '/' . $name;
                $this->recursiveCopy($path, $destinationPath);
            }
        }

        // Create backup info file
        File::put($backupPath . '/backup-info.json', json_encode([
            'created_at' => now()->toISOString(),
            'created_by' => auth()->user()->name ?? 'System',
            'reason' => 'Emergency Lockdown',
            'paths_backed_up' => array_keys($criticalPaths)
        ], JSON_PRETTY_PRINT));

        return true;
    } catch (\Exception $e) {
        Log::error('Failed to create emergency backup: ' . $e->getMessage());
        return false;
    }
}

/**
 * Deactivate emergency lockdown
 */
public function deactivateEmergencyLockdown(): array
{
    $results = [];

    try {
        // 1. Disable maintenance mode
        $artisanPath = base_path('artisan');
        exec("php {$artisanPath} up", $output, $returnCode);
        $results['maintenance_disabled'] = $returnCode === 0;

        // 2. Restore disabled users
        $restoredUsers = $this->restoreDisabledUsers();
        $results['users_restored'] = $restoredUsers;

        // 3. Clean up emergency htaccess rules
        $this->cleanupEmergencyHtaccess();
        $results['htaccess_cleaned'] = true;

        // 4. Clear lockdown cache
        Cache::forget('emergency_lockdown_active');
        Cache::forget('emergency_lockdown_secret');
        Cache::forget('emergency_disabled_users');
        $results['cache_cleared'] = true;

        // 5. Log deactivation
        $this->alertService->createAlert(
            'emergency_lockdown_deactivated',
            __('filament-watchdog-v5::messages.service.emergency.deactivated_title'),
            __('filament-watchdog-v5::messages.service.emergency.deactivated_body'),
            'high',
            [
                'action_by' => Auth::user()?->name ?? 'System',
                'deactivated_at' => now()->toISOString(),
                'ip_address' => $this->getRealIpAddress()
            ]
        );

        $results['status'] = 'success';

    } catch (\Exception $e) {
        $results['status'] = 'failed';
        $results['error'] = $e->getMessage();
        Log::error('Failed to deactivate emergency lockdown: ' . $e->getMessage());
    }

    return $results;
}

/**
 * Check if emergency lockdown is currently active
 */
public function isLockdownActive(): bool
{
    return Cache::has('emergency_lockdown_active');
}

/**
 * Get lockdown status information
 */
public function getLockdownStatus(): ?array
{
    return Cache::get('emergency_lockdown_active');
}

/**
 * Get emergency access URL for administrators
 * Fixed to work with Laravel maintenance mode properly
 */
public function getEmergencyAccessUrl(): ?string
{
    $secret = Cache::get('emergency_lockdown_secret');
    if ($secret) {
        // Laravel maintenance mode expects the secret in a specific format
        // The URL should be: domain.com/secret-key (not as a query parameter)
        return url('/' . $secret);
    }
    return null;
}

/**
 * Get emergency access information with multiple options
 */
public function getEmergencyAccessInfo(): array
{
    $secret = Cache::get('emergency_lockdown_secret');
    $lockdownInfo = Cache::get('emergency_lockdown_active');

    if (!$secret) {
        return [
            'access_available' => false,
            'message' => 'No emergency access configured'
        ];
    }

    return [
        'access_available' => true,
        'secret_key' => $secret,
        'primary_url' => url('/' . $secret),
        'alternative_urls' => [
            // Some servers might need these alternative formats
            url('/?secret=' . $secret),
            url('/index.php/' . $secret),
        ],
        'cli_command' => "php artisan up --secret={$secret}",
        'lockdown_info' => $lockdownInfo,
        'instructions' => [
            'Try the primary URL first: ' . url('/' . $secret),
            'If that doesn\'t work, try adding /admin after the secret',
            'Alternative: Use the CLI command to bring the site up',
            'Then navigate to your admin panel normally'
        ]
    ];
}

// Helper methods
private function getAdminEmails(): array
{
    $recipients = config('filament-watchdog.alerts.email_recipients', []);
    $adminEmails = config('filament-watchdog.alerts.admin_emails', []);

    return array_merge($recipients, $adminEmails);
}

private function sendLockdownNotification(string $email, array $details): void
{
    try {
        Mail::send([], [], function ($message) use ($email, $details) {
            $message->to($email)
                ->subject(__('filament-watchdog-v5::messages.service.emergency.email.subject'))
                ->html($this->buildLockdownEmailContent($details));
        });
    } catch (\Exception $e) {
        Log::error("Failed to send lockdown notification to {$email}: " . $e->getMessage());
    }
}

private function buildLockdownEmailContent(array $details): string
{
    $primaryAccessUrl = url('/' . $details['secret_key']);
    $alternativeAccessUrl = url('/?secret=' . $details['secret_key']);
    $adminUrl = url('/' . $details['secret_key'] . '/admin');

    return "
        <div style=\"font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\">
            <h1 style=\"color: #dc3545; text-align: center;\">" . __('filament-watchdog-v5::messages.service.emergency.email.header_title') . "</h1>
            <h2 style=\"color: #dc3545; text-align: center;\">" . __('filament-watchdog-v5::messages.service.emergency.email.header_subtitle') . "</h2>
            
            <div style=\"background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;\">
                <p><strong>" . __('filament-watchdog-v5::messages.service.emergency.email.intro_text') . "</strong></p>
            </div>
            
            <h3 style=\"color: #495057;\">" . __('filament-watchdog-v5::messages.service.emergency.email.details_title') . "</h3>
            <table style=\"width: 100%; border-collapse: collapse;\">
                <tr><td><strong>" . __('filament-watchdog-v5::messages.service.emergency.email.id') . ":</strong></td><td>{$details['lockdown_id']}</td></tr>
                <tr><td><strong>" . __('filament-watchdog-v5::messages.service.emergency.email.alert_id') . ":</strong></td><td>{$details['alert_id']}</td></tr>
                <tr><td><strong>" . __('filament-watchdog-v5::messages.service.emergency.email.activated_by') . ":</strong></td><td>{$details['activated_by']}</td></tr>
                <tr><td><strong>" . __('filament-watchdog-v5::messages.service.emergency.email.time') . ":</strong></td><td>{$details['activated_at']}</td></tr>
                <tr><td><strong>" . __('filament-watchdog-v5::messages.service.emergency.email.ip') . ":</strong></td><td>{$details['ip_address']}</td></tr>
            </table>

            <h3 style=\"color: #495057;\">" . __('filament-watchdog-v5::messages.service.emergency.email.access_options') . "</h3>
            <div style=\"background: #fff3cd; padding: 15px; border-radius: 8px; margin: 15px 0;\">
                <h4>" . __('filament-watchdog-v5::messages.service.emergency.email.option_1') . "</h4>
                <p><a href=\"{$primaryAccessUrl}\" style=\"background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;\">{$primaryAccessUrl}</a></p>
                
                <h4>" . __('filament-watchdog-v5::messages.service.emergency.email.option_2') . "</h4>
                <ul>
                    <li><a href=\"{$alternativeAccessUrl}\">{$alternativeAccessUrl}</a></li>
                    <li><a href=\"{$adminUrl}\">{$adminUrl}</a></li>
                </ul>
                
                <h4>" . __('filament-watchdog-v5::messages.service.emergency.email.option_3') . "</h4>
                <code style=\"background: #f8f9fa; padding: 5px; border-radius: 3px; display: block; margin: 5px 0;\">
                    php artisan up --secret={$details['secret_key']}
                </code>
                <p><em>" . __('filament-watchdog-v5::messages.service.emergency.email.then_navigate') . " <a href=\"" . url('/admin') . "\">" . url('/admin') . "</a></em></p>
            </div>
            
            <h3 style=\"color: #495057;\">" . __('filament-watchdog-v5::messages.service.emergency.email.actions_taken') . "</h3>
            <ul>
                <li>✅ " . __('filament-watchdog-v5::messages.service.emergency.email.action_1') . "</li>
                <li>✅ " . __('filament-watchdog-v5::messages.service.emergency.email.action_2') . "</li>
                <li>✅ " . __('filament-watchdog-v5::messages.service.emergency.email.action_3') . "</li>
                <li>✅ " . __('filament-watchdog-v5::messages.service.emergency.email.action_4') . "</li>
                <li>✅ " . __('filament-watchdog-v5::messages.service.emergency.email.action_5') . "</li>
            </ul>

            <h3 style=\"color: #495057;\">" . __('filament-watchdog-v5::messages.service.emergency.email.next_steps') . "</h3>
            <ol>
                <li><strong>" . __('filament-watchdog-v5::messages.service.emergency.email.step_1_strong') . "</strong> " . __('filament-watchdog-v5::messages.service.emergency.email.step_1') . "</li>
                <li><strong>" . __('filament-watchdog-v5::messages.service.emergency.email.step_2_strong') . "</strong> " . __('filament-watchdog-v5::messages.service.emergency.email.step_2') . "</li>
                <li><strong>" . __('filament-watchdog-v5::messages.service.emergency.email.step_3_strong') . "</strong> " . __('filament-watchdog-v5::messages.service.emergency.email.step_3') . "</li>
                <li><strong>" . __('filament-watchdog-v5::messages.service.emergency.email.step_4_strong') . "</strong> " . __('filament-watchdog-v5::messages.service.emergency.email.step_4') . "</li>
            </ol>

            <div style=\"background: #d1ecf1; padding: 15px; border-radius: 8px; margin: 20px 0;\">
                <h4 style=\"color: #0c5460;\">" . __('filament-watchdog-v5::messages.service.emergency.email.troubleshooting') . "</h4>
                <ul>
                    <li>" . __('filament-watchdog-v5::messages.service.emergency.email.ts_1') . "</li>
                    <li>" . __('filament-watchdog-v5::messages.service.emergency.email.ts_2') . "</li>
                    <li>" . __('filament-watchdog-v5::messages.service.emergency.email.ts_3') . "</li>
                    <li>" . __('filament-watchdog-v5::messages.service.emergency.email.ts_4') . "</li>
                    <li>" . __('filament-watchdog-v5::messages.service.emergency.email.ts_5') . "</li>
                </ul>
            </div>

            <hr style=\"margin: 30px 0;\">
            <p style=\"font-size: 12px; color: #6c757d;\"><em>" . __('filament-watchdog-v5::messages.service.emergency.email.footer_1') . "</em></p>
            <p style=\"font-size: 12px; color: #6c757d;\"><em>" . __('filament-watchdog-v5::messages.service.emergency.email.footer_2') . "</em></p>
        </div>
        ";
}
private function logLockdownActivation(string $lockdownId, array $results): void
{
    Log::critical('EMERGENCY LOCKDOWN ACTIVATED', [
        'lockdown_id' => $lockdownId,
        'results' => $results,
        'user' => auth()->user()->email ?? 'Unknown',
        'ip' => $this->getRealIpAddress(),
        'timestamp' => now(),
        'user_agent' => request()->userAgent()
    ]);
}

private function restoreDisabledUsers(): int
{
    try {
        $disabledUsers = Cache::get('emergency_disabled_users', []);
        $restoredCount = 0;

        foreach ($disabledUsers as $user) {
            DB::table('users')
                ->where('id', $user['id'])
                ->update([
                    'active' => true,
                    'disabled_reason' => null
                ]);
            $restoredCount++;
        }

        return $restoredCount;
    } catch (\Exception $e) {
        Log::error('Failed to restore disabled users: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Enhanced cleanup for emergency security (No htaccess modification)
 */
private function cleanupEmergencyHtaccess(): void
{
    try {
        // No .htaccess cleanup needed since we don't modify it
        Log::info('Emergency security cleanup: No htaccess modifications to clean');

        // Clean up emergency files
        $ipWhitelistPath = storage_path('app/emergency-ip-whitelist.txt');
        $securityStatusPath = storage_path('app/emergency-security-status.json');

        // Archive the files instead of deleting (for audit trail)
        $archivePath = storage_path('app/emergency-archives/' . date('Y-m-d-H-i-s'));
        File::makeDirectory($archivePath, 0755, true);

        if (File::exists($ipWhitelistPath)) {
            File::move($ipWhitelistPath, $archivePath . '/ip-whitelist.txt');
        }

        if (File::exists($securityStatusPath)) {
            File::move($securityStatusPath, $archivePath . '/security-status.json');
        }

        Log::info('Emergency security files archived successfully', [
            'archive_path' => $archivePath
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to cleanup emergency security files: ' . $e->getMessage());
    }
}

private function recursiveCopy(string $source, string $destination): void
{
    if (is_dir($source)) {
        File::makeDirectory($destination, 0755, true);
        $files = scandir($source);

        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $this->recursiveCopy($source . '/' . $file, $destination . '/' . $file);
            }
        }
    } else {
        File::copy($source, $destination);
    }
}
}