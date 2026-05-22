/**
 * Alpine factory para o widget de tamanho de texto.
 *
 * Mantém o estado do slider sincronizado com:
 *   - CSS variable --font-scale no <html>
 *   - cookie gs_font_scale (1 ano)
 *   - column users.font_scale via POST /preferences/font-scale (best-effort)
 *
 * Funciona em guest e autenticado. Em guest o endpoint POST aceita na mesma
 * (o controller só actualiza DB se houver user autenticado).
 */
window.fontScaleWidget = function () {
    const levels = [0.85, 0.93, 1.00, 1.10, 1.20, 1.35, 1.50];
    const labels = window.__fontScaleLabels || [
        'Very small', 'Small', 'Small-medium', 'Medium',
        'Medium-large', 'Large', 'Very large',
    ];

    function readCurrent() {
        const inline = parseFloat(document.documentElement.style.getPropertyValue('--font-scale'));
        if (!isNaN(inline) && inline > 0) return inline;
        const m = document.cookie.match(/(?:^|;\s*)gs_font_scale=([\d.]+)/);
        return m ? parseFloat(m[1]) : 1;
    }

    return {
        open: false,
        scale: readCurrent(),
        get index() {
            for (let i = 0; i < levels.length; i++) {
                if (Math.abs(levels[i] - this.scale) < 0.01) return i;
            }
            return 2;
        },
        get currentLabel() { return labels[this.index] || ''; },
        setScale(i) {
            if (i < 0 || i >= levels.length) return;
            this.scale = levels[i];
            document.documentElement.style.setProperty('--font-scale', this.scale);
            document.cookie = 'gs_font_scale=' + this.scale + '; path=/; max-age=' + (60 * 60 * 24 * 365) + '; samesite=lax';
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            fetch('/preferences/font-scale', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ scale: this.scale }),
            }).catch(() => {});
        },
    };
};
