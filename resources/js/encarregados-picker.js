// Encarregados picker: searchable, multi-select with per-item metadata.

document.addEventListener('alpine:init', () => {
    window.Alpine.data('encarregadosPicker', (config) => ({
        selected: config.initial || [],
        parentescoOptions: config.parentescoOptions || [],
        searchUrl: config.searchUrl,
        labels: config.labels || {},

        query: '',
        results: [],
        loading: false,
        dropdown: false,
        debounce: null,
        controller: null,
        loadedOnce: false,

        isSelected(id) {
            return this.selected.some((e) => e.id === id);
        },

        openDropdown() {
            this.dropdown = true;
            if (!this.loadedOnce) {
                this.fetch('');
                this.loadedOnce = true;
            }
        },

        closeDropdown() {
            this.dropdown = false;
        },

        onSearch() {
            clearTimeout(this.debounce);
            this.debounce = setTimeout(() => this.fetch(this.query), 200);
        },

        async fetch(q) {
            if (this.controller) this.controller.abort();
            this.controller = new AbortController();
            this.loading = true;
            try {
                const res = await fetch(`${this.searchUrl}?q=${encodeURIComponent(q || '')}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: this.controller.signal,
                });
                const data = await res.json();
                this.results = data.results || [];
            } catch (e) {
                if (e.name !== 'AbortError') this.results = [];
            } finally {
                this.loading = false;
            }
        },

        add(r) {
            if (this.isSelected(r.id)) return;
            this.selected.push({
                id: r.id,
                name: r.name,
                email: r.email,
                bi: r.bi,
                parentesco: 'outro',
                principal: this.selected.length === 0,
            });
            this.query = '';
            this.dropdown = false;
        },

        remove(id) {
            const wasPrincipal = this.selected.find((e) => e.id === id)?.principal;
            this.selected = this.selected.filter((e) => e.id !== id);
            if (wasPrincipal && this.selected.length > 0) {
                this.selected[0].principal = true;
            }
        },

        setParentesco(id, value) {
            const e = this.selected.find((x) => x.id === id);
            if (e) e.parentesco = value;
        },

        togglePrincipal(id) {
            const target = this.selected.find((e) => e.id === id);
            if (!target) return;
            if (target.principal) {
                target.principal = false;
            } else {
                this.selected.forEach((e) => (e.principal = false));
                target.principal = true;
            }
        },

        initials(name) {
            return (name || '')
                .split(' ')
                .filter(Boolean)
                .slice(0, 2)
                .map((w) => w[0])
                .join('')
                .toUpperCase();
        },
    }));
});
