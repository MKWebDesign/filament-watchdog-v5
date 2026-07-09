<x-filament-panels::page>
    <x-filament::section>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem">
            <h3 style="font-size:1rem;font-weight:600">{{ __('filament-watchdog-v5::messages.page.timeline.title') }}</h3>
            <div style="display:flex;align-items:center;gap:0.5rem">
                <span style="font-size:0.875rem;color:#6b7280">{{ __('filament-watchdog-v5::messages.page.timeline.last_24_hours') }}</span>
                <div style="width:0.5rem;height:0.5rem;background:#22c55e;border-radius:9999px"></div>
            </div>
        </div>

        <div style="display:flex;align-items:center;justify-content:center;padding:3rem 0">
            <div style="text-align:center">
                <svg style="width:4rem;height:4rem;margin:0 auto;color:#9ca3af" fill="none" stroke="#9ca3af" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h4 style="margin-top:1rem;font-size:1rem;font-weight:500">{{ __('filament-watchdog-v5::messages.page.timeline.no_threats') }}</h4>
                <p style="margin-top:0.5rem;font-size:0.875rem;color:#6b7280">{{ __('filament-watchdog-v5::messages.page.timeline.secure_desc') }}</p>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
