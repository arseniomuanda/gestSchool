@props([
    'show' => 'false',          // expressão Alpine boolean (ex: "clearAllOpen")
    'onConfirm' => null,        // expressão Alpine a correr no Confirmar (ex: "doClearAll()")
    'title' => null,
    'message' => null,
    'confirmLabel' => null,
    'cancelLabel' => null,
    'variant' => 'danger',      // danger|primary|secondary
])
<div x-show="{{ $show }}" x-cloak
     role="dialog" aria-modal="true"
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     @keydown.escape.window="{{ $show }} = false">
    {{-- overlay --}}
    <div class="absolute inset-0 bg-gray-900/60"
         x-show="{{ $show }}"
         x-transition.opacity
         @click="{{ $show }} = false"></div>

    {{-- diálogo --}}
    <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6"
         x-show="{{ $show }}"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        @if($title)
            <h3 class="text-lg font-bold text-navy mb-2">{{ $title }}</h3>
        @endif
        @if($message)
            <p class="text-sm text-muted mb-5">{{ $message }}</p>
        @endif

        {{ $slot ?? '' }}

        <div class="flex items-center justify-end gap-2 mt-4">
            <x-btn variant="secondary" type="button" @click="{{ $show }} = false">
                {{ $cancelLabel ?? __('Cancel') }}
            </x-btn>
            @if($onConfirm)
                <x-btn :variant="$variant" type="button" @click="{{ $onConfirm }}">
                    {{ $confirmLabel ?? __('Confirm') }}
                </x-btn>
            @endif
        </div>
    </div>
</div>
