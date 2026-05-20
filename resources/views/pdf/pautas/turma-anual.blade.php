@php
    $titulo = $turma->nome_completo;
    $subtitulo = __('Annual gradebook') . ' · ' . $turma->anoLectivo->codigo;
@endphp
<x-pdf-layout :titulo="$titulo" :subtitulo="$subtitulo">
    <style>@page { size: A4 landscape; margin: 1cm }</style>

    <div class="info-row">
        <strong>{{ __('School Year') }}:</strong> {{ $turma->anoLectivo->codigo }}
        @if($turma->curso) · <strong>{{ __('Course') }}:</strong> {{ $turma->curso->nome }} @endif
        · <strong>{{ __('Class Director') }}:</strong> {{ $turma->directorTurma?->user?->name ?? '—' }}
        · <strong>{{ __('Students') }}:</strong> {{ count($matriculas) }}
    </div>

    <table class="data" style="font-size:8px">
        <thead>
            <tr>
                <th rowspan="2" style="vertical-align: bottom; text-align: left; width: 17%">{{ __('Student') }}</th>
                @foreach($atribuicoes as $atr)
                    <th colspan="{{ count($trimestres) + 1 }}">{{ $atr->disciplina->sigla ?: \Illuminate\Support\Str::limit($atr->disciplina->nome, 5) }}</th>
                @endforeach
                <th rowspan="2" style="vertical-align: bottom; background:#dfe8e3">{{ __('Average') }}</th>
                <th rowspan="2" style="vertical-align: bottom">{{ __('Situation') }}</th>
            </tr>
            <tr>
                @foreach($atribuicoes as $atr)
                    @foreach($trimestres as $t)
                        <th style="font-size:7px">{{ $t->numero }}º</th>
                    @endforeach
                    <th style="font-size:7px; background:#f0f1f6">{{ __('An.') }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($matriculas as $m)
                <tr>
                    <td class="name">{{ $m->aluno->user->name }}</td>
                    @foreach($atribuicoes as $atr)
                        @foreach($trimestres as $t)
                            @php($v = $mediasPorTrimestre[$m->id][$atr->disciplina_id][$t->numero] ?? null)
                            <td class="{{ ($v !== null && $v < $calc->notaMinima) ? 'neg' : '' }}">{{ $v !== null ? rtrim(rtrim((string) $v, '0'), '.') : '—' }}</td>
                        @endforeach
                        @php($anual = $mediasAnuais[$m->id][$atr->disciplina_id] ?? null)
                        <td style="background:#f8fafc" class="{{ ($anual !== null && $anual < $calc->notaMinima) ? 'neg' : '' }}"><strong>{{ $anual !== null ? $anual : '—' }}</strong></td>
                    @endforeach
                    <td style="background:#dfe8e3" class="{{ ($mediaGeral[$m->id] ?? null) !== null && $mediaGeral[$m->id] < $calc->notaMinima ? 'neg' : '' }}"><strong>{{ ($mediaGeral[$m->id] ?? null) !== null ? $mediaGeral[$m->id] : '—' }}</strong></td>
                    @php($sit = $situacao[$m->id])
                    <td class="{{ $sit === 'aprovado' ? 'approved' : ($sit === 'recurso' ? 'second' : ($sit === 'reprovado' ? 'failed' : 'pending')) }}">{{ __(ucfirst($sit)) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer-note">
        <p><strong>{{ __('Formula') }}:</strong> {{ $calc->formulaDescricao() }}</p>
        <p><strong>{{ __('Passing grade') }}:</strong> ≥ {{ $calc->notaMinima }}</p>
        <p><strong>{{ __('Rule') }}:</strong> 0 {{ __('negatives') }} → {{ __('Aprovado') }} · 1–{{ $calc->maxNegativasRecurso }} → {{ __('Recurso') }} · {{ $calc->maxNegativasRecurso + 1 }}+ → {{ __('Reprovado') }}</p>
    </div>

    <div class="sig-block">
        <div class="sig-cell"><div class="sig-line">{{ __('Class Director') }}</div></div>
        <div class="sig-cell"><div class="sig-line">{{ __('Director Pedagógico') }}</div></div>
    </div>
</x-pdf-layout>
