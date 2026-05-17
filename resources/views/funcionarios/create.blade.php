<x-app-layout>
    <x-page-header :title="__('New') . ' — ' . ($categoria === 'auxiliar' ? __('Auxiliary Staff') : __('Administrative Staff'))" />
    <x-card>
        <form method="POST" action="{{ route($routeBase . '.store') }}">
            @csrf
            @include('funcionarios._form', ['funcionario' => null])
        </form>
    </x-card>
</x-app-layout>
