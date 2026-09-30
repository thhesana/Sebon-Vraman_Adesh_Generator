@extends('layouts.app')

@section('title', 'Add Employee')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="page page-narrow">
    <x-page-header title="Add Employee" subtitle="Register a new employee" />

    <div class="card">
        <div class="card-header">Employee Details</div>
        <form method="POST" action="{{ route('employees.store') }}">
            @csrf

            <div class="card-body">
                @include('employee._form')
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset('js/employee/form.js') }}"></script>
@endpush
