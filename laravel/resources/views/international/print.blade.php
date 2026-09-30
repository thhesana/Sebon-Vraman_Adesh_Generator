<!DOCTYPE html>
<html lang="ne">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>International TADA Forms - {{ $batchId }}</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://nepalidatepicker.sajanmaharjan.com.np/v5/nepali.datepicker/css/nepali.datepicker.v5.0.6.min.css" rel="stylesheet">
<link href="{{ asset('css/theme.css') }}" rel="stylesheet">
<link href="{{ asset('css/international/print.css') }}" rel="stylesheet">
</head>
<body>

@php
    $count = $employees->count();
@endphp

<div class="print-controls no-print">
    <span class="badge badge-soft-success">{{ $count }} Employee(s) - {{ $count }} Form(s)</span>
    <button type="button" class="btn btn-primary" id="printBtn">Print All Forms</button>
</div>

@foreach ($employees as $index => $employee)
@php
    $isLast = ($index === $count - 1);
    $empNameNepali = $employee->employee?->EmpNameInNepali ?? $employee->employee?->EmpName ?? '-';
    $dailyRate = $employee->daily_rate;
    $days = $employee->days;
    $totalDailyUSD = (float) $dailyRate * (float) $days;
    $totalDailyNPR = $totalDailyUSD * (float) $usdRate;
    $dressAllowance = $employee->dress_amount;
    $grandTotalNPR = $totalDailyNPR + $dressAllowance;
@endphp
<div class="print-container nepali {{ ! $isLast ? 'page-break' : '' }}" lang="ne">
    <div class="header">
        <div class="header-line">अनुसूची – ५</div>
        <div class="header-line">(नियम ३८ को उपनियम (३) सँग सम्बन्धित)</div>
        <div class="header-line">भ्रमण आदेश</div>
        <div class="header-line">अन्तरदेशिय । अन्तर्राष्ट्रिय</div>
    </div>
    <div class="header-number-date">
        संख्या: {{ $employee->Chalani_id.'/'.$fyText }}
        <br>
        मिति: <x-international.bs-date :date="$employee->form_date" />
    </div>
    <div class="form-info">
        <div>१. भ्रमण गर्ने पदाधिकारी वा कर्मचारीको नाम: श्री {{ $empNameNepali }}</div>
        <div>२. पद: {{ $employee->employee?->designationType?->designationTypeInNepali ?? '-' }}</div>
        <div>३. कार्यालय: नेपाल धितोपत्र बोर्ड</div>
        <div>४. भ्रमण गर्ने स्थान (विदेश भए मुलुक र शहर खुलाउने): {{ $employee->city?->City_name }}, {{ $employee->country?->Country_name }}</div>
        <div>५. भ्रमणको उद्देश्य: {{ $employee->travel_objective }} </div>
        <div>
            ६. भ्रमण गर्ने अवधि:
            <x-international.bs-date :date="$employee->travelDateStart" /> देखि
            <x-international.bs-date :date="$employee->travelDateEnd" /> (तदनुसार
            {{ $employee->travelDateStart?->format('Y/m/d') }} देखि
            {{ $employee->travelDateEnd?->format('Y/m/d') }}) सम्म
            <x-international.amount :value="$days" blank="N/A" /> दिन ।
        </div>
        <div>७. भ्रमण गर्ने साधन: हवाईजहाज तथा ट्याक्सी ।</div>
        <div>८. भ्रमणको निमित्त माग गरेको पेश्की: रु. <x-international.amount :value="$grandTotalNPR" blank="N/A" />/-</div>
    </div>
    <table class="doc-table">
        <thead>
            <tr>
                <th class="col-sn">सि.नं.</th>
                <th class="col-desc">विवरण</th>
                <th class="col-rate">दर <br>(यु.एस.डि.)</th>
                <th class="col-days">दिन</th>
                <th class="col-usd">जम्मा भत्ता<br>(यु.एस.डि.)</th>
                <th class="col-fx">विनिमय दर<br>(मिति <x-international.bs-date :date="$employee->form_date" />)</th>
                <th class="col-npr">जम्मा रकम रु.</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">१</td>
                <td>दैनिक भत्ता<br/><small>(नेपाल धितोपत्र बोर्ड आर्थिक प्रशासन सम्बन्धी नियमावली २०६६ को नियम ४७ को व्यवस्था बमोजिम। )</small></td>
                <td class="text-right"><x-international.amount :value="$dailyRate" blank="N/A" /></td>
                <td class="text-center"><x-international.amount :value="$days" blank="N/A" /></td>
                <td class="text-right"><x-international.amount :value="$totalDailyUSD" blank="N/A" /></td>
                <td class="text-right"><x-international.amount :value="$usdRate" blank="N/A" /></td>
                <td class="text-right"><x-international.amount :value="$totalDailyNPR" blank="N/A" /></td>
            </tr>
            <tr class="row-tall">
                <td class="text-center">२</td>
                <td>लुगा भत्ता<br/><small>(नेपाल धितोपत्र बोर्ड आर्थिक प्रशासन सम्बन्धी नियमावली २०६६ को नियम ४८ को व्यवस्था बमोजिम। )</small></td>
                @if ($dressAllowance > 0)
                <td colspan="4"></td>
                <td class="text-right"><x-international.amount :value="$dressAllowance" blank="N/A" /></td>
                @else
                <td colspan="5" class="text-center"><em>दुई वर्ष पूरा नभएको ।</em></td>
                @endif
            </tr>
            <tr>
                <td colspan="6" class="text-right">जम्मा</td>
                <td colspan="2" class="text-right"><x-international.amount :value="$grandTotalNPR" blank="N/A" /></td>
            </tr>
        </tbody>
    </table>

    <div class="bottom-section">
        <div class="bottom-left">
            <div class="gap-sm">९. भ्रमण सम्बन्धी अन्य आदेश:</div>
            <div class="sign-dots">.............................................</div>
            <div>भ्रमण आदेश दिने अधिकारी</div>
            <div>मिति: <x-international.bs-date :date="$employee->form_date" /></div>
            <div class="gap-lg">बोधार्थ:</div>
            <div>१. श्री लेखा तथा वित्त शाखा</div>
        </div>

        <div class="bottom-right">
            <div class="bottom-right-title">
                (लेखा तथा वित्त शाखाको प्रयोजनका लागि)
            </div>
            <div class="bottom-right-subtitle">भ्रमण खर्च</div>
            <div class="bottom-right-content">
                बजेट नं. शीर्षक बाट नगद÷चेक नं. .............रु.............मात्र दिइएको छ ।
            </div><br>
            <div class="bottom-right-signatures">
                <div class="bottom-right-sig-left">
                    <div>बुझिलिनेको सही ...............</div>
                    <div class="gap-sm">नाम, थरः श्री {{ $empNameNepali }}</div>
                    <div class="gap-xs">मितिः <x-international.bs-date :date="$employee->form_date" /></div>
                </div>
                <div class="bottom-right-sig-right">
                    <div>.......................</div>
                    <div class="gap-sm">(सहायक निर्देशक, लेखा तथा वित्त शाखा)</div>
                    <div class="gap-xs">मिति:<x-international.bs-date :date="$employee->form_date" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div> <br><br>
                </div>
            </div>
        </div>
    </div>
</div>
@endforeach

<script src="https://nepalidatepicker.sajanmaharjan.com.np/v5/nepali.datepicker/js/nepali.datepicker.v5.0.6.min.js"></script>
<script src="{{ asset('js/international/print.js') }}"></script>
</body>
</html>
