<x-app-layout>
    <x-page-header :title="__('Class gradebook for a term')">
        <x-slot name="subtitleSlot">
            <x-turma-label :turma="$turma" :showAno="true" /> · {{ $trimestre->numero }}º {{ __('Term') }}
        </x-slot>
        <x-slot name="actions">
            @role('director_geral|director_pedagogico')
                <x-btn type="button" variant="secondary" icon="mail"
                       x-on:click="$dispatch('open-modal','notify-boletim')">{{ __('Notify guardians') }}</x-btn>

                <x-modal name="notify-boletim" maxWidth="lg">
                    <form method="POST" action="{{ route('pautas.turma-trimestre.notificar', ['turma' => $turma, 'trimestre' => $trimestre]) }}" class="p-6">
                        @csrf
                        <h3 class="text-lg font-semibold text-navy mb-2">{{ __('Notify guardians') }}</h3>
                        <p class="text-sm text-muted mb-4">{{ __('Send an email to the guardians of every student in this class, informing the trimester report is available.') }}</p>
                        <div class="flex justify-end gap-2">
                            <x-btn type="button" variant="secondary" x-on:click="$dispatch('close-modal','notify-boletim')">{{ __('Cancel') }}</x-btn>
                            <x-btn type="submit" variant="primary" icon="send">{{ __('Send') }}</x-btn>
                        </div>
                    </form>
                </x-modal>
            @endrole
            <x-btn variant="danger" icon="file-down" :href="route('pautas.turma-trimestre.pdf', ['turma' => $turma, 'trimestre' => $trimestre])">{{ __('Export PDF') }}</x-btn>
            <x-btn variant="secondary" :href="route('pautas.index')">{{ __('Back') }}</x-btn>
        </x-slot>
    </x-page-header>

    <div class="print-page">
        @include('pautas._print-header', [
            'titulo' => $turma->classe->nome . ' ' . $turma->nome . ($turma->curso ? ' (' . $turma->curso->sigla . ')' : ''),
            'subtitulo' => $trimestre->numero . 'º ' . __('Term') . ' · ' . $turma->anoLectivo->codigo,
        ])

        <x-card>
            <div class="table-wrapper">
                <table class="table print-table text-xs">
                    <thead>
                        <tr>
                            <th class="text-left">{{ __('Student') }}</th>
                            @foreach($atribuicoes as $atr)
                                <th class="text-center" title="{{ $atr->disciplina->nome }}">{{ $atr->disciplina->sigla ?: \Illuminate\Support\Str::limit($atr->disciplina->nome, 5) }}</th>
                            @endforeach
                            <th class="text-center !text-navy">{{ __('Average') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($matriculas as $m)
                            <tr>
                                <td class="font-semibold text-navy">{{ $m->aluno->user->name }}</td>
                                @foreach($atribuicoes as $atr)
                                    @php($v = $mediasTurma[$m->id][$atr->disciplina_id] ?? null)
                                    <td class="text-center {{ ($v !== null && $v < $calc->notaMinima) ? 'nota-negativa' : '' }}">
                                        {{ $v !== null ? rtrim(rtrim((string) $v, '0'), '.') : '—' }}
                                    </td>
                                @endforeach
                                <td class="text-center font-bold {{ ($mediaGeral[$m->id] ?? null) !== null && $mediaGeral[$m->id] < $calc->notaMinima ? 'nota-negativa' : 'text-navy' }}">
                                    {{ ($mediaGeral[$m->id] ?? null) !== null ? $mediaGeral[$m->id] : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ 2 + count($atribuicoes) }}" class="table-empty">{{ __('No records found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <p class="text-xs text-muted mt-4">
                <strong>{{ __('Passing grade') }}:</strong> ≥ {{ $calc->notaMinima }} · <strong>{{ __('Average') }}:</strong> {{ __('simple average of all subjects.') }}
            </p>
        </x-card>
    </div>
</x-app-layout>
