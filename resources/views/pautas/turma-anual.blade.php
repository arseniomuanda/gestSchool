<x-app-layout>
    <x-page-header :title="__('Annual gradebook')">
        <x-slot name="subtitleSlot">
            <x-turma-label :turma="$turma" :showAno="true" />
        </x-slot>
        <x-slot name="actions">
            <x-btn variant="danger" icon="file-down" :href="route('pautas.turma-anual.pdf', array_merge(['turma' => $turma], ['peso_t1' => $calc->pesos[0], 'peso_t2' => $calc->pesos[1], 'peso_t3' => $calc->pesos[2]]))">{{ __('Export PDF') }}</x-btn>
            <x-btn variant="secondary" :href="route('pautas.index')">{{ __('Back') }}</x-btn>
        </x-slot>
    </x-page-header>

    {{-- Toggle de fórmula --}}
    <x-card :title="__('Calculation formula')" class="no-print">
        <form method="GET" class="flex flex-wrap items-end gap-3" autocomplete="off">
            <div>
                <label class="form-label">{{ __('Weight') }} 1º</label>
                <input type="number" min="1" max="5" name="peso_t1" value="{{ $calc->pesos[0] }}" class="form-input w-20">
            </div>
            <div>
                <label class="form-label">{{ __('Weight') }} 2º</label>
                <input type="number" min="1" max="5" name="peso_t2" value="{{ $calc->pesos[1] }}" class="form-input w-20">
            </div>
            <div>
                <label class="form-label">{{ __('Weight') }} 3º</label>
                <input type="number" min="1" max="5" name="peso_t3" value="{{ $calc->pesos[2] }}" class="form-input w-20">
            </div>
            <x-btn variant="secondary" type="submit" size="sm">{{ __('Recalculate') }}</x-btn>
            <a href="?peso_t1=1&peso_t2=1&peso_t3=1" class="btn-link">{{ __('Simple (1-1-1)') }}</a>
            <a href="?peso_t1=1&peso_t2=1&peso_t3=2" class="btn-link">{{ __('Weighted (1-1-2)') }}</a>
            <span class="text-xs text-muted ms-auto"><strong>{{ __('Formula') }}:</strong> {{ $calc->formulaDescricao() }}</span>
        </form>
    </x-card>

    <div class="print-page">
        @include('pautas._print-header', [
            'titulo' => $turma->classe->nome . ' ' . $turma->nome . ($turma->curso ? ' (' . $turma->curso->sigla . ')' : ''),
            'subtitulo' => __('Annual gradebook') . ' · ' . $turma->anoLectivo->codigo,
        ])

        <x-card>
            <div class="table-wrapper">
                <table class="table print-table text-xs">
                    <thead>
                        <tr>
                            <th rowspan="2" class="text-left align-bottom">{{ __('Student') }}</th>
                            @foreach($atribuicoes as $atr)
                                <th colspan="{{ count($trimestres) + 1 }}" class="text-center" title="{{ $atr->disciplina->nome }}">
                                    {{ $atr->disciplina->sigla ?: $atr->disciplina->nome }}
                                </th>
                            @endforeach
                            <th rowspan="2" class="text-center align-bottom !text-navy">{{ __('Average') }}</th>
                            <th rowspan="2" class="text-center align-bottom !text-navy">{{ __('Situation') }}</th>
                        </tr>
                        <tr>
                            @foreach($atribuicoes as $atr)
                                @foreach($trimestres as $t)
                                    <th class="text-center text-[10px]">{{ $t->numero }}º</th>
                                @endforeach
                                <th class="text-center text-[10px] !text-navy">An.</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($matriculas as $m)
                            <tr>
                                <td class="font-semibold text-navy">{{ $m->aluno->user->name }}</td>
                                @foreach($atribuicoes as $atr)
                                    @foreach($trimestres as $t)
                                        @php($v = $mediasPorTrimestre[$m->id][$atr->disciplina_id][$t->numero] ?? null)
                                        <td class="text-center {{ ($v !== null && $v < $calc->notaMinima) ? 'nota-negativa' : '' }}">
                                            {{ $v !== null ? rtrim(rtrim((string) $v, '0'), '.') : '—' }}
                                        </td>
                                    @endforeach
                                    @php($anual = $mediasAnuais[$m->id][$atr->disciplina_id] ?? null)
                                    <td class="text-center font-bold {{ ($anual !== null && $anual < $calc->notaMinima) ? 'nota-negativa' : 'text-navy' }}">
                                        {{ $anual !== null ? $anual : '—' }}
                                    </td>
                                @endforeach
                                <td class="text-center font-bold {{ ($mediaGeral[$m->id] ?? null) !== null && $mediaGeral[$m->id] < $calc->notaMinima ? 'nota-negativa' : 'text-navy' }}">
                                    {{ ($mediaGeral[$m->id] ?? null) !== null ? $mediaGeral[$m->id] : '—' }}
                                </td>
                                <td class="text-center situacao-{{ $situacao[$m->id] }}">
                                    {{ __(ucfirst($situacao[$m->id])) }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ 3 + (count($atribuicoes) * (count($trimestres) + 1)) }}" class="table-empty">{{ __('No records found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="text-xs text-muted mt-4 space-y-1">
                <p><strong>{{ __('Formula') }}:</strong> {{ $calc->formulaDescricao() }}</p>
                <p><strong>{{ __('Passing grade') }}:</strong> ≥ {{ $calc->notaMinima }} · {{ __('Negative') }} = {{ __('annual average') }} &lt; {{ $calc->notaMinima }}</p>
                <p><strong>{{ __('Rule') }}:</strong> 0 {{ __('negatives') }} → {{ __('Approved') }} · 1–{{ $calc->maxNegativasRecurso }} → {{ __('Second-chance') }} · {{ $calc->maxNegativasRecurso + 1 }}+ → {{ __('Failed') }}</p>
            </div>
        </x-card>
    </div>
</x-app-layout>
