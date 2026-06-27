<?php include_once(BASEPATH . '/views/elements/default-htmx-head.php'); ?>
<div class="flex flex-col items-center justify-start md:justify-center min-h-[80vh] md:px-4 pt-10 md:pt-0" 
     x-data="{ 
        searching: false, 
        query: '',
        category: 'all',
        search() {
            if(this.query.trim() === '' && this.category === 'all') return;
            this.searching = true;
            setTimeout(() => { this.searching = false }, 800);
        }
     }">
    
    <!-- <h3><?= htmlspecialchars((string) $title) ?></h3> -->
    <!-- <div class="text-center mb-6 md:mb-10">
        <h1 class="text-4xl md:text-6xl font-bold tracking-tight bg-transparent select-none whitespace-nowrap">
            <span class="text-white">“</span>
            <span class="text-[#e62414]">C</span><span class="text-[#4285F4]">a</span><span class="text-[#FBBC05]">r</span><span class="text-[#00c028]">i</span>
            <span class="text-white italic ml-1 md:ml-2"> </span>
            <span class="text-[#e62414]">A</span><span class="text-[#4285F4]">H</span><span class="text-[#00c028]">!</span>
            <span class="text-white">”</span>
        </h1>
        <p class="text-gray-400 mt-2 font-mono text-[10px] md:text-sm uppercase tracking-[0.2em]">System Intelligence Engine</p>
    </div> -->

    <div class="text-center mb-6 md:mb-10">
        <h1 class="text-4xl md:text-6xl font-bold tracking-tight bg-transparent select-none whitespace-nowrap">
            <span class="text-white">“</span>
            <span class="text-[#e62414]">P</span><span class="text-[#4285F4]">H</span><span class="text-[#FBBC05]">P</span>
            
            <span class="text-white italic ml-1 md:ml-2"> </span>
            
            <span class="text-[#00c028]">M</span><span class="text-[#e62414]">i</span><span class="text-[#4285F4]">c</span><span class="text-[#FBBC05]">r</span><span class="text-[#00c028]">o</span><span class="text-[#e62414]">d</span><span class="text-[#4285F4]">a</span><span class="text-[#FBBC05]">t</span><span class="text-[#00c028]">a</span>
            
            <span class="text-white">”</span>
        </h1>
        <p class="text-gray-400 mt-2 font-mono text-[10px] md:text-sm uppercase tracking-[0.2em]">System Intelligence Engine</p>
    </div>

    <div class="w-full max-w-3xl relative group">
        <form hx-get="<?= url('get_logs'); ?>" 
              hx-target="#search-results" 
              hx-indicator="#loading-spinner"
              @submit.prevent="search()"
              class="flex flex-col gap-3 md:gap-0 md:flex-row md:items-center md:bg-gray-800 md:border-2 md:border-gray-700 md:rounded-full md:focus-within:border-indigo-500 md:focus-within:ring-4 md:focus-within:ring-indigo-500/10 transition-all shadow-2xl">
            
            <div class="relative" 
                 x-data="{ 
                    open: false, 
                    selectedLabel: 'All Logs',
                    options: [
                        { val: 'all', label: 'All Logs' },
                        { val: 'error', label: 'Errors' },
                        { val: 'cron', label: 'Cron Jobs' },
                        { val: 'system', label: 'System' }
                    ]
                 }" 
                 @click.away="open = false">
                
                <input type="hidden" name="category" :value="category">

                <button type="button" 
                        @click="open = !open"
                        class="flex items-center justify-between w-full md:w-36 bg-gray-800 md:bg-transparent border-2 border-gray-700 md:border-0 md:border-r rounded-2xl md:rounded-none text-gray-400 text-sm font-semibold py-4 px-5 focus:outline-none hover:text-white transition">
                    <span x-text="selectedLabel"></span>
                    <svg class="w-4 h-4 ml-2 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <div x-show="open" x-cloak x-transition
                     class="absolute left-0 mt-2 w-full md:w-48 bg-gray-800 border border-gray-700 rounded-2xl shadow-2xl z-[100] overflow-hidden">
                    <div class="py-1">
                        <template x-for="opt in options" :key="opt.val">
                            <button type="button"
                                    @click="category = opt.val; selectedLabel = opt.label; open = false"
                                    class="w-full text-left px-4 py-3 text-sm transition"
                                    :class="category === opt.val ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'">
                                <span x-text="opt.label"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <div class="flex-1 relative flex items-center bg-gray-800 md:bg-transparent border-2 border-gray-700 md:border-0 rounded-2xl md:rounded-none">
                <div class="absolute left-4 text-gray-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input 
                    name="q"
                    x-model="query"
                    type="text" 
                    placeholder="Search logs..." 
                    autocomplete="off" 
                    class="w-full bg-transparent text-white text-base md:text-lg py-4 pl-12 pr-4 focus:outline-none"
                >
            </div>

            <div class="md:pr-2">
                <button type="submit" 
                        class="w-full md:w-auto bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-4 md:py-2.5 rounded-2xl md:rounded-full font-bold transition flex items-center justify-center space-x-2 shadow-lg shadow-indigo-500/20">
                    <span>Search</span>
                    <div id="loading-spinner" class="htmx-indicator animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></div>
                </button>
            </div>
        </form>
    </div>

    <div class="mt-8 flex flex-wrap justify-center gap-4 text-[10px] md:text-xs text-gray-500 font-mono">
        <div class="flex items-center bg-gray-800/50 px-3 py-1 rounded-full border border-gray-700">
            <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span> 1.2M Logs
        </div>
        <div class="flex items-center bg-gray-800/50 px-3 py-1 rounded-full border border-gray-700">
            <span class="w-2 h-2 bg-indigo-500 rounded-full mr-2"></span> Mode: <span class="text-gray-300 ml-1" x-text="category.toUpperCase()"></span>
        </div>
    </div>

    <div id="search-results" class="w-full overflow-y-auto max-w-4xl mt-8 md:mt-12 space-y-4"></div>
</div>