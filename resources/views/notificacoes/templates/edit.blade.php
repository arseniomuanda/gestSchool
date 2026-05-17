<x-app-layout>
    <x-page-header :title="__('Edit template') . ' — ' . $template->event_key" :subtitle="$template->channel . ' · ' . $template->locale">
        <x-slot name="actions">
            <x-btn :href="route('notificacoes.templates.index')" variant="secondary" icon="arrow-left">{{ __('Back') }}</x-btn>
        </x-slot>
    </x-page-header>

    <x-card>
        <form method="POST" action="{{ route('notificacoes.templates.update', $template) }}" class="space-y-4">
            @csrf
            @method('PUT')

            @if($template->channel === 'email')
                <div>
                    <label class="form-label">{{ __('Subject') }}</label>
                    <input type="text" name="subject" value="{{ old('subject', $template->subject) }}" class="form-input">
                </div>
            @endif

            <div>
                <label class="form-label">{{ __('Body') }}</label>
                <textarea name="body" rows="10" class="form-input font-mono text-sm">{{ old('body', $template->body) }}</textarea>
                <div class="text-xs text-muted mt-2">
                    {{ __('Available placeholders:') }}
                    <code>{{ '{{nome_destinatario}}' }}</code>,
                    <code>{{ '{{titulo}}' }}</code>,
                    <code>{{ '{{mensagem}}' }}</code>,
                    <code>{{ '{{aluno}}' }}</code>,
                    <code>{{ '{{trimestre}}' }}</code>,
                    <code>{{ '{{faltas}}' }}</code>
                </div>
            </div>

            <div>
                <label class="form-label inline-flex items-center gap-2">
                    <input type="checkbox" name="active" value="1" @checked($template->active) class="rounded">
                    {{ __('Active') }}
                </label>
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                <x-btn type="submit" variant="primary" icon="save">{{ __('Save') }}</x-btn>
            </div>
        </form>
    </x-card>
</x-app-layout>
