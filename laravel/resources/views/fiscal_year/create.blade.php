@extends('layouts.app')

@section('title', 'Add New Fiscal Year')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow rounded-4">
                <div class="card-header bg-primary text-white text-center rounded-top-4">
                    <h4 class="mb-0">Add New Fiscal Year</h4>
                </div>
                <div class="card-body">
                    <form action="{{ url('/add_fiscal_year.php') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="fy" class="form-label">Fiscal Year</label>
                            <input type="text" name="fy" id="fy" value="{{ old('fy') }}" class="form-control" placeholder="Example: 2082/83" required>
                        </div>
                        <div class="mb-3">
                            <label for="fy_startdate" class="form-label">Start Date</label>
                            <input type="date" name="fy_startdate" id="fy_startdate" value="{{ old('fy_startdate') }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="fy_enddate" class="form-label">End Date</label>
                            <input type="date" name="fy_enddate" id="fy_enddate" value="{{ old('fy_enddate') }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="fy_status" class="form-label">Status</label>
                            <select name="fy_status" id="fy_status" class="form-select" required>
                                <option value="">-- Select Status --</option>
                                <option value="ACTIVE" {{ old('fy_status') == 'ACTIVE' ? 'selected' : '' }}>ACTIVE</option>
                                <option value="INACTIVE" {{ old('fy_status') == 'INACTIVE' ? 'selected' : '' }}>INACTIVE</option>
                            </select>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg">Add Fiscal Year</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
