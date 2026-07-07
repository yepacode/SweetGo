<?php

namespace App\Providers;

use App\Models\Bitacora;
use App\View\Composers\NotificacionesComposer;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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
        // Forzar HTTPS y cookies "secure" en producción.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
            config([
                'session.secure' => true,
                'session.http_only' => true,
                'session.same_site' => 'lax',
            ]);
        }

        // Notificaciones en el layout admin.
        View::composer('layouts.admin', NotificacionesComposer::class);

        // Bitácora: inicio y cierre de sesión.
        Event::listen(Login::class, function (Login $event) {
            Bitacora::create([
                'user_id' => $event->user->getAuthIdentifier(),
                'accion' => 'inició sesión',
                'descripcion' => "Inició sesión: {$event->user->name}",
                'ip' => request()->ip(),
            ]);
        });

        Event::listen(Logout::class, function (Logout $event) {
            if ($event->user) {
                Bitacora::create([
                    'user_id' => $event->user->getAuthIdentifier(),
                    'accion' => 'cerró sesión',
                    'descripcion' => "Cerró sesión: {$event->user->name}",
                    'ip' => request()->ip(),
                ]);
            }
        });
    }
}
