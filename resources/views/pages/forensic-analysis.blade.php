<x-filament-panels::page>
    <x-filament::section>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem">
            <h3 style="font-size:1rem;font-weight:600">Forensic Analysis</h3>
            <button style="background:#3b82f6;color:white;padding:0.5rem 1rem;border-radius:0.5rem;border:none;cursor:pointer;font-size:0.875rem">
                Start Analysis
            </button>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
            <div style="background:#f9fafb;border-radius:0.5rem;padding:1rem">
                <h4 style="font-weight:500;margin-bottom:0.75rem">File System Analysis</h4>
                <div style="display:flex;flex-direction:column;gap:0.5rem;font-size:0.875rem">
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:#6b7280">Files scanned:</span>
                        <span style="font-weight:500">0</span>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:#6b7280">Changes detected:</span>
                        <span style="font-weight:500">0</span>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:#6b7280">Last scan:</span>
                        <span style="font-weight:500">Never</span>
                    </div>
                </div>
            </div>

            <div style="background:#f9fafb;border-radius:0.5rem;padding:1rem">
                <h4 style="font-weight:500;margin-bottom:0.75rem">Network Analysis</h4>
                <div style="display:flex;flex-direction:column;gap:0.5rem;font-size:0.875rem">
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:#6b7280">Connections monitored:</span>
                        <span style="font-weight:500">0</span>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:#6b7280">Suspicious activity:</span>
                        <span style="font-weight:500">0</span>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:#6b7280">Blocked IPs:</span>
                        <span style="font-weight:500">0</span>
                    </div>
                </div>
            </div>
        </div>

        <div style="display:flex;align-items:center;justify-content:center;padding:3rem 0;margin-top:1.5rem">
            <div style="text-align:center">
                <svg style="width:4rem;height:4rem;margin:0 auto;color:#9ca3af" fill="none" stroke="#9ca3af" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <h4 style="margin-top:1rem;font-size:1rem;font-weight:500">Ready for Analysis</h4>
                <p style="margin-top:0.5rem;font-size:0.875rem;color:#6b7280">Click "Start Analysis" to begin forensic examination.</p>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
