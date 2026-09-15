<div class="min-h-screen w-full bg-[#091323] text-[#d9e3f9] antialiased flex flex-col justify-center relative font-['Hanken_Grotesk',sans-serif]">

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="{{ asset('images/logo-tohelp-kecil.png') }}">

  <!-- Fonts & Material Symbols -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:ital,wght@0,300..900;1,300..900&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

  <!-- Tailwind Script & Configuration -->
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "surface-tint": "#b4c5ff",
            "surface-bright": "#30394b",
            "on-surface-variant": "#c3c6d7",
            "on-secondary": "#472a00",
            "outline": "#8d90a0",
            "surface-container-lowest": "#050e1e",
            "surface-container-highest": "#2c3546",
            "on-error": "#690005",
            "outline-variant": "#434655",
            "background": "#091323",
            "tertiary": "#7bd0ff",
            "on-secondary-fixed": "#2a1700",
            "on-secondary-container": "#5b3800",
            "secondary-fixed": "#ffddb8",
            "on-primary": "#002a78",
            "on-primary-fixed": "#00174b",
            "inverse-on-surface": "#273142",
            "primary-container": "#2563eb",
            "on-primary-fixed-variant": "#003ea8",
            "tertiary-fixed-dim": "#7bd0ff",
            "tertiary-container": "#00759f",
            "on-secondary-fixed-variant": "#653e00",
            "surface": "#091323",
            "error-container": "#93000a",
            "primary-fixed-dim": "#b4c5ff",
            "surface-dim": "#091323",
            "on-tertiary-fixed-variant": "#004c69",
            "primary": "#b4c5ff",
            "surface-container-low": "#121c2c",
            "secondary": "#ffb95f",
            "on-surface": "#d9e3f9",
            "tertiary-fixed": "#c4e7ff",
            "inverse-primary": "#0053db",
            "surface-variant": "#2c3546",
            "on-tertiary-container": "#e1f2ff",
            "secondary-fixed-dim": "#ffb95f",
            "on-background": "#d9e3f9",
            "error": "#ffb4ab",
            "on-tertiary": "#00354a",
            "primary-fixed": "#dbe1ff",
            "on-error-container": "#ffdad6",
            "secondary-container": "#ee9800",
            "on-primary-container": "#eeefff",
            "surface-container-high": "#212a3b",
            "surface-container": "#162030",
            "inverse-surface": "#d9e3f9",
            "on-tertiary-fixed": "#001e2c"
          }
        }
      }
    };
  </script>

  <style>
    .login-form-wrapper input[type="text"],
    .login-form-wrapper input[type="email"],
    .login-form-wrapper input[type="password"] {
      background-color: #050e1e !important;
      border-color: rgba(67, 70, 85, 0.5) !important;
      color: #d9e3f9 !important;
      border-radius: 0.5rem !important;
      padding: 0.85rem 1rem !important;
      font-size: 0.9375rem !important;
    }
    .login-form-wrapper input:focus {
      border-color: #ffb95f !important;
      box-shadow: 0 0 0 1px #ffb95f !important;
    }
    .login-form-wrapper label, 
    .login-form-wrapper .fi-fo-field-wrp-label span {
      color: #d9e3f9 !important;
      font-size: 0.875rem !important;
      font-weight: 500 !important;
    }
    .login-form-wrapper input[type="checkbox"] {
      border-radius: 0.25rem !important;
      background-color: #050e1e !important;
      border-color: rgba(67, 70, 85, 0.7) !important;
      color: #ee9800 !important;
    }
  </style>

  <main class="w-full min-h-screen flex flex-col lg:flex-row items-stretch relative overflow-x-hidden">
    
    <!-- Left Side: Login Form Panel -->
    <section class="w-full lg:w-[46%] xl:w-[42%] flex flex-col justify-between p-6 sm:p-10 lg:p-12 z-20 bg-surface border-r border-outline-variant/30">
      
      <div class="max-w-md w-full mx-auto my-auto flex flex-col justify-center py-8">
        
        <!-- Header & Custom Logo ToHelp (Enlarged) -->
        <div class="mb-8">
          <div class="mb-6 flex items-center">
            <img 
              src="{{ asset('images/logo-tohelp.png') }}?v={{ time() }}" 
              alt="ToHelp SerbaBisa Logo" 
              class="h-12 sm:h-14 w-auto object-contain"
              onerror="this.onerror=null; this.src='{{ asset('logo-tohelp.png') }}';"
            >
          </div>
          <h1 class="text-2xl sm:text-3xl font-bold text-on-surface tracking-tight mb-2">Masuk ke Akun Anda</h1>
          <p class="text-sm text-on-surface-variant">Portal internal manajemen & operasional ToHelp SerbaBisa</p>
        </div>



        @if (session()->has('status'))
          <div class="mb-5 p-4 bg-primary-container/30 border border-primary/40 rounded-lg text-on-primary-container text-xs flex items-center gap-2.5">
            <span class="material-symbols-outlined text-[20px] text-primary">info</span>
            <span>{{ session('status') }}</span>
          </div>
        @endif

        <!-- Livewire Filament Form -->
        <form wire:submit.prevent="authenticate" class="flex flex-col gap-5 login-form-wrapper" id="authPortalForm">
          {{ $this->form }}

          <!-- Submit Button -->
          <div class="pt-2">
            <button 
              type="submit" 
              class="w-full py-3.5 px-4 bg-secondary-container text-on-secondary-fixed text-sm font-bold rounded-lg shadow-md hover:bg-secondary active:scale-[0.99] transition-all flex items-center justify-center gap-2 cursor-pointer" 
              id="loginSubmitBtn"
              wire:loading.attr="disabled"
            >
              <span wire:loading.remove>Masuk ke Portal</span>
              <span wire:loading.remove class="material-symbols-outlined text-[18px]">arrow_forward</span>
              <span wire:loading class="flex items-center gap-2">
                <span class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                <span>Memverifikasi Akun...</span>
              </span>
            </button>
          </div>



        </form>

      </div>



    </section>

    <!-- Right Side: Branding Showcase Panel -->
    <section class="w-full lg:w-[54%] xl:w-[58%] relative min-h-[400px] lg:min-h-screen bg-[#F8FAFC] flex flex-col justify-between p-8 sm:p-12 lg:p-16 overflow-hidden border-l border-slate-200">
      
      <!-- Center Content Showcase -->
      <div class="my-auto py-8 max-w-xl z-10 flex flex-col gap-6">
        <div>
          <h2 class="text-3xl sm:text-4xl lg:text-5xl text-[#091323] font-bold leading-tight mb-4">
            Solusi Terintegrasi Layanan Lapangan
          </h2>
          <p class="text-slate-600 text-sm sm:text-base leading-relaxed">
            Pusat koordinasi dan monitoring penugasan kerja mitra operasional di seluruh wilayah cabang layanan ToHelp SerbaBisa secara real-time dan terstandardisasi.
          </p>
        </div>
      </div>



    </section>

  </main>

  <script>
    // CSRF & Session Expiration Handler (Prevent 419 stuck)
    document.addEventListener('livewire:init', () => {
      Livewire.hook('request', ({ fail }) => {
        fail(({ status, preventDefault }) => {
          if (status === 419) {
            preventDefault();
            // Otomatis reload halaman untuk mendapatkan token CSRF & sesi baru secara mulus
            window.location.reload();
          }
        });
      });
    });

    // Auto refresh token jika tab dibiarkan idle lebih dari 10 menit
    let pageOpenedAt = Date.now();
    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'visible' && (Date.now() - pageOpenedAt > 10 * 60 * 1000)) {
        window.location.reload();
      }
    });
  </script>

</div>
