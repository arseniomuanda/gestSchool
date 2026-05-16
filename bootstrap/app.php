<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Confia em qualquer proxy upstream (nginx → cloudflared, etc.) e lê
        // X-Forwarded-Proto/Host para gerar URLs absolutas no esquema correcto.
        // Sem isto, ao expor via tunnel HTTPS os assets saem em http:// e o
        // browser bloqueia por mixed content.
        $middleware->trustProxies(at: '*');

        // SetLocale corre como global para que funcione em qualquer rota,
        // inclusive 404 (onde o middleware do grupo web pode não correr).
        $middleware->append(\App\Http\Middleware\SetLocale::class);

        // O cookie de locale fica fora da encriptação para que rotas
        // sem sessão (ex: 404 sem auth) também o consigam ler.
        $middleware->encryptCookies(except: ['gestschool_locale']);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Quando o utilizador não tem o role/permission necessário (Spatie),
        // mostra a view 403 em vez de uma página default.
        $exceptions->render(function (UnauthorizedException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('You do not have permission to view this resource.')], 403);
            }
            return response()->view('errors.403', [
                'exception' => $e,
                'message' => __('You do not have permission to view this resource.'),
            ], 403);
        });

        // Não autenticado: redirecciona para login (default Laravel) — mantemos.
        // Para casos onde queremos a view 401, o Laravel já trata via /login redirect.

        // Reportar para o log mas não expor detalhes em produção (default).
    })->create();
