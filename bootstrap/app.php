<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(fn (Request $request) => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Throwable $exception, Request $request) {
            if ($exception instanceof AuthenticationException) {
                return $request->expectsJson() ? null : redirect()->guest(route('login'));
            }
            if ($request->expectsJson() || $exception instanceof ValidationException) return null;

            $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
            if ($status < 500) return null;

            $referer = $request->headers->get('referer');
            $refererHost = $referer ? parse_url($referer, PHP_URL_HOST) : null;
            if ($request->hasSession() && $request->isMethod('POST') && $refererHost === $request->getHost()) {
                return redirect()->to($referer)->withInput()->with('form_error',
                    'We could not save this information because of a server problem. Your entered values were kept; please review the form and try again.');
            }

            return response()->view('errors.generic', [
                'retryUrl' => $request->user() ? route('dashboard') : route('login'),
            ], 500);
        });
    })->create();
