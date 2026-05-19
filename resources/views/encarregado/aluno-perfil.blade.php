<x-app-layout>
    @php
        $resumoActivo = $matriculaActiva ? ($resumos[$matriculaActiva->id] ?? null) : null;
        $anosNaEscola = $aluno->matriculas->pluck('ano_lectivo_id')->unique()->count();
        $estadoVariant = match ($matriculaActiva?->estado) {
            'activa' => 'success',
            'aprovado' => 'success',
            'reprovado' => 'danger',
            'transferido' => 'info',
            'desistente' => 'muted',
            default => 'muted',
        };
    @endphp

    <x-page-header :title="$aluno->user->name" :subtitle="__('Process') . ': ' . $aluno->numero_processo">
        <x-slot name="actions">
            @if($matriculaActiva)
                <x-btn variant="primary" icon="file-text" :href="route('boletim.show', $matriculaActiva)">{{ __('Report Card') }}</x-btn>
                <x-btn variant="info" icon="clock" :href="route('horarios.turma', $matriculaActiva->turma)">{{ __('Schedule') }}</x-btn>
                <x-btn variant="dark" icon="printer" :href="route('boletim.pdf', $matriculaActiva)">PDF</x-btn>
            @endif
            <x-btn variant="secondary" icon="arrow-left" :href="route('meus-educandos.index')">{{ __('Back') }}</x-btn>
        </x-slot>
    </x-page-header>

    {{-- ========== KPIs ========== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-stat-card
            :label="__('Current Class')"
            :value="$matriculaActiva ? ($matriculaActiva->turma->classe->nome . ' ' . $matriculaActiva->turma->nome) : '—'"
            icon="layers"
            variant="primary"
            :trend="$matriculaActiva?->turma?->curso?->sigla ?? __('Common Core')" />

        <x-stat-card
            :label="__('Current Average')"
            :value="$resumoActivo && $resumoActivo['media_anual'] !== null ? number_format($resumoActivo['media_anual'], 1) : '—'"
            icon="gauge"
            :variant="($resumoActivo['media_anual'] ?? 0) >= 10 ? 'success' : 'danger'"
            :trend="__('Out of 20')" />

        <x-stat-card
            :label="__('Attendance')"
            :value="($resumoActivo['presencas_pct'] ?? null) !== null ? $resumoActivo['presencas_pct'] . '%' : '—'"
            icon="clipboard-check"
            :variant="($resumoActivo['presencas_pct'] ?? 100) >= 85 ? 'success' : 'warning'"
            :trend="$resumoActivo ? ($resumoActivo['faltas'] . ' ' . __('absences')) : null" />

        <x-stat-card
            :label="__('Years at school')"
            :value="$anosNaEscola"
            icon="calendar"
            variant="info"
            :trend="$matriculaActiva?->anoLectivo?->codigo" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- ========== Coluna 1-2: Percurso académico ========== --}}
        <div class="lg:col-span-2 space-y-6">
            <x-card :title="__('Academic Path')">
                <x-slot name="actions">
                    <span class="text-xs text-muted">{{ $aluno->matriculas->count() }} {{ __('enrollments') }}</span>
                </x-slot>

                @if($aluno->matriculas->isEmpty())
                    <x-empty icon="inbox" :title="__('No enrollments yet')" :description="__('No enrollment records found for this student.')" />
                @else
                    {{-- Desktop: tabela --}}
                    <div class="hidden md:block table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('School Year') }}</th>
                                    <th>{{ __('Class Groups') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th class="text-right">{{ __('Average') }}</th>
                                    <th class="text-right">{{ __('Attendance') }}</th>
                                    <th class="text-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($aluno->matriculas as $m)
                                    @php
                                        $r = $resumos[$m->id] ?? ['media_anual' => null, 'presencas_pct' => null, 'faltas' => 0];
                                        $estVar = match ($m->estado) {
                                            'activa' => 'success',
                                            'aprovado' => 'success',
                                            'reprovado' => 'danger',
                                            'transferido' => 'info',
                                            'desistente' => 'muted',
                                            default => 'muted',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="font-semibold text-navy">{{ $m->anoLectivo->codigo }}</td>
                                        <td>
                                            <div class="text-navy">{{ $m->turma->classe->nome }} {{ $m->turma->nome }}</div>
                                            <div class="text-xs text-muted">{{ $m->turma->curso?->nome ?? __('Common Core') }}</div>
                                        </td>
                                        <td>
                                            <x-badge :variant="$estVar">{{ __($m->estado) }}</x-badge>
                                        </td>
                                        <td class="text-right">
                                            @if($r['media_anual'] !== null)
                                                <span @class([
                                                    'font-semibold tabular-nums',
                                                    'text-danger' => $r['media_anual'] < 10,
                                                    'text-success' => $r['media_anual'] >= 14,
                                                    'text-navy' => $r['media_anual'] >= 10 && $r['media_anual'] < 14,
                                                ])>{{ number_format($r['media_anual'], 1) }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            @if($r['presencas_pct'] !== null)
                                                <span @class([
                                                    'tabular-nums',
                                                    'text-warning' => $r['presencas_pct'] < 85,
                                                    'text-navy' => $r['presencas_pct'] >= 85,
                                                ])>{{ $r['presencas_pct'] }}%</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="table-actions">
                                            <x-btn-link :href="route('boletim.show', $m)">{{ __('View') }}</x-btn-link>
                                            <x-btn-link :href="route('boletim.pdf', $m)">PDF</x-btn-link>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Mobile: stack de cards --}}
                    <ul class="md:hidden space-y-3 -mx-2">
                        @foreach($aluno->matriculas as $m)
                            @php
                                $r = $resumos[$m->id] ?? ['media_anual' => null, 'presencas_pct' => null, 'faltas' => 0];
                                $estVar = match ($m->estado) {
                                    'activa' => 'success',
                                    'aprovado' => 'success',
                                    'reprovado' => 'danger',
                                    'transferido' => 'info',
                                    'desistente' => 'muted',
                                    default => 'muted',
                                };
                            @endphp
                            <li class="border border-gray-200 rounded-input p-3">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="text-base font-bold text-navy">{{ $m->anoLectivo->codigo }}</div>
                                        <div class="text-sm text-navy mt-0.5">{{ $m->turma->classe->nome }} {{ $m->turma->nome }}</div>
                                        <div class="text-xs text-muted">{{ $m->turma->curso?->nome ?? __('Common Core') }}</div>
                                    </div>
                                    <x-badge :variant="$estVar">{{ __($m->estado) }}</x-badge>
                                </div>

                                <div class="grid grid-cols-2 gap-2 mt-3 text-center">
                                    <div class="rounded bg-gray-50 py-2">
                                        <div class="text-[10px] uppercase tracking-wider text-muted font-semibold">{{ __('Average') }}</div>
                                        <div @class([
                                            'text-base font-bold tabular-nums mt-0.5',
                                            'text-danger' => ($r['media_anual'] ?? null) !== null && $r['media_anual'] < 10,
                                            'text-success' => ($r['media_anual'] ?? 0) >= 14,
                                            'text-navy' => ($r['media_anual'] ?? 0) >= 10 && ($r['media_anual'] ?? 0) < 14,
                                            'text-muted' => $r['media_anual'] === null,
                                        ])>
                                            {{ $r['media_anual'] !== null ? number_format($r['media_anual'], 1) : '—' }}
                                        </div>
                                    </div>
                                    <div class="rounded bg-gray-50 py-2">
                                        <div class="text-[10px] uppercase tracking-wider text-muted font-semibold">{{ __('Attendance') }}</div>
                                        <div @class([
                                            'text-base font-bold tabular-nums mt-0.5',
                                            'text-warning' => ($r['presencas_pct'] ?? null) !== null && $r['presencas_pct'] < 85,
                                            'text-navy' => ($r['presencas_pct'] ?? 0) >= 85,
                                            'text-muted' => $r['presencas_pct'] === null,
                                        ])>
                                            {{ $r['presencas_pct'] !== null ? $r['presencas_pct'].'%' : '—' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2 mt-3">
                                    <x-btn variant="secondary" size="sm" :href="route('boletim.show', $m)" icon="eye">{{ __('View') }}</x-btn>
                                    <x-btn variant="primary" size="sm" :href="route('boletim.pdf', $m)" icon="file-down">PDF</x-btn>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            <x-card :title="__('Personal Data')">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
                    <div>
                        <dt class="form-label">{{ __('Process Number') }}</dt>
                        <dd class="font-mono text-navy">{{ $aluno->numero_processo }}</dd>
                    </div>
                    <div>
                        <dt class="form-label">{{ __('Birth Date') }}</dt>
                        <dd class="text-navy">{{ $aluno->data_nascimento?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="form-label">{{ __('Gender') }}</dt>
                        <dd class="text-navy">{{ $aluno->sexo === 'M' ? __('Male') : ($aluno->sexo === 'F' ? __('Female') : '—') }}</dd>
                    </div>
                    <div>
                        <dt class="form-label">{{ __('Nationality') }}</dt>
                        <dd class="text-navy">{{ $aluno->nacionalidade }}</dd>
                    </div>
                    <div>
                        <dt class="form-label">{{ __('Place of Birth') }}</dt>
                        <dd class="text-navy">{{ $aluno->naturalidade ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="form-label">{{ __('Address') }}</dt>
                        <dd class="text-navy">{{ $aluno->morada ?? '—' }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>

        {{-- ========== Coluna 3: Encarregados + atalhos ========== --}}
        <div class="space-y-6">
            <x-card :title="__('Guardians of this student')">
                <ul class="divide-y divide-gray-100 -my-2">
                    @foreach($aluno->encarregados as $e)
                        <li class="py-3 flex items-start gap-3">
                            <div class="w-9 h-9 rounded-full bg-primary-soft text-primary-600 font-bold flex items-center justify-center text-sm shrink-0">
                                {{ collect(explode(' ', $e->user->name))->take(2)->map(fn($w) => mb_substr($w, 0, 1))->join('') }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-navy font-semibold truncate">{{ $e->user->name }}</div>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <x-badge variant="muted">{{ __($e->pivot->parentesco) }}</x-badge>
                                    @if($e->pivot->principal)
                                        <x-badge variant="primary">{{ __('Primary') }}</x-badge>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-card>

            <x-card :title="__('Quick print')" compact>
                <p class="text-sm text-muted mb-4">{{ __('Download report cards directly') }}.</p>
                <div class="space-y-2">
                    @foreach($aluno->matriculas as $m)
                        <a href="{{ route('boletim.pdf', $m) }}"
                           class="flex items-center gap-3 px-3 py-2 rounded-input border border-gray-200 hover:border-primary hover:bg-primary-soft/40 transition group">
                            <x-lucide-file-down class="w-4 h-4 text-muted group-hover:text-primary" />
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-semibold text-navy truncate">{{ __('Report Card') }} {{ $m->anoLectivo->codigo }}</div>
                                <div class="text-xs text-muted truncate">{{ $m->turma->classe->nome }} {{ $m->turma->nome }} · {{ __($m->estado) }}</div>
                            </div>
                            <x-lucide-arrow-down class="w-4 h-4 text-muted group-hover:text-primary" />
                        </a>
                    @endforeach
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
