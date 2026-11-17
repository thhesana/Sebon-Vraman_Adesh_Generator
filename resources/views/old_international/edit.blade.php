@extends('layouts.app')

@section('title', "Edit Old Int'l Travel Record")

@section('content')
<div class="page intl-form old-intl-form">
    <x-page-header title="Edit Old International Travel Record" subtitle="Chalani #: {{ $record->order_number }} · {{ $record->employee?->EmpName ?? $record->EmpPersonalCode }}">
        <a href="{{ route('old_international.print', $record) }}" class="btn btn-outline-primary" target="_blank" rel="noopener">Print</a>
        <a href="{{ route('old_international.index') }}" class="btn btn-secondary">Back to Records</a>
    </x-page-header>

    <form method="POST" action="{{ route('old_international.update', $record) }}" id="oldIntlForm">
        @csrf
        @method('PUT')
        @include('old_international._form', ['submitText' => 'Update Record'])
    </form>
</div>
@endsection
