@php
    use App\Services\FontScale;
    $labels = [
        __('Very small'), __('Small'), __('Small-medium'),
        __('Medium'), __('Medium-large'), __('Large'), __('Very large'),
    ];
@endphp

<div x-data="fontScaleWidget()" class="space-y-4">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-bold text-navy">{{ __('Text size') }}</h3>
        <span class="text-sm font-bold text-navy" x-text="currentLabel"></span>
    </div>

    {{-- Slider visual: 7 As crescentes sobre uma linha com pontos --}}
    <div class="relative pt-3 pb-2">
        {{-- Linha de fundo --}}
        <div class="absolute left-4 right-4 top-1/2 h-px bg-gray-200" aria-hidden="true"></div>

        <div class="relative flex items-end justify-between gap-1">
            @foreach(FontScale::LEVELS as $i => $value)
                <button type="button"
                        x-on:click="setScale({{ $i }})"
                        :class="index === {{ $i }} ? 'text-navy' : 'text-gray-400 hover:text-gray-600'"
                        class="flex flex-col items-center justify-end gap-2 px-1 group transition"
                        :aria-pressed="index === {{ $i }}"
                        aria-label="{{ $labels[$i] }}">
                    <span @class([
                        'leading-none transition',
                        'text-[0.625rem]' => $i === 0,
                        'text-xs' => $i === 1,
                        'text-sm' => $i === 2,
                        'text-base' => $i === 3,
                        'text-lg' => $i === 4,
                        'text-xl' => $i === 5,
                        'text-2xl' => $i === 6,
                    ])
                    :class="index === {{ $i }} ? 'font-extrabold' : 'font-medium'">A</span>
                    <span class="w-2.5 h-2.5 rounded-full border border-gray-300 transition"
                          :class="index === {{ $i }} ? 'bg-navy border-navy scale-110' : 'bg-gray-200'"></span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Pré-visualização --}}
    <div class="bg-gray-50 border-l-4 border-navy rounded p-3 sm:p-4">
        <p class="text-base text-navy leading-snug">{{ __('This is the text size you will see across the app.') }}</p>
    </div>
</div>
