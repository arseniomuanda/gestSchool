<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'GestSchool') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=nunito:300,400,600,700&display=swap" rel="stylesheet" />

    <script>
        (function () {
            try {
                if (localStorage.getItem('sb:collapsed') === 'true') {
                    document.documentElement.classList.add('sidebar-collapsed');
                }
            } catch (e) {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans">
    <div class="app-shell">
        <x-navbar />
        <x-sidebar />
        @auth<x-smart-search />@endauth

        <main class="app-main">
            <div class="app-content">
                @isset($header)
                    <div class="mb-6">{{ $header }}</div>
                @endisset
                <x-flash />
                {{ $slot }}
            </div>

            <footer class="px-6 py-4 mt-8 border-t border-gray-100 text-xs text-muted flex flex-wrap items-center justify-between gap-2">
                <span>© {{ date('Y') }} {{ config('app.name') }}</span>
                <a href="{{ route('legal.privacidade') }}" class="hover:text-primary inline-flex items-center gap-1">
                    <x-lucide-shield class="w-3 h-3" />
                    {{ __('Privacy Policy') }}
                </a>
            </footer>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
