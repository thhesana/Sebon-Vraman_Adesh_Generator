<!DOCTYPE html>
<html lang="ne">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>भ्रमण आदेश - Batch {{ $batchId }}</title>
    <script src="https://nepalidatepicker.sajanmaharjan.com.np/v5/nepali.datepicker/js/nepali.datepicker.v5.0.6.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">
    <link href="{{ asset('css/domestic/print.css') }}" rel="stylesheet">
</head>
<body>

<button id="printAll" type="button" class="btn btn-primary print-button no-print">Print All ({{ $records->count() }} records)</button>

@foreach ($records as $record)
    @include('domestic.partials.print-order', ['record' => $record, 'isLast' => $loop->last])
@endforeach

<script src="{{ asset('js/domestic/print.js') }}"></script>
</body>
</html>
