{{-- Nepali date picker + AD<->BS sync for the fiscal year pages. --}}
@push('styles')
<link href="{{ asset('calender/nepali.datepicker.v5.0.6.min.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<script src="{{ asset('calender/nepali.datepicker.v5.0.6.min.js') }}"></script>
<script src="{{ \App\Support\Asset::v('js/fiscal_year/bs-dates.js') }}"></script>
@endpush
