@props([
    'title' => null,
    'searchPlaceholder' => null,
    'searchValue' => '',
    'searchName' => 'q',
    'createUrl' => null,
    'createLabel' => null,
    'filters' => null,
])

<x-card :title="$title">
    @if($searchPlaceholder || $createUrl || $filters)
        <form method="GET" class="filter-bar mb-6" autocomplete="off" role="search">
            @if($searchPlaceholder)
                <div class="grow min-w-[200px]">
                    <label class="form-label">{{ __('Search') }}</label>
                    <input type="search"
                           name="{{ $searchName }}"
                           value="{{ $searchValue }}"
                           class="form-input"
                           placeholder="{{ $searchPlaceholder }}"
                           autocomplete="off"
                           autocorrect="off"
                           autocapitalize="off"
                           spellcheck="false"
                           data-form-type="search">
                </div>
            @endif
            {{ $filters }}
            <div class="flex items-end gap-2">
                <button type="submit" class="btn btn-secondary">{{ __('Search') }}</button>
                @if(request()->query())
                    <a href="{{ url()->current() }}" class="btn-link-muted text-xs">{{ __('Clear') }}</a>
                @endif
            </div>
            @if($createUrl)
                <a href="{{ $createUrl }}" class="btn btn-primary ms-auto">
                    <x-lucide-plus class="w-4 h-4" />
                    {{ $createLabel ?? __('New') }}
                </a>
            @endif
        </form>
    @endif

    <div class="table-wrapper">
        <table class="table">
            {{ $slot }}
        </table>
    </div>

    @isset($footer)
        <div class="mt-4">{{ $footer }}</div>
    @endisset
</x-card>
