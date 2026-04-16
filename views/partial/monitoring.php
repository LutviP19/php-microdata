<div class="max-w-4xl mx-auto pb-8">
    <header class="mb-2">
        <h1 class="text-3xl font-bold">System Monitoring</h1>
        <p class="text-gray-400 mt-2">Memantau status sistem secara real-time.</p>
    </header>

    <div class="space-y-4 mb-8">
        <div class="flex items-center justify-between">
            <div id="status-spinner" class="htmx-indicator">
                <svg class="animate-spin h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
            hx-get="<?= url('service-status'); ?>"
            hx-trigger="load, every 10s"
            hx-indicator="#status-spinner"
            hx-swap="innerHTML transition:true">
            
            <script>document.addEventListener('DOMContentLoaded', () => htmx.trigger('#status-spinner', 'load'));</script>
            
            <p class="text-gray-500 text-sm animate-pulse">Memuat metrik sistem...</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-gray-800 p-4 rounded-xl border border-gray-700">
            <p class="text-sm text-gray-400">Total Run</p>
            <p class="text-2xl font-bold">1,284</p>
        </div>
        <div class="bg-gray-800 p-4 rounded-xl border border-gray-700">
            <p class="text-sm text-gray-400">Errors</p>
            <p class="text-2xl font-bold text-red-500">0</p>
        </div>
        <div class="bg-gray-800 p-4 rounded-xl border border-gray-700">
            <p class="text-sm text-gray-400">Next Sync</p>
            <p class="text-2xl font-bold text-indigo-400">05:00</p>
        </div>
    </div>

    <div class="bg-gray-800 rounded-xl border border-gray-700 shadow-2xl overflow-hidden">
        <div class="bg-gray-700 px-4 py-2 flex items-center space-x-2">
            <div class="w-3 h-3 rounded-full bg-red-500"></div>
            <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
            <div class="w-3 h-3 rounded-full bg-green-500"></div>
            <span class="text-xs text-gray-400 ml-2 font-mono italic">cron_output.log</span>
        </div>

        <div id="log-container" hx-get="<?= url('get_logs'); ?>" hx-trigger="load, every 65s" hx-swap="innerHTML"
            hx-on::after-settle="this.scrollTo({top: this.scrollHeight, behavior: 'smooth'})"
            class="h-96 overflow-y-auto p-4 font-mono text-xs md:text-sm text-green-400 leading-none space-y-0.5 bg-black/90 rounded-xl border border-gray-800 shadow-inner">
            <div class="flex items-center space-x-2">
                <span class="animate-pulse inline-block w-2 h-2 bg-green-500 rounded-full"></span>
                <span>Menghubungkan ke stream server...</span>
            </div>
        </div>
    </div>

    <div class="mt-4 flex justify-between items-center text-xs text-gray-500 uppercase tracking-widest">
        <span>Server: PHP 8.5 + Tailwind + Alpine</span>
        <button class="hover:text-white transition">Clear Logs</button>
    </div>
</div>