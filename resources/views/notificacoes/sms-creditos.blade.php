<x-app-layout>
    <x-page-header :title="__('SMS credits')" :subtitle="__('Track balance and request more credits.')">
        <x-slot name="actions">
            <x-btn :href="route('notificacoes.index')" variant="secondary" icon="arrow-left">{{ __('Back') }}</x-btn>
        </x-slot>
    </x-page-header>


    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <x-card>
            <div class="text-xs text-muted uppercase tracking-wide">{{ __('Current balance') }}</div>
            @if($saldoAtual !== null)
                <div class="text-3xl font-bold text-navy mt-2">{{ number_format($saldoAtual, 0, ',', ' ') }}</div>
                <div class="text-xs text-muted mt-1">{{ __('SMS available (Ombala)') }}</div>
            @else
                <div class="text-3xl font-bold text-muted mt-2">—</div>
                <div class="text-xs text-muted mt-1">{{ __('Balance not available — check OMBALA_API_KEY in .env.') }}</div>
            @endif
        </x-card>

        <x-card class="md:col-span-2">
            <h3 class="text-base font-semibold text-navy mb-2">{{ __('Request more credits') }}</h3>
            <form method="POST" action="{{ route('notificacoes.sms-creditos.store') }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="form-label">{{ __('Quantity') }}</label>
                        <input type="number" name="quantity_requested" min="1" max="1000000" required class="form-input" placeholder="5000">
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label">{{ __('Notes (optional)') }}</label>
                        <input type="text" name="notes" class="form-input" maxlength="1000">
                    </div>
                </div>
                <div class="flex justify-end">
                    <x-btn type="submit" variant="primary" icon="plus">{{ __('Submit request') }}</x-btn>
                </div>
            </form>
        </x-card>
    </div>

    <x-card>
        <h3 class="text-base font-semibold text-navy mb-3">{{ __('Request history') }}</h3>
        @if($requests->isEmpty())
            <x-empty title="{{ __('No records') }}" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-muted">
                        <tr>
                            <th class="px-3 py-2">{{ __('When') }}</th>
                            <th class="px-3 py-2">{{ __('Requested by') }}</th>
                            <th class="px-3 py-2">{{ __('Quantity') }}</th>
                            <th class="px-3 py-2">{{ __('Status') }}</th>
                            <th class="px-3 py-2">{{ __('Notes') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($requests as $r)
                            <tr>
                                <td class="px-3 py-2 whitespace-nowrap">{{ $r->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-3 py-2">{{ $r->requestedBy?->name }}</td>
                                <td class="px-3 py-2 font-mono">{{ number_format($r->quantity_requested, 0, ',', ' ') }}</td>
                                <td class="px-3 py-2"><x-badge :variant="match($r->status){'aprovado'=>'success','rejeitado'=>'danger','enviado'=>'info',default=>'muted'}">{{ __($r->status) }}</x-badge></td>
                                <td class="px-3 py-2">{{ $r->notes }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $requests->links() }}</div>
        @endif
    </x-card>
</x-app-layout>
