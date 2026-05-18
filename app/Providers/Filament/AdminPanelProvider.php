<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SetFilamentLocale;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
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
            ->login()
            ->userMenuItems([
                Action::make('switchToArabic')
                    ->label('العربية')
                    ->icon('heroicon-o-language')
                    ->url(fn(): string => route('admin.switch-language', ['locale' => 'ar']))
                    ->visible(fn(): bool => app()->getLocale() !== 'ar'),

                Action::make('switchToEnglish')
                    ->label('English')
                    ->icon('heroicon-o-language')
                    ->url(fn(): string => route('admin.switch-language', ['locale' => 'en']))
                    ->visible(fn(): bool => app()->getLocale() !== 'en'),
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                function (): string {
                    $locale = app()->getLocale();
                    $direction = $locale === 'ar' ? 'rtl' : 'ltr';

                    return <<<HTML
                        <script>
                            document.documentElement.setAttribute('dir', '{$direction}');
                            document.documentElement.setAttribute('lang', '{$locale}');
                        </script>
                    HTML;
                }
            )
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                SetFilamentLocale::class,
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
