<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'GestSchool') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=nunito:300,400,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans bg-gray-50">
    <div class="min-h-screen grid lg:grid-cols-2">
        <aside class="hidden lg:flex bg-sidebar text-white flex-col justify-between p-12">
            <div>
                <h1 class="text-white text-3xl font-bold">GestSchool</h1>
                <p class="text-sidebar-text/60 mt-2 text-sm">{{ __('School Management') }}</p>
            </div>
            <div class="text-sidebar-text/50 text-xs space-y-1">
                <p>© {{ date('Y') }} GestSchool — {{ __('School Management') }}</p>
                <p>
                    {{ __('Built by') }}
                    <a href="https://www.linkedin.com/in/ars%C3%A9nio-muanda-91808518b/" target="_blank" rel="noopener" class="text-sidebar-text/80 hover:text-white underline-offset-2 hover:underline">Arsénio Muanda</a>
                </p>
            </div>
        </aside>

        <main class="flex items-center justify-center px-6 py-12">
            <div class="w-full max-w-md">
                <div class="lg:hidden mb-8 text-center">
                    <h1 class="text-navy text-2xl font-bold">GestSchool</h1>
                </div>
                <x-flash />
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
