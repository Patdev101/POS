<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
    $middleware->redirectGuestsTo(function (Request $request) {
        return $request->is('api/*')
            ? null
            : route('login');
    });

    $middleware->appendToGroup('api', \App\Http\Middleware\EnsureNoForcedPasswordChange::class);
})

    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // A dropped or unreachable database must never show its raw error
        // (server address, database name, SQL) to whoever is at the till.
        // The real error still goes to the log.
        $exceptions->render(function (\PDOException $e, Request $request) {
            if ($e instanceof \Illuminate\Database\UniqueConstraintViolationException) {
                return null;
            }

            $message = 'The system can\'t reach its database right now. Please try again in a moment.';

            return $request->is('api/*') || $request->expectsJson()
                ? response()->json(['message' => $message], 503)
                : response($message, 503);
        });
    })->create();
