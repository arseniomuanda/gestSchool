@props([
    'variant' => 'navbar', // 'navbar' (estilo discreto) ou 'floating' (botão com border, p/ guest)
])

<div class="relative" x-data="fontScaleWidget()" @click.outside="open = false">
    <button type="button"
            x-on:click="open = !open"
            @class([
                'navbar-icon-btn' => $variant === 'navbar',
                'w-9 h-9 inline-flex items-center justify-center rounded-full bg-white border border-gray-200 text-navy hover:bg-gray-50 shadow-sm transition' => $variant === 'floating',
            ])
            :title="'{{ __('Text size') }}' + ' — ' + currentLabel"
            :aria-expanded="open"
            aria-label="{{ __('Text size') }}">
        <span class="font-bold leading-none flex items-baseline">
            <span class="text-sm">A</span><span class="text-[0.625rem] opacity-70">A</span>
        </span>
    </button>

    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute end-0 mt-2 w-[320px] max-w-[calc(100vw-2rem)] bg-white border border-gray-200 rounded-lg shadow-xl p-4 sm:p-5 z-50">
        @include('partials.font-scale-widget')
    </div>
</div>
