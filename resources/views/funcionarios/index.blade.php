<x-app-layout>
    <x-page-header :title="$categoria === 'auxiliar' ? __('Auxiliary Staff') : __('Administrative Staff')" />

    <x-data-table
        :searchPlaceholder="__('Search')"
        :searchValue="$q ?? ''"
        :createUrl="route($routeBase . '.create')">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('Name') }}</th>
                @if($categoria === 'auxiliar')
                    <th>{{ __('Auxiliary function') }}</th>
                @else
                    <th>{{ __('Position') }}</th>
                    <th>{{ __('Department') }}</th>
                @endif
                <th>{{ __('Roles') }}</th>
                <th class="text-right">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($funcionarios as $f)
                <tr>
                    <td class="font-mono text-xs text-muted">{{ $f->numero_funcionario ?? '—' }}</td>
                    <td class="font-semibold text-navy">{{ $f->user->name }}</td>
                    @if($categoria === 'auxiliar')
                        <td>{{ $f->funcao ? __('funcao_' . $f->funcao) : '—' }}</td>
                    @else
                        <td>{{ $f->cargo ?? '—' }}</td>
                        <td>{{ $f->departamento ?? '—' }}</td>
                    @endif
                    <td>
                        @foreach($f->user->roles as $r)
                            <x-badge variant="muted">{{ __($r->name) }}</x-badge>
                        @endforeach
                    </td>
                    <td class="table-actions">
                        <x-btn-link :href="route($routeBase . '.show', $f)">{{ __('View') }}</x-btn-link>
                        <x-btn-link variant="muted" :href="route($routeBase . '.edit', $f)">{{ __('Edit') }}</x-btn-link>
                        <form action="{{ route($routeBase . '.destroy', $f) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('Delete?') }}');">
                            @csrf @method('DELETE')<button class="btn-link btn-link-danger">{{ __('Delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ $categoria === 'auxiliar' ? 5 : 6 }}" class="table-empty">{{ __('No records found.') }}</td></tr>
            @endforelse
        </tbody>
        <x-slot name="footer">{{ $funcionarios->links() }}</x-slot>
    </x-data-table>
</x-app-layout>
