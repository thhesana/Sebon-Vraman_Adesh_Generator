{{-- One travel order (schedule 5). $record: DomesticTada with employee, district, travelType, fiscalYear and verifier loaded. --}}
@php
    $employee = $record->employee;
    $form_date = $record->domestic_form_date?->format('Y-m-d') ?? 'N/A';
    $start_date = $record->domestic_travelDateStart?->format('Y-m-d') ?? 'N/A';
    $end_date = $record->domestic_travelDateEnd?->format('Y-m-d') ?? 'N/A';
    $total_days = (float) $record->domestic_totalday;
    $total_tada = (float) $record->domestic_tada;
    $empNameNepali = $employee?->EmpNameInNepali ?? $employee?->EmpName ?? '-';
    $designationNepali = $employee?->designationByName?->designationTypeInNepali ?? $employee?->Designation ?? '-';
    $travel_mode = $record->travelType?->type ?? 'सार्वजनिक यातायात';
    $fy_mode = $record->fiscalYear?->fy ?? '';
    $verifier_post = filled($record->verifier?->tadaverifierPost) ? $record->verifier->tadaverifierPost : 'कार्यकारी निर्देशक';
    $daily_rate_display = $record->display_rate;
@endphp

<div @class(['print-container', 'record-container', 'page-break' => ! $isLast]) lang="ne">
    <div class="header">
        <h2 class="nepali-text bold-header">अनुसूची – ५</h2>
        <div class="header-subtitle nepali-text bold-header">(नियम ३८ को उपनियम (३) सँग सम्बन्धित)</div>
        <h2 class="nepali-text bold-header">भ्रमण आदेश</h2>
        <div class="header-subtitle nepali-text bold-header">अन्तरदेशिय । अन्तर्राष्ट्रिय</div>
        <div class="header-right">
            <strong class="nepali-text">संख्या:</strong> <span class="nepali-number">{{ $record->domestic_Chalani_id }}/{{ $fy_mode }}</span><br>
            <strong class="nepali-text">मिति:</strong>
            <span class="nepali-date nepali-text" data-ad-date="{{ $form_date }}">
                {{ $form_date }}
            </span>
        </div>
    </div>

    <div class="form-info">
        <div><span class="nepali-text nepali-number">१.</span> <span class="nepali-text">भ्रमण गर्ने पदाधिकारी वा कर्मचारीको नाम: श्री</span> <span class="nepali-number">{{ $empNameNepali }}</span></div> <br>
        <div><span class="nepali-text nepali-number">२.</span> <span class="nepali-text">पद:</span> <span class="nepali-number">{{ $designationNepali }}</span></div><br>
        <div class="nepali-text nepali-number">३. कार्यालय: नेपाल धितोपत्र बोर्ड</div><br>
        <div><span class="nepali-text nepali-number">४.</span> <span class="nepali-text">भ्रमण गर्ने स्थान (विदेश भए मुलुक र शहर खुलाउने):</span> <span class="nepali-number">{{ $record->district?->District_name_nepali }}</span></div><br>
        <div>
            <span class="nepali-text nepali-number">५.</span> <span class="nepali-text">भ्रमणको उद्देश्य:</span>
            <span>{{ $record->domestic_travel_objective }}</span>
        </div><br>
        <div>
            <span class="nepali-text nepali-number">६.</span> <span class="nepali-text">भ्रमण गर्ने अवधि:</span>
            <span class="nepali-date nepali-text nepali-number" data-ad-date="{{ $start_date }}">
                {{ $start_date }}
            </span>
            <span class="nepali-text">देखि</span>
            <span class="nepali-date nepali-text nepali-number" data-ad-date="{{ $end_date }}">
                {{ $end_date }}
            </span>
            <span class="nepali-text">सम्म</span>
            <span class="nepali-number">{{ number_format($total_days, 0) }}</span>
            <span class="nepali-text">दिन ।</span> <br>
        </div><br>
        <div class="nepali-text nepali-number">७. भ्रमण गर्ने साधन: {{ $travel_mode }} ।</div> <br>
        <div>
            <span class="nepali-text nepali-number">८.</span> <span class="nepali-text">भ्रमणको निमित्त माग गरेको पेश्की: रु.</span> <span class="nepali-number">{{ number_format($total_tada, 2) }}</span><span class="nepali-text">/-</span>
            <div class="tada-calculation nepali-text nepali-number">
                दैनिक भत्ताः (दैनिक भत्ता रु. {{ number_format($daily_rate_display, 0) }} X {{ number_format($total_days, 0) }} दिनको) को जम्मा रु. {{ number_format($total_tada, 0) }}।–
                <br>(नेपाल धितोपत्र बोर्ड आर्थिक प्रशासन सम्बन्धी नियमावली २०६६ को नियम ४२ ले व्यवस्था गरे बमोजिम । )<BR>
            </div> <br>
            <span class="nepali-text nepali-number">९. </span><span class="nepali-text">भ्रमण सम्बन्धी अन्य आदेश:</span>
        </div>
    </div>

    <div class="bottom-section">
        <div class="bottom-left">
            <div class="sign-line">.......................................</div>
            <div class="nepali-text nepali-number">भ्रमण आदेश दिने अधिकारी</div>
            <div class="nepali-text nepali-number">{{ $verifier_post }}</div>
            <div class="nepali-text">मिति: <span class="nepali-date nepali-text" data-ad-date="{{ $form_date }}"></span></div> <br>
            <div class="gap-lg"><strong class="nepali-text bold-header">बोधार्थ:</strong></div>
            <div class="nepali-text nepali-number">१. श्री लेखा तथा वित्त शाखा</div>
        </div>

        <div class="bottom-right">
            <div class="bottom-right-subtitle nepali-text nepali-number">भ्रमण खर्च</div>
            <div class="bottom-right-content nepali-text nepali-number">
                बजेट नं. शीर्षक बाट नगद/चेक नं. .........रु.	|- मात्र दिइएको छ ।
            </div><br><br>
            <div class="bottom-right-signatures">
                <div class="bottom-right-sig-left">
                    <div class="nepali-text nepali-number">बुझिलिनेको सही ............................</div>
                    <div class="gap-md"><span class="nepali-text">नाम, थरः श्री</span> <span class="nepali-number">{{ $empNameNepali }}</span></div> <br>
                    <div class="nepali-text gap-sm">मितिः <span class="nepali-date nepali-text" data-ad-date="{{ $form_date }}"></span></div>
                </div>
                <div class="bottom-right-sig-right">
                    <div class="nepali-number">....................................&nbsp;&nbsp;&nbsp;</div>
                    <div class="nepali-text nepali-number gap-md">(सहायक निर्देशक, लेखा तथा वित्त शाखा)</div> <br>
                    <div class="nepali-text gap-sm">मिति: <span class="nepali-date nepali-text" data-ad-date="{{ $form_date }}"></span></div> <br>
                </div>
            </div>
        </div>
    </div>
</div>
