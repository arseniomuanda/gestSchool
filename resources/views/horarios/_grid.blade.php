@php
    $tempos = config('escola.tempos_lectivos');
    $dias = array_filter(\App\Models\Horario::diasSemana(), fn ($_, $k) => in_array($k, config('escola.dias_lectivos', [1,2,3,4,5])), ARRAY_FILTER_USE_BOTH);
    $modo = $modo ?? 'turma';   // 'turma' ou 'professor'
@endphp

<x-card>
    <div class="overflow-x-auto">
        <table class="table print-table text-sm">
            <thead>
                <tr>
                    <th class="text-center" style="width: 100px">{{ __('Time') }}</th>
                    @foreach($dias as $num => $nome)
                        <th class="text-center">{{ $nome }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($tempos as $tempoNum => [$ini, $fim])
                    <tr>
                        <td class="text-center font-mono text-xs text-muted">
                            <div class="font-bold text-navy">{{ $tempoNum }}º</div>
                            <div>{{ \Carbon\Carbon::parse($ini)->format('H:i') }}</div>
                            <div class="text-[10px]">{{ \Carbon\Carbon::parse($fim)->format('H:i') }}</div>
                        </td>
                        @foreach($dias as $diaNum => $diaNome)
                            @php($key = $diaNum . '-' . $tempoNum)
                            @php($slot = $horarios[$key] ?? null)
                            <td class="align-top text-center">
                                @if($slot)
                                    @foreach($slot as $h)
                                        <div class="rounded p-2 mb-1 text-xs" style="background-color: {{ $modo === 'turma' ? '#dfe8e3' : '#e3f8e3' }};">
                                            @if($modo === 'turma')
                                                <div class="font-semibold text-navy">{{ $h->atribuicao->disciplina->nome }}</div>
                                                <div class="text-[10px] text-muted">{{ $h->atribuicao->professor->user->name }}</div>
                                            @else
                                                <div class="font-semibold text-navy">{{ $h->atribuicao->disciplina->nome }}</div>
                                                <div class="text-[10px] text-muted"><x-turma-label :turma="$h->atribuicao->turma" :inline="true" /></div>
                                            @endif
                                            @if($h->sala)
                                                <div class="text-[10px] text-muted mt-0.5">📍 {{ $h->sala }}</div>
                                            @endif
                                            @hasanyrole('director_geral|director_pedagogico|secretario')
                                                <div class="mt-1 text-[10px] no-print">
                                                    <a href="{{ route('horarios.edit', $h) }}" class="text-primary hover:underline">{{ __('Edit') }}</a>
                                                </div>
                                            @endhasanyrole
                                        </div>
                                    @endforeach
                                @else
                                    <span class="text-muted text-xs">—</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-card>
