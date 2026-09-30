@extends('layouts.app')

@section('title', 'TADA Definer Master By Level')

@section('content')
<div class="container mt-4">
    <h3 class="text-center mb-4">TADA Definer Master By Level</h3>

    <table class="table table-bordered table-striped">
        <thead class="bg-primary text-white">
            <tr>
                <th>Level Name</th>
                <th>TADA (USD) </th>
            </tr>
        </thead>
        <tbody>
        @foreach ($levels as $row)
            <tr>
                <td>{{ $row->TadaDefinerMasterBylevel_name }}</td>
                <td>{{ $row->tadaInUSD }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
