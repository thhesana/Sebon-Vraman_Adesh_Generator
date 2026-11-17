<!DOCTYPE html>
<html lang="ne">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Old International TADA Form - {{ $record->order_number }}</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="{{ \App\Support\Asset::v('css/theme.css') }}" rel="stylesheet">
<link href="{{ \App\Support\Asset::v('css/international/print.css') }}" rel="stylesheet">
</head>
<body>
@php
    // Every figure comes from what was entered; nothing is recalculated here.
    $emp = $record->employee;
    $empNameNepali = $emp?->EmpNameInNepali ?? $emp?->EmpName ?? '-';
    // Position as entered on the form (Nepali name when the master has one).
    $designation = $record->designation?->designationTypeInNepali ?: ($record->designation?->designationType ?? '-');
    $place = collect([$record->city?->City_name, $record->country?->Country_name])->filter()->implode(', ');
    $rateDate = $record->exchange_rate_dateBS;
    // Stored BS 'YYYY-MM-DD' printed as YYYY/MM/DD.
    $bs = fn (?string $date) => $date ? str_replace('-', '/', $date) : '-';
    $dress = $record->dress_amount;
    $grandTotal = $record->grand_total;
@endphp

<div class="print-controls no-print">
    <span class="badge badge-soft-warning">Old record (manual entry)</span>
    <button type="button" class="btn btn-primary" id="printBtn">Print Form</button>
</div>

<div class="print-container nepali" lang="ne">
    <div class="header">
        <div class="header-line">अनुसूची – ५</div>
        <div class="header-line">(नियम ३८ को उपनियम (३) सँग सम्बन्धित)</div>
        <div class="header-line">भ्रमण आदेश</div>
        <div class="header-line">अन्तरदेशिय । अन्तर्राष्ट्रिय</div>
    </div>
    <div class="header-number-date">
        संख्या: {{ $record->order_number }}
        <br>
        मिति: {{ $bs($record->form_dateBS) }}
    </div>
    <div class="form-info">
        <div>१. भ्रमण गर्ने पदाधिकारी वा कर्मचारीको नाम: श्री {{ $empNameNepali }}</div>
        <div>२. पद: {{ $designation }}</div>
        <div>३. कार्यालय: नेपाल धितोपत्र बोर्ड</div>
        <div>४. भ्रमण गर्ने स्थान (विदेश भए मुलुक र शहर खुलाउने): {{ $place }}</div>
        <div>५. भ्रमणको उद्देश्य: {{ $record->travel_objective }}</div>
        <div>
            ६. भ्रमण गर्ने अवधि:
            {{ $bs($record->travelDateStartBS) }} देखि
            {{ $bs($record->travelDateEndBS) }} (तदनुसार
            <span class="bs-to-ad" data-bs="{{ $record->travelDateStartBS }}"></span> देखि
            <span class="bs-to-ad" data-bs="{{ $record->travelDateEndBS }}"></span>) सम्म
            <x-international.amount :value="$record->total_days" blank="N/A" /> दिन ।
        </div>
        <div>७. भ्रमण गर्ने साधन: हवाईजहाज तथा ट्याक्सी ।</div>
        <div>८. भ्रमणको निमित्त माग गरेको पेश्की: रु. {{ number_format($grandTotal, 2) }}/-</div>
    </div>
    <table class="doc-table">
        <thead>
            <tr>
                <th class="col-sn">सि.नं.</th>
                <th class="col-desc">विवरण</th>
                <th class="col-rate">दर <br>(यु.एस.डि.)</th>
                <th class="col-days">दिन</th>
                <th class="col-usd">जम्मा भत्ता<br>(यु.एस.डि.)</th>
                <th class="col-fx">विनिमय दर<br>(मिति {{ $bs($rateDate) }})</th>
                <th class="col-npr">जम्मा रकम रु.</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">१</td>
                <td>दैनिक भत्ता<br/><small>(नेपाल धितोपत्र बोर्ड आर्थिक प्रशासन सम्बन्धी नियमावली २०६६ को नियम ४७ को व्यवस्था बमोजिम। )</small></td>
                <td class="text-right"><x-international.amount :value="$record->daily_rate_usd" blank="N/A" /></td>
                <td class="text-center">{{ number_format((float) $record->total_days, 2) }}</td>
                <td class="text-right">{{ number_format((float) $record->total_usd, 2) }}</td>
                <td class="text-right">{{ number_format((float) $record->exchange_rate, 2) }}</td>
                <td class="text-right">{{ number_format((float) $record->total_npr, 2) }}</td>
            </tr>
            <tr class="row-tall">
                <td class="text-center">२</td>
                <td>लुगा भत्ता<br/><small>(नेपाल धितोपत्र बोर्ड आर्थिक प्रशासन सम्बन्धी नियमावली २०६६ को नियम ४८ को व्यवस्था बमोजिम। )</small></td>
                @if ($dress > 0)
                <td colspan="4"></td>
                <td class="text-right">{{ number_format($dress, 2) }}</td>
                @else
                <td colspan="5" class="text-center"><em>दुई वर्ष पूरा नभएको ।</em></td>
                @endif
            </tr>
            <tr>
                <td colspan="6" class="text-right">जम्मा</td>
                <td class="text-right">{{ number_format($grandTotal, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="bottom-section">
        <div class="bottom-left">
            <div class="gap-sm">९. भ्रमण सम्बन्धी अन्य आदेश:</div>
            <div class="sign-dots">.............................................</div>
            <div>भ्रमण आदेश दिने अधिकारी</div>
            @if (filled($record->verifier?->tadaverifierPost))
                <div>{{ $record->verifier->tadaverifierPost }}</div>
            @endif
            <div>मिति: {{ $bs($record->form_dateBS) }}</div>
            <div class="gap-lg">बोधार्थ:</div>
            <div>१. श्री लेखा तथा वित्त शाखा</div>
        </div>

        <div class="bottom-right">
            <div class="bottom-right-title">(लेखा तथा वित्त शाखाको प्रयोजनका लागि)</div>
            <div class="bottom-right-subtitle">भ्रमण खर्च</div>
            <div class="bottom-right-content">
                बजेट नं. शीर्षक बाट नगद÷चेक नं. .............रु.............मात्र दिइएको छ ।
            </div><br>
            <div class="bottom-right-signatures">
                <div class="bottom-right-sig-left">
                    <div>बुझिलिनेको सही ...............</div>
                    <div class="gap-sm">नाम, थरः श्री {{ $empNameNepali }}</div>
                    <div class="gap-xs">मितिः {{ $bs($record->form_dateBS) }}</div>
                </div>
                <div class="bottom-right-sig-right">
                    <div>.......................</div>
                    <div class="gap-sm">(सहायक निर्देशक, लेखा तथा वित्त शाखा)</div>
                    <div class="gap-xs">मिति: {{ $bs($record->form_dateBS) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('calender/nepali.datepicker.v5.0.6.min.js') }}"></script>
<script src="{{ \App\Support\Asset::v('js/old_international/print.js') }}"></script>
</body>
</html>
