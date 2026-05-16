@php
    $falta = $falta ?? null;
@endphp
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <x-combobox
        name="professor_id"
        :label="__('Teacher')"
        required
        :placeholder="__('Choose teacher')"
        :selected="old('professor_id', $falta?->professor_id)"
        :options="collect($professores)->map(fn($p) => [
            'value' => $p->id,
            'label' => $p->user->name,
            'hint'  => $p->numero_professor,
        ])"
    />

    <x-input name="data" type="date" :label="__('Date')" :value="old('data', $falta?->data?->format('Y-m-d') ?? now()->toDateString())" required />

    <x-select name="tempo_inicio" :label="__('Start period')" required :placeholder="null">
        @foreach($tempos as $n => [$ini, $fim])
            <option value="{{ $n }}" @selected(old('tempo_inicio', $falta?->tempo_inicio ?? 1) == $n)>{{ $n }}º ({{ $ini }})</option>
        @endforeach
    </x-select>

    <x-select name="tempo_fim" :label="__('End period')" required :placeholder="null">
        @foreach($tempos as $n => [$ini, $fim])
            <option value="{{ $n }}" @selected(old('tempo_fim', $falta?->tempo_fim ?? array_key_last($tempos)) == $n)>{{ $n }}º ({{ $fim }})</option>
        @endforeach
    </x-select>

    <x-select name="tipo" :label="__('Type')" required :placeholder="null">
        <option value="justificada" @selected(old('tipo', $falta?->tipo ?? 'injustificada') === 'justificada')>{{ __('Justified') }}</option>
        <option value="injustificada" @selected(old('tipo', $falta?->tipo ?? 'injustificada') === 'injustificada')>{{ __('Unjustified') }}</option>
        <option value="licenca" @selected(old('tipo', $falta?->tipo) === 'licenca')>{{ __('Leave') }}</option>
    </x-select>

    <x-combobox
        name="substituto_id"
        :label="__('Substitute teacher') . ' (' . __('optional') . ')'"
        :placeholder="__('Choose substitute')"
        :selected="old('substituto_id', $falta?->substituto_id)"
        :options="collect($professores)->map(fn($p) => [
            'value' => $p->id,
            'label' => $p->user->name,
        ])"
    />

    <div class="sm:col-span-2">
        <x-textarea name="motivo" :label="__('Reason')" :value="old('motivo', $falta?->motivo)" :rows="3" />
    </div>

    @if(! $falta || ! $falta->justificacao_em)
        <div class="sm:col-span-2">
            <x-checkbox name="ja_justificada" :checked="old('ja_justificada')" :label="__('Mark as already justified now')" />
        </div>
    @endif
</div>

<div class="flex items-center gap-3 mt-6">
    <x-btn variant="primary" type="submit">{{ __('Save') }}</x-btn>
    <x-btn variant="secondary" :href="route('faltas-professores.index')">{{ __('Cancel') }}</x-btn>
</div>
