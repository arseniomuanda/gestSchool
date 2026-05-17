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
                       class="flex items-start gap-4 p-4 border border-gray-100 rounded-input hover:border-warning hover:bg-warning/5 transition group">
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
                                            <span class="text-muted">— {{ $nota->avaliacao->titulo }}</span>
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

    {{-- ========== Lista de educandos ========== --}}
    <x-card :title="__('My Children')">
        <x-slot name="actions">
            <a href="{{ route('meus-educandos.index') }}" class="btn-link">
                {{ __('See all') }} <x-lucide-arrow-right class="w-3 h-3 inline" />
            </a>
        </x-slot>

        @if($alunos->isEmpty())
            <x-empty icon="users" :title="__('No linked students')" />
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($alunos as $aluno)
                    @php
                        $matricula = $aluno->matriculas->firstWhere('estado', 'activa');
                        $iniciais = collect(explode(' ', $aluno->user->name))->take(2)->map(fn($w) => mb_substr($w, 0, 1))->join('');
                    @endphp
                    <a href="{{ route('meus-educandos.show', $aluno) }}"
                       class="block border border-gray-100 hover:border-primary hover:shadow-card-hover rounded-input p-5 transition">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-full bg-primary-soft text-primary-600 inline-flex items-center justify-center font-bold text-sm shrink-0">
                                {{ $iniciais }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-navy truncate">{{ $aluno->user->name }}</div>
                                <div class="text-xs text-muted font-mono">{{ $aluno->numero_processo }}</div>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center gap-2 text-xs">
                            @if($matricula)
                                <x-badge variant="primary">{{ $matricula->turma->classe->nome }} {{ $matricula->turma->nome }}</x-badge>
                                <span class="text-muted">{{ $matricula->anoLectivo->codigo }}</span>
                            @else
                                <x-badge variant="muted">{{ __('No active enrollment') }}</x-badge>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </x-card>
</x-app-layout>
