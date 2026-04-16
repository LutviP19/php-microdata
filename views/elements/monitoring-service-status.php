<div class="bg-gray-800/80 border border-gray-700 p-2 rounded-2xl shadow-xl hover:border-gray-600 transition-all">
    <div class="flex items-start justify-between mb-4">
        <div class="flex items-center space-x-3">
            <div class="w-3 h-3 rounded-full bg-<?= $color ?>-500 <?= $isUp ? 'animate-pulse' : '' ?>"></div>
            <h4 class="text-sm font-bold text-gray-100"><?= $name ?></h4>
        </div>
        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-gray-900 border 
            <?= $isUp 
                ? 'text-emerald-400 border-emerald-800/50' 
                : 'text-rose-400 border-rose-800/50' 
            ?>">
            <?= $isUp ? 'ONLINE' : 'OFFLINE' ?>
        </span>
    </div>

    <div class="grid grid-cols-2 gap-2 mt-2">
        <div class="bg-gray-900/50 p-1 rounded-lg border border-gray-700/50 text-center">
            <p class="text-[9px] text-gray-500 uppercase">Latency</p>
            <p class="text-xs font-bold <?= $latencyColor ?>"><?= $isUp ? $latency . 'ms' : '---' ?></p>
        </div>
        <div class="bg-gray-900/50 p-1 rounded-lg border border-gray-700/50 text-center">
            <p class="text-[9px] text-gray-500 uppercase">Memory</p>
            <p class="text-xs font-bold text-blue-400"><?= $extra['mem'] ?></p>
        </div>
    </div>

    <?php if ($isUp && $latency > 200): ?>
        <div class="mt-2 text-[10px] text-amber-500 flex items-center justify-center space-x-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <span>Koneksi Lambat</span>
        </div>
    <?php endif; ?>
</div>