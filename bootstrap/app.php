<?php

use App\Console\Commands\GenerateWebPushKeys;
use App\Console\Commands\SendDuePushNotifications;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        GenerateWebPushKeys::class,
        SendDuePushNotifications::class,
    ])
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('tasks:send-push')->everyFiveMinutes();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
