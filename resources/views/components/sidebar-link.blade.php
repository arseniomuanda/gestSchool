@props([
    'href' => '#',
    'icon' => null,
    'active' => false,
    'label' => null,
    'disabled' => false,
    'badge' => null,    // ex: "soon", "novo", "beta"
])

@php
    $tooltip = $label ?? trim(strip_tags($slot));
@endphp

@if($disabled)
    <span title="{{ $tooltip }}" class="sidebar-link is-disabled" aria-disabled="true">
        @if($icon)<x-dynamic-component :component="'lucide-' . $icon" class="sidebar-icon" />@endif
        <span>{{ $slot }}</span>
        @if($badge)<span class="sidebar-badge">{{ $badge }}</span>@endif
    </span>
@else
    <a href="{{ $href }}" title="{{ $tooltip }}" {{ $attributes->class(['sidebar-link', 'is-active' => $active]) }}>
        @if($icon)<x-dynamic-component :component="'lucide-' . $icon" class="sidebar-icon" />@endif
        <span>{{ $slot }}</span>
        @if($badge)<span class="sidebar-badge">{{ $badge }}</span>@endif
    </a>
@endif
