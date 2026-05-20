@php($evento = $evento ?? null)
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div class="sm:col-span-2">
        <x-input name="titulo" :label="__('Title')" :value="$evento?->titulo" required />
    </div>
    <x-select name="ano_lectivo_id" :label="__('School Year')" required :placeholder="null">
        @foreach($anos as $a)<option value="{{ $a->id }}" @selected(old('ano_lectivo_id', $evento?->ano_lectivo_id ?? $anos->firstWhere('activo', true)?->id) == $a->id)>{{ $a->codigo }}</option>@endforeach
    </x-select>
    <x-select name="tipo" :label="__('Type')" required :placeholder="null">
        @foreach($tipos as $key => $info)
            <option value="{{ $key }}" @selected(old('tipo', $evento?->tipo ?? 'evento') === $key)>{{ $info['nome'] }}</option>
        @endforeach
    </x-select>
    <x-input name="data_inicio" :label="__('Start')" type="date" :value="$evento?->data_inicio?->format('Y-m-d')" required />
    <x-input name="data_fim" :label="__('End')" type="date" :value="$evento?->data_fim?->format('Y-m-d')" help="{{ __('Leave empty for a single-day event.') }}" />
    <x-input name="hora_inicio" :label="__('Start time')" type="time" :value="$evento?->hora_inicio" />
    <x-input name="hora_fim" :label="__('End time')" type="time" :value="$evento?->hora_fim" />
    <div class="flex items-end form-group">
        <x-checkbox name="dia_inteiro" :label="__('All day')" :checked="old('dia_inteiro', $evento?->dia_inteiro ?? true)" />
    </div>
    <div>
        <x-input name="cor" :label="__('Color (optional)')" :value="$evento?->cor" placeholder="#0f4d3a" help="{{ __('Override default color of the event type.') }}" />
    </div>
    <x-select name="classe_id" :label="__('Class (optional)')" help="{{ __('Limit visibility to a class level.') }}">
        @foreach($classes as $c)<option value="{{ $c->id }}" @selected(old('classe_id', $evento?->classe_id) == $c->id)>{{ $c->nome }}</option>@endforeach
    </x-select>
    <x-select name="turma_id" :label="__('Class Group (optional)')" help="{{ __('Limit visibility to a single class group.') }}">
        @foreach($turmas as $t)<option value="{{ $t->id }}" @selected(old('turma_id', $evento?->turma_id) == $t->id)>{{ $t->display_label }}</option>@endforeach
    </x-select>
    <div class="sm:col-span-2">
        <x-textarea name="descricao" :label="__('Description')" :value="$evento?->descricao" :rows="3" />
    </div>
</div>
<div class="flex items-center gap-3 mt-6">
    <x-btn variant="primary" type="submit">{{ __('Save') }}</x-btn>
    <x-btn variant="secondary" :href="route('eventos.index')">{{ __('Cancel') }}</x-btn>
</div>
