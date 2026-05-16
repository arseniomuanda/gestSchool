{{--
    Smart search command palette.
    Opens with Cmd/Ctrl+K or via [data-smart-search-open] triggers.
--}}
<div x-data="smartSearch()" x-cloak @open-smart-search.window="openPalette()">
    {{-- Hidden icon bank: lets JS pick a server-rendered Lucide SVG by name --}}
    <div class="hidden" id="search-icon-bank" aria-hidden="true">
        <span data-icon="graduation-cap"><x-lucide-graduation-cap class="w-4 h-4" /></span>
        <span data-icon="user-cog"><x-lucide-user-cog class="w-4 h-4" /></span>
        <span data-icon="user-check"><x-lucide-user-check class="w-4 h-4" /></span>
        <span data-icon="briefcase"><x-lucide-briefcase class="w-4 h-4" /></span>
        <span data-icon="users-round"><x-lucide-users-round class="w-4 h-4" /></span>
        <span data-icon="book-open"><x-lucide-book-open class="w-4 h-4" /></span>
        <span data-icon="layers"><x-lucide-layers class="w-4 h-4" /></span>
        <span data-icon="award"><x-lucide-award class="w-4 h-4" /></span>
        <span data-icon="file-text"><x-lucide-file-text class="w-4 h-4" /></span>
        <span data-icon="megaphone"><x-lucide-megaphone class="w-4 h-4" /></span>
    </div>

    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition.opacity.duration.150ms
        @click="closePalette()"
        class="fixed inset-0 z-40 bg-navy/40 backdrop-blur-sm"
    ></div>

    {{-- Palette --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
        class="fixed inset-x-0 top-20 z-50 mx-auto w-full max-w-2xl px-4"
        @keydown.window.escape="closePalette()"
    >
        <div class="bg-white rounded-lg shadow-card-hover border border-gray-100 overflow-hidden">
            {{-- Input --}}
            <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100">
                <x-lucide-search class="w-5 h-5 text-muted shrink-0" />
                <input
                    type="text"
                    x-ref="input"
                    x-model="query"
                    @input="onInput()"
                    @keydown="onKey($event)"
                    placeholder="{{ __('Search students, teachers, classes, subjects…') }}"
                    class="flex-1 border-0 p-0 text-base text-navy placeholder:text-body focus:outline-none focus:ring-0 bg-transparent"
                    role="combobox"
                    aria-autocomplete="list"
                    :aria-expanded="open.toString()"
                    autocomplete="off"
                    autocorrect="off"
                    autocapitalize="off"
                    spellcheck="false"
                    name="global_search"
                    data-1p-ignore
                    data-lpignore="true"
                    data-form-type="other"
                />
                <span x-show="loading" class="text-xs text-muted">{{ __('Searching…') }}</span>
                <kbd class="hidden sm:inline-flex" x-show="!loading">esc</kbd>
            </div>

            {{-- Results --}}
            <div class="max-h-[60vh] overflow-y-auto" x-ref="list">
                {{-- Empty: too short --}}
                <template x-if="query.trim().length < 2 && !loading">
                    <div class="px-4 py-10 text-center">
                        <x-lucide-search class="w-10 h-10 mx-auto text-gray-200 mb-2" />
                        <p class="text-sm text-muted">{{ __('Type at least 2 characters to search.') }}</p>
                        <p class="text-xs text-body mt-3">
                            <kbd>↑</kbd> <kbd>↓</kbd> {{ __('navigate') }} ·
                            <kbd>↵</kbd> {{ __('open') }} ·
                            <kbd>esc</kbd> {{ __('close') }}
                        </p>
                    </div>
                </template>

                {{-- Empty: no results --}}
                <template x-if="query.trim().length >= 2 && !loading && groups.length === 0">
                    <div class="px-4 py-10 text-center">
                        <p class="text-sm text-muted">{{ __('No results for') }} "<span class="text-navy font-semibold" x-text="query"></span>"</p>
                    </div>
                </template>

                {{-- Grouped results --}}
                <template x-for="(group, gi) in groups" :key="group.type">
                    <div class="py-1">
                        <div class="px-4 pt-2 pb-1 flex items-center gap-2 text-[10px] uppercase tracking-widest text-muted font-semibold">
                            <span class="text-muted" x-html="iconFor(group.icon)"></span>
                            <span x-text="group.label" class="flex-1"></span>
                            <span class="text-body font-normal" x-text="group.results.length"></span>
                        </div>
                        <template x-for="(r, ri) in group.results" :key="group.type + '-' + ri">
                            <a
                                :href="r.url"
                                :data-search-idx="flatIndexFor(gi, ri)"
                                :class="flatIndexFor(gi, ri) === activeIndex ? 'bg-primary-soft' : ''"
                                @mouseenter="activeIndex = flatIndexFor(gi, ri)"
                                class="flex items-center gap-3 px-4 py-2.5 text-sm transition-colors"
                            >
                                <div class="flex-1 min-w-0">
                                    <div class="text-navy font-medium truncate" x-text="r.title"></div>
                                    <div class="text-xs text-muted truncate" x-show="r.subtitle" x-text="r.subtitle"></div>
                                </div>
                                <x-lucide-arrow-right class="w-4 h-4 text-muted shrink-0" />
                            </a>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
