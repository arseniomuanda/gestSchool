@php
    $titulo = $matricula->aluno->user->name;
    $subtitulo = __('Report Card') . ' · ' . $matricula->anoLectivo->codigo;
@endphp
<x-pdf-layout :titulo="$titulo" :subtitulo="$subtitulo">
    <div class="info-row">
        <strong>{{ __('Student') }}:</strong> {{ $matricula->aluno->user->name }}
        · <strong>{{ __('Enrollment Number') }}:</strong> {{ $matricula->numero_matricula }}
        · <strong>{{ __('Class Groups') }}:</strong> {{ $matricula->turma->nome_completo }}
        · <strong>{{ __('School Year') }}:</strong> {{ $matricula->anoLectivo->codigo }}
    </div>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 40%; text-align: left">{{ __('Subjects List') }}</th>
                @foreach($trimestres as $t)<th>{{ $t->numero }}º {{ __('Term') }}</th>@endforeach
                <th style="background:#dfe8e3">{{ __('Annual Average') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($medias as $info)
                <tr>
                    <td class="name">{{ $info['nome'] }}</td>
                    @foreach($trimestres as $t)
                        @php($m = $info['trimestres'][$t->id] ?? null)
                        <td class="{{ $m !== null && $m < 10 ? 'neg' : '' }}">{{ $m !== null ? $m : '—' }}</td>
                    @endforeach
                    <td style="background:#f8fafc" class="{{ ($info['anual'] ?? null) !== null && $info['anual'] < 10 ? 'neg' : '' }}"><strong>{{ $info['anual'] !== null ? $info['anual'] : '—' }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="{{ 2 + count($trimestres) }}" style="text-align:center; padding:20px">{{ __('No records found.') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-note">
        <p><strong>{{ __('Passing grade') }}:</strong> ≥ 10 — escala de 0 a 20.</p>
    </div>

    <div class="sig-block">
        <div class="sig-cell"><div class="sig-line">{{ __('Class Director') }}</div></div>
        <div class="sig-cell"><div class="sig-line">{{ __('Director Pedagógico') }}</div></div>
    </div>
</x-pdf-layout>
