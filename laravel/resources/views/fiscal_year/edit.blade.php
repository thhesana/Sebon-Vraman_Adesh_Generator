@extends('layouts.app')

@section('title', 'Edit Fiscal Year')

@push('styles')
<style>
    .fy-edit-card { width: 100%; max-width: 400px; padding: 20px; background-color: #f8f9fa; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
    .fy-edit-card h2 { color: #007bff; margin-bottom: 20px; font-size: 24px; }
    .fy-edit-card label { font-weight: bold; }
</style>
@endpush

@section('content')
<div class="container d-flex justify-content-center align-items-center" style="min-height: 60vh;">
    <div class="fy-edit-card">
        <h2 class="text-center">Edit Fiscal Year</h2>
        <form method="POST" action="{{ route('fiscal_years.update', $row) }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label for="fy" class="form-label">Fiscal Year:</label>
                <input type="text" class="form-control" id="fy" name="fy" value="{{ old('fy', $row->fy) }}" required>
            </div>
            <div class="mb-3">
                <label for="fy_startdate" class="form-label">Start Date:</label>
                <input type="date" class="form-control" id="fy_startdate" name="fy_startdate" value="{{ old('fy_startdate', \Carbon\Carbon::parse($row->fy_startdate)->format('Y-m-d')) }}" required>
            </div>
            <div class="mb-3">
                <label for="fy_enddate" class="form-label">End Date:</label>
                <input type="date" class="form-control" id="fy_enddate" name="fy_enddate" value="{{ old('fy_enddate', \Carbon\Carbon::parse($row->fy_enddate)->format('Y-m-d')) }}" required>
            </div>
            <div class="mb-3">
                <label for="fy_status" class="form-label">Status:</label>
                @php $status = old('fy_status', $row->fy_status); @endphp
                <select class="form-select" id="fy_status" name="fy_status" required>
                    <option value="ACTIVE" {{ $status == 'ACTIVE' ? 'selected' : '' }}>ACTIVE</option>
                    <option value="INACTIVE" {{ $status == 'INACTIVE' ? 'selected' : '' }}>INACTIVE</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">Update Fiscal Year</button>
        </form>
    </div>
</div>
@endsection
