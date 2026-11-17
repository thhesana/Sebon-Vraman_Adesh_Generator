@extends('layouts.app')

@section('title', "Add Old Int'l Travel Record")

@section('content')
<div class="page intl-form old-intl-form">
    <x-page-header title="Add Old International Travel Record" subtitle="Enter the figures exactly as written on the old travel order.">
        <a href="{{ route('old_international.index') }}" class="btn btn-secondary">Back to Records</a>
    </x-page-header>

    <form method="POST" action="{{ route('old_international.store') }}" id="oldIntlForm">
        @csrf
        @include('old_international._form', ['submitText' => 'Save Record'])
    </form>
</div>
@endsection
