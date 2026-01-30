<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . "/..");
}

/**
 * Require Core init File.
 */
require_once BASEPATH .'/app/Core/init.php';

/**
 * Include default model
 */
$modelName = 'DashboardModel';
$modelPath = BASEPATH . "/app/Models/" . $modelName . ".php";
if (file_exists($modelPath)) {
    include_once $modelPath;
    // Include Core Global process_request File.
    include_once BASEPATH .'/app/Core/process_request.php';
}
?>

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
        [x-cloak] { display: none !important; }
    </style>
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

            <nav class="mt-10 px-4 space-y-2" x-data="{ activePage: 'dashboard' }">
                <button 
                    hx-get="router.php?page=dashboard" 
                    hx-target="#main-content" 
                    hx-push-url="true"
                    @click="activePage = 'dashboard'; sidebarOpen = false"
                    :class="activePage === 'dashboard' ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:bg-gray-700/50'"
                    class="w-full flex items-center px-4 py-3 rounded-lg group transition">
                    <svg 
                    :class="activePage === 'dashboard' ? 'text-indigo-400' : ''" 
                    class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    Dashboard
                </button>

                <button 
                    hx-get="router.php?page=monitoring" 
                    hx-target="#main-content" 
                    hx-push-url="true"
                    @click="activePage = 'monitoring'; sidebarOpen = false"
                    :class="activePage === 'monitoring' ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:bg-gray-700/50'"
                    class="w-full flex items-center px-4 py-3 rounded-lg group transition">
                    <svg 
                    :class="activePage === 'monitoring' ? 'text-indigo-400' : ''" 
                    class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M22 12h-4l-3 9L9 3l-3 9H2">
                    </path></svg>
                    Monitoring
                </button>

                <button 
                    hx-get="router.php?page=settings" 
                    hx-target="#main-content" 
                    hx-push-url="true"
                    @click="activePage = 'settings'; sidebarOpen = false"
                    :class="activePage === 'settings' ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:bg-gray-700/50'"
                    class="w-full flex items-center px-4 py-3 rounded-lg group transition">
                    <svg 
                    :class="activePage === 'settings' ? 'text-indigo-400' : ''" 
                    class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Settings
                </button>

                <button 
                    hx-get="router.php?page=settingsx" 
                    hx-target="#main-content" 
                    hx-push-url="true"
                    @click="activePage = 'settingsx'; sidebarOpen = false"
                    :class="activePage === 'settingsx' ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:bg-gray-700/50'"
                    class="w-full flex items-center px-4 py-3 rounded-lg group transition">
                    <svg 
                    :class="activePage === 'settingsx' ? 'text-indigo-400' : ''" 
                    class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Settings X 
                </button>
            </nav>
        </aside>

        <main class="flex-1 p-6 overflow-y-auto bg-gray-900">
            <div id="main-content" class="flex-1 overflow-y-auto">
                <?php include BASEPATH . "/views/dashboard.php"; // Load default saat pertama buka ?>
            </div>
        </main>
    </div>

</body>
</html>