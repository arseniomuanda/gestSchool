<x-app-layout>
    <x-page-header :title="__('Register absence')">
        <x-slot name="actions">
            <x-btn variant="secondary" :href="route('faltas-professores.index')">{{ __('Cancel') }}</x-btn>
        </x-slot>
    </x-page-header>

    <x-card>
        <form action="{{ route('faltas-professores.store') }}" method="POST">
            @csrf
            @include('faltas-professores._form')
        </form>
    </x-card>
</x-app-layout>
