<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | LogSearch Terminal</title>
    <script src="<?= asset('js/tailwindcss.js'); ?>"></script>
    <script defer src="<?= asset('js/alpinejs-cdn.min.js'); ?>"></script>
    <script src="<?= asset('js/htmx.min.js'); ?>"></script>
    
    <style>
        [x-cloak] { display: none !important; }
        .htmx-indicator { display: none; }
        .htmx-request .htmx-indicator { display: inline; }
        .htmx-request.btn-content { display: none; }
    </style>
</head>
<body class="h-full bg-black text-gray-100 font-sans selection:bg-indigo-500/30">

    <div class="min-h-screen flex items-center justify-center relative overflow-hidden px-4">
        <div class="absolute top-0 -left-4 w-72 h-72 bg-indigo-900/20 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-0 -right-4 w-72 h-72 bg-purple-900/20 rounded-full blur-[120px]"></div>

        <div class="max-w-md w-full space-y-8 relative" x-data="{ showError: false, errorMessage: '' }">
            
            <div class="text-center">
                <div class="mx-auto h-12 w-12 bg-indigo-600 rounded-xl flex items-center justify-center mb-4 shadow-lg shadow-indigo-500/20">
                    <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h2 class="text-3xl font-bold tracking-tight text-white">LogSearch <span class="text-indigo-500">v2</span></h2>
                <p class="mt-2 text-sm text-gray-500 font-mono">Restricted Access Terminal</p>
            </div>

            <div class="bg-gray-900/50 backdrop-blur-xl border border-gray-800 p-8 rounded-2xl shadow-2xl">
                
                <div id="response-message" 
                     x-show="showError" 
                     x-cloak
                     class="mb-6 p-3 rounded-lg bg-red-900/20 border border-red-800/50 text-red-400 text-sm flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                    <span x-text="errorMessage"></span>
                </div>

                <form hx-post="/auth/login" 
                      hx-target="#response-message"
                      hx-swap="innerHTML"
                      hx-on::before-request="showError = false"
                      hx-on::after-request="if(event.detail.xhr.status !== 200) { showError = true; errorMessage = event.detail.xhr.responseText; } else { window.location.href = '/dashboard' }"
                      class="space-y-5">
                    
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Username</label>
                        <input type="text" name="username" required
                               class="w-full bg-black/50 border border-gray-700 rounded-xl px-4 py-3 text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all"
                               placeholder="Enter your identity">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Password</label>
                        <input type="password" name="password" required
                               class="w-full bg-black/50 border border-gray-700 rounded-xl px-4 py-3 text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all"
                               placeholder="••••••••">
                    </div>

                    <div class="flex items-center justify-between py-2">
                        <label class="flex items-center text-sm text-gray-400 cursor-pointer">
                            <input type="checkbox" class="w-4 h-4 rounded border-gray-700 bg-black text-indigo-600 focus:ring-offset-gray-900">
                            <span class="ml-2">Keep session alive</span>
                        </label>
                    </div>

                    <button type="submit" 
                            class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-3 rounded-xl transition-all duration-200 transform active:scale-[0.98] shadow-lg shadow-indigo-600/20 flex items-center justify-center">
                        <span class="htmx-indicator mr-2">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                        <span>AUTHENTICATE</span>
                    </button>
                </form>
            </div>

            <p class="text-center text-xs text-gray-600 font-mono">
                &copy; 2026 LOGSEARCH.IO // SYSTEM_STABLE_V2
            </p>
        </div>

        <!-- <div class="min-h-screen flex items-center justify-center bg-black px-4">
            <div class="max-w-md w-full space-y-8 bg-gray-900/50 p-10 rounded-2xl border border-gray-800 backdrop-blur-sm shadow-2xl">
                
                <div class="text-center">
                    <div class="mx-auto h-12 w-12 bg-indigo-600 rounded-xl flex items-center justify-center mb-4 shadow-lg shadow-indigo-500/20">
                        <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h2 class="text-3xl font-extrabold text-white tracking-tight">LogSearch Access</h2>
                    <p class="mt-2 text-sm text-gray-400">Enter your credentials to access the terminal</p>
                </div>

                <div id="login-error" class="hidden bg-red-900/20 border border-red-800 text-red-400 px-4 py-3 rounded-lg text-sm">
                </div>

                <form class="mt-8 space-y-6" 
                      hx-post="/auth/login" 
                      hx-target="#login-error" 
                      hx-swap="innerHTML"
                      hx-on::after-request="if(event.detail.xhr.status === 200) window.location.href = '/dashboard'">
                    
                    <div class="space-y-4">
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-400 mb-1">Username</label>
                            <input id="username" name="username" type="text" required 
                                   class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition placeholder-gray-500"
                                   placeholder="admin_core">
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-400 mb-1">Password</label>
                            <input id="password" name="password" type="password" required 
                                   class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition placeholder-gray-500"
                                   placeholder="••••••••">
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember-me" name="remember-me" type="checkbox" 
                                   class="h-4 w-4 bg-gray-800 border-gray-700 text-indigo-600 rounded focus:ring-indigo-500 focus:ring-offset-gray-900">
                            <label for="remember-me" class="ml-2 block text-sm text-gray-400">Remember session</label>
                        </div>
                    </div>

                    <button type="submit" 
                            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-bold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 shadow-lg shadow-indigo-500/20">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <svg class="htmx-indicator animate-spin h-5 w-5 text-indigo-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                        AUTHENTICATE
                    </button>
                </form>

                <p class="text-center text-xs text-gray-600 font-mono">
                    SECURE ACCESS TERMINAL v2.0.26
                </p>
            </div>
        </div> -->

    </div>

</body>
</html>