<x-app-layout>
    @php
        $meses = [1=>__('January'),2=>__('February'),3=>__('March'),4=>__('April'),5=>__('May'),6=>__('June'),7=>__('July'),8=>__('August'),9=>__('September'),10=>__('October'),11=>__('November'),12=>__('December')];
        $diasSem = [__('Mon'),__('Tue'),__('Wed'),__('Thu'),__('Fri'),__('Sat'),__('Sun')];
        $prevMonth = $inicio->copy()->subMonth();
        $nextMonth = $inicio->copy()->addMonth();
        $today = now()->toDateString();
    @endphp

    <x-page-header :title="__('School Calendar')" :subtitle="$meses[$mes] . ' ' . $ano">
        <x-slot name="actions">
            <x-btn variant="danger" icon="file-down" :href="route('eventos.pdf', ['ano' => $ano, 'mes' => $mes])">{{ __('Export PDF') }}</x-btn>
            @if($podeGerir)
                <x-btn variant="primary" icon="plus" :href="route('eventos.create')">{{ __('New') }}</x-btn>
            @endif
        </x-slot>
    </x-page-header>

    {{-- Navegação --}}
    <x-card>
        <div class="flex items-center justify-between mb-4">
            <a href="{{ route('eventos.index', ['ano' => $prevMonth->year, 'mes' => $prevMonth->month]) }}" class="btn btn-secondary btn-sm">
                <x-lucide-chevron-left class="w-4 h-4" /> {{ $meses[$prevMonth->month] }}
            </a>
            <h2 class="text-lg font-bold text-navy">{{ $meses[$mes] }} {{ $ano }}</h2>
            <a href="{{ route('eventos.index', ['ano' => $nextMonth->year, 'mes' => $nextMonth->month]) }}" class="btn btn-secondary btn-sm">
                {{ $meses[$nextMonth->month] }} <x-lucide-chevron-right class="w-4 h-4" />
            </a>
        </div>

        {{-- Grid mensal --}}
        <table class="w-full border-collapse table-fixed text-xs">
            <thead>
                <tr>
                    @foreach($diasSem as $d)<th class="border border-gray-200 bg-gray-50 py-2 text-muted font-semibold">{{ $d }}</th>@endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($semanas as $semana)
                    <tr>
                        @foreach($semana as $dia)
                            @php
                                $dateStr = $dia->toDateString();
                                $inMonth = $dia->month === $mes;
                                $isToday = $dateStr === $today;
                                $evs = $porDia[$dateStr] ?? [];
                            @endphp
                            <td class="border border-gray-200 align-top h-24 p-1 {{ $inMonth ? '' : 'bg-gray-50' }}">
                                <div class="flex justify-end">
                                    <span class="text-xs {{ $isToday ? 'inline-flex items-center justify-center w-6 h-6 rounded-full bg-primary text-white font-bold' : ($inMonth ? 'text-navy' : 'text-muted') }}">
                                        {{ $dia->day }}
                                    </span>
                                </div>
                                @foreach($evs as $ev)
                                    <a href="{{ route('eventos.show', $ev) }}" class="block text-[10px] mt-1 px-1.5 py-0.5 rounded truncate"
                                       style="background-color: {{ $ev->cor_efectiva }}20; color: {{ $ev->cor_efectiva }}; border-left: 3px solid {{ $ev->cor_efectiva }};"
                                       title="{{ $ev->titulo }} ({{ $ev->tipo_nome }})">
                                        {{ $ev->titulo }}
                                    </a>
                                @endforeach
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Legenda --}}
        <div class="hidden sm:flex flex-wrap gap-3 mt-6 text-xs">
            @foreach(config('escola.tipos_evento') as $key => $info)
                <span class="inline-flex items-center gap-1">
                    <span class="w-3 h-3 rounded" style="background-color: {{ $info['cor'] }};"></span>
                    {{ $info['nome'] }}
                </span>
            @endforeach
        </div>
    </x-card>

    {{-- Lista de eventos do mês --}}
    <x-card :title="__('Events this month')">
        @if($mesEventos->isEmpty())
            <x-empty :title="__('No events this month')" icon="calendar-x" />
        @else
            <ul class="divide-y divide-gray-100">
                @foreach($mesEventos as $ev)
                    <li class="py-3 flex items-center gap-3">
                        <div class="w-2 h-12 rounded" style="background-color: {{ $ev->cor_efectiva }};"></div>
                        <div class="flex-1">
                            <a href="{{ route('eventos.show', $ev) }}" class="text-navy font-semibold hover:underline">{{ $ev->titulo }}</a>
                            <div class="text-xs text-muted mt-0.5">
                                {{ $ev->data_inicio->format('d/m') }}
                                @if($ev->data_fim && $ev->data_fim->ne($ev->data_inicio)) – {{ $ev->data_fim->format('d/m') }}@endif
                                · <span style="color: {{ $ev->cor_efectiva }};">{{ $ev->tipo_nome }}</span>
                                @if($ev->turma) · <x-turma-label :turma="$ev->turma" :inline="true" />
                                @elseif($ev->classe) · {{ $ev->classe->nome }} @endif
                            </div>
                        </div>
                        @if($podeGerir)
                            <a href="{{ route('eventos.edit', $ev) }}" class="btn-link">{{ __('Edit') }}</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>
</x-app-layout>
