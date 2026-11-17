@extends('layouts.app')

@section('title', 'TADA Definer Master By Level')

@section('content')
<div class="page">
    <x-page-header title="TADA Definer Master By Level" subtitle="TADA rates per employee level (USD)" />

    <x-data-table :headings="['Level Name', 'TADA (USD)']">
        @forelse ($levels as $row)
            <tr>
                <td>{{ $row->TadaDefinerMasterBylevel_name }}</td>
                <td class="num">{{ $row->tadaInUSD }}</td>
            </tr>
        @empty
            <x-empty-row colspan="2" message="No records found." />
        @endforelse
    </x-data-table>
</div>
@endsection
