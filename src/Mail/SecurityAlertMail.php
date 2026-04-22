<?php

namespace MKWebDesign\FilamentWatchdog\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use MKWebDesign\FilamentWatchdog\Models\SecurityAlert;

class SecurityAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly SecurityAlert $alert
    ) {}

    public function envelope(): Envelope
    {
        $severity = strtoupper($this->alert->severity);
        $prefix = match ($this->alert->severity) {
            'critical' => '[CRITICAL]',
            'high'     => '[HIGH]',
            'medium'   => '[MEDIUM]',
            default    => '[INFO]',
        };

        return new Envelope(
            subject: "{$prefix} Security Alert: {$this->alert->title}",
        );
    }

    public function content(): Content
    {
        $headerBg = match ($this->alert->severity) {
            'critical' => '#dc2626',
            'high'     => '#ea580c',
            'medium'   => '#d97706',
            default    => '#2563eb',
        };

        $dashboardUrl = rtrim(config('app.url'), '/') . '/admin/security-dashboard';

        return new Content(
            view: 'filament-watchdog::emails.security-alert',
            with: [
                'alert'        => $this->alert,
                'headerBg'     => $headerBg,
                'dashboardUrl' => $dashboardUrl,
            ],
        );
    }
}
