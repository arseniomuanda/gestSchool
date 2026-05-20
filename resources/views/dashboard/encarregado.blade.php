<x-app-layout>
    <x-page-header :title="__('Dashboard')" :subtitle="__('Welcome') . ', ' . Auth::user()->name" help="dashboard.encarregado">
        <x-slot name="actions">
            <x-btn variant="primary" icon="users" :href="route('meus-educandos.index')">{{ __('My Children') }}</x-btn>
        </x-slot>
    </x-page-header>

    {{-- ========== Painel de alertas ========== --}}
    @if(! empty($alertas))
        <x-card :title="__('Attention required')">
            <x-slot name="actions">
                <x-badge variant="warning">{{ count($alertas) }}</x-badge>
            </x-slot>

            <div class="space-y-3">
                @foreach($alertas as $a)
                    @php
                        $aluno = $a['aluno'];
                        $matricula = $a['matricula'];
                        $iniciais = collect(explode(' ', $aluno->user->name))->take(2)->map(fn($w) => mb_substr($w, 0, 1))->join('');
                    @endphp
                    <a href="{{ route('meus-educandos.show', $aluno) }}"
                       class="flex items-start gap-3 p-3 sm:p-4 border border-gray-100 rounded-input hover:border-warning hover:bg-warning/5 transition group">
                        <span class="w-10 h-10 rounded-full bg-warning/15 text-warning inline-flex items-center justify-center font-bold shrink-0">
                            {{ $iniciais }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-semibold text-navy truncate">{{ $aluno->user->name }}</span>
                                <x-badge variant="muted">{{ $matricula->turma->classe->nome }} {{ $matricula->turma->nome }}</x-badge>
                            </div>
                            <ul class="mt-2 space-y-1 text-sm">
                                @if($a['faltas_recentes'] >= 3)
                                    <li class="flex items-center gap-2 text-warning">
                                        <x-lucide-clock class="w-4 h-4 shrink-0" />
                                        <span>
                                            <strong class="tabular-nums">{{ $a['faltas_recentes'] }}</strong>
                                            {{ __('absences in the last 30 days') }}
                                        </span>
                                    </li>
                                @endif
                                @foreach($a['notas_baixas'] as $nota)
                                    <li class="flex items-start gap-2 text-danger">
                                        <x-lucide-trending-down class="w-4 h-4 shrink-0 mt-0.5" />
                                        <span>
                                            <strong class="tabular-nums">{{ number_format($nota->valor, 1) }}</strong>
                                            {{ __('in') }} {{ $nota->avaliacao->atribuicao->disciplina->nome }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <x-lucide-chevron-right class="w-5 h-5 text-muted group-hover:text-warning transition shrink-0 mt-1" />
                    </a>
                @endforeach
            </div>
        </x-card>
    @endif

    {{-- ========== Atalhos rápidos ========== --}}
    <x-card :title="__('Quick actions')">
        @php
            $primeiraMatricula = collect($resumosActivos)->first()['matricula'] ?? null;
        @endphp
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3">
            @if($primeiraMatricula)
                <a href="{{ route('boletim.show', $primeiraMatricula) }}" class="qa-tile">
                    <x-lucide-file-text class="qa-icon" />
                    <span>{{ __('Report Card') }}</span>
                </a>
                <a href="{{ route('horarios.turma', $primeiraMatricula->turma) }}" class="qa-tile">
                    <x-lucide-clock class="qa-icon" />
                    <span>{{ __('Schedule') }}</span>
                </a>
            @endif
            <a href="{{ route('comunicados.index') }}" class="qa-tile">
                <x-lucide-megaphone class="qa-icon" />
                <span>{{ __('Announcements') }}</span>
            </a>
            <a href="{{ route('eventos.index') }}" class="qa-tile">
                <x-lucide-calendar class="qa-icon" />
                <span>{{ __('Calendar') }}</span>
            </a>
        </div>
    </x-card>

    {{-- ========== Grid principal: educandos | eventos+comunicados ========== --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 sm:gap-6">

        {{-- Coluna principal: Meus Educandos --}}
        <x-card :title="__('My Children')" class="xl:col-span-2">
            <x-slot name="actions">
                <a href="{{ route('meus-educandos.index') }}" class="btn-link text-sm">
                    {{ __('See all') }} <x-lucide-arrow-right class="w-3 h-3 inline" />
                </a>
            </x-slot>

            @if($alunos->isEmpty())
                <x-empty icon="users" :title="__('No linked students')" />
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                    @foreach($alunos as $aluno)
                        @php
                            $resumo = $resumosActivos[$aluno->id] ?? null;
                            $matricula = $resumo['matricula'] ?? null;
                            $summary = $resumo['summary'] ?? null;
                            $iniciais = collect(explode(' ', $aluno->user->name))->take(2)->map(fn($w) => mb_substr($w, 0, 1))->join('');
                        @endphp
                        <article class="border border-gray-100 hover:border-primary rounded-input overflow-hidden flex flex-col transition">
                            <a href="{{ route('meus-educandos.show', $aluno) }}" class="flex items-start gap-3 p-3 sm:p-4 hover:bg-primary-soft/30 transition">
                                <span class="w-11 h-11 rounded-full bg-primary-soft text-primary-600 inline-flex items-center justify-center font-bold shrink-0">
                                    {{ $iniciais }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-navy truncate">{{ $aluno->user->name }}</div>
                                    <div class="text-[11px] text-muted font-mono mt-0.5">{{ $aluno->numero_processo }}</div>
                                    @if($matricula)
                                        <div class="mt-1.5 flex items-center gap-1.5 flex-wrap text-xs">
                                            <x-badge variant="primary">{{ $matricula->turma->classe->nome }} {{ $matricula->turma->nome }}</x-badge>
                                            <span class="text-muted">{{ $matricula->anoLectivo->codigo }}</span>
                                        </div>
                                    @else
                                        <div class="mt-1.5"><x-badge variant="muted">{{ __('No active enrollment') }}</x-badge></div>
                                    @endif
                                </div>
                            </a>

                            @if($summary)
                                <div class="grid grid-cols-3 divide-x divide-gray-100 border-t border-gray-100 text-center">
                                    <div class="px-2 py-2.5">
                                        <div class="text-[10px] uppercase tracking-wider text-muted font-semibold">{{ __('Average') }}</div>
                                        <div @class([
                                            'text-base sm:text-lg font-bold tabular-nums mt-0.5',
                                            'text-success' => ($summary['media_anual'] ?? 0) >= 14,
                                            'text-navy' => ($summary['media_anual'] ?? 0) >= 10 && ($summary['media_anual'] ?? 0) < 14,
                                            'text-danger' => ($summary['media_anual'] ?? null) !== null && $summary['media_anual'] < 10,
                                            'text-muted' => ($summary['media_anual'] ?? null) === null,
                                        ])>
                                            {{ $summary['media_anual'] !== null ? number_format($summary['media_anual'], 1) : '—' }}
                                        </div>
                                    </div>
                                    <div class="px-2 py-2.5">
                                        <div class="text-[10px] uppercase tracking-wider text-muted font-semibold">{{ __('Attendance') }}</div>
                                        <div @class([
                                            'text-base sm:text-lg font-bold tabular-nums mt-0.5',
                                            'text-success' => ($summary['presencas_pct'] ?? 0) >= 90,
                                            'text-navy' => ($summary['presencas_pct'] ?? 0) >= 75 && ($summary['presencas_pct'] ?? 0) < 90,
                                            'text-warning' => ($summary['presencas_pct'] ?? null) !== null && $summary['presencas_pct'] < 75,
                                            'text-muted' => ($summary['presencas_pct'] ?? null) === null,
                                        ])>
                                            {{ $summary['presencas_pct'] !== null ? $summary['presencas_pct'].'%' : '—' }}
                                        </div>
                                    </div>
                                    <div class="px-2 py-2.5">
                                        <div class="text-[10px] uppercase tracking-wider text-muted font-semibold">{{ __('Absences') }}</div>
                                        <div class="text-base sm:text-lg font-bold tabular-nums mt-0.5 text-navy">
                                            {{ $summary['faltas'] ?? 0 }}
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="grid grid-cols-2 gap-2 p-2 sm:p-3 bg-gray-50/60 border-t border-gray-100 mt-auto">
                                @if($matricula)
                                    <x-btn variant="primary" size="sm" :href="route('boletim.show', $matricula)" icon="file-text">{{ __('Report Card') }}</x-btn>
                                @endif
                                <x-btn variant="secondary" size="sm" :href="route('meus-educandos.show', $aluno)" icon="user" class="@if(!$matricula) col-span-2 @endif">{{ __('Profile') }}</x-btn>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </x-card>

        {{-- Coluna lateral: eventos + comunicados --}}
        <div class="space-y-4 sm:space-y-6">
            {{-- Próximos eventos --}}
            <x-card :title="__('Upcoming events')" compact>
                <x-slot name="actions">
                    <a href="{{ route('eventos.index') }}" class="btn-link text-xs">{{ __('All') }} <x-lucide-arrow-right class="w-3 h-3 inline" /></a>
                </x-slot>
                @if($proximosEventos->isEmpty())
                    <x-empty :title="__('No upcoming events')" icon="calendar-x" />
                @else
                    <ul class="-my-2 divide-y divide-gray-100">
                        @foreach($proximosEventos as $ev)
                            <li class="py-2.5">
                                <a href="{{ route('eventos.show', $ev) }}" class="flex items-start gap-3 group">
                                    <div class="w-1 self-stretch rounded shrink-0" style="background-color: {{ $ev->cor_efectiva }};"></div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-semibold text-navy group-hover:text-primary transition truncate">{{ $ev->titulo }}</div>
                                        <div class="text-xs text-muted mt-0.5 flex items-center gap-1.5 flex-wrap">
                                            <span class="tabular-nums">{{ $ev->data_inicio->format('d/m') }}</span>
                                            @if($ev->data_fim && $ev->data_fim->ne($ev->data_inicio))
                                                <span class="tabular-nums">– {{ $ev->data_fim->format('d/m') }}</span>
                                            @endif
                                            <span style="color: {{ $ev->cor_efectiva }};">· {{ $ev->tipo_nome }}</span>
                                            @if($ev->turma)
                                                <span>· {{ $ev->turma->classe->nome }} {{ $ev->turma->nome }}</span>
                                            @elseif($ev->classe)
                                                <span>· {{ $ev->classe->nome }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            {{-- Últimos comunicados --}}
            <x-card :title="__('Recent announcements')" compact>
                <x-slot name="actions">
                    <a href="{{ route('comunicados.index') }}" class="btn-link text-xs">{{ __('All') }} <x-lucide-arrow-right class="w-3 h-3 inline" /></a>
                </x-slot>
                @if($ultimosComunicados->isEmpty())
                    <x-empty :title="__('No announcements yet')" icon="megaphone" />
                @else
                    <ul class="-my-2 divide-y divide-gray-100">
                        @foreach($ultimosComunicados as $c)
                            <li class="py-2.5">
                                <a href="{{ route('comunicados.show', $c) }}" class="block group">
                                    <div class="text-sm font-semibold text-navy group-hover:text-primary transition truncate">{{ $c->titulo }}</div>
                                    <div class="text-xs text-muted mt-0.5 flex items-center gap-1.5 flex-wrap">
                                        <span>{{ $c->publicado_em?->diffForHumans() }}</span>
                                        <span>·</span>
                                        <span class="truncate">{{ $c->autor?->name }}</span>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
