<x-app-layout>
    <x-page-header :title="__('Notification templates')" :subtitle="__('Edit subject and body for each event × channel.')">
        <x-slot name="actions">
            <x-btn :href="route('notificacoes.index')" variant="secondary" icon="arrow-left">{{ __('Back') }}</x-btn>
        </x-slot>
    </x-page-header>

    <x-card>
        @if($templates->isEmpty())
            <x-empty title="{{ __('No templates yet — run seeder.') }}" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-muted">
                        <tr>
                            <th class="px-3 py-2">{{ __('Event') }}</th>
                            <th class="px-3 py-2">{{ __('Channel') }}</th>
                            <th class="px-3 py-2">{{ __('Locale') }}</th>
                            <th class="px-3 py-2">{{ __('Subject') }}</th>
                            <th class="px-3 py-2">{{ __('Active') }}</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($templates as $t)
                            <tr>
                                <td class="px-3 py-2 font-mono text-xs">{{ $t->event_key }}</td>
                                <td class="px-3 py-2"><x-badge variant="muted">{{ $t->channel }}</x-badge></td>
                                <td class="px-3 py-2">{{ $t->locale }}</td>
                                <td class="px-3 py-2">{{ \Illuminate\Support\Str::limit($t->subject ?? '—', 60) }}</td>
                                <td class="px-3 py-2"><x-badge :variant="$t->active ? 'success' : 'muted'">{{ $t->active ? __('Yes') : __('No') }}</x-badge></td>
                                <td class="px-3 py-2 text-right">
                                    <x-btn :href="route('notificacoes.templates.edit', $t)" variant="secondary" icon="edit">{{ __('Edit') }}</x-btn>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-app-layout>
