<x-app-layout>
    <x-page-header :title="__('Notifications')" :subtitle="__('Configure email & SMS, manage templates and view sent history.')" />

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <x-stat-card icon="send" :value="$stats['total']" :label="__('Total sent')" />
        <x-stat-card icon="check-circle" :value="$stats['sent']" :label="__('Successful')" />
        <x-stat-card icon="alert-circle" :value="$stats['failed']" :label="__('Failed')" />
        <x-stat-card icon="clock" :value="$stats['last24h']" :label="__('Last 24h')" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-card>
            <h3 class="text-base font-semibold text-navy mb-2">{{ __('Settings') }}</h3>
            <p class="text-sm text-muted mb-4">{{ __('SMTP credentials, sender, SMS API and template configuration.') }}</p>
            <div class="flex gap-2 flex-wrap">
                <x-btn :href="route('notificacoes.settings')" variant="primary" icon="settings">{{ __('Email & SMS') }}</x-btn>
                <x-btn :href="route('notificacoes.templates.index')" variant="secondary" icon="file-text">{{ __('Templates') }}</x-btn>
            </div>
        </x-card>

        <x-card>
            <h3 class="text-base font-semibold text-navy mb-2">{{ __('SMS credits') }}</h3>
            <p class="text-sm text-muted mb-4">{{ __('View balance and request more credits from Ombala.') }}</p>
            <div class="flex gap-2 flex-wrap">
                <x-btn :href="route('notificacoes.sms-creditos')" variant="primary" icon="credit-card">{{ __('Manage credits') }}</x-btn>
                <x-btn :href="route('notificacoes.historico')" variant="secondary" icon="list">{{ __('History') }}</x-btn>
            </div>
        </x-card>
    </div>

</x-app-layout>
