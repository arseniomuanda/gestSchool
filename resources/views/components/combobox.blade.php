@props([
    'name',
    'label' => null,
    'options' => [],          // Iterable de ['value' => ..., 'label' => ..., 'hint' => ...]
    'selected' => null,        // valor seleccionado (string|int|null)
    'placeholder' => null,     // texto quando nada está seleccionado
    'searchPlaceholder' => null,
    'help' => null,
    'required' => false,
    'disabled' => false,
    'clearable' => true,       // mostrar X para limpar
    'maxVisible' => 100,       // limite de itens renderizados (virtualização leve)
    'emptyText' => null,
])

@php
    $id = $attributes->get('id') ?? $name;
    $placeholder ??= __('Select') . '...';
    $searchPlaceholder ??= __('Search') . '...';
    $emptyText ??= __('No results found');

    // Normalizar options para array de objectos comparáveis
    $normalized = collect($options)->map(function ($o) {
        if (is_array($o)) {
            return [
                'value' => (string) ($o['value'] ?? ''),
                'label' => (string) ($o['label'] ?? ''),
                'hint'  => isset($o['hint']) ? (string) $o['hint'] : null,
            ];
        }
        if (is_object($o)) {
            return [
                'value' => (string) ($o->value ?? ''),
                'label' => (string) ($o->label ?? ''),
                'hint'  => isset($o->hint) ? (string) $o->hint : null,
            ];
        }
        return ['value' => (string) $o, 'label' => (string) $o, 'hint' => null];
    })->values()->all();

    $selectedValue = $selected !== null ? (string) $selected : null;
    $hasError = $errors->has($name);
@endphp

<div class="form-group">
    @if($label)
        <label for="{{ $id }}" class="form-label">
            {{ $label }}@if($required) <span class="text-danger">*</span>@endif
        </label>
    @endif

    <div
        x-data="combobox({
            options: @js($normalized),
            initialValue: @js($selectedValue),
            maxVisible: {{ (int) $maxVisible }},
        })"
        x-on:keydown.escape.prevent.stop="close()"
        x-on:click.outside="close()"
        @class([
            'combobox',
            'is-open' => false,
        ])
    >
        {{-- Hidden input — é isto que o form submete --}}
        <input type="hidden"
               name="{{ $name }}"
               id="{{ $id }}"
               x-model="value"
               @if($required) required @endif />

        {{-- Trigger --}}
        <button type="button"
                x-ref="trigger"
                x-on:click="toggle()"
                x-on:keydown.down.prevent="open(); highlightFirst()"
                x-on:keydown.up.prevent="open(); highlightLast()"
                x-on:keydown.enter.prevent="toggle()"
                x-bind:aria-expanded="isOpen"
                aria-haspopup="listbox"
                role="combobox"
                @if($disabled) disabled @endif
                @class([
                    'combobox-trigger',
                    'has-error' => $hasError,
                    'is-disabled' => $disabled,
                ])
                x-bind:class="{ 'is-open': isOpen }">
            <span class="combobox-value" x-bind:class="{ 'is-empty': !value }">
                <span x-show="selectedLabel" x-text="selectedLabel"></span>
                <span x-show="!selectedLabel">{{ $placeholder }}</span>
            </span>

            @if($clearable)
                <button type="button"
                        x-show="value && !{{ $disabled ? 'true' : 'false' }}"
                        x-on:click.stop="clear()"
                        x-cloak
                        class="combobox-clear"
                        tabindex="-1"
                        aria-label="{{ __('Clear') }}">
                    <x-lucide-x class="w-3 h-3" />
                </button>
            @endif

            <x-lucide-chevron-down class="combobox-icon-chevron" />
        </button>

        {{-- Painel --}}
        <div x-show="isOpen"
             x-cloak
             role="listbox"
             class="combobox-panel">

            <div class="combobox-search">
                <x-lucide-search class="combobox-search-icon" />
                <input type="text"
                       x-ref="search"
                       x-model="query"
                       x-on:keydown.down.prevent="moveHighlight(1)"
                       x-on:keydown.up.prevent="moveHighlight(-1)"
                       x-on:keydown.enter.prevent="selectHighlighted()"
                       x-on:keydown.escape.prevent="close()"
                       placeholder="{{ $searchPlaceholder }}"
                       class="combobox-search-input"
                       role="combobox"
                       aria-autocomplete="list"
                       autocomplete="off"
                       autocorrect="off"
                       autocapitalize="off"
                       spellcheck="false"
                       data-1p-ignore
                       data-lpignore="true"
                       data-form-type="other" />
                <kbd class="combobox-search-kbd">↵</kbd>
            </div>

            <div class="combobox-list" x-ref="list">
                <template x-for="(opt, idx) in visibleOptions" x-bind:key="opt.value">
                    <div role="option"
                         x-bind:id="'cbo-' + opt.value"
                         x-on:mousemove="highlight = idx"
                         x-on:click="select(opt.value)"
                         class="combobox-option"
                         x-bind:class="{
                            'is-active': highlight === idx,
                            'is-selected': value === opt.value,
                         }">
                        <div class="combobox-option-main">
                            <span class="combobox-option-label" x-html="renderLabel(opt.label)"></span>
                            <template x-if="opt.hint">
                                <span class="combobox-option-hint" x-text="opt.hint"></span>
                            </template>
                        </div>
                        <x-lucide-check class="combobox-option-check" />
                    </div>
                </template>

                <template x-if="filtered.length === 0">
                    <div class="combobox-empty">
                        <x-lucide-search-x class="combobox-empty-icon" />
                        <span class="combobox-empty-text">{{ $emptyText }}</span>
                        <span class="combobox-empty-hint" x-show="query" x-cloak>
                            "<span x-text="query"></span>"
                        </span>
                    </div>
                </template>
            </div>

            <div class="combobox-footer" x-show="filtered.length > 0">
                <span>
                    <span x-text="visibleOptions.length"></span>
                    <span x-show="visibleOptions.length < filtered.length" x-cloak>
                        / <span x-text="filtered.length"></span>
                    </span>
                    <template x-if="visibleOptions.length < filtered.length">
                        <span class="text-muted">— {{ __('refine search') }}</span>
                    </template>
                </span>
                <span class="hidden sm:inline">
                    <kbd class="combobox-search-kbd">↑↓</kbd>
                    <kbd class="combobox-search-kbd ml-1">Esc</kbd>
                </span>
            </div>
        </div>
    </div>

    @if($help)<span class="form-help">{{ $help }}</span>@endif
    @error($name)<span class="form-error">{{ $message }}</span>@enderror
