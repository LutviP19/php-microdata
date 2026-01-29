<div class="flex flex-col items-center justify-center min-h-[60vh] text-center">
    <div class="relative">
        <h1 class="text-9xl font-black text-gray-800 animate-pulse">404</h1>
        <p class="absolute inset-0 flex items-center justify-center text-2xl font-bold text-indigo-500 uppercase tracking-widest">
            Page Not Found
        </p>
    </div>

    <div class="mt-8">
        <h2 class="text-2xl font-semibold text-white">Opps! Sepertinya Anda tersesat.</h2>
        <p class="mt-4 text-gray-400 max-w-md mx-auto">
            Halaman yang Anda cari tidak ada atau telah dipindahkan ke dimensi lain. 
            Pastikan URL yang Anda masukkan sudah benar.
        </p>
    </div>

    <div class="mt-10">
        <a href="index.php?page=dashboard" 
           class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 transition duration-150 ease-in-out shadow-lg shadow-indigo-500/20">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali ke Dashboard
        </a>
    </div>

    <div class="mt-12 p-4 bg-gray-800/50 rounded-lg border border-gray-700 font-mono text-xs text-gray-500">
        <p>$ curl -I localhost/<?= htmlspecialchars($_GET['page'] ?? 'unknown') ?></p>
        <p class="text-red-400">HTTP/1.1 404 Not Found</p>
    </div>
</div>