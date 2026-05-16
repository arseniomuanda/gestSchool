<x-app-layout>
    <x-page-header :title="$falta->professor->user->name" :subtitle="$falta->data->format('d/m/Y') . ' · ' . $falta->tempo_inicio . 'º–' . $falta->tempo_fim . 'º'">
        <x-slot name="actions">
            @can('update', $falta)
                <x-btn variant="primary" icon="pencil" :href="route('faltas-professores.edit', $falta)">{{ __('Edit') }}</x-btn>
            @endcan
            @can('justify', $falta)
                @unless($falta->justificacao_em)
                    <form action="{{ route('faltas-professores.justify', $falta) }}" method="POST" class="inline">
                        @csrf
                        <x-btn variant="success" type="submit" icon="check">{{ __('Mark justified') }}</x-btn>
                    </form>
                @endunless
            @endcan
            @can('delete', $falta)
                <form action="{{ route('faltas-professores.destroy', $falta) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <x-btn variant="danger" type="submit" icon="trash-2">{{ __('Delete') }}</x-btn>
                </form>
            @endcan
            <x-btn variant="secondary" :href="route('faltas-professores.index')">{{ __('Back') }}</x-btn>
        </x-slot>
    </x-page-header>

    <x-card>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
            <div>
                <dt class="form-label">{{ __('Type') }}</dt>
                <dd>
                    @switch($falta->tipo)
                        @case('justificada')<x-badge variant="success">{{ __('Justified') }}</x-badge>@break
                        @case('injustificada')<x-badge variant="danger">{{ __('Unjustified') }}</x-badge>@break
                        @case('licenca')<x-badge variant="info">{{ __('Leave') }}</x-badge>@break
                    @endswitch
                </dd>
            </div>
            <div>
                <dt class="form-label">{{ __('Justified at') }}</dt>
                <dd class="text-navy">{{ $falta->justificacao_em?->format('d/m/Y H:i') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="form-label">{{ __('Periods') }}</dt>
                <dd class="text-navy">{{ $falta->tempo_inicio }}º–{{ $falta->tempo_fim }}º ({{ $falta->duracao_tempos }} {{ __('periods') }})</dd>
            </div>
            <div>
                <dt class="form-label">{{ __('Substitute') }}</dt>
                <dd class="text-navy">{{ $falta->substituto?->user?->name ?? '—' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="form-label">{{ __('Reason') }}</dt>
                <dd class="text-navy whitespace-pre-line">{{ $falta->motivo ?? '—' }}</dd>
            </div>
            <div>
                <dt class="form-label">{{ __('Registered by') }}</dt>
                <dd class="text-navy">{{ $falta->registadoPor?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="form-label">{{ __('Registered at') }}</dt>
                <dd class="text-navy">{{ $falta->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>
            </div>
        </dl>
    </x-card>
</x-app-layout>
