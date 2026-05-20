@php
    $titulo = $turma->nome_completo;
    $subtitulo = __('Class gradebook for a term') . ' · ' . $trimestre->numero . 'º ' . __('Term');
@endphp
<x-pdf-layout :titulo="$titulo" :subtitulo="$subtitulo">
    <div class="info-row">
        <strong>{{ __('School Year') }}:</strong> {{ $turma->anoLectivo->codigo }}
        @if($turma->curso) · <strong>{{ __('Course') }}:</strong> {{ $turma->curso->nome }} @endif
        · <strong>{{ __('Class Director') }}:</strong> {{ $turma->directorTurma?->user?->name ?? '—' }}
        · <strong>{{ __('Students') }}:</strong> {{ count($matriculas) }}
    </div>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 25%; text-align: left">{{ __('Student') }}</th>
                @foreach($atribuicoes as $atr)
                    <th>{{ $atr->disciplina->sigla ?: \Illuminate\Support\Str::limit($atr->disciplina->nome, 6) }}</th>
                @endforeach
                <th style="background:#dfe8e3">{{ __('Average') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($matriculas as $m)
                <tr>
                    <td class="name">{{ $m->aluno->user->name }}</td>
                    @foreach($atribuicoes as $atr)
                        @php($v = $mediasTurma[$m->id][$atr->disciplina_id] ?? null)
                        <td class="{{ ($v !== null && $v < $calc->notaMinima) ? 'neg' : '' }}">{{ $v !== null ? rtrim(rtrim((string) $v, '0'), '.') : '—' }}</td>
                    @endforeach
                    <td style="background:#f8fafc" class="{{ ($mediaGeral[$m->id] ?? null) !== null && $mediaGeral[$m->id] < $calc->notaMinima ? 'neg' : '' }}"><strong>{{ ($mediaGeral[$m->id] ?? null) !== null ? $mediaGeral[$m->id] : '—' }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer-note">
        <p><strong>{{ __('Passing grade') }}:</strong> ≥ {{ $calc->notaMinima }} · <strong>{{ __('Average') }}:</strong> {{ __('simple average of all subjects.') }}</p>
    </div>

    <div class="sig-block">
        <div class="sig-cell"><div class="sig-line">{{ __('Class Director') }}</div></div>
        <div class="sig-cell"><div class="sig-line">{{ __('Director Pedagógico') }}</div></div>
    </div>
</x-pdf-layout>
