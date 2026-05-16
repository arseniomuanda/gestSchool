@php($aluno = $aluno ?? null)
@php($u = $aluno?->user)
@php($initialEncarregados = collect(old('encarregados', $aluno?->encarregados?->map(fn($e) => [
    'id' => $e->id,
    'name' => $e->user?->name,
    'email' => $e->user?->email,
    'bi' => $e->bi,
    'parentesco' => $e->pivot->parentesco ?? 'outro',
    'principal' => (bool) $e->pivot->principal,
])->values()->all() ?? []))->values()->all())
@php($parentescoOptions = [
    ['value' => 'pai', 'label' => __('Father')],
    ['value' => 'mae', 'label' => __('Mother')],
    ['value' => 'tutor', 'label' => __('Tutor')],
    ['value' => 'irmao', 'label' => __('Sibling')],
    ['value' => 'outro', 'label' => __('Other')],
])

<div class="card-section">
    <h4 class="card-title">{{ __('Personal Data') }}</h4>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-input name="name" :label="__('Name')" :value="$u?->name" required />
        <x-input name="numero_processo" :label="__('Process Number')" :value="$aluno?->numero_processo" required />
        <x-input name="email" :label="__('Email')" type="email" :value="$u?->email" />
        <x-input name="phone" :label="__('Phone')" :value="$u?->phone" />
        <x-input name="bi" :label="__('BI Number')" :value="$aluno?->bi" />
        <x-input name="data_nascimento" :label="__('Birth Date')" type="date" :value="$aluno?->data_nascimento?->format('Y-m-d')" />
        <x-select name="sexo" :label="__('Gender')">
            <option value="M" @selected(old('sexo', $aluno?->sexo) === 'M')>{{ __('Male') }}</option>
            <option value="F" @selected(old('sexo', $aluno?->sexo) === 'F')>{{ __('Female') }}</option>
        </x-select>
        <x-input name="nacionalidade" label="{{ __('Nationality') }}" :value="$aluno?->nacionalidade ?? 'Angolana'" />
        <x-input name="naturalidade" label="{{ __('Place of Birth') }}" :value="$aluno?->naturalidade" />
        <div class="sm:col-span-2"><x-textarea name="morada" :label="__('Address')" :value="$aluno?->morada" :rows="2" /></div>
    </div>
</div>

<div class="card-section">
    <h4 class="card-title">{{ __('Academic Data') }}</h4>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-input name="classe" :label="__('Grade')" :value="$aluno?->classe" />
        <x-input name="turma" :label="__('Class')" :value="$aluno?->turma" />
        <x-input name="ano_lectivo" :label="__('School Year')" :value="$aluno?->ano_lectivo" placeholder="2026/2027" />
        <div class="sm:col-span-3"><x-textarea name="observacoes" label="{{ __('Observations') }}" :value="$aluno?->observacoes" :rows="2" /></div>
    </div>
</div>

