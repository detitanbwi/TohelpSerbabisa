{{-- Global SPA & Livewire Navigation Loading Indicator for Filament Panels --}}
<div id="filament-global-loading-bar" 
     class="fixed top-0 left-0 right-0 h-[3px] pointer-events-none transition-all duration-300 opacity-0 -translate-y-full"
     style="z-index: 999999;">
    <div id="filament-global-loading-progress" 
         class="h-full bg-gradient-to-r from-amber-500 via-amber-400 to-yellow-300 shadow-[0_0_10px_rgba(245,158,11,0.7)] transition-all duration-200"
         style="width: 0%;"></div>
</div>

{{-- Subtle Redirect Notice Toast / Indicator --}}
<div id="filament-redirect-overlay" 
     class="fixed top-4 right-4 pointer-events-none transition-all duration-300 opacity-0 translate-y-[-10px] hidden"
     style="z-index: 999998;">
    <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-xl bg-gray-900/90 dark:bg-gray-800/90 text-white shadow-xl backdrop-blur-md border border-gray-700/50 text-xs font-medium">
        <svg class="animate-spin h-4 w-4 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Memuat halaman tujuan...</span>
    </div>
</div>

<script>
    (function() {
        let loadingBar = null;
        let progressBar = null;
        let redirectOverlay = null;
        let progressInterval = null;
        let currentProgress = 0;
        let activeRequests = 0;
        let requestTimer = null;

        function getElements() {
            loadingBar = document.getElementById('filament-global-loading-bar');
            progressBar = document.getElementById('filament-global-loading-progress');
            redirectOverlay = document.getElementById('filament-redirect-overlay');
        }

        function startProgress() {
            getElements();
            if (!loadingBar || !progressBar) return;

            clearInterval(progressInterval);
            currentProgress = 15;
            progressBar.style.transition = 'width 200ms ease';
            progressBar.style.width = currentProgress + '%';
            loadingBar.classList.remove('opacity-0', '-translate-y-full');
            loadingBar.classList.add('opacity-100', 'translate-y-0');

            progressInterval = setInterval(() => {
                if (currentProgress < 70) {
                    currentProgress += Math.random() * 8 + 3;
                } else if (currentProgress < 90) {
                    currentProgress += Math.random() * 2 + 0.5;
                }
                if (progressBar) {
                    progressBar.style.width = Math.min(currentProgress, 92) + '%';
                }
            }, 250);
        }

        function finishProgress() {
            getElements();
            if (!loadingBar || !progressBar) return;

            clearInterval(progressInterval);
            currentProgress = 100;
            progressBar.style.transition = 'width 150ms ease';
            progressBar.style.width = '100%';

            setTimeout(() => {
                if (loadingBar) {
                    loadingBar.classList.add('opacity-0', '-translate-y-full');
                    loadingBar.classList.remove('opacity-100', 'translate-y-0');
                    setTimeout(() => {
                        if (progressBar) {
                            progressBar.style.transition = 'none';
                            progressBar.style.width = '0%';
                        }
                    }, 300);
                }
            }, 250);

            // Hide redirect indicator if visible
            if (redirectOverlay) {
                redirectOverlay.classList.add('opacity-0', 'translate-y-[-10px]');
                setTimeout(() => {
                    redirectOverlay.classList.add('hidden');
                }, 300);
            }
            document.body.classList.remove('is-navigating-redirect');
        }

        function showRedirectNotice() {
            getElements();
            startProgress();
            document.body.classList.add('is-navigating-redirect');
            if (redirectOverlay) {
                redirectOverlay.classList.remove('hidden');
                setTimeout(() => {
                    redirectOverlay.classList.remove('opacity-0', 'translate-y-[-10px]');
                    redirectOverlay.classList.add('opacity-100', 'translate-y-0');
                }, 10);
            }
        }

        // Livewire 3 SPA Navigation Hooks
        document.addEventListener('livewire:navigating', () => {
            startProgress();
        });

        document.addEventListener('livewire:navigated', () => {
            finishProgress();
        });

        // Livewire Request Hooks (catch redirects & keep loading indicators alive)
        document.addEventListener('livewire:init', () => {
            if (typeof Livewire !== 'undefined' && Livewire.hook) {
                Livewire.hook('request', ({ uri, options, payload, respond, succeed, fail }) => {
                    activeRequests++;
                    
                    // Show progress bar if request takes > 120ms
                    clearTimeout(requestTimer);
                    requestTimer = setTimeout(() => {
                        if (activeRequests > 0) {
                            startProgress();
                        }
                    }, 120);

                    succeed(({ status, json }) => {
                        // Check if response contains a redirect effect
                        try {
                            const parsed = typeof json === 'string' ? JSON.parse(json) : json;
                            if (parsed?.effects?.redirect) {
                                // Keep loading active during redirect!
                                showRedirectNotice();
                                return;
                            }
                        } catch (e) {}

                        activeRequests = Math.max(0, activeRequests - 1);
                        if (activeRequests === 0 && !document.body.classList.contains('is-navigating-redirect')) {
                            clearTimeout(requestTimer);
                            finishProgress();
                        }
                    });

                    fail(({ status, content, preventDefault }) => {
                        activeRequests = Math.max(0, activeRequests - 1);
                        clearTimeout(requestTimer);
                        finishProgress();
                    });
                });
            }
        });
    })();
</script>
