<x-app-layout>
    <x-page-header :title="__('Gradebook') . ' — ' . __('by subject')">
        <x-slot name="subtitleSlot">
            <x-turma-label :turma="$atribuicao->turma" /> · {{ $atribuicao->disciplina->nome }} · {{ $trimestre->numero }}º {{ __('Term') }}
        </x-slot>
        <x-slot name="actions">
            <x-btn variant="danger" icon="file-down" :href="route('pautas.disciplina.pdf', ['atribuicao' => $atribuicao, 'trimestre' => $trimestre])">{{ __('Export PDF') }}</x-btn>
            <x-btn variant="secondary" :href="route('pautas.index')">{{ __('Back') }}</x-btn>
        </x-slot>
    </x-page-header>

    <div class="print-page">
        @include('pautas._print-header', [
            'titulo' => $atribuicao->turma->classe->nome . ' ' . $atribuicao->turma->nome . ' · ' . $atribuicao->disciplina->nome,
            'subtitulo' => $trimestre->numero . 'º ' . __('Term') . ' · ' . $atribuicao->anoLectivo?->codigo,
        ])

        <x-card>
            <div class="table-wrapper">
                <table class="table print-table">
                    <thead>
                        <tr>
                            <th>{{ __('Student') }}</th>
                            @foreach($avaliacoes as $av)
                                <th class="text-center">
                                    <div class="text-xs">{{ $av->titulo }}</div>
                                    <div class="text-[0.625rem] text-muted normal-case tracking-normal">{{ __('weight') }} {{ rtrim(rtrim($av->peso, '0'), '.') }}</div>
                                </th>
                            @endforeach
                            <th class="text-center !text-navy font-bold">{{ __('Term Average') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($matriculas as $m)
                            <tr>
                                <td class="font-semibold text-navy">{{ $m->aluno->user->name }}</td>
                                @foreach($avaliacoes as $av)
                                    @php($v = $notasMap[$m->id][$av->id] ?? null)
                                    <td class="text-center {{ ($v !== null && $v < $calc->notaMinima) ? 'nota-negativa' : '' }}">
                                        {{ $v !== null ? rtrim(rtrim((string) $v, '0'), '.') : '—' }}
                                    </td>
                                @endforeach
                                <td class="text-center font-bold {{ ($medias[$m->id] ?? null) !== null && $medias[$m->id] < $calc->notaMinima ? 'nota-negativa' : 'text-navy' }}">
                                    {{ ($medias[$m->id] ?? null) !== null ? $medias[$m->id] : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ 2 + count($avaliacoes) }}" class="table-empty">{{ __('No records found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <p class="text-xs text-muted mt-4">
                <strong>{{ __('Formula') }}:</strong> {{ __('Term average = weighted by evaluation weight.') }}
                · <strong>{{ __('Passing grade') }}:</strong> ≥ {{ $calc->notaMinima }}
            </p>
        </x-card>
    </div>
</x-app-layout>
