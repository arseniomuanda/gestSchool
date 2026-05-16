<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Privacy Policy') }} · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=nunito:300,400,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans bg-gray-50 text-navy">
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-4xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                <span class="font-bold text-navy">GestSchool</span>
            </a>
            <a href="{{ url('/') }}" class="text-sm text-muted hover:text-primary inline-flex items-center gap-1">
                <x-lucide-arrow-left class="w-4 h-4" /> {{ __('Back') }}
            </a>
        </div>
    </header>

    <main class="max-w-3xl mx-auto py-10 px-6">
        <h1 class="text-3xl font-bold text-navy mb-2">{{ __('Privacy Policy') }}</h1>
        <p class="text-sm text-muted mb-8">
            {{ __('Version') }} <strong>{{ $versao }}</strong> · {{ __('Last update') }}: {{ $dataAtualizacao }}
        </p>

        <div class="space-y-6 text-sm leading-relaxed">

            <section>
                <h2 class="text-xl font-semibold mb-2">1. {{ __('Who we are') }}</h2>
                <p>
                    {{ __('This system is operated by') }}
                    <strong>{{ $instituicao['nome'] }}</strong>{{ $instituicao['nif'] ? ' (NIF ' . $instituicao['nif'] . ')' : '' }},
                    {{ __('located at') }} {{ $instituicao['morada'] }}.
                    {{ __('We are the data controller for the personal data processed in this platform, under the terms of the Angolan Personal Data Protection Law') }}
                    (<strong>{{ __('Law No. 22/11 of 17 June') }}</strong>).
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">2. {{ __('What data we collect') }}</h2>
                <p class="mb-2">{{ __('We collect and process only the data strictly necessary for school management:') }}</p>
                <ul class="list-disc list-inside space-y-1 text-muted">
                    <li>{{ __('Identification data: name, BI/ID number, date and place of birth, nationality') }}</li>
                    <li>{{ __('Contact data: address, email, phone (of the student and the guardian)') }}</li>
                    <li>{{ __('Academic data: enrollments, grades, attendance, evaluations') }}</li>
                    <li>{{ __('Health-related data only when relevant for adapted teaching (SEN), with explicit consent') }}</li>
                    <li>{{ __('Technical data: IP, browser, session identifier — for security and audit') }}</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">3. {{ __('Why we process it') }}</h2>
                <ul class="list-disc list-inside space-y-1 text-muted">
                    <li>{{ __('Manage enrollments, grades, attendance and the academic life of students') }}</li>
                    <li>{{ __('Communicate with guardians, students and staff') }}</li>
                    <li>{{ __('Comply with legal obligations (reporting to MED, calendar, certifications)') }}</li>
                    <li>{{ __('Guarantee security and integrity of the school records') }}</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">4. {{ __('How long we keep it') }}</h2>
                <p>
                    {{ __('Personal data of enrolled or recently enrolled students are kept for the duration of the school relationship and for') }}
                    <strong>{{ $retencaoAnos }} {{ __('years') }}</strong>
                    {{ __('after the student leaves or completes the studies, after which the data is anonymized.') }}
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">5. {{ __('How we protect it') }}</h2>
                <ul class="list-disc list-inside space-y-1 text-muted">
                    <li>{{ __('Sensitive fields (BI, address, observations) are encrypted at rest in the database') }}</li>
                    <li>{{ __('Access to data is role-based and audited') }}</li>
                    <li>{{ __('Communications use HTTPS in production') }}</li>
                    <li>{{ __('Passwords are hashed; never visible to anyone') }}</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">6. {{ __('Your rights as data subject') }}</h2>
                <p class="mb-2">{{ __('You may, at any time and free of charge, exercise the following rights:') }}</p>
                <ul class="list-disc list-inside space-y-1 text-muted">
                    <li>{{ __('Access — know what data we hold about you') }}</li>
                    <li>{{ __('Information — receive details about the processing') }}</li>
                    <li>{{ __('Rectification — correct inaccurate data') }}</li>
                    <li>{{ __('Cancellation — request deletion when no longer necessary') }}</li>
                    <li>{{ __('Opposition — object to processing, including for direct marketing') }}</li>
                    <li>{{ __('Portability — receive your data in a structured electronic format') }}</li>
                </ul>
                <p class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded text-sm">
                    {{ __('To exercise these rights, contact the Data Protection Officer (DPO):') }}
                    <strong>{{ $dpo['nome'] }}</strong> · <a href="mailto:{{ $dpo['email'] }}" class="text-primary hover:underline">{{ $dpo['email'] }}</a>
                    @if($dpo['telefone']) · {{ $dpo['telefone'] }}@endif
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">7. {{ __('Supervisory Authority') }}</h2>
                <p>
                    {{ __('You may also lodge a complaint with the') }} <strong>{{ $apd['nome'] }}</strong> —
                    <a href="{{ $apd['site'] }}" target="_blank" rel="noopener" class="text-primary hover:underline">{{ $apd['site'] }}</a>.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-2">8. {{ __('Changes to this policy') }}</h2>
                <p>
                    {{ __('When this policy changes materially, we will request renewed consent from guardians at the next interaction with the system. The version above lets you check whether what you consented to is still up to date.') }}
                </p>
            </section>

        </div>

        <footer class="mt-12 pt-6 border-t border-gray-200 text-xs text-muted">
            © {{ date('Y') }} {{ config('app.name') }} · {{ __('Law No. 22/11 of 17 June') }}
        </footer>
    </main>
</body>
</html>
