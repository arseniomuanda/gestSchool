<x-app-layout>
    <x-page-header :title="$funcionario->user->name" :subtitle="__('Edit')" />
    <x-card>
        <form method="POST" action="{{ route($routeBase . '.update', $funcionario) }}">
            @csrf @method('PUT')
            @include('funcionarios._form', ['funcionario' => $funcionario])
        </form>
    </x-card>
</x-app-layout>
