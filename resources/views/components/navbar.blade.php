@php $user = auth()->user(); @endphp

<header class="navbar">
    <a href="{{ route('dashboard') }}" class="navbar-brand">
        <span class="navbar-brand-text">GestSchool</span>
    </a>

    <div class="navbar-inner">
        <button type="button"
                class="lg:hidden navbar-icon-btn"
                x-on:click="sidebarOpen = true"
                aria-label="{{ __('Open menu') }}">
            <x-lucide-menu class="w-5 h-5" />
        </button>

        <button type="button" class="hidden lg:inline-flex sidebar-toggle-btn" data-sb-toggle-collapse aria-label="{{ __('Toggle sidebar') }}" title="{{ __('Toggle sidebar') }} (Ctrl+B)">
            <x-lucide-panel-left class="sidebar-toggle-icon" />
        </button>

        <button
            type="button"
            class="hidden md:flex items-center gap-2 text-sm text-muted hover:text-navy px-3 py-1.5 border border-gray-200 rounded-btn min-w-[180px] transition"
            x-data
            @click="$dispatch('open-smart-search')"
            title="{{ __('Search') }} (Ctrl+K)"
        >
            <x-lucide-search class="w-4 h-4 shrink-0" />
            <span class="hidden xl:inline flex-1 text-left">{{ __('Search…') }}</span>
            <kbd class="hidden xl:inline-flex">⌘K</kbd>
        </button>

        <div class="navbar-actions">
            <div class="flex items-center text-xs gap-1">
                <a href="{{ route('locale.switch', 'pt') }}" class="px-2 py-1 rounded {{ app()->getLocale() === 'pt' ? 'bg-navy text-white' : 'text-muted hover:text-navy' }}">PT</a>
                <a href="{{ route('locale.switch', 'en') }}" class="px-2 py-1 rounded {{ app()->getLocale() === 'en' ? 'bg-navy text-white' : 'text-muted hover:text-navy' }}">EN</a>
            </div>

            <button type="button" class="navbar-icon-btn relative">
                <x-lucide-bell class="w-5 h-5" />
            </button>

            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button @click="open = !open" type="button" class="flex items-center gap-2 px-2 py-1.5 hover:bg-gray-100 rounded-btn transition">
                    <span class="w-8 h-8 rounded-full bg-primary-soft text-primary inline-flex items-center justify-center font-semibold text-xs">
                        {{ collect(explode(' ', $user?->name ?? ''))->take(2)->map(fn($w) => substr($w, 0, 1))->join('') }}
                    </span>
                    <span class="hidden sm:block text-sm font-semibold text-navy">{{ $user?->name }}</span>
                    <x-lucide-chevron-down class="w-4 h-4 text-muted" />
                </button>

                <div x-show="open" x-cloak x-transition class="dropdown end-0">
                    <div class="px-4 py-3 border-b border-gray-100">
                        <div class="text-xs text-muted">{{ __('Active session') }}</div>
                        @foreach($user?->roles ?? [] as $r)
                            <span class="badge badge-muted mt-1 me-1">{{ __($r->name) }}</span>
                        @endforeach
                    </div>
                    <a href="{{ route('profile.edit') }}" class="dropdown-item">{{ __('Profile') }}</a>
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item w-full text-start text-danger">{{ __('Log Out') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
