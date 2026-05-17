<x-app-layout>
    <x-page-header :title="__('Teacher absences')" :subtitle="__('Absences from service')">
        <x-slot name="actions">
            @can('create', App\Models\FaltaProfessor::class)
                <x-btn variant="primary" icon="plus" :href="route('faltas-professores.create')">{{ __('Register absence') }}</x-btn>
            @endcan
        </x-slot>
    </x-page-header>

    <x-card>
        <form method="GET" class="flex flex-wrap items-end gap-3 mb-4" autocomplete="off" role="search">
            <div>
                <label class="form-label">{{ __('Month') }}</label>
                <input type="month" name="mes" value="{{ $mes }}" class="form-input" autocomplete="off">
            </div>
            @if($professores->isNotEmpty())
                <div>
                    <label class="form-label">{{ __('Teacher') }}</label>
                    <select name="professor_id" class="form-select">
                        <option value="">— {{ __('All') }} —</option>
                        @foreach($professores as $p)
                            <option value="{{ $p->id }}" @selected($profId == $p->id)>{{ $p->user->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <x-btn variant="secondary" type="submit">{{ __('Filter') }}</x-btn>
            @if($mes || $profId)
                <x-btn variant="muted" :href="route('faltas-professores.index')">{{ __('Clear') }}</x-btn>
            @endif
        </form>

        @if($faltas->isEmpty())
            <x-empty :title="__('No absences in this period')" icon="calendar-check" />
        @else
            <div class="overflow-x-auto">
                <table class="table text-sm">
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Teacher') }}</th>
                            <th>{{ __('Periods') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Substitute') }}</th>
                            <th>{{ __('Justified') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($faltas as $f)
                            <tr>
                                <td>{{ $f->data->format('d/m/Y') }}</td>
                                <td>{{ $f->professor->user->name }}</td>
                                <td>{{ $f->tempo_inicio }}º–{{ $f->tempo_fim }}º <span class="text-xs text-muted">({{ $f->duracao_tempos }})</span></td>
                                <td>
                                    @switch($f->tipo)
                                        @case('justificada')<x-badge variant="success">{{ __('Justified') }}</x-badge>@break
                                        @case('injustificada')<x-badge variant="danger">{{ __('Unjustified') }}</x-badge>@break
                                        @case('licenca')<x-badge variant="info">{{ __('Leave') }}</x-badge>@break
                                    @endswitch
                                </td>
                                <td class="text-xs text-muted">{{ $f->substituto?->user?->name ?? '—' }}</td>
                                <td>
                                    @if($f->justificacao_em)
                                        <x-badge variant="success">{{ $f->justificacao_em->format('d/m/Y') }}</x-badge>
                                    @else
                                        <x-badge variant="muted">{{ __('Pending') }}</x-badge>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('faltas-professores.show', $f) }}" class="btn-link text-xs">{{ __('View') }}</a>
                                    @can('justify', $f)
                                        @unless($f->justificacao_em)
                                            <form action="{{ route('faltas-professores.justify', $f) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="btn-link text-xs text-success">{{ __('Mark justified') }}</button>
                                            </form>
                                        @endunless
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $faltas->links() }}</div>
        @endif
    </x-card>
</x-app-layout>
