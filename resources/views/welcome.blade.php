@php
    $locale = app()->getLocale();
    $isPt = $locale === 'pt';
    $title = $isPt
        ? 'gestSchool — Gestão escolar para escolas públicas e instituições sem fins lucrativos'
        : 'gestSchool — School management for public and non-profit schools';
    $description = $isPt
        ? 'Sistema de gestão escolar completo para escolas públicas, IPSS e ONG. Matrículas, pautas, boletins, presenças e comunicados num único sistema. Da iniciação ao ensino médio. Sem licenças caras.'
        : 'Complete school management system for public, non-profit and NGO schools. Enrollments, gradebooks, report cards, attendance and announcements in one place. From kindergarten to secondary. No expensive licenses.';
    $canonical = url('/');
    $appUrl = config('app.url', $canonical);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fdf6e7">

    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="author" content="Arsénio Muanda">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <link rel="canonical" href="{{ $canonical }}">

    <link rel="alternate" hreflang="pt-AO" href="{{ route('locale.switch', 'pt') }}">
    <link rel="alternate" hreflang="en" href="{{ route('locale.switch', 'en') }}">
    <link rel="alternate" hreflang="x-default" href="{{ $appUrl }}">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="{{ $isPt ? 'pt_AO' : 'en_US' }}">
    <meta property="og:site_name" content="gestSchool">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-serif-display:400|nunito:400,600,700,800|caveat:600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script type="application/ld+json">
    @php
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'EducationalApplication',
            'name' => 'gestSchool',
            'description' => $description,
            'url' => $canonical,
            'applicationCategory' => 'EducationalApplication',
            'operatingSystem' => 'Web (Laravel)',
            'inLanguage' => ['pt-AO', 'en'],
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'AOA',
                'availability' => 'https://schema.org/InStock',
                'eligibleCustomerType' => 'https://schema.org/PublicSector',
            ],
            'audience' => [
                '@type' => 'EducationalAudience',
                'educationalRole' => 'administrator',
            ],
            'author' => [
                '@type' => 'Person',
                'name' => 'Arsénio Muanda',
                'url' => 'https://www.linkedin.com/in/ars%C3%A9nio-muanda-91808518b/',
            ],
            'featureList' => ['Matrículas','Pautas','Boletins','Presenças','Comunicados','Horários','Avaliações','Encarregados de educação'],
        ];
    @endphp
    {!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
</head>
<body class="ws-body">

    {{-- Decorative floaters --}}
    <div class="ws-deco" aria-hidden="true">
        <span class="ws-deco-blob ws-deco-blob--yellow"></span>
        <span class="ws-deco-blob ws-deco-blob--coral"></span>
        <span class="ws-deco-dots ws-deco-dots--a"></span>
        <span class="ws-deco-star ws-deco-star--a">✦</span>
        <span class="ws-deco-star ws-deco-star--b">✦</span>
    </div>

    {{-- ============ HEADER ============ --}}
    <header class="ws-header">
        <a href="{{ url('/') }}" class="ws-brand">
            <span class="ws-brand-mark" aria-hidden="true">
                <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 4 L36 12 L20 20 L4 12 Z" fill="#0f4d3a"/>
                    <path d="M8 16 V26 C8 30 14 33 20 33 C26 33 32 30 32 26 V16" stroke="#0f4d3a" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                    <circle cx="35" cy="14" r="2" fill="#fbbf24"/>
                </svg>
            </span>
            <span class="ws-brand-name">gestSchool</span>
        </a>

        <nav class="ws-nav" aria-label="{{ __('Section navigation') }}">
            <a href="#para-quem">{{ $isPt ? 'Para quem' : 'For whom' }}</a>
            <a href="#modulos">{{ $isPt ? 'Módulos' : 'Modules' }}</a>
            <a href="#numeros">{{ $isPt ? 'Números' : 'Numbers' }}</a>
            <a href="#licenca">{{ $isPt ? 'Licença' : 'License' }}</a>
        </nav>

        <div class="ws-header-actions">
            <div class="ws-locale" role="group" aria-label="{{ __('Language') }}">
                <a href="{{ route('locale.switch', 'pt') }}" @class(['is-active' => $isPt])>PT</a>
                <a href="{{ route('locale.switch', 'en') }}" @class(['is-active' => ! $isPt])>EN</a>
            </div>
            @auth
                <a href="{{ route('dashboard') }}" class="ws-btn ws-btn--primary ws-btn--sm">
                    {{ __('Dashboard') }} <span aria-hidden="true">→</span>
                </a>
            @else
                <a href="{{ route('login') }}" class="ws-btn ws-btn--primary ws-btn--sm">
                    {{ __('Log in') }} <span aria-hidden="true">→</span>
                </a>
            @endauth
        </div>
    </header>

    <main>

        {{-- ============ HERO ============ --}}
        <section class="ws-hero" aria-labelledby="hero-title">
            <div class="ws-hero-left">
                <p class="ws-eyebrow">
                    <span class="ws-eyebrow-dot"></span>
                    {{ $isPt ? 'Para escolas públicas, IPSS, ONG e missões' : 'For public, non-profit and mission schools' }}
                </p>

                <h1 id="hero-title" class="ws-display">
                    {{ $isPt ? 'Gerir uma escola pode ser ' : 'Running a school can be ' }}<span class="ws-underline">{{ $isPt ? 'simples' : 'simple' }}<svg class="ws-underline-svg" viewBox="0 0 200 18" preserveAspectRatio="none" aria-hidden="true"><path d="M3 12 Q 40 2, 80 9 T 160 7 T 197 11" stroke="#fbbf24" stroke-width="5" fill="none" stroke-linecap="round"/></svg></span>{{ $isPt ? '.' : '.' }}
                </h1>

                <p class="ws-lede">
                    {{ $isPt
                        ? 'Da iniciação ao ensino médio. Matrículas, pautas, boletins, presenças e comunicados num único sistema feito para escolas que servem comunidades — não para vender licenças.'
                        : 'From kindergarten to secondary education. Enrollments, gradebooks, report cards, attendance and announcements in one system built for schools that serve communities — not for selling licenses.' }}
                </p>

                <div class="ws-hero-actions">
                    @auth
                        <a href="{{ route('dashboard') }}" class="ws-btn ws-btn--primary">
                            {{ __('Open dashboard') }} <span aria-hidden="true">→</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="ws-btn ws-btn--primary">
                            {{ __('Sign in') }} <span aria-hidden="true">→</span>
                        </a>
                    @endauth
                    <a href="https://github.com/arseniomuanda/gestSchool" target="_blank" rel="noopener" class="ws-btn ws-btn--ghost">
                        {{ $isPt ? 'Ver no GitHub' : 'View on GitHub' }}
                    </a>
                </div>

                <ul class="ws-hero-pills">
                    <li><span aria-hidden="true">✓</span> {{ $isPt ? 'Grátis para escolas públicas' : 'Free for public schools' }}</li>
                    <li><span aria-hidden="true">✓</span> {{ $isPt ? 'PT-AO + EN' : 'PT-AO + EN' }}</li>
                    <li><span aria-hidden="true">✓</span> {{ $isPt ? 'Self-hosted' : 'Self-hosted' }}</li>
                </ul>
            </div>

            <div class="ws-hero-right" aria-hidden="true">
                {{-- Hero illustration: chalkboard-style card with floating school items --}}
                <div class="ws-hero-board">
                    <div class="ws-hero-board-top">
                        <span class="ws-hero-board-dot"></span>
                        <span class="ws-hero-board-dot"></span>
                        <span class="ws-hero-board-dot"></span>
                        <span class="ws-hero-board-title">{{ $isPt ? 'Painel de hoje' : "Today's panel" }}</span>
                    </div>
                    <div class="ws-hero-board-body">
                        <div class="ws-hero-mini">
                            <span class="ws-hero-mini-icon ws-hero-mini-icon--green">A</span>
                            <div>
                                <strong>{{ $isPt ? '12 aulas hoje' : '12 lessons today' }}</strong>
                                <small>{{ $isPt ? '3 ainda por dar' : '3 still pending' }}</small>
                            </div>
                        </div>
                        <div class="ws-hero-mini">
                            <span class="ws-hero-mini-icon ws-hero-mini-icon--yellow">B</span>
                            <div>
                                <strong>{{ $isPt ? 'Pauta 2.º trimestre' : '2nd term gradebook' }}</strong>
                                <small>{{ $isPt ? 'pronta para imprimir' : 'ready to print' }}</small>
                            </div>
                        </div>
                        <div class="ws-hero-mini">
                            <span class="ws-hero-mini-icon ws-hero-mini-icon--coral">C</span>
                            <div>
                                <strong>{{ $isPt ? '1 novo comunicado' : '1 new announcement' }}</strong>
                                <small>{{ $isPt ? 'Reunião de pais — sexta' : 'Parents meeting — Friday' }}</small>
                            </div>
                        </div>
                    </div>
                </div>
                <span class="ws-hero-blob"></span>
            </div>
        </section>

        {{-- Wavy divider --}}
        <div class="ws-wave" aria-hidden="true">
            <svg viewBox="0 0 1440 80" preserveAspectRatio="none">
                <path d="M0,40 C240,0 480,80 720,40 C960,0 1200,80 1440,40 L1440,80 L0,80 Z" fill="#0f4d3a"/>
            </svg>
        </div>

        {{-- ============ PARA QUEM (audiences) ============ --}}
        <section id="para-quem" class="ws-section ws-section--dark" aria-labelledby="para-quem-title">
            <div class="ws-container">
                <header class="ws-section-head">
                    <span class="ws-kicker">{{ $isPt ? 'Para toda a comunidade' : 'For the whole community' }}</span>
                    <h2 id="para-quem-title" class="ws-section-title ws-section-title--light">
                        {{ $isPt ? 'Alunos, famílias e direcção — todos no mesmo sistema.' : 'Students, families and staff — all in one system.' }}
                    </h2>
                </header>

                <div class="ws-audience">
                    <article class="ws-audience-card ws-audience-card--yellow">
                        <div class="ws-audience-icon">
                            <svg viewBox="0 0 48 48" fill="none"><circle cx="24" cy="16" r="9" stroke="currentColor" stroke-width="3"/><path d="M8 42 C 8 32, 16 28, 24 28 S 40 32, 40 42" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                        </div>
                        <h3>{{ $isPt ? 'Alunos' : 'Students' }}</h3>
                        <p>{{ $isPt ? 'Consultam o seu percurso, notas, presenças, calendário e comunicados — desde a 1ª classe.' : 'Check their journey, grades, attendance, calendar and announcements — from grade 1.' }}</p>
                    </article>
                    <article class="ws-audience-card ws-audience-card--coral">
                        <div class="ws-audience-icon">
                            <svg viewBox="0 0 48 48" fill="none"><path d="M12 16 C 12 10, 16 8, 24 8 S 36 10, 36 16 V 30 C 36 36, 30 38, 24 38 S 12 36, 12 30 Z" stroke="currentColor" stroke-width="3"/><path d="M20 22 L 28 22 M 20 28 L 26 28" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                        </div>
                        <h3>{{ $isPt ? 'Encarregados' : 'Guardians' }}</h3>
                        <p>{{ $isPt ? 'Acompanham os educandos: boletins, presenças e comunicados da escola, sem ter de telefonar para a secretaria.' : 'Follow their children: report cards, attendance and school announcements without calling the office.' }}</p>
                    </article>
                    <article class="ws-audience-card ws-audience-card--sky">
                        <div class="ws-audience-icon">
                            <svg viewBox="0 0 48 48" fill="none"><rect x="8" y="10" width="32" height="28" rx="3" stroke="currentColor" stroke-width="3"/><path d="M16 18 L32 18 M16 24 L28 24 M16 30 L24 30" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                        </div>
                        <h3>{{ $isPt ? 'Direcção & secretaria' : 'Direction & office' }}</h3>
                        <p>{{ $isPt ? 'Matrícula, distribuição de turmas, atribuição de professores, pautas trimestrais — sem folhas avulsas.' : 'Enrollment, class assignment, teacher allocation, term gradebooks — no loose sheets.' }}</p>
                    </article>
                </div>
            </div>
        </section>

        {{-- Wavy divider --}}
        <div class="ws-wave ws-wave--up" aria-hidden="true">
            <svg viewBox="0 0 1440 80" preserveAspectRatio="none">
                <path d="M0,40 C240,80 480,0 720,40 C960,80 1200,0 1440,40 L1440,0 L0,0 Z" fill="#0f4d3a"/>
            </svg>
        </div>

        {{-- ============ MÓDULOS ============ --}}
        <section id="modulos" class="ws-section" aria-labelledby="modulos-title">
            <div class="ws-container">
                <header class="ws-section-head">
                    <span class="ws-kicker">{{ $isPt ? 'O que tem dentro' : "What's inside" }}</span>
                    <h2 id="modulos-title" class="ws-section-title">
                        {{ $isPt ? 'Tudo o que uma escola faz — e nada mais.' : 'Everything a school does — and nothing more.' }}
                    </h2>
                </header>

                <div class="ws-modules">
                    <article class="ws-module">
                        <span class="ws-module-badge ws-module-badge--green">01</span>
                        <h3>{{ $isPt ? 'Pessoas' : 'People' }}</h3>
                        <ul>
                            <li>{{ $isPt ? 'Alunos, encarregados, professores, funcionários' : 'Students, guardians, teachers, staff' }}</li>
                            <li>{{ $isPt ? 'BI, naturalidade, ficha completa' : 'ID, place of birth, full record' }}</li>
                            <li>{{ $isPt ? 'Encarregado a vários educandos' : 'Guardian linked to multiple children' }}</li>
                        </ul>
                    </article>
                    <article class="ws-module">
                        <span class="ws-module-badge ws-module-badge--yellow">02</span>
                        <h3>{{ $isPt ? 'Estrutura académica' : 'Academic structure' }}</h3>
                        <ul>
                            <li>{{ $isPt ? 'Anos lectivos + trimestres' : 'Academic years + terms' }}</li>
                            <li>{{ $isPt ? '1ª-13ª classe, cursos do médio' : 'Grade 1-13, secondary courses' }}</li>
                            <li>{{ $isPt ? 'Turmas, salas, turnos, disciplinas' : 'Class groups, rooms, shifts, subjects' }}</li>
                        </ul>
                    </article>
                    <article class="ws-module">
                        <span class="ws-module-badge ws-module-badge--coral">03</span>
                        <h3>{{ $isPt ? 'Operação pedagógica' : 'Pedagogical operation' }}</h3>
                        <ul>
                            <li>{{ $isPt ? 'Aulas, sumários e presenças' : 'Lessons, summaries and attendance' }}</li>
                            <li>{{ $isPt ? 'Avaliações por trimestre' : 'Term-based evaluations' }}</li>
                            <li>{{ $isPt ? 'Pautas por disciplina, turma e anuais' : 'Per-subject, class and annual gradebooks' }}</li>
                        </ul>
                    </article>
                    <article class="ws-module">
                        <span class="ws-module-badge ws-module-badge--sky">04</span>
                        <h3>{{ $isPt ? 'Comunicação & boletins' : 'Communication & report cards' }}</h3>
                        <ul>
                            <li>{{ $isPt ? 'Comunicados segmentados' : 'Targeted announcements' }}</li>
                            <li>{{ $isPt ? 'Boletim individual por aluno' : 'Individual report card per student' }}</li>
                            <li>{{ $isPt ? 'Calendário escolar com eventos' : 'School calendar with events' }}</li>
                        </ul>
                    </article>
                </div>
            </div>
        </section>

        {{-- ============ NÚMEROS ============ --}}
        <section id="numeros" class="ws-section ws-section--soft" aria-labelledby="numeros-title">
            <div class="ws-container">
                <header class="ws-section-head ws-section-head--center">
                    <span class="ws-kicker">{{ $isPt ? 'Já pronto de fábrica' : 'Out of the box' }}</span>
                    <h2 id="numeros-title" class="ws-section-title">
                        {{ $isPt ? 'Uma escola inteira no seed para tu experimentares.' : 'A whole school in the seed so you can explore.' }}
                    </h2>
                </header>

                <div class="ws-stats">
                    <div class="ws-stat">
                        <div class="ws-stat-num" data-count-to="3000">3000</div>
                        <div class="ws-stat-label">{{ $isPt ? 'alunos' : 'students' }}</div>
                        <div class="ws-stat-meta">{{ $isPt ? '5 anos de histórico' : '5 years of history' }}</div>
                    </div>
                    <div class="ws-stat">
                        <div class="ws-stat-num" data-count-to="290">290</div>
                        <div class="ws-stat-label">{{ $isPt ? 'turmas' : 'class groups' }}</div>
                        <div class="ws-stat-meta">{{ $isPt ? '1ª à 13ª classe' : '1st to 13th grade' }}</div>
                    </div>
                    <div class="ws-stat">
                        <div class="ws-stat-num" data-count-to="190">190K</div>
                        <div class="ws-stat-label">{{ $isPt ? 'notas' : 'grades' }}</div>
                        <div class="ws-stat-meta">{{ $isPt ? 'avaliações × matrículas' : 'evals × enrollments' }}</div>
                    </div>
                    <div class="ws-stat">
                        <div class="ws-stat-num" data-count-to="32">32</div>
                        <div class="ws-stat-label">{{ $isPt ? 'disciplinas' : 'subjects' }}</div>
                        <div class="ws-stat-meta">{{ $isPt ? 'currículo por curso' : 'per-course curriculum' }}</div>
                    </div>
                </div>

                <p class="ws-stats-note">
                    <span class="ws-handwritten">{{ $isPt ? 'gerado em ~40 segundos' : 'seeded in ~40 seconds' }}</span>
                    — <code>php artisan migrate:fresh --seed</code>
                </p>
            </div>
        </section>

        {{-- ============ LICENÇA ============ --}}
        <section id="licenca" class="ws-section" aria-labelledby="licenca-title">
            <div class="ws-container">
                <header class="ws-section-head">
                    <span class="ws-kicker">{{ $isPt ? 'Quem pode usar' : 'Who can use it' }}</span>
                    <h2 id="licenca-title" class="ws-section-title">
                        {{ $isPt ? 'Grátis para quem cuida — pago para quem revende.' : 'Free for those who serve — paid for those who resell.' }}
                    </h2>
                </header>

                <div class="ws-license">
                    <article class="ws-license-card ws-license-card--allow">
                        <div class="ws-license-tag">{{ $isPt ? 'Sim' : 'Yes' }}</div>
                        <ul>
                            <li>{{ $isPt ? 'Escolas públicas (todos os níveis)' : 'Public schools (all levels)' }}</li>
                            <li>{{ $isPt ? 'IPSS, ONG, missões, fundações' : 'NGOs, missions, foundations' }}</li>
                            <li>{{ $isPt ? 'Modificar, adaptar, distribuir' : 'Modify, adapt, redistribute' }}</li>
                            <li>{{ $isPt ? 'Contribuir via Pull Request' : 'Contribute via Pull Request' }}</li>
                        </ul>
                    </article>
                    <article class="ws-license-card ws-license-card--deny">
                        <div class="ws-license-tag">{{ $isPt ? 'Não' : 'No' }}</div>
                        <ul>
                            <li>{{ $isPt ? 'Revenda ou sublicenciamento' : 'Resale or sublicensing' }}</li>
                            <li>{{ $isPt ? 'Oferecer como SaaS pago' : 'Offer as paid SaaS' }}</li>
                            <li>{{ $isPt ? 'Cobrar instalação ou suporte' : 'Charge for install or support' }}</li>
                            <li>{{ $isPt ? 'Escolas privadas com fins lucrativos' : 'For-profit private schools' }}</li>
                        </ul>
                    </article>
                </div>

                <a href="https://github.com/arseniomuanda/gestSchool/blob/main/LICENSE" target="_blank" rel="noopener" class="ws-btn ws-btn--ghost ws-license-link">
                    {{ $isPt ? 'Ler licença completa' : 'Read full license' }} <span aria-hidden="true">↗</span>
                </a>
            </div>
        </section>

        {{-- ============ FINAL CTA ============ --}}
        <section class="ws-cta-section" aria-labelledby="cta-title">
            <div class="ws-cta-block">
                <span class="ws-cta-floater ws-cta-floater--a">✦</span>
                <span class="ws-cta-floater ws-cta-floater--b">◯</span>
                <h2 id="cta-title">
                    {{ $isPt ? 'Pronto para experimentar uma escola viva?' : 'Ready to explore a living school?' }}
                </h2>
                <p>
                    {{ $isPt ? 'Faz login com qualquer perfil demo. A password é sempre' : 'Sign in with any demo account. Password is always' }}
                    <code>password</code>.
                </p>
                <div class="ws-cta-actions">
                    @auth
                        <a href="{{ route('dashboard') }}" class="ws-btn ws-btn--yellow">
                            {{ __('Open dashboard') }} <span aria-hidden="true">→</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="ws-btn ws-btn--yellow">
                            {{ __('Sign in') }} <span aria-hidden="true">→</span>
                        </a>
                    @endauth
                    <a href="https://github.com/arseniomuanda/gestSchool" target="_blank" rel="noopener" class="ws-btn ws-btn--ghost-light">
                        GitHub ↗
                    </a>
                </div>
            </div>
        </section>
    </main>

    {{-- ============ FOOTER ============ --}}
    <footer class="ws-footer">
        <div class="ws-container">
            <div class="ws-footer-row">
                <span class="ws-footer-brand">◯ gestSchool</span>
                <span>{{ $isPt ? 'Construído por' : 'Built by' }}
                    <a href="https://www.linkedin.com/in/ars%C3%A9nio-muanda-91808518b/" target="_blank" rel="noopener">Arsénio Muanda</a>
                </span>
                <span>© {{ date('Y') }} · Luanda, Angola</span>
            </div>
            <div class="ws-footer-row ws-footer-row--links">
                <a href="{{ url('/privacidade') }}">{{ $isPt ? 'Política de privacidade' : 'Privacy policy' }}</a>
                <a href="https://github.com/arseniomuanda/gestSchool/blob/main/LICENSE" target="_blank" rel="noopener">{{ $isPt ? 'Licença' : 'License' }}</a>
                <a href="https://github.com/arseniomuanda/gestSchool" target="_blank" rel="noopener">GitHub</a>
            </div>
        </div>
    </footer>

    <script>
        (function () {
            if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            const els = document.querySelectorAll('[data-count-to]');
            if (!els.length) return;
            const io = new IntersectionObserver((entries) => {
                entries.forEach((e) => {
                    if (!e.isIntersecting) return;
                    const el = e.target;
                    io.unobserve(el);
                    const target = parseInt(el.dataset.countTo, 10) || 0;
                    const original = el.textContent;
                    const suffix = original.replace(/[\d,\.]/g, '');
                    const duration = 1100;
                    const start = performance.now();
                    function tick(now) {
                        const t = Math.min(1, (now - start) / duration);
                        const eased = 1 - Math.pow(1 - t, 3);
                        const val = Math.round(target * eased);
                        el.textContent = val.toLocaleString('pt-PT') + suffix;
                        if (t < 1) requestAnimationFrame(tick);
                        else el.textContent = original;
                    }
                    requestAnimationFrame(tick);
                });
            }, { threshold: 0.4 });
            els.forEach((el) => io.observe(el));
        })();
    </script>
</body>
</html>
