<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set the default string length for the database schema
        Schema::defaultStringLength(191);

        // Force HTTPS in production or behind SSL proxy
        if (app()->environment('production') || str_contains(config('app.url'), 'https://') || request()->header('x-forwarded-proto') === 'https' || request()->isSecure()) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Implicitly grant 'super_admin' role all permissions
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

        // Register custom edit profile form with username support
        \Livewire\Livewire::component('edit_profile_form', \App\Livewire\CustomEditProfileForm::class);

        // Register custom login Livewire component
        \Livewire\Livewire::component('app.filament.pages.auth.login', \App\Filament\Pages\Auth\Login::class);

        // Share Cabang and Active Cabang ONLY for frontend web views (not Filament internal components)
        \Illuminate\Support\Facades\View::composer(['pages.*', 'layouts.*', 'components.*', 'welcome', 'errors.*'], function ($view) {
            try {
                static $cachedCabangs = null;
                if ($cachedCabangs === null) {
                    $cachedCabangs = \App\Models\Cabang::all();
                }

                if ($cachedCabangs->isNotEmpty()) {
                    $selectedId = session('selected_cabang_id') ?? request('cabang_id');
                    $activeCabang = $cachedCabangs->firstWhere('id', $selectedId) ?? $cachedCabangs->first();
                    $hasSelectedCabang = session()->has('selected_cabang_id') || request()->has('cabang_id');

                    $view->with([
                        'globalCabangs' => $cachedCabangs,
                        'globalActiveCabang' => $activeCabang,
                        'globalHasSelectedCabang' => $hasSelectedCabang,
                    ]);
                }
            } catch (\Throwable $e) {
                // Ignore during migrations or initial setup
            }
        });
    }
}
