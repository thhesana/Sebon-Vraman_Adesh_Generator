@extends('layouts.app')

@section('title', 'Add Employee')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<h2 class="text-center mt-4">Add Employee</h2>
<div class="container mt-3">
@if ($errors->any())
    <div class="alert alert-danger">
        @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
@endif
<form method="POST" action="{{ url('/employee_add.php') }}">
    @csrf

    <div class="mb-3">
        <label>Employee Code:</label>
        <input type="text" name="EmpPersonalCode" value="{{ old('EmpPersonalCode') }}" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Name in Nepali:</label>
        <input type="text" name="EmpNameInNepali" value="{{ old('EmpNameInNepali') }}" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Name in English:</label>
        <input type="text" name="EmpName" value="{{ old('EmpName') }}" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Designation:</label>
        <select name="Designation" id="designation" class="form-control" required>
            <option value="">-- Select Designation --</option>
            @foreach ($designations as $d)
                <option value="{{ $d->designationType }}" {{ old('Designation') == $d->designationType ? 'selected' : '' }}>{{ $d->designationType }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label>Level:</label>
        <select name="LevelName" id="level" class="form-control" required>
            <option value="">-- Select Level --</option>
            @foreach ($levels as $l)
                <option value="{{ $l->levelName }}" {{ old('LevelName') == $l->levelName ? 'selected' : '' }}>{{ $l->levelName }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label>Gender:</label>
        <select name="Gender" class="form-control" required>
            <option value="">-- Select Gender --</option>
            <option {{ old('Gender') == 'Male' ? 'selected' : '' }}>Male</option>
            <option {{ old('Gender') == 'Female' ? 'selected' : '' }}>Female</option>
            <option {{ old('Gender') == 'Other' ? 'selected' : '' }}>Other</option>
        </select>
    </div>

    <div class="mb-3">
        <label>Email:</label>
        <input type="email" name="Email" value="{{ old('Email') }}" class="form-control" required>
    </div>

    <button class="btn btn-primary">Save Employee</button>
    <a href="{{ url('/employee_view.php') }}" class="btn btn-secondary">Back</a>

</form>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('#designation').select2();
    $('#level').select2();
});
</script>
@endpush
