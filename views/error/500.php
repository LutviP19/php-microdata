<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cron Simulator Dashboard</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
</head>
<body class="bg-gray-900 text-gray-100 font-sans antialiased" x-data="{ sidebarOpen: false }">
    <main class="flex-1 p-6 overflow-y-auto bg-gray-900">   
        <div class="flex flex-col items-center justify-center min-h-[70vh] text-center px-4">
            <div class="relative mb-8">
                <div class="absolute inset-0 bg-red-500/20 blur-3xl rounded-full"></div>
                <svg class="relative w-32 h-32 text-red-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" 
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                    </path>
                </svg>
            </div>

            <div class="space-y-4">
                <h1 class="text-5xl md:text-6xl font-black text-white tracking-tighter">
                    500 <span class="text-red-500">SERVER ERROR</span>
                </h1>
                <p class="text-gray-400 text-lg max-w-lg mx-auto leading-relaxed">
                    Sepertinya sistem kami mengalami <span class="text-red-400 italic">kernel panic</span> kecil. 
                    Tenang, data Anda tetap aman, hanya saja server butuh waktu untuk bernafas.
                </p>
            </div>

            <div class="mt-10 w-full max-w-2xl bg-black/50 border border-gray-800 rounded-2xl p-6 font-mono text-sm text-left shadow-2xl overflow-hidden">
                <div class="flex space-x-2 mb-4">
                    <div class="w-3 h-3 bg-red-500/50 rounded-full"></div>
                    <div class="w-3 h-3 bg-gray-700 rounded-full"></div>
                    <div class="w-3 h-3 bg-gray-700 rounded-full"></div>
                </div>
                <div class="text-gray-500 space-y-1">
                    <p class="text-red-400 font-bold">[CRITICAL] Uncaught Exception: System overload</p>
                    <p>at StackTrace: app/Core/Router.php:142</p>
                    <p>at Request: <?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/unknown') ?></p>
                    <p class="animate-pulse">_ Waiting for recovery...</p>
                </div>
            </div>

            <div class="mt-10 flex flex-col md:flex-row gap-4">
                <button onclick="window.location.reload()" 
                        class="px-8 py-3 bg-gray-800 hover:bg-gray-700 text-white font-bold rounded-xl transition flex items-center justify-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span>Coba Lagi</span>
                </button>
                
                <a href="<?= url('dashboard'); ?>" 
                   class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition shadow-lg shadow-indigo-500/20">
                   Kembali ke Dashboard
                </a>
            </div>
        </div>
    </main>
</body>
</html>