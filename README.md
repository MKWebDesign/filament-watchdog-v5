![FilamentWatchdog Logo](https://raw.githubusercontent.com/MKWebDesign/filament-watchdog-v5/main/art/logo.png)
# FilamentWatchdog v5

**Advanced security monitoring and intrusion detection plugin for FilamentPHP v5**

[![License](https://img.shields.io/packagist/l/mkwebdesign/filament-watchdog-v5.svg?style=flat-square)](https://github.com/mkwebdesign/filament-watchdog-v5/blob/main/LICENSE.md)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/mkwebdesign/filament-watchdog-v5.svg?style=flat-square)](https://packagist.org/packages/mkwebdesign/filament-watchdog-v5)

---

## Requirements

- PHP 8.2+
- Laravel 11+, 12+ or 13+
- FilamentPHP 5.x

## Features

- **File Integrity Monitoring** — SHA-256 hash baseline with automatic change detection
- **Malware Detection** — Pattern-based scanning with configurable signatures
- **Security Alerts** — Severity-based alerting with email notifications
- **Activity Logging** — Track logins, admin actions and high-risk events
- **Emergency Lockdown** — One-click maintenance mode with admin bypass
- **Quarantine System** — Isolate suspicious files automatically
- **Artisan Commands** — Manual scans, baseline creation, cleanup and debug tools
- **Laravel Scheduler** — Automated scans every minute via cron

## Installation

```bash
composer require mkwebdesign/filament-watchdog-v5
```

Publish the config:

```bash
php artisan vendor:publish --tag=filament-watchdog-config
```

Run the migrations:

```bash
php artisan migrate
```

## Panel Registration

Add the plugin to your `AdminPanelProvider`:

```php
use MKWebDesign\FilamentWatchdog\FilamentWatchdogPlugin;

->plugin(FilamentWatchdogPlugin::make())
```

## Cron Setup

Add the standard Laravel scheduler to your crontab:

```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## Artisan Commands

```bash
# Run a full security scan
php artisan watchdog:scan

# Create a new file baseline
php artisan watchdog:baseline

# Show debug info and statistics
php artisan watchdog:debug

# Clean up old logs
php artisan watchdog:cleanup --days=30

# Emergency lockdown
php artisan watchdog:emergency-lockdown activate
php artisan watchdog:emergency-lockdown deactivate
```

## Configuration

After publishing, edit `config/filament-watchdog.php` to configure monitored paths, excluded paths, malware signatures, email recipients and more.

## Author

**Martin Knops | MKWebDesign**

- Website: [mkwebdesign.nl](https://mkwebdesign.nl)
- GitHub: [@mkwebdesign](https://github.com/mkwebdesign)

## License

MIT — see [LICENSE.md](LICENSE.md)

---

Made with ❤️ by [MKWebDesign](https://mkwebdesign.nl)
