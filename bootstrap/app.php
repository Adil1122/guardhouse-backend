<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureUserAccessMiddleware;
use App\Http\Middleware\CheckHoneypot;
use App\Http\Middleware\CheckReferer;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use App\Http\Middleware\BindCustomerProfileId;
use App\Http\Middleware\BindSiteId;
use App\Http\Middleware\BindShiftId;
use App\Http\Middleware\CanEditInvoice;
use App\Http\Middleware\CanEditTimesheet;
use App\Http\Middleware\BindShiftClockInOutTime;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'ensure.user.access' => EnsureUserAccessMiddleware::class,
            'check.referer' => CheckReferer::class,
            'check.honeypot' => CheckHoneypot::class,
            'ability'   => CheckAbilities::class,
            'abilities' => CheckForAnyAbility::class,
            'bind.customer.profile.id' => BindCustomerProfileId::class,
            'bind.site.id' => BindSiteId::class,
            'bind.shift.id' => BindShiftId::class,
            'can.edit.invoice' => CanEditInvoice::class,
            'can.edit.timesheet' => CanEditTimesheet::class,
            'bind.shift.clock.in.out.time' => BindShiftClockInOutTime::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        $middleware->statefulApi();

        // Add CORS middleware globally
        $middleware->append(HandleCors::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::error('Validation Failed: ' . json_encode($e->errors()));
        });
    })->create();
