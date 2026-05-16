<x-app-layout>
    <x-page-header :title="$professor->user->name">
        <x-slot name="subtitleSlot">
            <div class="flex flex-wrap items-center gap-2 mt-1">
                @if($professor->assistente)
                    <x-badge variant="muted">{{ __('Assistant Teacher') }}</x-badge>
                @else
                    <x-badge variant="info">{{ __('Teacher') }}</x-badge>
                @endif
                @if($turmasDirigidasActivas->isNotEmpty())
                    <x-badge variant="success">
                        <x-lucide-shield class="w-3 h-3 inline" />
                        {{ trans_choice('Director of :n class group|Director of :n class groups', $turmasDirigidasActivas->count(), ['n' => $turmasDirigidasActivas->count()]) }}
                    </x-badge>
                @endif
                @if($professor->antiguidade !== null)
                    <x-badge variant="muted">
                        <x-lucide-clock class="w-3 h-3 inline" />
                        {{ trans_choice(':n year of service|:n years of service', $professor->antiguidade, ['n' => $professor->antiguidade]) }}
                    </x-badge>
                @endif
            </div>
        </x-slot>
        <x-slot name="actions">
            <x-btn variant="primary" icon="pencil" :href="route('professores.edit', $professor)">{{ __('Edit') }}</x-btn>
            <x-btn variant="secondary" :href="route('professores.index')">{{ __('Back') }}</x-btn>
        </x-slot>
    </x-page-header>

    {{-- Atalhos rápidos --}}
    <x-card>
        <h3 class="form-label text-xs uppercase tracking-wide mb-3">{{ __('Quick shortcuts:') }}</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <a href="{{ route('horarios.professor', $professor) }}" class="block p-3 rounded border border-gray-200 hover:border-primary hover:bg-blue-50/50 transition">
                <x-lucide-calendar-days class="w-5 h-5 text-primary mb-1" />
                <div class="text-sm font-semibold text-navy">{{ __('Personal schedule') }}</div>
                <div class="text-xs text-muted">{{ __('Class timetable') }}</div>
            </a>
            <a href="{{ route('pautas.index') }}" class="block p-3 rounded border border-gray-200 hover:border-primary hover:bg-blue-50/50 transition">
                <x-lucide-table-2 class="w-5 h-5 text-primary mb-1" />
                <div class="text-sm font-semibold text-navy">{{ __('My gradebooks') }}</div>
                <div class="text-xs text-muted">{{ __('Gradebook') }}</div>
            </a>
            <a href="{{ route('presencas.index') }}" class="block p-3 rounded border border-gray-200 hover:border-primary hover:bg-blue-50/50 transition">
                <x-lucide-clipboard-check class="w-5 h-5 text-primary mb-1" />
                <div class="text-sm font-semibold text-navy">{{ __('Attendance sheets') }}</div>
                <div class="text-xs text-muted">{{ __('Attendance') }}</div>
            </a>
            <a href="{{ route('aulas.index') }}" class="block p-3 rounded border border-gray-200 hover:border-primary hover:bg-blue-50/50 transition">
                <x-lucide-clipboard-list class="w-5 h-5 text-primary mb-1" />
                <div class="text-sm font-semibold text-navy">{{ __('My lessons') }}</div>
                <div class="text-xs text-muted">{{ __('Lessons') }}</div>
            </a>
        </div>
    </x-card>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Identificação --}}
        <x-card :title="__('Identification')">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div><dt class="form-label">{{ __('Email') }}</dt><dd class="text-navy break-all">{{ $professor->user->email }}</dd></div>
                <div><dt class="form-label">{{ __('Phone') }}</dt><dd class="text-navy">{{ $professor->user->phone ?? '—' }}</dd></div>
                <div><dt class="form-label">{{ __('BI Number') }}</dt><dd class="text-navy">{{ $professor->bi ?? '—' }}</dd></div>
                <div><dt class="form-label">{{ __('Gender') }}</dt><dd class="text-navy">{{ $professor->sexo ? __($professor->sexo === 'M' ? 'Male' : 'Female') : '—' }}</dd></div>
                <div><dt class="form-label">{{ __('Birth Date') }}</dt><dd class="text-navy">{{ $professor->data_nascimento?->format('d/m/Y') ?? '—' }}</dd></div>
                <div class="sm:col-span-2"><dt class="form-label">{{ __('Address') }}</dt><dd class="text-navy">{{ $professor->morada ?? '—' }}</dd></div>
            </dl>
        </x-card>

        {{-- Carreira --}}
        <x-card :title="__('Career')">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div><dt class="form-label">{{ __('Staff Number') }}</dt><dd class="text-navy">{{ $professor->numero_professor ?? '—' }}</dd></div>
                <div>
                    <dt class="form-label">{{ __('Hire Date') }}</dt>
                    <dd class="text-navy">
                        {{ $professor->data_admissao?->format('d/m/Y') ?? '—' }}
                        @if($professor->antiguidade !== null)
                            <span class="text-xs text-muted">· {{ trans_choice(':n year|:n years', $professor->antiguidade, ['n' => $professor->antiguidade]) }}</span>
                        @endif
                    </dd>
                </div>
                <div class="sm:col-span-2"><dt class="form-label">{{ __('Qualification') }}</dt><dd class="text-navy">{{ $professor->habilitacoes ?? '—' }}</dd></div>
                <div class="sm:col-span-2"><dt class="form-label">{{ __('Speciality') }}</dt><dd class="text-navy">{{ $professor->especialidade ?? '—' }}</dd></div>
                <div class="sm:col-span-2"><dt class="form-label">{{ __('Subjects') }}</dt><dd class="text-navy">{{ $professor->disciplinas ?? '—' }}</dd></div>
            </dl>
        </x-card>
    </div>

    {{-- Atribuições activas --}}
    <x-card :title="__('Active assignments')">
        @if($anoActivo)
            <p class="text-xs text-muted -mt-4 mb-4">
                {{ __('Active year') }}: <strong>{{ $anoActivo->codigo }}</strong>
                · {{ $atribuicoesActivas->count() }} {{ __('assignments') }}
                · {{ $cargaSemanal }} {{ __('periods/week') }}
            </p>
        @endif

        @if($atribuicoesActivas->isEmpty())
            <x-empty :title="__('No active assignments')" icon="link-2-off" />
        @else
            <div class="overflow-x-auto">
                <table class="table text-sm">
                    <thead>
                        <tr>
                            <th class="text-start">{{ __('Class Groups') }}</th>
                            <th class="text-start">{{ __('Subject') }}</th>
                            <th class="text-center">{{ __('Weekly Hours') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($atribuicoesActivas->sortBy([['turma.classe.nome', 'asc'], ['turma.nome', 'asc'], ['disciplina.nome', 'asc']]) as $a)
                            <tr>
                                <td>
                                    <a href="{{ route('horarios.turma', $a->turma) }}" class="text-primary hover:underline">
                                        <x-turma-label :turma="$a->turma" />
                                    </a>
                                </td>
                                <td class="text-navy">{{ $a->disciplina->nome }} <span class="text-muted text-xs">({{ $a->disciplina->sigla }})</span></td>
                                <td class="text-center"><x-badge variant="muted">{{ $a->disciplina->carga_horaria_semanal ?? '—' }}</x-badge></td>
                                <td class="text-end">
                                    <a href="{{ route('pautas.disciplina', ['atribuicao' => $a->id, 'trimestre' => 1]) }}" class="btn-link text-xs">
                                        <x-lucide-table-2 class="w-3 h-3 inline" /> {{ __('Gradebook') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    {{-- Direcção de turma --}}
    @if($turmasDirigidasActivas->isNotEmpty())
        <x-card :title="__('Class direction')">
            <p class="text-sm text-muted mb-3">{{ __('Class groups under direct responsibility:') }}</p>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($turmasDirigidasActivas as $t)
                    <a href="{{ route('turmas.show', $t) }}" class="block p-3 rounded border border-gray-200 hover:border-primary hover:bg-blue-50/50 transition">
                        <div class="flex items-center gap-2">
                            <x-lucide-shield class="w-4 h-4 text-success" />
                            <span class="font-semibold text-navy">
                                <x-turma-label :turma="$t" />
                            </span>
                        </div>
                        @if($t->anoLectivo)
                            <div class="text-xs text-muted mt-1">{{ $t->anoLectivo->codigo }}</div>
                        @endif
                    </a>
                @endforeach
            </div>
        </x-card>
    @endif
</x-app-layout>
