<?php

namespace App\Http\Controllers\Preferences;

use App\Http\Controllers\Controller;
use App\Services\FontScale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cookie;

class FontScaleController extends Controller
{
    public function update(Request $request): JsonResponse|Response
    {
        $data = $request->validate([
            'scale' => ['required', 'numeric', 'in:'.implode(',', FontScale::LEVELS)],
        ]);
        $scale = (float) $data['scale'];

        if ($user = $request->user()) {
            $user->font_scale = $scale;
            $user->save();
        }

        // Cookie de 1 ano, sempre — também para guests
        Cookie::queue(
            FontScale::COOKIE_NAME,
            (string) $scale,
            FontScale::COOKIE_MINUTES,
            '/',
            null,
            false,
            false,            // httpOnly=false: o widget precisa de ler
            false,
            'lax',
        );

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['scale' => $scale]);
        }
        return back();
    }
}
