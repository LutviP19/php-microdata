<?php if(!isHtmx()): ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>401 - Unauthorized</title>
    <script src="<?= asset('js/tailwindcss.js'); ?>"></script>
</head>
<body class="bg-gray-900 text-gray-100 font-sans antialiased">
<?php endif; ?>

<div class="flex flex-col items-center justify-center min-h-[60vh] text-center p-6">
    <div class="relative">
        <h1 class="text-9xl font-black text-gray-800 animate-pulse">401</h1>
        <p class="absolute inset-0 flex items-center justify-center text-2xl font-bold text-red-500 uppercase tracking-widest">
            Unauthorized
        </p>
    </div>

    <div class="mt-8">
        <div class="flex justify-center mb-4 text-red-500">
            <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
            </svg>
        </div>
        <h2 class="text-2xl font-semibold text-white">Sesi Anda Telah Berakhir.</h2>
        <p class="mt-4 text-gray-400 max-w-md mx-auto">
            Maaf, Anda perlu login kembali untuk dapat melanjutkan akses ke area ini atau memantau proses infrastruktur.
        </p>
    </div>

    <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
        <a href="<?= url('login'); ?>" 
           class="inline-flex items-center px-8 py-3 border border-transparent text-base font-medium rounded-xl text-white bg-red-600 hover:bg-red-700 transition duration-150 ease-in-out shadow-lg shadow-red-500/20">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
            </svg>
            Login Sekarang
        </a>
        
        <button onclick="window.location.reload()" 
           class="inline-flex items-center px-8 py-3 border border-gray-700 text-base font-medium rounded-xl text-gray-300 bg-gray-800 hover:bg-gray-700 transition duration-150 ease-in-out">
            Refresh Halaman
        </button>
    </div>

    <div class="mt-12 p-4 bg-gray-800/50 rounded-lg border border-gray-700 font-mono text-xs text-gray-500 max-w-sm overflow-hidden">
        <p>$ curl -I localhost/<?= htmlspecialchars($_GET['page'] ?? 'admin_dashboard') ?></p>
        <p class="text-red-400">HTTP/1.1 401 Unauthorized</p>
        <p class="text-gray-600 italic">// WWW-Authenticate: Bearer realm="Access to internal logs"</p>
    </div>
</div>

<?php if(!isHtmx()): ?>
</body>
</html>
<?php endif; ?>