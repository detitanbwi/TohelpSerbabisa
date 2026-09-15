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
    }
}
