<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cookie;

/**
 * Helper para a preferência de tamanho de texto do utilizador.
 *
 * 7 níveis lineares (10% steps): 0.85, 0.93, 1.00, 1.10, 1.20, 1.35, 1.50.
 * Persistido via:
 *  - cookie `gs_font_scale` (1 ano) — sempre, mesmo guest
 *  - coluna `users.font_scale` — quando autenticado, para cross-device
 */
class FontScale
{
    /** @var array<int,float> */
    public const LEVELS = [0.85, 0.93, 1.00, 1.10, 1.20, 1.35, 1.50];

    public const DEFAULT = 1.00;
    public const COOKIE_NAME = 'gs_font_scale';
    public const COOKIE_MINUTES = 60 * 24 * 365;       // 1 ano

    public static function isValid(float $scale): bool
    {
        foreach (self::LEVELS as $v) {
            if (abs($v - $scale) < 0.001) return true;
        }
        return false;
    }

    public static function indexOf(float $scale): int
    {
        foreach (self::LEVELS as $i => $v) {
            if (abs($v - $scale) < 0.001) return $i;
        }
        return 2; // default = 1.00
    }

    /** Resolve a escala actual: DB (auth) → cookie → default. */
    public static function current(?User $user = null): float
    {
        if ($user && $user->font_scale && self::isValid((float) $user->font_scale)) {
            return (float) $user->font_scale;
        }
        $cookie = (float) Cookie::get(self::COOKIE_NAME, self::DEFAULT);
        return self::isValid($cookie) ? $cookie : self::DEFAULT;
    }

    /** @return array<int,string> Labels traduzidos. */
    public static function labels(): array
    {
        return [
            __('Very small'),
            __('Small'),
            __('Small-medium'),
            __('Medium'),
            __('Medium-large'),
            __('Large'),
            __('Very large'),
        ];
    }
}
