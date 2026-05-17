@php
    $user = auth()->user();
    $isDirectorGeral = $user?->hasRole('director_geral');
    $isAdmin = $user?->hasAnyRole(['director_geral', 'director_pedagogico', 'secretario']);
    $isProf = $user?->hasAnyRole(['professor', 'professor_assistente']);
    $isEnc = $user?->hasRole('encarregado');
@endphp

<aside class="sidebar scroll-thin" data-sidebar>
    <nav class="py-4">
        <div class="sb-group" data-sb-group="main">
            <div class="sb-group-items">
                <x-sidebar-link :href="route('dashboard')" icon="layout-dashboard" :active="request()->routeIs('dashboard')" :label="__('Dashboard')">
                    {{ __('Dashboard') }}
                </x-sidebar-link>
            </div>
        </div>

        @if($isAdmin)
            {{-- Módulo 1 + 2: Alunos & Encarregados de Educação --}}
            <x-sidebar-section :title="__('Students & Guardians')" group="students-guardians">
                <x-sidebar-link :href="route('alunos.index')" icon="graduation-cap" :active="request()->routeIs('alunos.*')" :label="__('Students')">{{ __('Students') }}</x-sidebar-link>
                <x-sidebar-link :href="route('encarregados.index')" icon="user-check" :active="request()->routeIs('encarregados.*')" :label="__('Guardians')">{{ __('Guardians') }}</x-sidebar-link>
            </x-sidebar-section>

            {{-- Módulo 3 + 4 + 5: Corpo Docente e Pessoal --}}
            <x-sidebar-section :title="__('Faculty & Staff')" group="faculty-staff">
                <x-sidebar-link :href="route('professores.index')" icon="user-cog" :active="request()->routeIs('professores.*')" :label="__('Teaching Staff')">{{ __('Teaching Staff') }}</x-sidebar-link>
                <x-sidebar-link :href="route('faltas-professores.index')" icon="user-x" :active="request()->routeIs('faltas-professores.*')" :label="__('Teacher absences')">{{ __('Teacher absences') }}</x-sidebar-link>
                <x-sidebar-link :href="route('funcionarios.index')" icon="briefcase" :active="request()->routeIs('funcionarios.*')" :label="__('Administrative Staff')">{{ __('Administrative Staff') }}</x-sidebar-link>
                <x-sidebar-link :href="route('pessoal-auxiliar.index')" icon="hard-hat" :active="request()->routeIs('pessoal-auxiliar.*')" :label="__('Auxiliary Staff')">
                    {{ __('Auxiliary Staff') }}
                </x-sidebar-link>
            </x-sidebar-section>

            {{-- Módulo 6: Estrutura Académica --}}
            <x-sidebar-section :title="__('Academic Structure')" group="academic">
                <x-sidebar-link :href="route('anos.index')" icon="calendar" :active="request()->routeIs('anos.*')" :label="__('Academic Years')">{{ __('Academic Years') }}</x-sidebar-link>
                <x-sidebar-link :href="route('trimestres.index')" icon="calendar-clock" :active="request()->routeIs('trimestres.*')" :label="__('Terms')">{{ __('Terms') }}</x-sidebar-link>
                <x-sidebar-link :href="route('classes.index')" icon="layers" :active="request()->routeIs('classes.*')" :label="__('Classes')">{{ __('Classes') }}</x-sidebar-link>
                <x-sidebar-link :href="route('cursos.index')" icon="award" :active="request()->routeIs('cursos.*')" :label="__('Courses')">{{ __('Courses') }}</x-sidebar-link>
                <x-sidebar-link :href="route('turmas.index')" icon="users-round" :active="request()->routeIs('turmas.*')" :label="__('Class Groups')">{{ __('Class Groups') }}</x-sidebar-link>
                <x-sidebar-link :href="route('disciplinas.index')" icon="book-open" :active="request()->routeIs('disciplinas.*')" :label="__('Subjects List')">{{ __('Subjects List') }}</x-sidebar-link>
            </x-sidebar-section>
        @endif

        {{-- Módulo 7: Operação Pedagógica --}}
        @if($isAdmin || $isProf)
            <x-sidebar-section :title="__('Pedagogical Operation')" group="operation">
                @if($isAdmin)
                    <x-sidebar-link :href="route('matriculas.index')" icon="file-text" :active="request()->routeIs('matriculas.*')" :label="__('Enrollments')">{{ __('Enrollments') }}</x-sidebar-link>
                    <x-sidebar-link :href="route('atribuicoes.index')" icon="link" :active="request()->routeIs('atribuicoes.*')" :label="__('Assignments')">{{ __('Assignments') }}</x-sidebar-link>
                @endif
                <x-sidebar-link :href="route('aulas.index')" icon="clipboard-check" :active="request()->routeIs('aulas.*') || request()->routeIs('presencas.*')" :label="__('Lessons') . ' / ' . __('Attendance')">
                    {{ __('Lessons') }} / {{ __('Attendance') }}
                </x-sidebar-link>
                <x-sidebar-link :href="route('avaliacoes.index')" icon="clipboard-list" :active="request()->routeIs('avaliacoes.*') || request()->routeIs('notas.*')" :label="__('Evaluations')">
                    {{ __('Evaluations') }}
                </x-sidebar-link>
                <x-sidebar-link :href="route('pautas.index')" icon="table-2" :active="request()->routeIs('pautas.*')" :label="__('Gradebook')">
                    {{ __('Gradebook') }}
                </x-sidebar-link>
                <x-sidebar-link :href="route('horarios.index')" icon="calendar-days" :active="request()->routeIs('horarios.*')" :label="__('Schedules')">
                    {{ __('Schedules') }}
                </x-sidebar-link>
                @if($isProf && ! $isAdmin)
                    <x-sidebar-link :href="route('faltas-professores.index')" icon="user-x" :active="request()->routeIs('faltas-professores.*')" :label="__('My absences')">
                        {{ __('My absences') }}
                    </x-sidebar-link>
                @endif
            </x-sidebar-section>
        @endif

        {{-- Módulo 8: Calendário e Eventos --}}
        <x-sidebar-section :title="__('Calendar')" group="calendar">
            <x-sidebar-link :href="route('eventos.index')" icon="calendar-heart" :active="request()->routeIs('eventos.*')" :label="__('School Calendar')">
                {{ __('School Calendar') }}
            </x-sidebar-link>
        </x-sidebar-section>

        @if($isEnc)
            <x-sidebar-section :title="__('My Children')" group="children">
                <x-sidebar-link :href="route('meus-educandos.index')" icon="users-round" :active="request()->routeIs('meus-educandos.*')" :label="__('My Children')">
                    {{ __('My Children') }}
                </x-sidebar-link>
            </x-sidebar-section>
        @endif

        {{-- Módulo 9: Biblioteca (a implementar) --}}
        @if($isAdmin)
            <x-sidebar-section :title="__('Library')" group="library">
                <x-sidebar-link href="#" icon="book-marked" :label="__('Library')" disabled :badge="__('soon')">
                    {{ __('Library') }}
                </x-sidebar-link>
            </x-sidebar-section>
        @endif

        {{-- Comunicação --}}
        <x-sidebar-section :title="__('Communication')" group="communication">
            <x-sidebar-link :href="route('comunicados.index')" icon="megaphone" :active="request()->routeIs('comunicados.*')" :label="__('Announcements')">
                {{ __('Announcements') }}
            </x-sidebar-link>
        </x-sidebar-section>

        {{-- Sistema: só direcção geral / pedagógico / secretário --}}
        @if($isAdmin)
            <x-sidebar-section :title="__('System')" group="system">
                <x-sidebar-link :href="route('users.index')" icon="users" :active="request()->routeIs('users.*')" :label="__('Users')">
                    {{ __('Users') }}
                </x-sidebar-link>
            </x-sidebar-section>
        @endif
    </nav>
</aside>
