{{--
    Drawer global de ajuda in-app (issue #43).
    Incluído uma única vez em layouts/app.blade.php. Escuta o evento
    'open-help' (dispatched pelo <x-help-button>) e abre painel
    deslizante da direita. Markdown vem do /help/{key} (cached server-side).
--}}
<div
    x-data="{
        open: false,
        loading: false,
        html: '',
        error: null,
        currentKey: null,

        async openHelp(key) {
            this.currentKey = key;
            this.open = true;
            this.loading = true;
            this.error = null;
            this.html = '';
            try {
                const res = await fetch(`/help/${encodeURIComponent(key)}`, {
                    headers: { 'Accept': 'text/html' },
                });
                if (!res.ok) {
                    this.error = res.status === 404
                        ? '{{ __('No help available for this page yet.') }}'
                        : `HTTP ${res.status}`;
                } else {
                    this.html = await res.text();
                }
            } catch (e) {
                this.error = e.message || '{{ __('Failed to load help.') }}';
            } finally {
                this.loading = false;
            }
        },

        close() { this.open = false; },
    }"
    @open-help.window="openHelp($event.detail.key)"
    @keydown.escape.window="open && close()"
>
    {{-- Overlay --}}
    <div x-show="open" x-cloak
         @click="close()"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-900/40 z-40"></div>

    {{-- Drawer --}}
    <aside x-show="open" x-cloak
           role="dialog"
           aria-modal="true"
           aria-labelledby="help-drawer-title"
           x-transition:enter="ease-out duration-300"
           x-transition:enter-start="translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="ease-in duration-200"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="translate-x-full"
           class="fixed top-0 right-0 bottom-0 z-50 w-full max-w-md bg-white shadow-2xl overflow-y-auto flex flex-col">

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 shrink-0">
            <h2 id="help-drawer-title" class="text-base font-bold text-navy flex items-center gap-2">
                <x-lucide-help-circle class="w-5 h-5 text-primary" />
                {{ __('Help') }}
            </h2>
            <button type="button" @click="close()" class="btn-link-muted" :aria-label="'{{ __('Close') }}'">
                <x-lucide-x class="w-5 h-5" />
            </button>
        </div>

        {{-- Conteúdo --}}
        <div class="flex-1 px-5 py-4 prose-help text-sm leading-relaxed text-navy">
            <template x-if="loading">
                <p class="text-muted italic">{{ __('Loading…') }}</p>
            </template>
            <template x-if="error">
                <div class="p-3 rounded bg-amber-50 border border-amber-200 text-amber-800">
                    <span x-text="error"></span>
                </div>
            </template>
            <div x-html="html" x-show="!loading && !error"></div>
        </div>

        {{-- Rodapé --}}
        <div class="px-5 py-3 border-t border-gray-100 text-xs text-muted shrink-0 flex items-center justify-between">
            <span x-text="currentKey ? ('🔑 ' + currentKey) : ''" class="font-mono opacity-60"></span>
            <span>{{ __('Press Esc to close') }}</span>
        </div>
    </aside>
</div>
