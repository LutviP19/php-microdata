<div class="max-w-4xl mx-auto">
    <header class="mb-8">
        <h1 class="text-3xl font-bold">System Log Monitoring</h1>
        <p class="text-gray-400 mt-2">Memantau eksekusi task scheduler secara real-time.</p>
    </header>

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

        <div id="log-container" hx-get="get_logs.php" hx-trigger="every 5s" hx-swap="innerHTML"
            hx-on::after-settle="this.scrollTo({top: this.scrollHeight, behavior: 'smooth'})"
            class="h-96 overflow-y-auto p-4 font-mono text-sm text-green-400 leading-relaxed">
            <span class="animate-pulse">Menghubungkan ke stream server...</span>
        </div>
    </div>

    <div class="mt-4 flex justify-between items-center text-xs text-gray-500 uppercase tracking-widest">
        <span>Server: PHP 8.x + Tailwind + Alpine</span>
        <button class="hover:text-white transition">Clear Logs</button>
    </div>
</div>