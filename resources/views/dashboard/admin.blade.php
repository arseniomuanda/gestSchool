<x-app-layout>
    <x-page-header :title="__('Dashboard')" :subtitle="__('School Management')" help="dashboard.admin" />

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6 mb-6">
        <x-stat-card :label="__('Users')" :value="$stats['users']" icon="users" variant="primary" :href="route('users.index')" />
        <x-stat-card :label="__('Staff')" :value="$stats['funcionarios']" icon="briefcase" variant="info" :href="route('funcionarios.index')" />
        <x-stat-card :label="__('Teachers')" :value="$stats['professores']" icon="user-cog" variant="success" :href="route('professores.index')" />
        <x-stat-card :label="__('Students')" :value="$stats['alunos']" icon="graduation-cap" variant="warning" :href="route('alunos.index')" />
        <x-stat-card :label="__('Guardians')" :value="$stats['encarregados']" icon="user-check" variant="danger" :href="route('encarregados.index')" />
    </div>

    <x-card :title="__('School Management')">
        <p class="text-sm text-muted">{{ __('Welcome') }}, <span class="text-navy font-semibold">{{ Auth::user()->name }}</span>.</p>
        <p class="text-sm text-muted mt-2">{{ __('Quick shortcuts:') }}</p>
        <div class="mt-4 flex flex-wrap gap-2">
            <x-btn variant="primary" size="sm" icon="clipboard-check" :href="route('aulas.index')">{{ __('Lessons') }}</x-btn>
            <x-btn variant="secondary" size="sm" icon="clipboard-list" :href="route('avaliacoes.index')">{{ __('Evaluations') }}</x-btn>
            <x-btn variant="secondary" size="sm" icon="table-2" :href="route('pautas.index')">{{ __('Gradebook') }}</x-btn>
            <x-btn variant="secondary" size="sm" icon="megaphone" :href="route('comunicados.index')">{{ __('Announcements') }}</x-btn>
        </div>
    </x-card>
</x-app-layout>