</div>

@once
    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('combobox', (config) => ({
                options: config.options,
                maxVisible: config.maxVisible || 100,
                value: config.initialValue || '',
                query: '',
                isOpen: false,
                highlight: 0,

                get filtered() {
                    if (!this.query.trim()) return this.options;
                    const q = this.query.toLowerCase().trim();
                    return this.options.filter(o =>
                        o.label.toLowerCase().includes(q) ||
                        (o.hint && o.hint.toLowerCase().includes(q))
                    );
                },
                get visibleOptions() {
                    return this.filtered.slice(0, this.maxVisible);
                },
                get selectedLabel() {
                    const found = this.options.find(o => o.value === this.value);
                    return found ? found.label : '';
                },
                open() {
                    if (this.isOpen) return;
                    this.isOpen = true;
                    this.highlight = Math.max(0, this.visibleOptions.findIndex(o => o.value === this.value));
                    this.$nextTick(() => this.$refs.search?.focus());
                },
                close() {
                    if (!this.isOpen) return;
                    this.isOpen = false;
                    this.query = '';
                    this.$refs.trigger?.focus();
                },
                toggle() { this.isOpen ? this.close() : this.open(); },
                clear() {
                    this.value = '';
                    this.$refs.trigger?.focus();
                },
                select(val) {
                    this.value = val;
                    this.close();
                },
                selectHighlighted() {
                    const opt = this.visibleOptions[this.highlight];
                    if (opt) this.select(opt.value);
                },
                moveHighlight(delta) {
                    const max = this.visibleOptions.length - 1;
                    if (max < 0) return;
                    this.highlight = (this.highlight + delta + max + 1) % (max + 1);
                    this.scrollHighlightIntoView();
                },
                highlightFirst() { this.highlight = 0; this.scrollHighlightIntoView(); },
                highlightLast() {
                    this.highlight = Math.max(0, this.visibleOptions.length - 1);
                    this.scrollHighlightIntoView();
                },
                scrollHighlightIntoView() {
                    this.$nextTick(() => {
                        const list = this.$refs.list;
                        if (!list) return;
                        const item = list.children[this.highlight];
                        if (!item) return;
                        const top = item.offsetTop;
                        const bottom = top + item.offsetHeight;
                        if (bottom > list.scrollTop + list.clientHeight) {
                            list.scrollTop = bottom - list.clientHeight;
                        } else if (top < list.scrollTop) {
                            list.scrollTop = top;
                        }
                    });
                },
                renderLabel(label) {
                    if (!this.query.trim()) return this.escape(label);
                    const q = this.query.trim().replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    const re = new RegExp('(' + q + ')', 'gi');
                    return this.escape(label).replace(re, '<mark>$1</mark>');
                },
                escape(s) {
                    return String(s)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;');
                },
            }));
        });
    </script>
    @endpush
@endonce
