@php
    $matricula = $matricula ?? null;
@endphp
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <x-combobox
        name="aluno_id"
        :label="__('Student')"
        required
        :placeholder="__('Search student by name or process number')"
        :selected="old('aluno_id', $matricula?->aluno_id)"
        :options="collect($alunos)->map(fn($a) => [
            'value' => $a->id,
            'label' => $a->user->name,
            'hint'  => $a->numero_processo,
        ])"
    />

    <x-select name="ano_lectivo_id" :label="__('School Year')" required>
        @foreach($anos as $a)
            <option value="{{ $a->id }}" @selected(old('ano_lectivo_id', $matricula?->ano_lectivo_id) == $a->id)>
                {{ $a->codigo }}
            </option>
        @endforeach
    </x-select>

    <x-combobox
        name="turma_id"
        :label="__('Class Groups')"
        required
        :placeholder="__('Choose class group')"
        :selected="old('turma_id', $matricula?->turma_id)"
        :options="collect($turmas)->map(fn($t) => [
            'value' => $t->id,
            'label' => $t->classe->nome . ' ' . $t->nome,
            'hint'  => $t->anoLectivo->codigo . ($t->curso ? ' · ' . $t->curso->sigla : ''),
        ])"
    />

    <x-input name="numero_matricula" :label="__('Enrollment Number')" :value="$matricula?->numero_matricula" required />
    <x-input name="data_matricula" :label="__('Enrollment Date')" type="date" :value="$matricula?->data_matricula?->format('Y-m-d') ?? now()->toDateString()" required />

    <x-select name="estado" :label="__('Status')" required :placeholder="null">
        @foreach(['activa', 'transferido', 'desistente', 'aprovado', 'reprovado'] as $e)
            <option value="{{ $e }}" @selected(old('estado', $matricula?->estado ?? 'activa') === $e)>
                {{ __($e) }}
            </option>
        @endforeach
    </x-select>

    <div class="sm:col-span-2">
        <x-textarea name="observacoes" label="{{ __('Observations') }}" :value="$matricula?->observacoes" :rows="2" />
    </div>
</div>

{{-- Consentimento LPD (Lei 22/11) — obrigatório em criação. Em edição,
     mostra o estado actual e permite renovar se a versão da política mudou. --}}
@php
    $versaoActual = config('legal.lpd_versao');
    $jaConsentiu = $matricula?->consentimento_lpd_em !== null;
    $versaoConsentida = $matricula?->consentimento_lpd_versao;
    $consentimentoDesfasado = $jaConsentiu && $versaoConsentida !== $versaoActual;
@endphp
<div class="mt-6 p-4 rounded border {{ $consentimentoDesfasado ? 'bg-amber-50 border-amber-200' : 'bg-blue-50 border-blue-200' }}">
    <h4 class="text-sm font-semibold text-navy mb-2">{{ __('Personal data consent (Law 22/11)') }}</h4>

    @if($jaConsentiu && ! $consentimentoDesfasado)
        <p class="text-xs text-muted mb-3">
            <x-lucide-check-circle class="w-4 h-4 inline text-green-600" />
            {{ __('Consent given on') }} {{ $matricula->consentimento_lpd_em->format('Y-m-d H:i') }}
            ({{ __('Version') }} {{ $versaoConsentida }}).
        </p>
    @elseif($consentimentoDesfasado)
        <p class="text-xs text-amber-800 mb-3">
            <x-lucide-alert-triangle class="w-4 h-4 inline" />
            {{ __('Policy has changed since the last consent (was') }} {{ $versaoConsentida }}, {{ __('now') }} {{ $versaoActual }}). {{ __('Please confirm again.') }}
        </p>
    @endif

    @php
        $labelConsentimento = __('I have read and consent to the processing of personal data under the Privacy Policy.') . ' (' . $versaoActual . ')';
    @endphp
    <x-checkbox
        name="consentimento_lpd"
        :checked="old('consentimento_lpd', $jaConsentiu && ! $consentimentoDesfasado)"
        :label="$labelConsentimento"
        required
    />
    <p class="text-xs text-muted mt-2">
        <a href="{{ route('legal.privacidade') }}" target="_blank" rel="noopener" class="text-primary hover:underline inline-flex items-center gap-1">
            <x-lucide-external-link class="w-3 h-3" />
            {{ __('Read the Privacy Policy') }}
        </a>
    </p>
</div>

<div class="flex items-center gap-3 mt-6">
    <x-btn variant="primary" type="submit">{{ __('Save') }}</x-btn>
    <x-btn variant="secondary" :href="route('matriculas.index')">{{ __('Cancel') }}</x-btn>
</div>
