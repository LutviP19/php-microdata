<?php if(!isHtmx()): ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access denied</title>
    <script src="<?= asset('js/tailwindcss.js'); ?>"></script>
</head>
<body class="bg-gray-900 text-gray-100 font-sans antialiased">
<?php endif; ?>

<div class="flex flex-col items-center justify-center min-h-[60vh] text-center p-6">
    <div class="relative">
        <h1 class="text-9xl font-black text-gray-800 animate-pulse">403</h1>
        <p class="absolute inset-0 flex items-center justify-center text-2xl font-bold text-amber-500 uppercase tracking-widest">
            Access Denied
        </p>
    </div>

    <div class="mt-8">
        <div class="flex justify-center mb-4 text-amber-500">
            <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
        </div>
        <h2 class="text-2xl font-semibold text-white">Oops! Area Terlarang.</h2>
        <p class="mt-4 text-gray-400 max-w-md mx-auto">
            Anda tidak memiliki izin yang cukup untuk mengakses halaman ini. 
            Silakan hubungi Administrator jika Anda merasa ini adalah kesalahan.
        </p>
    </div>

    <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
        <a href="<?= url('dashboard'); ?>" 
           class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-xl text-white bg-amber-600 hover:bg-amber-700 transition duration-150 ease-in-out shadow-lg shadow-amber-500/20">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            Dashboard
        </a>
        
        <a href="<?= url('logout'); ?>" 
           class="inline-flex items-center px-6 py-3 border border-gray-700 text-base font-medium rounded-xl text-gray-300 bg-gray-800 hover:bg-gray-700 transition duration-150 ease-in-out">
            Ganti Akun
        </a>
    </div>

    <div class="mt-12 p-4 bg-gray-800/50 rounded-lg border border-gray-700 font-mono text-xs text-gray-500 max-w-sm overflow-hidden">
        <p>$ curl -X GET localhost/<?= htmlspecialchars($_GET['page'] ?? 'protected_area') ?></p>
        <p class="text-amber-400">HTTP/1.1 403 Forbidden</p>
        <p class="text-gray-600 italic">// Auth-Status: Insufficient Permissions</p>
    </div>
</div>

<?php if(!isHtmx()): ?>
</body>
</html>
<?php endif; ?>