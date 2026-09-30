@extends('layouts.app')

@section('title', 'Employee List')

@push('styles')
<link href="{{ asset('css/employee/index.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="page">
    <x-page-header title="Employee List" subtitle="Search filters the list as you type">
        <a href="{{ route('employees.create') }}" class="btn btn-primary">Add Employee</a>
    </x-page-header>

    <div class="toolbar">
        <input type="search" id="searchInput" class="form-control employee-search" placeholder="Search employees..." aria-label="Search employees">
    </div>

    <x-data-table id="empTable" :headings="['S.N.', 'Name', 'Name (Nepali)', 'Designation', 'Level', 'Gender', 'Email', 'Action']">
        @forelse ($employees as $row)
            <tr>
                <td class="num">{{ $loop->iteration }}</td>
                <td>{{ $row->EmpName }}</td>
                <td><span class="nepali" lang="ne">{{ $row->EmpNameInNepali }}</span></td>
                <td>{{ $row->Designation }}</td>
                <td>{{ $row->LevelName }}</td>
                <td>{{ $row->Gender }}</td>
                <td>{{ $row->Email }}</td>
                <td>
                    <a href="{{ route('employees.edit', $row) }}" class="btn btn-sm btn-primary">Edit</a>
                </td>
            </tr>
        @empty
            <x-empty-row colspan="8" message="No employees found" />
        @endforelse
    </x-data-table>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/employee/index.js') }}"></script>
@endpush
