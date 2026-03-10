<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cron Simulator Dashboard</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>

    <style>
        /* Custom scrollbar untuk log agar serasi dengan dark mode */
        #log-container::-webkit-scrollbar {
            width: 8px;
        }
        #log-container::-webkit-scrollbar-track {
            background: #2d2d2d;
        }
        #log-container::-webkit-scrollbar-thumb {
            background: #4a4a4a;
            border-radius: 4px;
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 font-sans antialiased" x-data="{ sidebarOpen: false }">

    <nav class="bg-gray-800 border-b border-gray-700 px-4 py-3 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <button @click="sidebarOpen = !sidebarOpen" class="md:hidden text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
            <span class="text-xl font-bold tracking-wider text-indigo-400 uppercase">Cron<span class="text-white text-sm font-light italic">Job</span></span>
        </div>
        
        <div class="flex items-center space-x-4">
            <span class="text-xs bg-green-900 text-green-300 px-2 py-1 rounded-full animate-pulse font-mono">SYSTEM ACTIVE</span>
            <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-xs font-bold">JD</div>
        </div>
    </nav>

    <div class="flex h-screen overflow-hidden">
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" 
               class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-800 transform transition-transform duration-300 ease-in-out md:relative md:translate-x-0 border-r border-gray-700">

           <div class="flex items-center justify-between px-6 py-5 border-b border-gray-700 md:hidden ">
                <span class="text-xl font-bold text-indigo-400">CRON<span class="text-white font-light">JOB</span></span>
                <button @click="sidebarOpen = false" class="text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <nav class="mt-5 px-4 space-y-2">
                <a href="#" class="flex items-center px-4 py-2 bg-gray-900 text-white rounded-lg group">
                    <svg class="w-5 h-5 mr-3 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    Dashboard
                </a>
                <a href="#" class="flex items-center px-4 py-2 text-gray-400 hover:bg-gray-700 hover:text-white rounded-lg transition">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Schedules
                </a>
                <a href="#" class="flex items-center px-4 py-2 text-gray-400 hover:bg-gray-700 hover:text-white rounded-lg transition">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Settings
                </a>
            </nav>
        </aside>

        <main class="flex-1 p-6 overflow-y-auto bg-gray-900">
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
                    
                    <div id="log-container" 
                         hx-get="get_logs.php" 
                         hx-trigger="every 5s" 
                         hx-swap="innerHTML"
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
        </main>
    </div>

</body>
</html>