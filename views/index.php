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
<body 
    hx-headers='{"X-API-KEY": "<?= str_replace('base64:', '', config('api.key')) ?>"}' 
    class="bg-gray-900 text-gray-100 font-sans antialiased" 
    x-data="{ sidebarOpen: false, openLogout: false, userMenuOpen: false, activePage: '<?= $page ?>' }"
    >

    <nav class="bg-gray-800 border-b border-gray-700 px-4 py-3 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <button @click="sidebarOpen = !sidebarOpen" class="md:hidden text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
            <span class="text-xl font-bold tracking-wider text-indigo-400 uppercase">Cron<span class="text-white text-sm font-light italic">Job</span></span>
        </div>
        
        <!-- <div class="flex items-center space-x-4">
            <span class="text-xs bg-green-900 text-green-300 px-2 py-1 rounded-full animate-pulse font-mono">SYSTEM ACTIVE</span>
            <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-xs font-bold">JD</div>
        </div> -->
        <div class="flex items-center space-x-4">
            <span class="text-xs bg-green-900 text-green-300 px-2 py-1 rounded-full animate-pulse font-mono">SYSTEM ACTIVE</span>
            
            <div class="relative" x-data="{ userMenuOpen: false }" @click.away="userMenuOpen = false">
                <button 
                    @click="userMenuOpen = !userMenuOpen"
                    class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-xs font-bold hover:bg-indigo-500 transition focus:outline-none ring-2 ring-indigo-900">
                    <?= get_initials($_SESSION['user_name'] ?? 'Demo User') ?>
                </button>

                <div 
                    x-show="userMenuOpen"
                    x-cloak
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute right-0 mt-2 w-48 bg-gray-900 border border-gray-800 rounded-lg shadow-xl py-2 z-50">
                    
                    <div class="px-4 py-2 border-b border-gray-800 mb-2">
                        <p class="text-xs text-gray-400">Signed in as</p>
                        <p class="text-sm font-semibold text-white truncate">
                            <?= htmlspecialchars($_SESSION['user_name'] ?? 'Demo User') ?>
                        </p>
                    </div>

                    <a href="/user-profile" 
                       hx-get="/user-profile" 
                       hx-target="#main-content" 
                       hx-push-url="true"
                       @click="userMenuOpen = false"
                       class="block px-4 py-2 text-sm text-gray-300 hover:bg-indigo-600 hover:text-white transition">
                       My Profile
                    </a>

                    <a href="/user-settings" 
                       hx-get="/user-settings" 
                       hx-target="#main-content" 
                       hx-push-url="true"
                       @click="userMenuOpen = false"
                       class="block px-4 py-2 text-sm text-gray-300 hover:bg-indigo-600 hover:text-white transition">
                       Settings
                    </a>

                    <div class="border-t border-gray-800 mt-2"></div>
                    
                    <button 
                        @click="openLogout = true; userMenuOpen = false" 
                        class="w-full text-left block px-4 py-2 text-sm text-red-400 hover:bg-red-900/30 transition">
                        Sign Out
                    </button>
                </div>
            </div>
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
                    hx-get="<?= url('dashboard'); ?>" 
                    hx-push-url="true" 
                    hx-target="#main-content" 
                    @click="activePage = 'dashboard'; sidebarOpen = false"
                    :class="activePage === 'dashboard' ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:bg-gray-700/50'"
                    class="w-full flex items-center px-4 py-3 rounded-lg group transition">
                    <svg 
                    :class="activePage === 'dashboard' ? 'text-indigo-400' : ''" 
                    class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    Dashboard
                </button>

                <button 
                    hx-get="<?= url('monitoring'); ?>" 
                    hx-push-url="true" 
                    hx-target="#main-content" 
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
                    hx-get="<?= url('settings'); ?>" 
                    hx-push-url="true" 
                    hx-target="#main-content" 
                    @click="activePage = 'settings'; sidebarOpen = false"
                    :class="activePage === 'settings' ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:bg-gray-700/50'"
                    class="w-full flex items-center px-4 py-3 rounded-lg group transition">
                    <svg 
                    :class="activePage === 'settings' ? 'text-indigo-400' : ''" 
                    class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Settings
                </button>

                <button 
                    hx-get="<?= url('settingsX'); ?>" 
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
                <?php include BASEPATH . "/views/partial/" . $page . ".php"; ?>
            </div>
        </main>
    </div>

<!-- ModalBox -->
<div 
    x-show="openLogout" 
    x-cloak
    class="fixed inset-0 z-[100] flex items-center justify-center p-4 overflow-x-hidden overflow-y-auto">
    
    <div 
        x-show="openLogout"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="openLogout = false"
        class="fixed inset-0 bg-black/80 backdrop-blur-sm"></div>

    <div 
        x-show="openLogout"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative w-full max-w-md p-6 bg-gray-900 border border-gray-800 rounded-2xl shadow-2xl">
        
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-900/20 mb-4">
                <svg class="h-6 w-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-white">Konfirmasi Logout</h3>
            <p class="mt-2 text-sm text-gray-400">
                Apakah Anda yakin ingin mengakhiri sesi terminal ini? Data yang belum tersimpan mungkin akan hilang.
            </p>
        </div>

        <div class="mt-6 flex space-x-3">
            <button 
                @click="openLogout = false"
                class="flex-1 px-4 py-2 text-sm font-medium text-gray-400 bg-gray-800 rounded-lg hover:bg-gray-700 transition">
                Batalkan
            </button>
            <button 
                hx-post="<?= url('/logout') ?>"
                class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 shadow-lg shadow-red-600/20 transition">
                Ya, Keluar
            </button>
        </div>
    </div>
</div>

<script>
// document.body.addEventListener('htmx:afterRequest', function(evt) {
//     console.log("Target Element:", evt.detail.target);
//     console.log("Response Status:", evt.detail.xhr.status);
//     if (evt.detail.target.id !== 'main-content') {
//         console.warn("HTMX tidak menargetkan #main-content!");
//     }
// });

// document.body.addEventListener('htmx:beforeSwap', function(evt) {
//     console.log("Konten yang diterima:", evt.detail.xhr.responseText);
// });

// document.body.addEventListener('htmx:swapError', function(evt) {
//     console.error("Terjadi kesalahan saat swap!");
// });
</script>

<script>
    // const targetNode = document.getElementById('main-content');
    
    // const observer = new MutationObserver((mutationsList) => {
    //     for (const mutation of mutationsList) {
    //         if (mutation.type === 'childList') {
    //             console.log('🔄 Perubahan DOM terdeteksi pada #main-content');
    //             console.log('Tipe:', mutation.type);
    //             console.log('Class saat ini:', targetNode.className);
                
    //             // Cek jika htmx-settling masih menempel terlalu lama
    //             if (targetNode.classList.contains('htmx-settling')) {
    //                 console.warn('⚠️ Elemen terjebak di fase settling! Mencoba memaksa...');
    //                 setTimeout(() => {
    //                     targetNode.classList.remove('htmx-settling');
    //                     console.log('✅ Paksa hapus htmx-settling');
    //                 }, 500);
    //             }
    //         }
    //     }
    // });

    // if (targetNode) {
    //     observer.observe(targetNode, { attributes: true, childList: true, subtree: true });
    //     console.log('🚀 Observer aktif: Memantau #main-content');
    // }
</script>
</body>
</html>