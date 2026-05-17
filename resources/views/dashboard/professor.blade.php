<x-app-layout>
    <x-page-header :title="__('Dashboard')" :subtitle="__('Welcome') . ', ' . Auth::user()->name" help="dashboard.professor" />

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-card :title="__('Profile')">
            @if($professor)
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-muted text-xs uppercase">{{ __('Process Number') }}</dt><dd class="text-navy font-semibold">{{ $professor->numero_professor ?? '—' }}</dd></div>
                    <div><dt class="text-muted text-xs uppercase">{{ __('Qualification') }}</dt><dd class="text-navy font-semibold">{{ $professor->habilitacoes ?? '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-muted text-xs uppercase">{{ __('Subjects') }}</dt><dd class="text-navy font-semibold">{{ $professor->disciplinas ?? '—' }}</dd></div>
                </dl>
            @endif
        </x-card>

        <x-card :title="__('Shortcuts')">
            <div class="grid grid-cols-2 gap-3">
                <x-btn variant="primary" icon="clipboard-check" :href="route('aulas.index')">{{ __('Lessons') }}</x-btn>
                <x-btn variant="secondary" icon="clipboard-list" :href="route('avaliacoes.index')">{{ __('Evaluations') }}</x-btn>
                <x-btn variant="secondary" icon="table-2" :href="route('pautas.index')">{{ __('Gradebook') }}</x-btn>
                <x-btn variant="secondary" icon="users" :href="route('meus-alunos.index')">{{ __('Students') }}</x-btn>
            </div>
        </x-card>
    </div>
</x-app-layout>
