<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\LoginCi;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(LoginCi::class)
            ->brandName('JOTA-JOTI 2026')
            // Fuentes autohospedadas (CLAUDE.md §3): sin CDN. La tipografía del evento llega con la Fase 2.
            ->font('system-ui', provider: LocalFontProvider::class)
            // Colores de marca (docs/design/identidad-visual.md); Filament genera la escala completa desde cada uno.
            ->colors([
                'primary' => Color::hex('#622599'),
                'info' => Color::hex('#0079C0'),
                'success' => Color::hex('#248737'),
                'warning' => Color::hex('#F9B700'),
                'danger' => Color::hex('#B3261E'),
            ])
            ->favicon(asset('brand/jotajoti-2026.svg'))
            ->navigationItems([
                NavigationItem::make('Vista de dirigente (celular)')
                    ->url('/dirigente')
                    ->icon(Heroicon::OutlinedDevicePhoneMobile)
                    ->group('Juego')
                    ->sort(10),
                NavigationItem::make('Vista de tienda (celular)')
                    ->url('/tienda')
                    ->icon('jota-moneda')
                    ->group('Juego')
                    ->sort(11),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
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
            ]);
    }
}
