{{-- Main navigation. Groups: label + items [label, route, active-route patterns]. --}}
@php
    $nav = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => ['dashboard']],
        ['label' => 'Vraman', 'active' => ['domestic.index', 'domestic.create', 'domestic.edit', 'international.index', 'international.create', 'international.edit'], 'items' => [
            ['Domestic Vraman', 'domestic.index', ['domestic.index', 'domestic.create', 'domestic.edit']],
            ["Int'l Vraman", 'international.index', ['international.index', 'international.create', 'international.edit'], 'intlVramanTab'],
        ]],
        ['label' => 'USD Rate', 'route' => 'usd.rates', 'active' => ['usd.*']],
        ['label' => 'Reports', 'active' => ['domestic.report', 'international.report', 'international.history'], 'items' => [
            ['Domestic Vraman Report', 'domestic.report', ['domestic.report']],
            ["Int'l Vraman Report", 'international.report', ['international.report']],
            ["Int'l Travel History", 'international.history', ['international.history']],
        ]],
        ['label' => 'Masters', 'active' => ['levels.*', 'countries.*', 'cities.*', 'districts.*', 'employees.*', 'fiscal_years.*'], 'items' => [
            ['Level Master', 'levels.index', ['levels.*']],
            ['Country', 'countries.index', ['countries.*']],
            ['City', 'cities.index', ['cities.*']],
            ['District', 'districts.index', ['districts.*']],
            ['Employee', 'employees.index', ['employees.*']],
            ['Fiscal Year', 'fiscal_years.index', ['fiscal_years.*']],
        ]],
    ];
@endphp
<nav class="mainnav" aria-label="Main navigation">
    <ul class="mainnav-inner nav">
        @foreach ($nav as $entry)
            @php $isActive = request()->routeIs(...$entry['active']); @endphp
            @if (isset($entry['items']))
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle {{ $isActive ? 'active' : '' }}" role="button" data-bs-toggle="dropdown" aria-expanded="false">{{ $entry['label'] }}</a>
                    <ul class="dropdown-menu">
                        @foreach ($entry['items'] as $item)
                            <li>
                                <a href="{{ route($item[1]) }}"
                                   class="dropdown-item {{ request()->routeIs(...$item[2]) ? 'active' : '' }}"
                                   @if (isset($item[3])) id="{{ $item[3] }}" data-usd-url="{{ route('usd.converter') }}" @endif>{{ $item[0] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </li>
            @else
                <li class="nav-item">
                    <a href="{{ route($entry['route']) }}" class="nav-link {{ $isActive ? 'active' : '' }}">{{ $entry['label'] }}</a>
                </li>
            @endif
        @endforeach
    </ul>
</nav>
