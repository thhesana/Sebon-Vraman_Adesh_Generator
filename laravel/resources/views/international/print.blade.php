<!DOCTYPE html>
<html lang="ne">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>International TADA Forms - {{ $batchId }}</title>

<link href="https://nepalidatepicker.sajanmaharjan.com.np/v5/nepali.datepicker/css/nepali.datepicker.v5.0.6.min.css" rel="stylesheet">

<style>
* { box-sizing: border-box; }

@media print {
    .no-print { display: none !important; }
    @page { size: A4; margin: 10mm 8mm 8mm 10mm; }
    body { margin: 0; padding: 0; }
    .page-break { page-break-after: always; page-break-inside: avoid; }
    .print-container { width: 100% !important; height: auto !important; min-height: auto !important; margin: 0 !important; padding: 0 !important; box-shadow: none !important; page-break-after: always; }
    .header { text-align: center !important; }
    .header-line { text-align: center !important; }
    .bottom-section { display: flex !important; gap: 5px !important; page-break-inside: avoid !important; }
    .bottom-left { flex: 0 0 32% !important; width: 32% !important; }
    .bottom-right { flex: 0 0 66% !important; width: 66% !important; }
    .bottom-right-content { white-space: normal !important; word-wrap: break-word !important; }
    table { width: 100% !important; }
    th, td { border: 1px solid #000 !important; }
}

body { font-family: 'Kalimati', 'Noto Sans Devanagari', Arial, sans-serif; font-size: 11pt; margin: 0; padding: 0; background-color: #f5f5f5; line-height: 1.3; }

.print-container { width: 210mm; min-height: 297mm; height: auto; margin: 0 auto 20px; background: white; padding: 10mm 10mm 8mm 10mm; box-sizing: border-box; box-shadow: 0 0 10px rgba(0,0,0,0.1); position: relative; display: flex; flex-direction: column; }
.header { text-align: center; margin-bottom: 10px; padding-bottom: 0; }
.header-line { margin: 1px 0; font-size: 11pt; font-family: 'Kalimati', 'Noto Sans Devanagari', Arial, sans-serif; line-height: 1.2; text-align: center; }
.header-number-date { text-align: right; margin: 3px 0 10px 0; font-size: 11pt; font-family: 'Kalimati', 'Noto Sans Devanagari', Arial, sans-serif; padding-right: 0; }
.form-info { margin: 6px 0; line-height: 1.3; font-family: 'Kalimati', 'Noto Sans Devanagari', Arial, sans-serif; }
.form-info div { margin: 2px 0; font-size: 11pt; }
table { width: 100%; border-collapse: collapse; margin: 6px 0; font-size: 11pt; font-family: 'Kalimati', 'Noto Sans Devanagari', Arial, sans-serif; }
table, th, td { border: 1px solid #000; }
th, td { padding: 4px 6px; text-align: left; vertical-align: middle; font-family: 'Kalimati', 'Noto Sans Devanagari', Arial, sans-serif; font-size: 11pt; line-height: 1.3; }
th { text-align: center; line-height: 1.2; }
.text-right { text-align: right; }
.text-center { text-align: center; }
.bottom-section { display: flex; width: 100%; margin-top: 10px; flex-shrink: 0; gap: 5px; page-break-inside: avoid; }
.bottom-left { flex: 0 0 32%; font-size: 11pt; font-family: 'Kalimati', 'Noto Sans Devanagari', Arial, sans-serif; vertical-align: top; }
.bottom-right { flex: 0 0 66%; border: 1px solid #000; padding: 8px 10px; font-size: 11pt; font-family: 'Kalimati', 'Noto Sans Devanagari', Arial, sans-serif; min-height: 120px; vertical-align: top; }
.bottom-right-title { text-align: center; margin-bottom: 6px; font-size: 11pt; font-family: 'Kalimati', 'Noto Sans Devanagari', Arial, sans-serif; }
.bottom-right-subtitle { margin: 6px 0; text-align: center; font-size: 11pt; font-family: 'Kalimati', 'Noto Sans Devanagari', Arial, sans-serif; }
.bottom-right-content { margin: 6px 0; line-height: 1.4; font-family: 'Kalimati', 'Noto Sans Devanagari', Arial, sans-serif; font-size: 11pt; word-wrap: break-word; white-space: normal; }
.bottom-right-signatures { display: flex; justify-content: space-between; margin-top: 12px; gap: 12px; }
.bottom-right-sig-left { flex: 1; font-size: 11pt; font-family: 'Kalimati', 'Noto Sans Devanagari', Arial, sans-serif; white-space: nowrap; }
.bottom-right-sig-right { flex: 1; text-align: right; font-size: 11pt; font-family: 'Kalimati', 'Noto Sans Devanagari', Arial, sans-serif; white-space: nowrap; }
.print-btn { position: fixed; top: 20px; right: 20px; padding: 10px 20px; background-color: #1E3A8A; color: #fff; border: none; border-radius: 5px; cursor: pointer; z-index: 1000; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
.print-btn:hover { background-color: #1e40af; }
.employee-count { position: fixed; top: 70px; right: 20px; background: #10B981; color: #fff; padding: 8px 15px; border-radius: 5px; font-size: 14px; z-index: 1000; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
small { font-size: 11pt; font-family: 'Kalimati', 'Noto Sans Devanagari', Arial, sans-serif; }
</style>
</head>
<body>

@use('App\Support\Fmt')

@php
    $count = count($employees);
    // Renders an AD date as a span the page JS converts to BS (falls back to the AD text).
    $bsDate = fn ($d) => '<span class="nepali-date" data-ad-date="'.e(Fmt::date($d)).'">'.e(Fmt::date($d, 'Y-m-d', '-')).'</span>';
@endphp

<button class="print-btn no-print" onclick="window.print()">🖨️ Print All Forms</button>
<div class="employee-count no-print">
    {{ $count }} Employee(s) - {{ $count }} Form(s)
</div>

@foreach ($employees as $index => $employee)
@php
    $isLast = ($index === $count - 1);
    $empNameNepali = $employee->EmployeeNamenepali ?? $employee->EmployeeName ?? '-';
    $dailyRate = $employee->tadaInUSD_Final;
    $days = $employee->totalday;
    $totalDailyUSD = (float) $dailyRate * (float) $days;
    $totalDailyNPR = $totalDailyUSD * (float) $usdRate;
    $dressAllowance = $employee->DressAllowance ?? 0;
    $grandTotalNPR = $totalDailyNPR + $dressAllowance;
@endphp
<div class="print-container {{ ! $isLast ? 'page-break' : '' }}">
    <div class="header">
        <div class="header-line">अनुसूची – ५</div>
        <div class="header-line">(नियम ३८ को उपनियम (३) सँग सम्बन्धित)</div>
        <div class="header-line">भ्रमण आदेश</div>
        <div class="header-line">अन्तरदेशिय । अन्तर्राष्ट्रिय</div>
    </div>
    <div class="header-number-date">
        संख्या: {{ $employee->Chalani_id.'/'.$fyText }}
        <br>
        मिति: {!! $bsDate($employee->form_date) !!}
    </div>
    <div class="form-info">
        <div>१. भ्रमण गर्ने पदाधिकारी वा कर्मचारीको नाम: श्री {{ $empNameNepali }}</div>
        <div>२. पद: {{ $employee->DesignationInNepali ?? '-' }}</div>
        <div>३. कार्यालय: नेपाल धितोपत्र बोर्ड</div>
        <div>४. भ्रमण गर्ने स्थान (विदेश भए मुलुक र शहर खुलाउने): {{ $employee->City_name }}, {{ $employee->Country_name }}</div>
        <div>५. भ्रमणको उद्देश्य: {{ $employee->travel_objective }} </div>
        <div>
            ६. भ्रमण गर्ने अवधि:
            {!! $bsDate($employee->travelDateStart) !!} देखि
            {!! $bsDate($employee->travelDateEnd) !!} (तदनुसार
            {{ Fmt::date($employee->travelDateStart, 'Y/m/d') }} देखि
            {{ Fmt::date($employee->travelDateEnd, 'Y/m/d') }}) सम्म
            {{ Fmt::num($days, 'N/A') }} दिन ।
        </div>
        <div>७. भ्रमण गर्ने साधन: हवाईजहाज तथा ट्याक्सी ।</div>
        <div>८. भ्रमणको निमित्त माग गरेको पेश्की: रु. {{ Fmt::num($grandTotalNPR, 'N/A') }}/-</div>
    </div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">सि.नं.</th>
                <th style="width: 30%;">विवरण</th>
                <th style="width: 10%;">दर <br>(यु.एस.डि.)</th>
                <th style="width: 7%;">दिन</th>
                <th style="width: 13%;">जम्मा भत्ता<br>(यु.एस.डि.)</th>
                <th style="width: 15%;">विनिमय दर<br>(मिति {!! $bsDate($employee->form_date) !!})</th>
                <th style="width: 20%;">जम्मा रकम रु.</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">१</td>
                <td>दैनिक भत्ता<br/><small>(नेपाल धितोपत्र बोर्ड आर्थिक प्रशासन सम्बन्धी नियमावली २०६६ को नियम ४७ को व्यवस्था बमोजिम। )</small></td>
                <td class="text-right">{{ Fmt::num($dailyRate, 'N/A') }}</td>
                <td class="text-center">{{ Fmt::num($days, 'N/A') }}</td>
                <td class="text-right">{{ Fmt::num($totalDailyUSD, 'N/A') }}</td>
                <td class="text-right">{{ Fmt::num($usdRate, 'N/A') }}</td>
                <td class="text-right">{{ Fmt::num($totalDailyNPR, 'N/A') }}</td>
            </tr>
            <tr style="height: 50px;">
                <td class="text-center">२</td>
                <td>लुगा भत्ता<br/><small>(नेपाल धितोपत्र बोर्ड आर्थिक प्रशासन सम्बन्धी नियमावली २०६६ को नियम ४८ को व्यवस्था बमोजिम। )</small></td>
                @if ($dressAllowance > 0)
                <td colspan="4"></td>
                <td class="text-right">{{ Fmt::num($dressAllowance, 'N/A') }}</td>
                @else
                <td colspan="5" class="text-center"><em>दुई वर्ष पूरा नभएको ।</em></td>
                @endif
            </tr>
            <tr>
                <td colspan="6" class="text-right">जम्मा</td>
                <td colspan="2" class="text-right">{{ Fmt::num($grandTotalNPR, 'N/A') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="bottom-section">
        <div class="bottom-left">
            <div style="margin-bottom: 8px;">९. भ्रमण सम्बन्धी अन्य आदेश:</div>
            <div style="margin: 25px 0 8px 0;">.............................................</div>
            <div>भ्रमण आदेश दिने अधिकारी</div>
            <div>मिति: {!! $bsDate($employee->form_date) !!}</div>
            <div style="margin-top: 20px;">बोधार्थ:</div>
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
                    <div style="margin-top: 8px;">नाम, थरः श्री {{ $empNameNepali }}</div>
                    <div style="margin-top: 4px;">मितिः {!! $bsDate($employee->form_date) !!}</div>
                </div>
                <div class="bottom-right-sig-right">
                    <div>.......................</div>
                    <div style="margin-top: 8px;">(सहायक निर्देशक, लेखा तथा वित्त शाखा)</div>
                    <div style="margin-top: 4px;">मिति:{!! $bsDate($employee->form_date) !!}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div> <br><br>
                </div>
            </div>
        </div>
    </div>
</div>
@endforeach

<script src="https://nepalidatepicker.sajanmaharjan.com.np/v5/nepali.datepicker/js/nepali.datepicker.v5.0.6.min.js"></script>
<script>
window.onload = function() {
    setTimeout(function() {
        document.querySelectorAll('.nepali-date').forEach(function(el) {
            var adDate = el.getAttribute('data-ad-date');
            if(adDate) {
                try {
                    el.textContent = NepaliFunctions.AD2BS(adDate, "YYYY-MM-DD", "YYYY/MM/DD");
                } catch(e){
                    console.error("Date conversion error:", adDate, e);
                }
            }
        });
    }, 500);
};
</script>
</body>
</html>
