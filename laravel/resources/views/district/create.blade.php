@extends('layouts.app')

@section('title', 'Add New District')

@push('styles')
<style>
    body { background-color: #f5f7fa; }
    .district-form { width: 45%; margin: 40px auto; background: #ffffff; padding: 20px; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
    .district-form h2 { text-align: center; color: #004080; margin-bottom: 20px; }
    .district-form .form-group { margin-bottom: 15px; }
    .district-form label { display: block; margin-bottom: 5px; font-weight: bold; }
    .district-form input[type="text"] { width: 100%; padding: 8px; box-sizing: border-box; }
    .district-form .btn-group { text-align: center; margin-top: 15px; display: block; }
    .district-form .btn-save { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; background-color: #004080; color: #ffffff; }
    .district-form .btn-save:hover { background-color: #003060; }
    .district-form .btn-back { background-color: #6c757d; color: #ffffff; text-decoration: none; padding: 8px 16px; border-radius: 4px; margin-left: 6px; }
    .district-form .btn-back:hover { background-color: #5a6268; }
    .district-form .error { background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 10px; border-radius: 4px; }
    .district-form .success { background: #d4edda; color: #155724; padding: 10px; margin-bottom: 10px; border-radius: 4px; }
</style>
@endpush

@section('content')
<div class="district-form">

    <h2>Add New District</h2>

    <form method="POST" action="{{ route('districts.store') }}">
        @csrf
        <div class="form-group">
            <label>District Name (English)</label>
            <input type="text" name="district_name" value="{{ old('district_name') }}" required>
        </div>

        <div class="form-group">
            <label>District Name (Nepali)</label>
            <input type="text" name="district_name_nepali" value="{{ old('district_name_nepali') }}" required>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn-save">Save</button>
            <a href="{{ route('districts.index') }}" class="btn-back">Back</a>
        </div>
    </form>

</div>
@endsection
