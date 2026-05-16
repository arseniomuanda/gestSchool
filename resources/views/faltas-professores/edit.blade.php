<x-app-layout>
    <x-page-header :title="__('Edit absence')" :subtitle="$falta->professor->user->name . ' · ' . $falta->data->format('d/m/Y')">
        <x-slot name="actions">
            <x-btn variant="secondary" :href="route('faltas-professores.show', $falta)">{{ __('Cancel') }}</x-btn>
        </x-slot>
    </x-page-header>

    <x-card>
        <form action="{{ route('faltas-professores.update', $falta) }}" method="POST">
            @csrf
            @method('PUT')
            @include('faltas-professores._form')
        </form>
    </x-card>
</x-app-layout>
