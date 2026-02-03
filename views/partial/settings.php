<div class="max-w-4xl mx-auto" x-data="{ saved: false }">
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-white">Settings</h2>
        <p class="text-gray-400 mt-1">Konfigurasi parameter sistem dan penjadwalan.</p>
    </div>

    <form class="space-y-6">
        <div class="bg-gray-800 rounded-2xl border border-gray-700 overflow-hidden shadow-sm">
            <div class="p-6 border-b border-gray-700 bg-gray-800/50">
                <h3 class="text-lg font-medium text-indigo-400">General Settings</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">System Name</label>
                    <input type="text" class="w-full bg-gray-900 border border-gray-700 rounded-xl px-4 py-2.5 text-white focus:ring-2 focus:ring-indigo-500 focus:outline-none transition" value="Cron Simulator Pro">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Log Retention (Days)</label>
                    <select class="w-full bg-gray-900 border border-gray-700 rounded-xl px-4 py-2.5 text-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option>7 Days</option>
                        <option selected>30 Days</option>
                        <option>90 Days</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 rounded-2xl border border-gray-700 overflow-hidden shadow-sm">
            <div class="p-6 border-b border-gray-700 bg-gray-800/50">
                <h3 class="text-lg font-medium text-indigo-400">Automation</h3>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between p-4 bg-gray-900/50 rounded-xl border border-gray-700">
                    <div>
                        <p class="font-medium text-white">Auto-restart on Failure</p>
                        <p class="text-xs text-gray-500 font-light">Mencoba menjalankan ulang task jika status exit error.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" checked class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Simulated Interval (Seconds)</label>
                    <div class="flex items-center space-x-4">
                        <input type="range" min="5" max="60" value="10" class="flex-1 h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer accent-indigo-500">
                        <span class="text-indigo-400 font-mono font-bold w-12 text-right">10s</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-4 pt-4">
            <button type="button" class="px-6 py-2.5 text-sm font-medium text-gray-400 hover:text-white transition">
                Reset to Default
            </button>
            <button type="button" 
                    @click="saved = true; setTimeout(() => saved = false, 3000)"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-2.5 rounded-xl font-semibold shadow-lg shadow-indigo-900/30 transition transform active:scale-95">
                Save Changes
            </button>
        </div>
    </form>

    <div x-show="saved" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-y-10 opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed bottom-10 right-10 bg-green-600 text-white px-6 py-3 rounded-2xl shadow-2xl flex items-center space-x-3 z-50">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        <span class="font-medium">Konfigurasi berhasil disimpan!</span>
    </div>
</div>