{{-- Painel Diagnóstico (Fases 4.1 + 4.2). Reutilizado por bulk-turma e bulk-professor.
     Tem que estar DENTRO do x-data="horarioEditor(...)" para ter acesso aos getters. --}}
@php
    $diasSemanaLocal = $diasSemana ?? \App\Models\Horario::diasSemana();
@endphp
<div class="mt-6 pt-6 border-t border-gray-100" x-data="{ openDetails: false }">
    <h4 class="form-label text-xs uppercase tracking-wide mb-3">{{ __('Diagnostic') }}</h4>

    {{-- 4.1: 4 cards de lacunas --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
        <div class="p-3 rounded border" x-bind:class="furos.length > 0 ? 'bg-red-50 border-red-200 text-red-800' : 'bg-green-50 border-green-200 text-green-800'">
            <div class="text-xs uppercase tracking-wide opacity-70">{{ __('Gaps') }}</div>
            <div class="text-2xl font-bold" x-text="furos.length"></div>
            <div class="text-[11px] mt-1 opacity-70">{{ __('Empty slots between two filled ones same day') }}</div>
        </div>
        <div class="p-3 rounded border" x-bind:class="naoEscaladas.length > 0 ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-green-50 border-green-200 text-green-800'">
            <div class="text-xs uppercase tracking-wide opacity-70">{{ __('Unscheduled') }}</div>
            <div class="text-2xl font-bold" x-text="naoEscaladas.length"></div>
            <div class="text-[11px] mt-1 opacity-70">{{ __('Assignments with zero periods') }}</div>
        </div>
        <div class="p-3 rounded border" x-bind:class="cargaIssues.falta.length > 0 ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-green-50 border-green-200 text-green-800'">
            <div class="text-xs uppercase tracking-wide opacity-70">{{ __('Missing periods') }}</div>
            <div class="text-2xl font-bold" x-text="cargaIssues.falta.length"></div>
            <div class="text-[11px] mt-1 opacity-70">{{ __('Subjects below weekly load') }}</div>
        </div>
        <div class="p-3 rounded border" x-bind:class="cargaIssues.excesso.length > 0 ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-green-50 border-green-200 text-green-800'">
            <div class="text-xs uppercase tracking-wide opacity-70">{{ __('Excess periods') }}</div>
            <div class="text-2xl font-bold" x-text="cargaIssues.excesso.length"></div>
            <div class="text-[11px] mt-1 opacity-70">{{ __('Subjects above weekly load') }}</div>
        </div>
    </div>

    {{-- 4.2: 3 cards de distribuição --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm mt-3">
        <div class="p-3 rounded border" x-bind:class="concentracaoDiaria.length > 0 ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-green-50 border-green-200 text-green-800'">
            <div class="text-xs uppercase tracking-wide opacity-70">{{ __('Daily concentration') }}</div>
            <div class="text-2xl font-bold" x-text="concentracaoDiaria.length"></div>
            <div class="text-[11px] mt-1 opacity-70">{{ __('Heavy subjects packed into one day') }}</div>
        </div>
        <div class="p-3 rounded border" x-bind:class="tempasConsecutivos.length > 0 ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-green-50 border-green-200 text-green-800'">
            <div class="text-xs uppercase tracking-wide opacity-70">{{ __('Consecutive periods') }}</div>
            <div class="text-2xl font-bold" x-text="tempasConsecutivos.length"></div>
            <div class="text-[11px] mt-1 opacity-70">{{ __('Teachers with too many periods in a row') }}</div>
        </div>
        <div class="p-3 rounded border" x-bind:class="horasMas.length > 0 ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-green-50 border-green-200 text-green-800'">
            <div class="text-xs uppercase tracking-wide opacity-70">{{ __('Difficult hours') }}</div>
            <div class="text-2xl font-bold" x-text="horasMas.length"></div>
            <div class="text-[11px] mt-1 opacity-70">{{ __('Heavy subjects in late slots') }}</div>
        </div>
    </div>

    {{-- Detalhes colapsáveis --}}
    <button type="button" class="btn-link btn-link-muted text-xs mt-3" @click="openDetails = !openDetails"
            x-show="furos.length + naoEscaladas.length + cargaIssues.falta.length + cargaIssues.excesso.length + concentracaoDiaria.length + tempasConsecutivos.length + horasMas.length > 0">
        <span x-show="!openDetails">{{ __('Show details') }}</span>
        <span x-show="openDetails">{{ __('Hide details') }}</span>
    </button>
    <div x-show="openDetails" x-cloak class="mt-2 text-xs space-y-3">
        <template x-if="cargaIssues.falta.length > 0">
            <div>
                <div class="font-semibold text-amber-800 mb-1">{{ __('Missing periods') }}</div>
                <ul class="list-disc list-inside space-y-0.5">
                    <template x-for="item in cargaIssues.falta" :key="'f-'+item.atrId">
                        <li>
                            <span x-text="atrPayload[item.atrId]?.disciplina_full"></span>
                            (<span x-text="item.actual"></span>/<span x-text="item.esperada"></span>)
                        </li>
                    </template>
                </ul>
            </div>
        </template>
        <template x-if="cargaIssues.excesso.length > 0">
            <div>
                <div class="font-semibold text-amber-800 mb-1">{{ __('Excess periods') }}</div>
                <ul class="list-disc list-inside space-y-0.5">
                    <template x-for="item in cargaIssues.excesso" :key="'e-'+item.atrId">
                        <li>
                            <span x-text="atrPayload[item.atrId]?.disciplina_full"></span>
                            (<span x-text="item.actual"></span>/<span x-text="item.esperada"></span>)
                        </li>
                    </template>
                </ul>
            </div>
        </template>
        <template x-if="naoEscaladas.length > 0">
            <div>
                <div class="font-semibold text-amber-800 mb-1">{{ __('Unscheduled') }}</div>
                <ul class="list-disc list-inside space-y-0.5">
                    <template x-for="id in naoEscaladas" :key="'n-'+id">
                        <li><span x-text="atrPayload[id]?.disciplina_full + (atrPayload[id]?.professor ? ' · ' + atrPayload[id].professor : '') + (atrPayload[id]?.turma_label ? ' [' + atrPayload[id].turma_label + ']' : '')"></span></li>
                    </template>
                </ul>
            </div>
        </template>
        <template x-if="concentracaoDiaria.length > 0">
            <div>
                <div class="font-semibold text-amber-800 mb-1">{{ __('Daily concentration') }}</div>
                <ul class="list-disc list-inside space-y-0.5">
                    <template x-for="item in concentracaoDiaria" :key="'c-'+item.atrId+'-'+item.dia">
                        <li>
                            <span x-text="item.disciplina"></span>:
                            <span x-text="item.dia_count + '/' + item.total"></span>
                            {{ __('periods on') }}
                            <span x-text="{{ \Illuminate\Support\Js::from($diasSemanaLocal) }}[item.dia] || ('dia ' + item.dia)"></span>
                        </li>
                    </template>
                </ul>
            </div>
        </template>
        <template x-if="tempasConsecutivos.length > 0">
            <div>
                <div class="font-semibold text-amber-800 mb-1">{{ __('Consecutive periods') }}</div>
                <ul class="list-disc list-inside space-y-0.5">
                    <template x-for="item in tempasConsecutivos" :key="'cc-'+item.professor_id+'-'+item.dia+'-'+item.start">
                        <li>
                            <span x-text="item.professor"></span>:
                            <span x-text="item.run"></span> {{ __('periods in a row on') }}
                            <span x-text="{{ \Illuminate\Support\Js::from($diasSemanaLocal) }}[item.dia] || ('dia ' + item.dia)"></span>
                            (<span x-text="item.start + 'º–' + item.end + 'º'"></span>)
                        </li>
                    </template>
                </ul>
            </div>
        </template>
        <template x-if="horasMas.length > 0">
            <div>
                <div class="font-semibold text-amber-800 mb-1">{{ __('Difficult hours') }}</div>
                <ul class="list-disc list-inside space-y-0.5">
                    <template x-for="item in horasMas" :key="'h-'+item.dia+'-'+item.tempo">
                        <li>
                            <span x-text="item.disciplina"></span>
                            {{ __('at') }}
                            <span x-text="{{ \Illuminate\Support\Js::from($diasSemanaLocal) }}[item.dia] || ('dia ' + item.dia)"></span>
                            <span x-text="item.tempo + 'º'"></span>
                        </li>
                    </template>
                </ul>
            </div>
        </template>
    </div>
</div>
