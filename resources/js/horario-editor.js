/**
 * Componente Alpine partilhado entre bulk-turma e bulk-professor.
 *
 * Modo formulário (selects) é a UI legada e fica intacta.
 * Modo visual usa SortableJS para drag & drop entre slots e entre a lista lateral.
 *
 * O state `slots` é a única fonte de verdade — ambos os modos lêem e escrevem nele.
 * O submit do formulário usa hidden inputs sincronizados via x-model.
 */
import Sortable from 'sortablejs';

const CLIPBOARD_KEY = 'gestschool_horario_clipboard';
const CLIPBOARD_TTL_MS = 24 * 60 * 60 * 1000;
const MODE_KEY = 'gestschool_horario_editor_mode';

function deepCopy(o) {
    return JSON.parse(JSON.stringify(o ?? null));
}

/**
 * Factory para o componente Alpine.
 *
 * @param {Object} opts
 *   - initial: estado inicial slots[dia][tempo]
 *   - diasLectivos: array dos dias (ex: [1,2,3,4,5])
 *   - atrPayload: { [atribuicaoId]: { turma_id, turma_label, disciplina, professor?, disciplina_full } }
 *   - turmaColors: { [turmaId]: { bg, fg, border } }
 *   - mode: 'turma' | 'professor' — pequeno ajuste de labels
 *   - i18n: chaves traduzidas usadas em popups
 */
