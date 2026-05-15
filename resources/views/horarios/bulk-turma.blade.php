@php
    use App\Support\TurmaColor;
    // Em bulk-turma a "turma" é única; mas usamos a mesma estrutura de payload
    // para que o componente Alpine seja reutilizável.
    $atrPayload = [];
    foreach ($atribuicoes as $a) {
        $atrPayload[$a->id] = [
            'turma_id' => $a->turma_id,
            'turma_label' => $a->turma->classe->nome . $a->turma->nome,
            'disciplina' => $a->disciplina->sigla ?: \Illuminate\Support\Str::limit($a->disciplina->nome, 8),
            'disciplina_full' => $a->disciplina->nome,
            'professor' => \Illuminate\Support\Str::limit($a->professor->user->name, 14),
            'carga_horaria' => $a->disciplina->carga_horaria_semanal,
        ];
    }
    $turmaColors = [ $turma->id => TurmaColor::for($turma->id) ];
    $i18n = [
        'column' => __('column'),
        'row' => __('row'),
        'confirmClearAll' => __('Clear the whole schedule?'),
        'swappedWarning' => __('Swapped two slots.'),
        'movedWarning' => __('Moved an existing slot.'),
        'replacedWarning' => __('Replaced an existing slot.'),
    ];
@endphp
<x-app-layout>
    <x-page-header :title="__('Schedule editor')">
        <x-slot name="subtitleSlot">
            <x-turma-label :turma="$turma" :showAno="true" />
        </x-slot>
        <x-slot name="actions">
            <x-btn variant="secondary" :href="route('horarios.turma', $turma)">{{ __('Cancel') }}</x-btn>
        </x-slot>
    </x-page-header>

    @if($atribuicoes->isEmpty())
        <x-card>
            <x-empty :title="__('No assignments for this class group')" icon="link" description="{{ __('Create assignments first (Atribuições).') }}">
                <x-btn variant="primary" :href="route('atribuicoes.index')">{{ __('Assignments') }}</x-btn>
            </x-empty>
        </x-card>
    @else
        <x-card x-data="horarioEditor({
            initial: {{ \Illuminate\Support\Js::from($initialSlots) }},
            diasLectivos: {{ \Illuminate\Support\Js::from($diasLectivos) }},
            atrPayload: {{ \Illuminate\Support\Js::from($atrPayload) }},
            turmaColors: {{ \Illuminate\Support\Js::from($turmaColors) }},
            mode: 'turma',
            i18n: {{ \Illuminate\Support\Js::from($i18n) }},
        })">
            <p class="text-sm text-muted mb-4">
                {{ __('Pick the assignment for each slot. Empty cells stay free. Saving overwrites the previous schedule of this class group.') }}
            </p>

            {{-- Toolbar global --}}
            <div class="flex flex-wrap items-center gap-2 mb-4 pb-4 border-b border-gray-100">
                <span class="text-xs text-muted me-2">{{ __('Clipboard:') }}</span>
                <span x-show="!clipboard" class="text-xs text-muted italic">{{ __('empty') }}</span>
                <span x-show="clipboard" class="text-xs">
                    <x-badge variant="info"><span x-text="clipboardLabel"></span></x-badge>
                    <button type="button" class="btn-link btn-link-muted ms-1" @click="clearClipboard()">{{ __('clear') }}</button>
                </span>
                <div class="ms-auto flex flex-wrap gap-2 items-center">
                    {{-- Mode toggle --}}
                    <div class="inline-flex rounded border border-gray-200 overflow-hidden text-xs">
                        <button type="button" @click="viewMode = 'form'"
                                x-bind:class="viewMode === 'form' ? 'bg-primary text-white' : 'bg-white text-navy hover:bg-gray-50'"
                                class="px-3 py-1 transition">
                            {{ __('Form mode') }}
                        </button>
                        <button type="button" @click="viewMode = 'visual'"
                                x-bind:class="viewMode === 'visual' ? 'bg-primary text-white' : 'bg-white text-navy hover:bg-gray-50'"
                                class="px-3 py-1 transition border-s border-gray-200">
                            {{ __('Visual mode') }}
                        </button>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" @click="confirmClearAll()">
                        <x-lucide-eraser class="w-4 h-4" /> {{ __('Clear all') }}
                    </button>
                </div>
            </div>

            {{-- Drag warning toast --}}
            <div x-show="dragWarning" x-cloak class="mb-3 px-3 py-2 bg-amber-50 border border-amber-200 text-amber-800 text-xs rounded">
                <x-lucide-info class="w-3 h-3 inline" /> <span x-text="dragWarning"></span>
            </div>

            <form method="POST" action="{{ route('horarios.bulk-turma.store', $turma) }}">
                @csrf

                {{-- Modo formulário (default) --}}
                <div x-show="viewMode === 'form'" class="overflow-x-auto">
                    <table class="table text-sm">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 100px">{{ __('Time') }}</th>
                                @foreach($diasLectivos as $diaNum)
                                    <th class="text-center">
                                        <div class="inline-flex items-center gap-1" x-data="{ open: false }" @click.outside="open = false">
                                            <span>{{ $diasSemana[$diaNum] }}</span>
                                            <button type="button" class="btn-link btn-link-muted text-xs" @click.prevent="open = !open" title="{{ __('Column actions') }}">
                                                <x-lucide-more-vertical class="w-4 h-4" />
                                            </button>
                                            <div x-show="open" x-cloak class="dropdown end-0 mt-6 text-start">
                                                <button type="button" class="dropdown-item w-full text-start" @click="copyColumn({{ $diaNum }}); open = false">{{ __('Copy column') }}</button>
                                                <button type="button" class="dropdown-item w-full text-start"
                                                        x-bind:disabled="clipboardType !== 'col'"
                                                        x-bind:class="clipboardType !== 'col' ? 'opacity-50 cursor-not-allowed' : ''"
                                                        @click="pasteColumn({{ $diaNum }}); open = false">{{ __('Paste column') }}</button>
                                                <button type="button" class="dropdown-item w-full text-start" @click="applyToAllDays({{ $diaNum }}); open = false">{{ __('Apply to all weekdays') }}</button>
                                                <div class="dropdown-divider"></div>
                                                <button type="button" class="dropdown-item w-full text-start text-danger" @click="clearColumn({{ $diaNum }}); open = false">{{ __('Clear column') }}</button>
                                            </div>
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tempos as $tempoNum => [$ini, $fim])
                                <tr>
                                    <td class="text-center align-middle">
                                        <div class="inline-flex items-center gap-1" x-data="{ open: false }" @click.outside="open = false">
                                            <div class="text-center">
                                                <div class="font-bold text-navy">{{ $tempoNum }}º</div>
                                                <div class="text-[10px] text-muted font-mono">{{ $ini }}–{{ $fim }}</div>
                                            </div>
                                            <button type="button" class="btn-link btn-link-muted text-xs" @click.prevent="open = !open">
                                                <x-lucide-more-vertical class="w-4 h-4" />
                                            </button>
                                            <div x-show="open" x-cloak class="dropdown start-0 ms-8 text-start">
                                                <button type="button" class="dropdown-item w-full text-start" @click="copyRow({{ $tempoNum }}); open = false">{{ __('Copy row') }}</button>
                                                <button type="button" class="dropdown-item w-full text-start"
                                                        x-bind:disabled="clipboardType !== 'row'"
                                                        x-bind:class="clipboardType !== 'row' ? 'opacity-50 cursor-not-allowed' : ''"
                                                        @click="pasteRow({{ $tempoNum }}); open = false">{{ __('Paste row') }}</button>
                                                <div class="dropdown-divider"></div>
                                                <button type="button" class="dropdown-item w-full text-start text-danger" @click="clearRow({{ $tempoNum }}); open = false">{{ __('Clear row') }}</button>
                                            </div>
                                        </div>
                                    </td>
                                    @foreach($diasLectivos as $diaNum)
                                        <td class="align-top p-1">
                                            <select name="slots[{{ $diaNum }}][{{ $tempoNum }}][atribuicao_id]"
                                                    x-model="slots[{{ $diaNum }}][{{ $tempoNum }}].atribuicao_id"
                                                    class="form-select text-xs">
                                                <option value="">—</option>
                                                @foreach($atribuicoes as $a)
                                                    <option value="{{ $a->id }}">
                                                        {{ $a->disciplina->sigla ?: \Illuminate\Support\Str::limit($a->disciplina->nome, 8) }} · {{ \Illuminate\Support\Str::limit($a->professor->user->name, 14) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input type="text"
                                                   name="slots[{{ $diaNum }}][{{ $tempoNum }}][sala]"
                                                   x-model="slots[{{ $diaNum }}][{{ $tempoNum }}].sala"
                                                   placeholder="{{ __('Room') }}"
                                                   class="form-input mt-1 text-xs" maxlength="20">
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Modo visual (drag & drop) --}}
                <div x-show="viewMode === 'visual'" x-cloak class="grid grid-cols-1 xl:grid-cols-4 gap-4">
                    <div class="xl:col-span-3 overflow-x-auto">
                        <table class="table text-xs">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 70px">{{ __('Time') }}</th>
                                    @foreach($diasLectivos as $diaNum)
                                        <th class="text-center">{{ $diasSemana[$diaNum] }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tempos as $tempoNum => [$ini, $fim])
                                    <tr>
                                        <td class="text-center align-middle">
                                            <div class="font-bold text-navy">{{ $tempoNum }}º</div>
                                            <div class="text-[10px] text-muted font-mono">{{ $ini }}–{{ $fim }}</div>
                                        </td>
                                        @foreach($diasLectivos as $diaNum)
                                            <td class="p-1 align-top">
                                                <div data-dnd-cell="{{ $diaNum }}:{{ $tempoNum }}"
                                                     class="dnd-cell min-h-[60px] rounded border border-dashed border-gray-200 bg-gray-50/60 p-1">
                                                    <template x-if="slots[{{ $diaNum }}][{{ $tempoNum }}].atribuicao_id">
                                                        <div :data-atribuicao-id="slots[{{ $diaNum }}][{{ $tempoNum }}].atribuicao_id"
                                                             :style="cellStyle(slots[{{ $diaNum }}][{{ $tempoNum }}].atribuicao_id)"
                                                             class="dnd-card cursor-move px-2 py-1 rounded text-[11px] leading-tight"
                                                             :title="cellLabel(slots[{{ $diaNum }}][{{ $tempoNum }}].atribuicao_id)">
                                                            <span x-text="cellLabel(slots[{{ $diaNum }}][{{ $tempoNum }}].atribuicao_id)"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pool de atribuições --}}
                    <div class="xl:col-span-1">
                        <h4 class="form-label text-xs uppercase tracking-wide mb-2">{{ __('Assignments') }}</h4>
                        <p class="text-[11px] text-muted mb-2">{{ __('Drag a card onto a slot. Drag back here to free a slot.') }}</p>
                        <div data-dnd-pool class="dnd-pool space-y-1 p-2 rounded border border-dashed border-gray-200 bg-gray-50/60 min-h-[120px]">
                            @foreach($atribuicoes as $a)
                                @php($c = TurmaColor::for($a->turma_id))
                                <div data-atribuicao-id="{{ $a->id }}"
                                     class="dnd-card cursor-move px-2 py-1 rounded text-[11px] leading-tight flex items-center justify-between gap-1"
                                     style="background: {{ $c['bg'] }}; border-left: 3px solid {{ $c['border'] }}; color: {{ $c['fg'] }}"
                                     x-bind:class="cargaCompleta('{{ $a->id }}') ? 'opacity-50' : ''"
                                     title="{{ $a->disciplina->nome }} · {{ $a->professor->user->name }}">
                                    <span class="truncate">{{ $a->disciplina->sigla ?: \Illuminate\Support\Str::limit($a->disciplina->nome, 8) }} · {{ \Illuminate\Support\Str::limit($a->professor->user->name, 14) }}</span>
                                    <span class="font-mono text-[10px] shrink-0 bg-white/40 px-1 rounded" x-text="cargaLabel('{{ $a->id }}')"></span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                @error('slots')<p class="form-error mt-3">{{ $message }}</p>@enderror

                <div class="flex items-center gap-3 mt-6 pt-6 border-t border-gray-100">
                    <x-btn variant="primary" type="submit" icon="save">{{ __('Save schedule') }}</x-btn>
                    <x-btn variant="secondary" :href="route('horarios.turma', $turma)">{{ __('Cancel') }}</x-btn>
                    <span class="text-xs text-muted ms-auto">
                        <span x-text="filledCount"></span> / <span x-text="totalCount"></span> {{ __('slots filled') }}
                    </span>
                </div>
            </form>
        </x-card>

        <x-card :title="__('Legend')">
            <p class="text-sm text-muted mb-3">{{ __('Available assignments for this class group:') }}</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                @foreach($atribuicoes as $a)
                    <div class="flex items-center gap-2">
                        <x-badge variant="info">{{ $a->disciplina->sigla ?: '—' }}</x-badge>
                        <span class="text-navy">{{ $a->disciplina->nome }}</span>
                        <span class="text-muted text-xs">· {{ $a->professor->user->name }}</span>
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif
</x-app-layout>
