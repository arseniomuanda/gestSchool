<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Servidor de páginas de ajuda in-app (issue #43).
 *
 * Resolve uma chave estilo `alunos.index` para o ficheiro
 * `resources/help/alunos/index.{locale}.md`, renderiza para HTML e
 * devolve. Fallback para `.pt.md` se o locale actual não tiver.
 *
 * Cache server-side de 1h por (chave + locale) para evitar reler/parse
 * do markdown em cada abertura do drawer.
 */
class HelpController extends Controller
{
    public function show(Request $request, string $key): Response
    {
        // Sanitização defensiva — só letras minúsculas, dígitos, ponto, hífen
        if (! preg_match('/^[a-z0-9.\-]+$/', $key)) {
            abort(404);
        }

        $locale = app()->getLocale();
        $cacheKey = "help.$key.$locale";

        $html = Cache::remember($cacheKey, now()->addHour(), function () use ($key, $locale) {
            $path = $this->resolvePath($key, $locale);
            if (! $path) return null;

            $markdown = File::get($path);
            return Str::markdown($markdown);
        });

        if (! $html) {
            return response("<p class='text-muted'>" . __('No help available for this page yet.') . "</p>", 404);
        }

        return response($html)->header('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Verifica existência sem ler conteúdo — usado pelo <x-help-button>
     * para decidir se renderiza ou não.
     */
    public static function exists(string $key, ?string $locale = null): bool
    {
        if (! preg_match('/^[a-z0-9.\-]+$/', $key)) return false;
        $locale = $locale ?? app()->getLocale();

        return (new self())->resolvePath($key, $locale) !== null;
    }

    /** Resolve a key para caminho absoluto, com fallback para `pt`. */
    protected function resolvePath(string $key, string $locale): ?string
    {
        $relativePath = str_replace('.', '/', $key);
        $base = resource_path("help/{$relativePath}");

        $candidates = [
            "{$base}.{$locale}.md",
            "{$base}.pt.md",   // fallback
        ];

        foreach ($candidates as $p) {
            if (File::exists($p)) {
                return $p;
            }
        }
        return null;
    }
}
