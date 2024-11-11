<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        api: __DIR__.'/../routes/api.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->throttleWithRedis();
        $middleware->throttleApi('api', true);
        $middleware->authenticateSessions();
        $middleware->statefulApi();

        $middleware->trimStrings(
            except: [
                'current',
                'current_password',
                'password',
                'password_confirmation',
                'current_password_2fa',
            ]
        );

        $middleware->validateCsrfTokens(
            except: [
                'api/auth/login',
            ]
        );

        $middleware->web(append: [
            \App\Http\MiddleWare\ProxyAuthentication::class,
            \App\Http\Middleware\HandleInertiaRequests::class, // Must be the last item!
            ]
        );

        $middleware->alias([
            '2fa' => \App\Http\Middleware\VerifyTwoFactorAuth::class,
            'webauthn' => \App\Http\Middleware\VerifyWebauthn::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontFlash([
            'current',
            'current_password',
            'password',
            'password_confirmation',
            'current_password_2fa',
        ]);
    })->create();