export function horarioEditor(opts) {
    return {
        slots: opts.initial,
        diasLectivos: opts.diasLectivos,
        atrPayload: opts.atrPayload || {},
        turmaColors: opts.turmaColors || {},
        diagnostico: opts.diagnostico || { max_consecutivos: 3, horas_dificeis: [] },
        suggestEndpoints: opts.suggestEndpoints || null,  // { greedy: url, ai: url } só na bulk-turma
        mode: opts.mode || 'turma',
        i18n: opts.i18n || {},

        viewMode: 'form',           // 'form' | 'visual'
        clipboard: null,
        clipboardType: null,
        clipboardOriginLabel: '',
        sortables: [],
        dragWarning: '',
        clearAllOpen: false,        // modal de confirmação "Limpar tudo"
        suggestOpen: false,         // modal de confirmação "Sobrescrever com sugestão"
        pendingSuggestion: null,    // método pendente: 'greedy' | 'ai'
        suggestLoading: false,
        suggestError: '',
        suggestMessage: '',

        init() {
            try {
                const stored = localStorage.getItem(CLIPBOARD_KEY);
                if (stored) {
                    const data = JSON.parse(stored);
                    if (Date.now() - data.timestamp < CLIPBOARD_TTL_MS) {
                        this.clipboard = data.value;
                        this.clipboardType = data.type;
                        this.clipboardOriginLabel = data.origin;
                    } else {
                        localStorage.removeItem(CLIPBOARD_KEY);
                    }
                }
            } catch (e) {
                localStorage.removeItem(CLIPBOARD_KEY);
            }
            const savedMode = localStorage.getItem(MODE_KEY);
            if (savedMode === 'visual' || savedMode === 'form') {
                this.viewMode = savedMode;
            }
            this.$nextTick(() => {
                if (this.viewMode === 'visual') this.initSortables();
            });
            this.$watch('viewMode', (m) => {
                localStorage.setItem(MODE_KEY, m);
                if (m === 'visual') this.$nextTick(() => this.initSortables());
                else this.destroySortables();
            });
        },

        // ---------- derivados ----------

        get filledCount() {
            let n = 0;
            for (const dia of this.diasLectivos) {
                for (const tempo in this.slots[dia]) {
                    if (this.slots[dia][tempo].atribuicao_id) n++;
                }
            }
            return n;
        },

        get totalCount() {
            if (!this.diasLectivos.length) return 0;
            const d = this.diasLectivos[0];
            return this.diasLectivos.length * Object.keys(this.slots[d] || {}).length;
        },

        get clipboardLabel() {
            if (!this.clipboard) return '';
            const t = this.clipboardType === 'col' ? (this.i18n.column || 'col') : (this.i18n.row || 'row');
            return `${t} ${this.clipboardOriginLabel}`;
        },

        get breakdownDisciplina() {
            const out = {};
            for (const dia of this.diasLectivos) {
                for (const tempo in this.slots[dia]) {
                    const id = this.slots[dia][tempo].atribuicao_id;
                    if (!id) continue;
                    const info = this.atrPayload[id];
                    if (!info) continue;
                    const key = info.disciplina_full || info.disciplina;
                    out[key] = (out[key] || 0) + 1;
                }
            }
            return out;
        },

        get breakdownTurma() {
            const out = {};
            for (const dia of this.diasLectivos) {
                for (const tempo in this.slots[dia]) {
                    const id = this.slots[dia][tempo].atribuicao_id;
                    if (!id) continue;
                    const info = this.atrPayload[id];
                    if (!info) continue;
                    const c = this.turmaColors[info.turma_id] || { bg: '#eee', border: '#ccc' };
                    if (!out[info.turma_id]) {
                        out[info.turma_id] = { label: info.turma_label, color: c.bg, border: c.border, count: 0 };
                    }
                    out[info.turma_id].count++;
                }
            }
            return out;
        },

        /** Map atribuicao_id (string) → nº de slots ocupados na semana. */
        get slotCountByAtr() {
            const out = {};
            for (const dia of this.diasLectivos) {
                for (const tempo in this.slots[dia]) {
                    const id = this.slots[dia][tempo].atribuicao_id;
                    if (!id) continue;
                    const key = String(id);
                    out[key] = (out[key] || 0) + 1;
                }
            }
            return out;
        },

        /** Devolve "actual/esperada" ou só "actual" se a disciplina não declarar carga. */
        cargaLabel(atrId) {
            const actual = this.slotCountByAtr[String(atrId)] || 0;
            const info = this.atrPayload[atrId];
            const esperada = info?.carga_horaria;
            return esperada ? `${actual}/${esperada}` : `${actual}`;
        },

        /** True quando carga horária semanal foi atingida ou ultrapassada. */
        cargaCompleta(atrId) {
            const actual = this.slotCountByAtr[String(atrId)] || 0;
            const info = this.atrPayload[atrId];
            return info?.carga_horaria ? actual >= info.carga_horaria : false;
        },

        // ---------- Diagnóstico de lacunas (Fase 4.1) ----------

        get furos() {
            const out = [];
            const tempos = Object.keys(this.slots[this.diasLectivos[0]] || {})
                .map(Number)
                .sort((a, b) => a - b);
            for (const dia of this.diasLectivos) {
                const ocupados = tempos.filter((t) => this.slots[dia]?.[t]?.atribuicao_id);
                if (ocupados.length < 2) continue;
                const min = ocupados[0];
                const max = ocupados[ocupados.length - 1];
                for (const t of tempos) {
                    if (t > min && t < max && !this.slots[dia][t].atribuicao_id) {
                        out.push({ dia, tempo: t });
                    }
                }
            }
            return out;
        },

        get naoEscaladas() {
            const usados = this.slotCountByAtr;
            return Object.keys(this.atrPayload).filter((id) => !usados[id]);
        },

        get cargaIssues() {
            const usados = this.slotCountByAtr;
            const out = { ok: [], falta: [], excesso: [], semConfig: [] };
            for (const [atrId, info] of Object.entries(this.atrPayload)) {
                const actual = usados[atrId] || 0;
                const esperada = info.carga_horaria;
                if (esperada == null) out.semConfig.push(atrId);
                else if (actual === esperada) out.ok.push(atrId);
                else if (actual < esperada) out.falta.push({ atrId, actual, esperada });
                else out.excesso.push({ atrId, actual, esperada });
            }
            return out;
        },

        // ---------- Análise de distribuição (Fase 4.2) ----------

        /**
         * Concentração diária por disciplina pesada. Um problema é quando >50%
         * dos tempos de uma disciplina pesada caem no mesmo dia (e há ≥2 tempos
         * nesse dia).
         * @returns {Array<{disciplina: string, dia: number, dia_count: number, total: number}>}
         */
        get concentracaoDiaria() {
            // contagem por (atrId, dia)
            const matriz = {};
            for (const dia of this.diasLectivos) {
                for (const tempo in this.slots[dia]) {
                    const id = this.slots[dia][tempo].atribuicao_id;
                    if (!id) continue;
                    const info = this.atrPayload[id];
                    if (!info?.eh_pesada) continue;
                    matriz[id] = matriz[id] || { porDia: {}, total: 0 };
                    matriz[id].porDia[dia] = (matriz[id].porDia[dia] || 0) + 1;
                    matriz[id].total++;
                }
            }
            const out = [];
            for (const [id, info] of Object.entries(matriz)) {
                if (info.total < 2) continue;
                for (const [dia, n] of Object.entries(info.porDia)) {
                    if (n >= 2 && n / info.total > 0.5) {
                        out.push({
                            atrId: id,
                            disciplina: this.atrPayload[id]?.disciplina_full,
                            dia: Number(dia),
                            dia_count: n,
                            total: info.total,
                        });
                    }
                }
            }
            return out;
        },

        /**
         * Professores com runs de tempos consecutivos > max_consecutivos num dia.
         * @returns {Array<{professor_id, professor, dia, run, start, end}>}
         */
        get tempasConsecutivos() {
            const limite = this.diagnostico.max_consecutivos || 3;
            const tempos = Object.keys(this.slots[this.diasLectivos[0]] || {})
                .map(Number)
                .sort((a, b) => a - b);
            const out = [];
            // agrupa por professor: { profId: { dia: [tempos ocupados] } }
            const porProf = {};
            for (const dia of this.diasLectivos) {
                for (const t of tempos) {
                    const id = this.slots[dia]?.[t]?.atribuicao_id;
                    if (!id) continue;
                    const info = this.atrPayload[id];
                    if (!info?.professor_id) continue;
                    const pid = info.professor_id;
                    porProf[pid] = porProf[pid] || { name: info.professor || `#${pid}`, dias: {} };
                    porProf[pid].dias[dia] = porProf[pid].dias[dia] || [];
                    porProf[pid].dias[dia].push(t);
                }
            }
            // detecta runs
            for (const [pid, info] of Object.entries(porProf)) {
                for (const [dia, listaTempos] of Object.entries(info.dias)) {
                    listaTempos.sort((a, b) => a - b);
                    let run = 1;
                    let start = listaTempos[0];
                    for (let i = 1; i < listaTempos.length; i++) {
                        if (listaTempos[i] === listaTempos[i - 1] + 1) {
                            run++;
                            if (run > limite && i === listaTempos.length - 1) {
                                out.push({ professor_id: pid, professor: info.name, dia: Number(dia), run, start, end: listaTempos[i] });
                            }
                        } else {
                            if (run > limite) {
                                out.push({ professor_id: pid, professor: info.name, dia: Number(dia), run, start, end: listaTempos[i - 1] });
                            }
                            run = 1;
                            start = listaTempos[i];
                        }
                    }
                    if (run > limite && !out.find((o) => o.professor_id === pid && o.dia === Number(dia) && o.start === start)) {
                        out.push({ professor_id: pid, professor: info.name, dia: Number(dia), run, start, end: listaTempos[listaTempos.length - 1] });
                    }
                }
            }
            return out;
        },

        /**
         * Slots em "horas difíceis" (config) ocupados por disciplina pesada.
         * @returns {Array<{dia, tempo, disciplina, atrId}>}
         */
        get horasMas() {
            const horas = this.diagnostico.horas_dificeis || [];
            if (!horas.length) return [];
            const out = [];
            for (const [dia, tempo] of horas) {
                const id = this.slots[dia]?.[tempo]?.atribuicao_id;
                if (!id) continue;
                const info = this.atrPayload[id];
                if (!info?.eh_pesada) continue;
                out.push({ dia, tempo, disciplina: info.disciplina_full, atrId: id });
            }
            return out;
        },

        cellStyle(atrId) {
            if (!atrId) return '';
            const info = this.atrPayload[atrId];
            if (!info) return '';
            const c = this.turmaColors[info.turma_id];
            if (!c) return '';
            return `background:${c.bg}; border-left: 3px solid ${c.border}; color:${c.fg}`;
        },

        cellLabel(atrId) {
            if (!atrId) return '';
            const info = this.atrPayload[atrId];
            if (!info) return atrId;
            // Em modo turma: "DISC · Prof"; modo professor: "[Turma] DISC"
            if (this.mode === 'professor') return `${info.turma_label} · ${info.disciplina}`;
            return `${info.disciplina}${info.professor ? ' · ' + info.professor : ''}`;
        },

        // ---------- clipboard / acções ----------

        persistClipboard(origin) {
            this.clipboardOriginLabel = String(origin);
            localStorage.setItem(CLIPBOARD_KEY, JSON.stringify({
                value: this.clipboard,
                type: this.clipboardType,
                origin: origin,
                timestamp: Date.now(),
            }));
        },

        clearClipboard() {
            this.clipboard = null;
            this.clipboardType = null;
            this.clipboardOriginLabel = '';
            localStorage.removeItem(CLIPBOARD_KEY);
        },

        copyColumn(dia) {
            this.clipboard = deepCopy(this.slots[dia] || {});
            this.clipboardType = 'col';
            this.persistClipboard(dia);
        },

        pasteColumn(dia) {
            if (this.clipboardType !== 'col' || !this.clipboard) return;
            this.slots[dia] = deepCopy(this.clipboard);
        },

        clearColumn(dia) {
            for (const tempo in this.slots[dia]) {
                this.slots[dia][tempo].atribuicao_id = '';
                this.slots[dia][tempo].sala = '';
            }
        },

        applyToAllDays(dia) {
            const source = deepCopy(this.slots[dia] || {});
            for (const d of this.diasLectivos) {
                if (d !== dia) this.slots[d] = deepCopy(source);
            }
        },

        copyRow(tempo) {
            const row = {};
            for (const d of this.diasLectivos) {
                row[d] = deepCopy(this.slots[d]?.[tempo] || {});
            }
            this.clipboard = row;
            this.clipboardType = 'row';
            this.persistClipboard(tempo + 'º');
        },

        pasteRow(tempo) {
            if (this.clipboardType !== 'row' || !this.clipboard) return;
            for (const d of this.diasLectivos) {
                if (this.clipboard[d]) {
                    this.slots[d][tempo] = deepCopy(this.clipboard[d]);
                }
            }
        },

        clearRow(tempo) {
            for (const d of this.diasLectivos) {
                if (this.slots[d]?.[tempo]) {
                    this.slots[d][tempo].atribuicao_id = '';
                    this.slots[d][tempo].sala = '';
                }
            }
        },

        confirmClearAll() {
            // Abre o modal inline em vez de window.confirm (rejeitado pelo UX)
            this.clearAllOpen = true;
        },

        doClearAll() {
            for (const d of this.diasLectivos) {
                for (const tempo in this.slots[d]) {
                    this.slots[d][tempo].atribuicao_id = '';
                    this.slots[d][tempo].sala = '';
                }
            }
            this.clearAllOpen = false;
        },

        // ---------- Auto-sugestão de horário (Fase 4.3) ----------

        /** Abre modal de confirmação se o horário já tem dados, senão aplica logo. */
        askSuggest(method) {
            this.suggestError = '';
            this.suggestMessage = '';
            this.pendingSuggestion = method;
            // Se a grelha está vazia, aplica directamente
            if (this.filledCount === 0) {
                this.applySuggestion();
            } else {
                this.suggestOpen = true;
            }
        },

        async applySuggestion() {
            const method = this.pendingSuggestion;
            this.suggestOpen = false;
            if (!method) return;

            const url = this.suggestEndpoints?.[method];
            if (!url) {
                this.suggestError = 'No endpoint configured for this suggestion method.';
                return;
            }
            this.suggestLoading = true;
            this.suggestError = '';
            this.suggestMessage = '';

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content
                    || document.querySelector('input[name="_token"]')?.value;
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf || '',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await res.json();
                if (!res.ok) {
                    this.suggestError = data?.error || `HTTP ${res.status}`;
                    return;
                }
                // Aplicar slots devolvidos ao state Alpine
                if (data.slots) {
                    for (const dia of this.diasLectivos) {
                        for (const tempo in this.slots[dia]) {
                            const proposed = data.slots[dia]?.[tempo];
                            if (proposed) {
                                this.slots[dia][tempo].atribuicao_id = proposed.atribuicao_id || '';
                                this.slots[dia][tempo].sala = proposed.sala || '';
                            }
                        }
                    }
                }
                const parts = [];
                if (data.method === 'greedy') parts.push(this.i18n.suggestedGreedy || 'Suggestion applied (greedy).');
                if (data.method === 'gemini') parts.push(this.i18n.suggestedAi || 'Suggestion applied (AI).');
                if (data.unplaced > 0) parts.push(`${data.unplaced} ${this.i18n.unplacedBlocks || 'blocks not placed.'}`);
                if (data.rejected > 0) parts.push(`${data.rejected} ${this.i18n.rejectedSlots || 'slots rejected.'}`);
                this.suggestMessage = parts.join(' ');
                setTimeout(() => { this.suggestMessage = ''; }, 5000);
            } catch (e) {
                this.suggestError = e.message || 'Failed to fetch suggestion.';
            } finally {
                this.suggestLoading = false;
                this.pendingSuggestion = null;
            }
        },

        // ---------- drag & drop ----------

        initSortables() {
            this.destroySortables();
            const cells = this.$root.querySelectorAll('[data-dnd-cell]');
            cells.forEach((cell) => {
                this.sortables.push(new Sortable(cell, {
                    group: 'horario-slots',
                    animation: 180,
                    fallbackOnBody: true,
                    swapThreshold: 0.7,
                    onAdd: (evt) => this.handleDrop(evt),
                    onRemove: () => { /* slot vazio: limpar gerido pelo onAdd da célula destino */ },
                    onEnd: () => { this.dragWarning = ''; },
                }));
            });
            const pool = this.$root.querySelector('[data-dnd-pool]');
            if (pool) {
                this.sortables.push(new Sortable(pool, {
                    group: { name: 'horario-slots', pull: 'clone', put: true },
                    sort: false,
                    animation: 180,
                    onAdd: (evt) => {
                        // arrastou de uma célula para o pool → limpa célula
                        const from = evt.from.dataset.dndCell;
                        if (from) {
                            const [dia, tempo] = from.split(':');
                            this.slots[dia][tempo].atribuicao_id = '';
                            this.slots[dia][tempo].sala = '';
                        }
                        // remover o nó DOM que o Sortable moveu para o pool — o pool é gerado por x-for
                        evt.item.remove();
                    },
                }));
            }
        },

        destroySortables() {
            this.sortables.forEach((s) => s.destroy());
            this.sortables = [];
        },

        countOccurrences(atrId) {
            let n = 0;
            for (const dia of this.diasLectivos) {
                for (const tempo in this.slots[dia]) {
                    if (this.slots[dia][tempo].atribuicao_id === atrId) n++;
                }
            }
            return n;
        },

        handleDrop(evt) {
            const fromCell = evt.from.dataset.dndCell;     // 'dia:tempo' ou undefined (do pool)
            const toCell = evt.to.dataset.dndCell;
            const atrId = evt.item.dataset.atribuicaoId;
            const fromPool = !fromCell;

            // Sempre limpar o nó movido — Alpine re-renderiza ambas as células
            evt.item.remove();

            if (!toCell || !atrId) return;

            const [td, tt] = toCell.split(':');
            const previousDest = this.slots[td][tt].atribuicao_id;

            if (fromPool) {
                // From pool: o pool é um menu — uma atribuição pode aparecer em vários slots
                // (carga horária semanal típica: 2-4 tempos/semana por disciplina)
                if (previousDest && previousDest !== atrId) {
                    this.dragWarning = this.i18n.replacedWarning || 'Replaced an existing slot.';
                }
                this.slots[td][tt].atribuicao_id = atrId;
            } else {
                // From cell → cell: mover (ou swap se destino ocupado)
                const [fd, ft] = fromCell.split(':');
                if (previousDest && previousDest !== atrId) {
                    this.slots[fd][ft].atribuicao_id = previousDest;
                    this.slots[td][tt].atribuicao_id = atrId;
                    this.dragWarning = this.i18n.swappedWarning || 'Swapped two slots.';
                } else {
                    this.slots[fd][ft].atribuicao_id = '';
                    this.slots[td][tt].atribuicao_id = atrId;
                }
            }

            if (this.dragWarning) {
                setTimeout(() => { this.dragWarning = ''; }, 2500);
            }
        },
    };
}

window.horarioEditor = horarioEditor;
