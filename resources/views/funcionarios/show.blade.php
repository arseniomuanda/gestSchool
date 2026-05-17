<x-app-layout>
    <x-page-header :title="$funcionario->user->name" :subtitle="$funcionario->is_auxiliar ? __('Auxiliary Staff') : __('Administrative Staff')">
        <x-slot name="actions">
            <x-btn variant="primary" icon="pencil" :href="route($routeBase . '.edit', $funcionario)">{{ __('Edit') }}</x-btn>
            <x-btn variant="secondary" :href="route($routeBase . '.index')">{{ __('Back') }}</x-btn>
        </x-slot>
    </x-page-header>

    <x-card>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
            <div><dt class="form-label">{{ __('Email') }}</dt><dd class="text-navy">{{ $funcionario->user->email }}</dd></div>
            <div><dt class="form-label">{{ __('Phone') }}</dt><dd class="text-navy">{{ $funcionario->user->phone ?? '—' }}</dd></div>
            <div><dt class="form-label">{{ __('Staff Number') }}</dt><dd class="font-mono">{{ $funcionario->numero_funcionario ?? '—' }}</dd></div>
            @if($funcionario->is_auxiliar)
                <div>
                    <dt class="form-label">{{ __('Auxiliary function') }}</dt>
                    <dd>{{ $funcionario->funcao ? __('funcao_' . $funcionario->funcao) : '—' }}</dd>
                </div>
            @endif
            <div><dt class="form-label">{{ __('Position') }}</dt><dd>{{ $funcionario->cargo ?? '—' }}</dd></div>
            <div><dt class="form-label">{{ __('Department') }}</dt><dd>{{ $funcionario->departamento ?? '—' }}</dd></div>
            <div><dt class="form-label">{{ __('Hire Date') }}</dt><dd>{{ $funcionario->data_admissao?->format('d/m/Y') ?? '—' }}</dd></div>
            <div class="sm:col-span-2">
                <dt class="form-label">{{ __('Roles') }}</dt>
                <dd class="space-x-1">@foreach($funcionario->user->roles as $r)<x-badge variant="muted">{{ __($r->name) }}</x-badge>@endforeach</dd>
            </div>
        </dl>
    </x-card>
</x-app-layout>
