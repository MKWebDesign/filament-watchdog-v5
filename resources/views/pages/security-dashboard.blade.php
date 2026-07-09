<x-filament-panels::page>
    @php
        $statusItems = [
            ['label' => __('filament-watchdog-v5::messages.page.dashboard.status.file_monitoring'),      'active' => $systemStatus['fileMonitoring']],
            ['label' => __('filament-watchdog-v5::messages.page.dashboard.status.malware_detection'),    'active' => $systemStatus['malwareDetection']],
            ['label' => __('filament-watchdog-v5::messages.page.dashboard.status.activity_monitoring'),  'active' => $systemStatus['activityMonitoring']],
            ['label' => __('filament-watchdog-v5::messages.page.dashboard.status.alert_system'),         'active' => $systemStatus['alertSystem']],
        ];
    @endphp

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;margin-bottom:1.5rem">

        {{-- System Status --}}
        <x-filament::section>
            <x-slot name="heading">{{ __('filament-watchdog-v5::messages.page.dashboard.sections.system_status') }}</x-slot>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                @foreach($statusItems as $item)
                    <div style="display:flex;align-items:center;gap:0.75rem">
                        <div style="flex-shrink:0;width:2rem;height:2rem;border-radius:9999px;background:{{ $item['active'] ? '#dcfce7' : '#fee2e2' }};display:flex;align-items:center;justify-content:center">
                            @if($item['active'])
                                <svg style="width:1.25rem;height:1.25rem;color:#16a34a" fill="none" stroke="#16a34a" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                <svg style="width:1.25rem;height:1.25rem" fill="none" stroke="#dc2626" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            @endif
                        </div>
                        <div>
                            <div style="font-size:0.875rem;font-weight:500">{{ $item['label'] }}</div>
                            <div style="font-size:0.875rem;color:{{ $item['active'] ? '#16a34a' : '#dc2626' }}">
                                {{ $item['active'] ? __('filament-watchdog-v5::messages.page.dashboard.status.active') : __('filament-watchdog-v5::messages.page.dashboard.status.disabled') }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- System Statistics --}}
        <x-filament::section>
            <x-slot name="heading">{{ __('filament-watchdog-v5::messages.page.dashboard.sections.system_statistics') }}</x-slot>

            <div style="display:flex;flex-direction:column;gap:1rem">
                @foreach([
                    ['label' => __('filament-watchdog-v5::messages.page.dashboard.stats.total_monitored'), 'value' => $stats['totalFiles'],        'warn' => false],
                    ['label' => __('filament-watchdog-v5::messages.page.dashboard.stats.modified_files'),  'value' => $stats['modifiedFiles'],      'warn' => $stats['modifiedFiles'] > 0],
                    ['label' => __('filament-watchdog-v5::messages.page.dashboard.stats.malware_detected'),'value' => $stats['malwareDetections'],  'warn' => $stats['malwareDetections'] > 0],
                    ['label' => __('filament-watchdog-v5::messages.page.dashboard.stats.unresolved_alerts'),'value' => $stats['unresolvedAlerts'],  'warn' => $stats['unresolvedAlerts'] > 0],
                ] as $stat)
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <span style="font-size:0.875rem;color:#6b7280">{{ $stat['label'] }}</span>
                        <span style="font-size:1.125rem;font-weight:600;color:{{ $stat['warn'] ? '#dc2626' : '#16a34a' }}">
                            {{ number_format($stat['value']) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

    </div>
</x-filament-panels::page>
