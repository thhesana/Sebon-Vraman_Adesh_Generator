@extends('layouts.app')

@section('title', 'Employee List')

@section('content')
<h2 class="text-center mt-4">Employee List</h2>
<div class="container mt-3">

    <a href="{{ url('/employee_add.php') }}" class="btn btn-success mb-3 float-end">Add New Employee</a>

    <input type="text" id="searchInput" class="form-control mb-3" placeholder="Search employees...">

    <table class="table table-bordered table-striped" id="empTable">
        <thead class="bg-primary text-white">
            <tr>
                <th>S.N.</th>
                <th style="display:none;">Code</th>
                <th>Name</th>
                <th>Name (Nepali)</th>
                <th>Designation</th>
                <th>Level</th>
                <th>Gender</th>
                <th>Email</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($employees as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td style="display:none;">{{ $row->EmpPersonalCode }}</td>
                    <td>{{ $row->EmpName }}</td>
                    <td>{{ $row->EmpNameInNepali }}</td>
                    <td>{{ $row->Designation }}</td>
                    <td>{{ $row->LevelName }}</td>
                    <td>{{ $row->Gender }}</td>
                    <td>{{ $row->Email }}</td>
                    <td>
                        <a href="{{ url('/employee_edit.php') }}?code={{ urlencode($row->EmpPersonalCode) }}"
                           class="btn btn-primary btn-sm">Edit</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
<script>
document.getElementById("searchInput").addEventListener("keyup", function() {
    let value = this.value.toLowerCase();
    let rows = document.querySelectorAll("#empTable tbody tr");

    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(value) ? "" : "none";
    });
});
</script>
@endpush
