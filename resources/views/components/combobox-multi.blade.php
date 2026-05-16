@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => [],          // array de valores
    'placeholder' => null,
    'searchPlaceholder' => null,
    'help' => null,
    'required' => false,
    'disabled' => false,
    'maxVisible' => 100,
    'maxChips' => 4,           // chips visíveis antes de "+ N"
    'emptyText' => null,
])

@php
    $id = $attributes->get('id') ?? $name;
    $placeholder ??= __('Select') . '...';
    $searchPlaceholder ??= __('Search') . '...';
    $emptyText ??= __('No results found');

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

    $selectedValues = collect($selected ?? [])->map(fn ($v) => (string) $v)->values()->all();
    $hasError = $errors->has($name) || $errors->has($name . '.*');
@endphp

<div class="form-group">
    @if($label)
        <label for="{{ $id }}" class="form-label">
            {{ $label }}@if($required) <span class="text-danger">*</span>@endif
        </label>
    @endif

    <div
        x-data="comboboxMulti({
            options: @js($normalized),
            initialValues: @js($selectedValues),
            maxVisible: {{ (int) $maxVisible }},
            maxChips: {{ (int) $maxChips }},
        })"
        x-on:keydown.escape.prevent.stop="close()"
        x-on:click.outside="close()"
        class="combobox"
    >
        {{-- Hidden inputs em array (name="X[]") --}}
        <template x-for="v in values" x-bind:key="v">
            <input type="hidden" x-bind:name="'{{ $name }}[]'" x-bind:value="v" />
        </template>

        {{-- Trigger --}}
        <button type="button"
                x-ref="trigger"
                x-on:click="toggle()"
                x-on:keydown.down.prevent="open()"
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

            <div class="combobox-value flex-wrap flex items-center gap-1">
                {{-- placeholder --}}
                <template x-if="values.length === 0">
                    <span class="combobox-value is-empty">{{ $placeholder }}</span>
                </template>

                {{-- chips visíveis --}}
                <template x-for="opt in visibleChips" x-bind:key="opt.value">
                    <span class="combobox-chip">
                        <span class="combobox-chip-label" x-text="opt.label"></span>
                        <span class="combobox-chip-remove"
                              x-on:click.stop="remove(opt.value)"
                              role="button"
                              x-bind:aria-label="'{{ __('Remove') }} ' + opt.label">
                            <x-lucide-x class="w-3 h-3" />
                        </span>
                    </span>
                </template>

                {{-- overflow --}}
                <template x-if="overflowCount > 0">
                    <span class="combobox-chip-overflow">
                        + <span x-text="overflowCount"></span>
                    </span>
                </template>
            </div>

            <button type="button"
                    x-show="values.length > 0 && !{{ $disabled ? 'true' : 'false' }}"
                    x-on:click.stop="clearAll()"
                    x-cloak
                    class="combobox-clear"
                    tabindex="-1"
                    aria-label="{{ __('Clear') }}">
                <x-lucide-x class="w-3 h-3" />
            </button>
            <x-lucide-chevron-down class="combobox-icon-chevron" />
        </button>

        {{-- Painel --}}
        <div x-show="isOpen"
             x-cloak
             role="listbox"
             aria-multiselectable="true"
             class="combobox-panel">

            <div class="combobox-search">
                <x-lucide-search class="combobox-search-icon" />
                <input type="text"
                       x-ref="search"
                       x-model="query"
                       x-on:keydown.down.prevent="moveHighlight(1)"
                       x-on:keydown.up.prevent="moveHighlight(-1)"
                       x-on:keydown.enter.prevent="toggleHighlighted()"
                       x-on:keydown.backspace="onBackspace($event)"
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
                         x-on:mousemove="highlight = idx"
                         x-on:click="toggleValue(opt.value)"
                         class="combobox-option"
                         x-bind:class="{
                            'is-active': highlight === idx,
                            'is-selected': values.includes(opt.value),
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
                    </div>
                </template>
            </div>

            <div class="combobox-footer" x-show="filtered.length > 0">
                <span>
                    <span x-text="values.length"></span>
                    {{ __('selected') }}
                    <template x-if="visibleOptions.length < filtered.length">
                        <span class="text-muted ml-1">
                            (<span x-text="visibleOptions.length"></span>/<span x-text="filtered.length"></span>)
                        </span>
                    </template>
                </span>
                <span x-show="values.length > 0"
                      x-on:click="clearAll()"
                      class="combobox-footer-action">
                    {{ __('Clear all') }}
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
            Alpine.data('comboboxMulti', (config) => ({
                options: config.options,
                maxVisible: config.maxVisible || 100,
                maxChips: config.maxChips || 4,
                values: [...(config.initialValues || [])],
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
                get selectedOptions() {
                    return this.values
                        .map(v => this.options.find(o => o.value === v))
                        .filter(Boolean);
                },
                get visibleChips() {
                    return this.selectedOptions.slice(0, this.maxChips);
                },
                get overflowCount() {
                    return Math.max(0, this.selectedOptions.length - this.maxChips);
                },

                open() {
                    if (this.isOpen) return;
                    this.isOpen = true;
                    this.highlight = 0;
                    this.$nextTick(() => this.$refs.search?.focus());
                },
                close() {
                    if (!this.isOpen) return;
                    this.isOpen = false;
                    this.query = '';
                    this.$refs.trigger?.focus();
                },
                toggle() { this.isOpen ? this.close() : this.open(); },
                clearAll() {
                    this.values = [];
                    if (!this.isOpen) this.$refs.trigger?.focus();
                },
                remove(val) {
                    this.values = this.values.filter(v => v !== val);
                },
                toggleValue(val) {
                    if (this.values.includes(val)) {
                        this.remove(val);
                    } else {
                        this.values = [...this.values, val];
                    }
                },
                toggleHighlighted() {
                    const opt = this.visibleOptions[this.highlight];
                    if (opt) this.toggleValue(opt.value);
                },
                onBackspace(e) {
                    // Apaga última pill se search está vazia
                    if (!this.query && this.values.length > 0) {
                        e.preventDefault();
                        this.values = this.values.slice(0, -1);
                    }
                },
                moveHighlight(delta) {
                    const max = this.visibleOptions.length - 1;
                    if (max < 0) return;
                    this.highlight = (this.highlight + delta + max + 1) % (max + 1);
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
