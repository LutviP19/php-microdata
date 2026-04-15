<?php if(!isHtmx()): ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>406 - Not Acceptable</title>
    <script src="<?= asset('js/tailwindcss.js'); ?>"></script>
</head>
<body class="bg-gray-900 text-gray-100 font-sans antialiased">
<?php endif; ?>

<div class="flex flex-col items-center justify-center min-h-[60vh] text-center p-6">
    <div class="relative">
        <h1 class="text-9xl font-black text-gray-800 animate-pulse">406</h1>
        <p class="absolute inset-0 flex items-center justify-center text-2xl font-bold text-rose-500 uppercase tracking-widest">
            Not Acceptable
        </p>
    </div>

    <div class="mt-8">
        <div class="flex justify-center mb-4 text-rose-500">
            <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <h2 class="text-2xl font-semibold text-white">Format Tidak Didukung.</h2>
        <p class="mt-4 text-gray-400 max-w-md mx-auto">
            Server tidak dapat menghasilkan konten yang sesuai dengan kriteria yang diminta oleh browser atau aplikasi Anda.
        </p>

        <?php if (isset($resultDefault['errors']) && is_array($resultDefault['errors'])): ?>
            <div class="mt-6 w-full max-w-md mx-auto text-left">
                <div class="bg-rose-500/10 border-l-4 border-rose-500 p-4 rounded-r-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-rose-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-rose-400 uppercase tracking-wider">Detail Error Validasi:</h3>
                            <ul class="mt-2 text-sm text-gray-300 list-disc list-inside space-y-1">
                                <?php foreach ($resultDefault['errors'] as $field => $message): ?>
                                    <li>
                                        <span class="text-rose-300 font-semibold"><?= htmlspecialchars($field); ?>:</span> 
                                        <?= htmlspecialchars($message); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
        <button onclick="window.location.reload()" 
           class="cursor-pointer inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-xl text-white bg-rose-600 hover:bg-rose-700 transition duration-150 ease-in-out shadow-lg shadow-rose-500/20">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Muat Ulang
        </button>
        
        <a href="<?= url('dashboard'); ?>" 
           class="inline-flex items-center px-6 py-3 border border-gray-700 text-base font-medium rounded-xl text-gray-300 bg-gray-800 hover:bg-gray-700 transition duration-150 ease-in-out">
            Kembali ke Dashboard
        </a>
    </div>

    <div class="mt-12 p-4 bg-gray-800/50 rounded-lg border border-gray-700 font-mono text-xs text-gray-500 max-w-sm overflow-hidden text-left">
        <p>$ curl -X GET localhost/<?= htmlspecialchars($_GET['page'] ?? 'api/v1/data') ?> -H "Accept: application/json"</p>
        <p class="text-rose-400">HTTP/1.1 406 Not Acceptable</p>
        <p class="text-gray-600 italic">// Content-Negotiation: Failed</p>
    </div>
</div>

<?php if(!isHtmx()): ?>
</body>
</html>
<?php endif; ?>