<x-app-layout>
    <x-page-header :title="__('My Children')" :subtitle="__('Track your children\'s school path and download their report cards')" />

    @if($alunos->isEmpty())
        <x-card>
            <x-empty icon="users" :title="__('No linked students')" :description="__('No students are currently linked to your account. Contact the school secretariat.')" />
        </x-card>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($alunos as $aluno)
                @php
                    $resumo = $resumosActivos[$aluno->id] ?? null;
                    $matricula = $resumo['matricula'] ?? null;
                    $summary = $resumo['summary'] ?? null;
                    $iniciais = collect(explode(' ', $aluno->user->name))->take(2)->map(fn($w) => mb_substr($w, 0, 1))->join('');
                    $totalAnos = $aluno->matriculas->pluck('ano_lectivo_id')->unique()->count();
                @endphp

                <article class="bg-white border border-gray-100 rounded-input overflow-hidden hover:border-primary hover:shadow-card-hover transition flex flex-col">
                    {{-- Header: avatar + nome + estado --}}
                    <a href="{{ route('meus-educandos.show', $aluno) }}" class="flex items-start gap-3 p-5 border-b border-gray-100 hover:bg-primary-soft/30 transition">
                        <span class="w-12 h-12 rounded-full bg-primary-soft text-primary-600 inline-flex items-center justify-center font-bold shrink-0">
                            {{ $iniciais }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <div class="font-bold text-navy truncate">{{ $aluno->user->name }}</div>
                            <div class="text-xs text-muted font-mono mt-0.5">{{ $aluno->numero_processo }}</div>
                            @if($matricula)
                                <div class="mt-2 flex items-center gap-2 text-xs">
                                    <x-badge variant="primary">{{ $matricula->turma->classe->nome }} {{ $matricula->turma->nome }}</x-badge>
                                    @if($matricula->turma->curso)
                                        <span class="text-muted">{{ $matricula->turma->curso->sigla }}</span>
                                    @endif
                                </div>
                            @else
                                <div class="mt-2"><x-badge variant="muted">{{ __('No active enrollment') }}</x-badge></div>
                            @endif
                        </div>
                    </a>

                    {{-- Mini stats --}}
                    @if($summary)
                        <div class="grid grid-cols-3 divide-x divide-gray-100 text-center">
                            <div class="px-3 py-3">
                                <div class="text-[0.625rem] uppercase tracking-wider text-muted font-semibold">{{ __('Average') }}</div>
                                <div @class([
                                    'text-lg font-bold tabular-nums mt-0.5',
                                    'text-success' => ($summary['media_anual'] ?? 0) >= 14,
                                    'text-navy' => ($summary['media_anual'] ?? 0) >= 10 && ($summary['media_anual'] ?? 0) < 14,
                                    'text-danger' => ($summary['media_anual'] ?? null) !== null && $summary['media_anual'] < 10,
                                    'text-muted' => ($summary['media_anual'] ?? null) === null,
                                ])>
                                    {{ $summary['media_anual'] !== null ? number_format($summary['media_anual'], 1) : '—' }}
                                </div>
                            </div>
                            <div class="px-3 py-3">
                                <div class="text-[0.625rem] uppercase tracking-wider text-muted font-semibold">{{ __('Attendance') }}</div>
                                <div @class([
                                    'text-lg font-bold tabular-nums mt-0.5',
                                    'text-success' => ($summary['presencas_pct'] ?? 0) >= 90,
                                    'text-navy' => ($summary['presencas_pct'] ?? 0) >= 75 && ($summary['presencas_pct'] ?? 0) < 90,
                                    'text-warning' => ($summary['presencas_pct'] ?? null) !== null && $summary['presencas_pct'] < 75,
                                    'text-muted' => ($summary['presencas_pct'] ?? null) === null,
                                ])>
                                    {{ $summary['presencas_pct'] !== null ? $summary['presencas_pct'].'%' : '—' }}
                                </div>
                            </div>
                            <div class="px-3 py-3">
                                <div class="text-[0.625rem] uppercase tracking-wider text-muted font-semibold">{{ __('Years') }}</div>
                                <div class="text-lg font-bold tabular-nums mt-0.5 text-navy">{{ $totalAnos }}</div>
                            </div>
                        </div>
                    @endif

                    {{-- Footer: acções --}}
                    <div class="mt-auto p-3 bg-gray-50/60 border-t border-gray-100 flex items-center gap-2">
                        <x-btn variant="secondary" size="sm" :href="route('meus-educandos.show', $aluno)" icon="user" class="flex-1">
                            {{ __('Profile') }}
                        </x-btn>
                        @if($matricula)
                            <x-btn variant="primary" size="sm" :href="route('boletim.pdf', $matricula)" icon="file-down" class="flex-1">
                                {{ __('Report Card') }}
                            </x-btn>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</x-app-layout>
