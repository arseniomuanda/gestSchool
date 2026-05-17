<x-app-layout>
    <x-page-header :title="__('Notifications history')" :subtitle="__('Audit log of all sent notifications.')">
        <x-slot name="actions">
            <x-btn :href="route('notificacoes.index')" variant="secondary" icon="arrow-left">{{ __('Back') }}</x-btn>
        </x-slot>
    </x-page-header>

    <x-card>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
            <select name="channel" class="form-input">
                <option value="">{{ __('All channels') }}</option>
                <option value="email" @selected(request('channel') === 'email')>{{ __('Email') }}</option>
                <option value="sms" @selected(request('channel') === 'sms')>SMS</option>
            </select>
            <select name="status" class="form-input">
                <option value="">{{ __('All statuses') }}</option>
                <option value="sent" @selected(request('status') === 'sent')>{{ __('Sent') }}</option>
                <option value="failed" @selected(request('status') === 'failed')>{{ __('Failed') }}</option>
                <option value="queued" @selected(request('status') === 'queued')>{{ __('Queued') }}</option>
            </select>
            <input type="text" name="event" value="{{ request('event') }}" placeholder="{{ __('Event key') }}" class="form-input">
            <x-btn type="submit" variant="primary" icon="filter">{{ __('Filter') }}</x-btn>
        </form>

        @if($notifications->isEmpty())
            <x-empty title="{{ __('No records') }}" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-muted">
                        <tr>
                            <th class="px-3 py-2">{{ __('When') }}</th>
                            <th class="px-3 py-2">{{ __('Recipient') }}</th>
                            <th class="px-3 py-2">{{ __('Channel') }}</th>
                            <th class="px-3 py-2">{{ __('Event') }}</th>
                            <th class="px-3 py-2">{{ __('Status') }}</th>
                            <th class="px-3 py-2">{{ __('Subject') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($notifications as $n)
                            <tr>
                                <td class="px-3 py-2 whitespace-nowrap">{{ $n->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-3 py-2">{{ $n->recipient?->name ?? '—' }}<br><span class="text-xs text-muted">{{ $n->recipient_address }}</span></td>
                                <td class="px-3 py-2"><x-badge variant="muted">{{ $n->channel }}</x-badge></td>
                                <td class="px-3 py-2 font-mono text-xs">{{ $n->event_key }}</td>
                                <td class="px-3 py-2">
                                    <x-badge :variant="$n->status === 'sent' ? 'success' : ($n->status === 'failed' ? 'danger' : 'muted')">{{ __($n->status) }}</x-badge>
                                    @if($n->error)
                                        <div class="text-xs text-danger mt-1" title="{{ $n->error }}">{{ \Illuminate\Support\Str::limit($n->error, 60) }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-2">{{ \Illuminate\Support\Str::limit($n->subject ?? '', 50) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $notifications->links() }}</div>
        @endif
    </x-card>
</x-app-layout>
