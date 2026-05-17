@props([
    'key' => null,
    'label' => null,
])
{{--
    Botão "?" no header da página. Renderiza-se condicionalmente:
    - Se a chave (default Route::currentRouteName()) tiver ficheiro de help → mostra
    - Se não → não renderiza nada (fail silent)
    Click dispatches evento Alpine 'open-help' que abre o <x-help-drawer>.
--}}
@php
    $resolvedKey = $key ?? \Illuminate\Support\Facades\Route::currentRouteName();
    $available = $resolvedKey && \App\Http\Controllers\HelpController::exists($resolvedKey);
@endphp
@if($available)
    {{-- x-data="{}" obriga Alpine a inicializar este elemento; sem isto,
         o @click pode não ser registado se não houver x-data ancestor. --}}
    <button type="button"
            x-data="{}"
            @click="window.dispatchEvent(new CustomEvent('open-help', { detail: { key: '{{ $resolvedKey }}' } }))"
            title="{{ $label ?? __('Help for this page') }}"
            aria-label="{{ $label ?? __('Help for this page') }}"
            class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-blue-50 text-primary hover:bg-blue-100 transition border border-blue-200">
        <x-lucide-help-circle class="w-5 h-5" />
    </button>
@endif
