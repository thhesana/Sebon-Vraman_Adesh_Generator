@extends('layouts.app')

@section('title', 'Position Master')

@section('content')
<div class="page">
    <x-page-header title="Position Master" subtitle="Positions (पद) used for employees and printed travel orders">
        <a href="{{ route('positions.create') }}" class="btn btn-primary">Add Position</a>
    </x-page-header>

    <x-search-form :action="route('positions.index')" :search="$search" placeholder="Search position (English or Nepali)..." />

    @if ($search !== '')
        <p class="text-muted">Showing results for: <strong>{{ $search }}</strong> ({{ $positions->total() }} found)</p>
    @endif

    <x-data-table id="positionTable" :headings="['S.N.', 'Position (English)', 'Position (Nepali)', 'Employees', 'Action']">
        @forelse ($positions as $row)
            <tr>
                <td class="num">{{ $positions->firstItem() + $loop->index }}</td>
                <td>{{ $row->designationType }}</td>
                <td lang="ne">{{ $row->designationTypeInNepali ?: '-' }}</td>
                <td class="num">{{ $row->employees_count }}</td>
                <td>
                    <a href="{{ route('positions.edit', $row) }}" class="btn btn-sm btn-primary">Edit</a>
                </td>
            </tr>
        @empty
            <x-empty-row colspan="5" message="No positions found" />
        @endforelse
    </x-data-table>

    <x-pagination :paginator="$positions" />
</div>
@endsection
