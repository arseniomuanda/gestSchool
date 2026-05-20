@php
    $titulo = $atribuicao->turma->nome_completo . ' · ' . $atribuicao->disciplina->nome;
    $subtitulo = __('Gradebook') . ' — ' . __('by subject') . ' · ' . $trimestre->numero . 'º ' . __('Term');
@endphp
<x-pdf-layout :titulo="$titulo" :subtitulo="$subtitulo">
    <div class="info-row">
        <strong>{{ __('School Year') }}:</strong> {{ $atribuicao->anoLectivo->codigo }}
        @if($atribuicao->turma->curso) · <strong>{{ __('Course') }}:</strong> {{ $atribuicao->turma->curso->sigla }} @endif
        · <strong>{{ __('Term') }}:</strong> {{ $trimestre->numero }}º
        · <strong>{{ __('Teacher') }}:</strong> {{ $atribuicao->professor->user->name }}
    </div>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 30%; text-align: left">{{ __('Student') }}</th>
                @foreach($avaliacoes as $av)
                    <th>{{ $av->titulo }}<br><span style="font-size:7px;color:#76838f">peso {{ rtrim(rtrim($av->peso, '0'), '.') }}</span></th>
                @endforeach
                <th style="background:#dfe8e3">{{ __('Term Average') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($matriculas as $m)
                <tr>
                    <td class="name">{{ $m->aluno->user->name }}</td>
                    @foreach($avaliacoes as $av)
                        @php($v = $notasMap[$m->id][$av->id] ?? null)
                        <td class="{{ ($v !== null && $v < $calc->notaMinima) ? 'neg' : '' }}">{{ $v !== null ? rtrim(rtrim((string) $v, '0'), '.') : '—' }}</td>
                    @endforeach
                    <td style="background:#f8fafc" class="{{ ($medias[$m->id] ?? null) !== null && $medias[$m->id] < $calc->notaMinima ? 'neg' : '' }}"><strong>{{ ($medias[$m->id] ?? null) !== null ? $medias[$m->id] : '—' }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer-note">
        <p><strong>{{ __('Passing grade') }}:</strong> ≥ {{ $calc->notaMinima }}</p>
        <p><strong>{{ __('Formula') }}:</strong> {{ __('Term average = weighted by evaluation weight.') }}</p>
    </div>

    <div class="sig-block">
        <div class="sig-cell"><div class="sig-line">{{ __('Teacher') }}</div></div>
        <div class="sig-cell"><div class="sig-line">{{ __('Director Pedagógico') }}</div></div>
    </div>
</x-pdf-layout>
