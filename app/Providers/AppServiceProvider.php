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

        // Implicitly grant 'super_admin' role all permissions
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

        // Register custom edit profile form with username support
        \Livewire\Livewire::component('edit_profile_form', \App\Livewire\CustomEditProfileForm::class);

        // Register custom login Livewire component
        \Livewire\Livewire::component('app.filament.pages.auth.login', \App\Filament\Pages\Auth\Login::class);

        // Share Cabang and Active Cabang across all Blade views
        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            try {
                if (Schema::hasTable('cabangs')) {
                    $cabangs = \App\Models\Cabang::all();
                    $selectedId = session('selected_cabang_id') ?? request('cabang_id');
                    $activeCabang = $cabangs->firstWhere('id', $selectedId) ?? $cabangs->first();
                    $hasSelectedCabang = session()->has('selected_cabang_id') || request()->has('cabang_id');
                    $view->with([
                        'globalCabangs' => $cabangs,
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