<div class="card-section">
    <div
        class="enc-picker"
        x-data="encarregadosPicker({
            initial: @js($initialEncarregados),
            parentescoOptions: @js($parentescoOptions),
            searchUrl: @js(route('encarregados.search')),
            labels: @js([
                'remove' => __('Remove'),
                'principal' => __('Principal guardian'),
                'mark_principal' => __('Mark as principal'),
            ]),
        })"
    >
        <div class="enc-picker-header">
            <div>
                <h4 class="card-title mb-0">{{ __('Guardians of this student') }}</h4>
                <p class="enc-picker-header-count">
                    <span x-text="selected.length"></span>
                    <span x-show="selected.length === 1">{{ __('guardian selected') }}</span>
                    <span x-show="selected.length !== 1">{{ __('guardians selected') }}</span>
                    <span class="mx-1.5 text-gray-300">·</span>
                    <span class="text-muted">{{ __('Search and click to add') }}</span>
                </p>
            </div>
            <a href="{{ route('encarregados.create') }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">
                <x-lucide-plus class="w-4 h-4" />
                <span>{{ __('New guardian') }}</span>
            </a>
        </div>

        {{-- Search --}}
        <div class="enc-picker-search" @click.outside="closeDropdown()">
            <div class="enc-picker-input-wrap">
                <x-lucide-search class="w-4 h-4 text-muted shrink-0" />
                <input
                    type="text"
                    x-model="query"
                    @input="onSearch()"
                    @focus="openDropdown()"
                    @keydown.escape.prevent="closeDropdown()"
                    placeholder="{{ __('Search guardian by name, BI or profession…') }}"
                    class="enc-picker-input"
                    role="combobox"
                    aria-autocomplete="list"
                    :aria-expanded="dropdown.toString()"
                    autocomplete="off"
                    autocorrect="off"
                    autocapitalize="off"
                    spellcheck="false"
                    name="enc_search"
                    data-1p-ignore
                    data-lpignore="true"
                    data-form-type="other"
                />
                <span x-show="loading" class="text-xs text-muted" x-cloak>…</span>
            </div>

            {{-- Dropdown --}}
            <div
                x-show="dropdown"
                x-cloak
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="enc-picker-dropdown"
            >
                <div class="enc-picker-dropdown-list">
                    <template x-if="!loading && results.length === 0">
                        <div class="enc-picker-empty">
                            <x-lucide-user-search class="w-8 h-8 mx-auto text-gray-200 mb-2" />
                            <p class="text-sm text-muted" x-show="query.trim().length < 2">{{ __('Type at least 2 characters or browse the list.') }}</p>
                            <p class="text-sm text-muted" x-show="query.trim().length >= 2">
                                {{ __('No guardians match') }} "<span class="text-navy font-semibold" x-text="query"></span>"
                            </p>
                        </div>
                    </template>
                    <template x-for="r in results" :key="r.id">
                        <button
                            type="button"
                            @click="add(r)"
                            :disabled="isSelected(r.id)"
                            class="enc-picker-option"
                            :class="isSelected(r.id) ? 'is-selected' : ''"
                        >
                            <div class="enc-picker-avatar" x-text="initials(r.name)"></div>
                            <div class="enc-picker-option-info">
                                <div class="enc-picker-option-name" x-text="r.name"></div>
                                <div class="enc-picker-option-meta">
                                    <span x-text="r.email || '—'"></span>
                                    <template x-if="r.bi"><span> · BI <span x-text="r.bi"></span></span></template>
                                    <template x-if="r.profissao"><span> · <span x-text="r.profissao"></span></span></template>
                                </div>
                            </div>
                            <template x-if="isSelected(r.id)">
                                <x-lucide-check class="w-4 h-4 enc-picker-option-action" />
                            </template>
                            <template x-if="!isSelected(r.id)">
                                <x-lucide-plus class="w-4 h-4 enc-picker-option-action" />
                            </template>
                        </button>
                    </template>
                </div>
                <div class="enc-picker-footer" x-show="!loading">
                    <span class="text-xs text-muted">
                        <span x-text="results.length"></span>
                        <span x-show="results.length === 1">{{ __('match') }}</span>
                        <span x-show="results.length !== 1">{{ __('matches') }}</span>
                    </span>
                    <a href="{{ route('encarregados.create') }}" target="_blank" rel="noopener" class="text-xs text-primary font-semibold hover:text-primary-600">
                        + {{ __('Create new guardian') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Selected list --}}
        <div class="enc-list" x-show="selected.length > 0" x-cloak>
            <template x-for="(enc, i) in selected" :key="enc.id">
                <div
                    class="enc-card"
                    :class="enc.principal ? 'is-principal' : ''"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                >
                    <input type="hidden" :name="`encarregados[${i}][id]`" :value="enc.id">
                    <input type="hidden" :name="`encarregados[${i}][parentesco]`" :value="enc.parentesco">
                    <input type="hidden" :name="`encarregados[${i}][principal]`" :value="enc.principal ? '1' : ''">

                    <button
                        type="button"
                        class="enc-card-star"
                        @click="togglePrincipal(enc.id)"
                        :title="enc.principal ? labels.principal : labels.mark_principal"
                    >
                        <x-lucide-star class="w-5 h-5" />
                    </button>

                    <div class="enc-card-body">
                        <div class="enc-card-header">
                            <div class="min-w-0">
                                <span class="enc-card-name" x-text="enc.name"></span>
                                <span x-show="enc.principal" class="enc-card-badge" x-cloak>{{ __('Principal') }}</span>
                            </div>
                            <button type="button" class="enc-card-remove" @click="remove(enc.id)" :title="labels.remove">
                                <x-lucide-x class="w-4 h-4" />
                            </button>
                        </div>
                        <div class="enc-card-meta">
                            <span x-text="enc.email || '—'"></span>
                            <template x-if="enc.bi"><span> · BI <span x-text="enc.bi"></span></span></template>
                        </div>

                        <div class="enc-pill-group" role="radiogroup">
                            <template x-for="opt in parentescoOptions" :key="opt.value">
                                <button
                                    type="button"
                                    class="enc-pill"
                                    :class="enc.parentesco === opt.value ? 'is-active' : ''"
                                    @click="setParentesco(enc.id, opt.value)"
                                    x-text="opt.label"
                                ></button>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Empty state --}}
        <div class="enc-empty" x-show="selected.length === 0" x-cloak>
            <x-lucide-users class="w-9 h-9 text-gray-200 mx-auto mb-2" />
            <p class="text-sm text-muted font-medium">{{ __('No guardians selected for this student.') }}</p>
            <p class="text-xs text-body mt-1">{{ __('Use the search above to add one.') }}</p>
        </div>
    </div>
</div>

<div class="card-section">
    <h4 class="card-title">{{ __('Access (optional)') }}</h4>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-input name="password" :label="__('Password')" type="password" />
        <x-input name="password_confirmation" :label="__('Confirm Password')" type="password" />
    </div>
</div>

<div class="flex items-center gap-3 mt-6">
    <x-btn variant="primary" type="submit">{{ __('Save') }}</x-btn>
    <x-btn variant="secondary" :href="route('alunos.index')">{{ __('Cancel') }}</x-btn>
</div>
