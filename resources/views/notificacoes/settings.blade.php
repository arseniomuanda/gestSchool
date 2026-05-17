<x-app-layout>
    <x-page-header :title="__('Notification settings')" :subtitle="__('Credentials are read from .env. To change them, edit the file on the server and restart php-fpm.')">
        <x-slot name="actions">
            <x-btn :href="route('notificacoes.index')" variant="secondary" icon="arrow-left">{{ __('Back') }}</x-btn>
        </x-slot>
    </x-page-header>

    <div x-data="{ tab: 'email' }" class="space-y-4">
        <div class="border-b border-slate-200 flex gap-1">
            <button type="button" @click="tab = 'email'"
                :class="tab === 'email' ? 'border-primary text-primary' : 'border-transparent text-muted hover:text-navy'"
                class="px-4 py-2 border-b-2 text-sm font-medium transition">{{ __('Email (SMTP)') }}</button>
            <button type="button" @click="tab = 'sms'"
                :class="tab === 'sms' ? 'border-primary text-primary' : 'border-transparent text-muted hover:text-navy'"
                class="px-4 py-2 border-b-2 text-sm font-medium transition">{{ __('SMS (Ombala)') }}</button>
        </div>

        {{-- Tab Email --}}
        <div x-show="tab === 'email'" x-cloak>
            <x-card>
                <div class="mb-4 p-3 bg-sky-50 border-l-4 border-sky-400 rounded text-sm text-sky-900">
                    {{ __('These values come from the .env file (MAIL_*). Edit there to change them.') }}
                </div>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-muted">{{ __('Mailer') }}</dt>
                        <dd class="font-mono text-navy">{{ $email['mailer'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-muted">{{ __('SMTP host') }}</dt>
                        <dd class="font-mono text-navy">{{ $email['smtp_host'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-muted">{{ __('SMTP port') }}</dt>
                        <dd class="font-mono text-navy">{{ $email['smtp_port'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-muted">{{ __('Sender email') }}</dt>
                        <dd class="font-mono text-navy">{{ $email['from_address'] ?? '—' }}</dd>
                    </div>
                    <div class="md:col-span-2">
                        <dt class="text-xs uppercase tracking-wide text-muted">{{ __('Sender name') }}</dt>
                        <dd class="font-mono text-navy">{{ $email['from_name'] ?? '—' }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card class="mt-4">
                <h3 class="text-sm font-semibold text-navy mb-2">{{ __('Send test email') }}</h3>
                <form method="POST" action="{{ route('notificacoes.settings.email.test') }}" class="flex gap-2 items-end">
                    @csrf
                    <div class="flex-1">
                        <label class="form-label">{{ __('Recipient') }}</label>
                        <input type="email" name="email" value="{{ auth()->user()->email }}" required class="form-input">
                    </div>
                    <x-btn type="submit" variant="secondary" icon="send">{{ __('Send test') }}</x-btn>
                </form>
            </x-card>
        </div>

        {{-- Tab SMS --}}
        <div x-show="tab === 'sms'" x-cloak>
            <x-card>
                <div class="mb-4 p-3 bg-amber-50 border-l-4 border-amber-400 rounded text-sm text-amber-800">
                    {{ __('SMS channel is in stub mode until the Ombala API spec is integrated. Configure OMBALA_* in .env now to be ready.') }}
                </div>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-muted">{{ __('Enabled') }}</dt>
                        <dd>
                            <x-badge :variant="$sms['enabled'] ? 'success' : 'muted'">
                                {{ $sms['enabled'] ? __('Yes') : __('No') }}
                            </x-badge>
                            <span class="ml-2 font-mono text-xs text-muted">OMBALA_ENABLED</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-muted">{{ __('API key configured') }}</dt>
                        <dd>
                            <x-badge :variant="$sms['has_api_key'] ? 'success' : 'danger'">
                                {{ $sms['has_api_key'] ? __('Yes') : __('No') }}
                            </x-badge>
                            <span class="ml-2 font-mono text-xs text-muted">OMBALA_API_KEY</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-muted">{{ __('API URL') }}</dt>
                        <dd class="font-mono text-navy break-all">{{ $sms['api_url'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-muted">{{ __('Sender ID') }}</dt>
                        <dd class="font-mono text-navy">{{ $sms['sender_id'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-muted">{{ __('Current balance') }}</dt>
                        <dd class="font-mono text-navy">
                            @if($sms['saldo'] !== null)
                                {{ number_format($sms['saldo'], 0, ',', ' ') }} SMS
                            @else
                                <span class="text-muted">— ({{ __('not available') }})</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-muted">{{ __('Approved senders') }}</dt>
                        <dd>
                            @if(count($sms['senders_aprovados']))
                                <div class="flex flex-wrap gap-1">
                                    @foreach($sms['senders_aprovados'] as $s)
                                        <x-badge variant="muted">{{ $s }}</x-badge>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-card>
        </div>
    </div>

</x-app-layout>
