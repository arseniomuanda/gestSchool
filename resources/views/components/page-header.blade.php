@props([
    'title',
    'subtitle' => null,
    'actions' => null,
    'breadcrumb' => null,
    'help' => true,    // false para suprimir o botão; ou string para override da chave
])

<header class="page-header">
    <div>
        @if($breadcrumb)<div class="page-breadcrumb mb-1">{{ $breadcrumb }}</div>@endif
        <h1 class="page-title">{{ $title }}</h1>
        @isset($subtitleSlot)
            <div class="page-subtitle">{{ $subtitleSlot }}</div>
        @elseif($subtitle)
            <p class="page-subtitle">{{ $subtitle }}</p>
        @endisset
    </div>
    @if($actions || $help !== false)
        <div class="flex items-center gap-2">
            @if($actions){{ $actions }}@endif
            @if($help !== false)
                <x-help-button :key="is_string($help) ? $help : null" />
            @endif
        </div>
    @endif
</header>
