<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use Filament\Pages\Dashboard;
use Filament\Navigation\MenuItem;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Navigation\NavigationGroup;
use Filament\Http\Middleware\Authenticate;
use Filament\Navigation\NavigationBuilder;
use Illuminate\Session\Middleware\StartSession;
use App\Filament\Karyawan\Widgets\AbsensiWidget;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Http\Middleware\AuthenticateSession;
use App\Filament\Karyawan\Resources\AbsensiResource;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Joaopaulolndev\FilamentEditProfile\Pages\EditProfilePage;
use Joaopaulolndev\FilamentEditProfile\FilamentEditProfilePlugin;

class KaryawanPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('karyawan')
            ->path('karyawan')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->login()
            ->discoverResources(in: app_path('Filament/Karyawan/Resources'), for: 'App\\Filament\\Karyawan\\Resources')
            ->discoverPages(in: app_path('Filament/Karyawan/Pages'), for: 'App\\Filament\\Karyawan\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Karyawan/Widgets'), for: 'App\\Filament\\Karyawan\\Widgets')
            ->widgets([
                AbsensiWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                FilamentEditProfilePlugin::make()
                    ->shouldRegisterNavigation(false)
                    ->shouldShowAvatarForm(
                        value: true,
                        directory: 'avatars', // image will be stored in 'storage/app/public/avatars
                        rules: 'mimes:jpeg,png,jpg|max:1024' //only accept jpeg and png files with a maximum size of 1MB
                    ),
            ])
            // topbar
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label(fn() => "Edit Profile")
                    ->url(fn (): string => EditProfilePage::getUrl())
                    ->icon('heroicon-m-cog-6-tooth'),
            ])
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                return $builder->groups([
                    NavigationGroup::make('')
                        ->items([
                            ...Dashboard::getNavigationItems(),
                            ...AbsensiResource::getNavigationItems(),
                        ]),
                ]);
            })
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn() => view('filament.karyawan.components.change-admin-panel')
            );
    }
}
