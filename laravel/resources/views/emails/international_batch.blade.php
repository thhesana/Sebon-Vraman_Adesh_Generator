@php
    $font = "font-family: 'Times New Roman', Times, serif;";
    $salutation = ($emp->Gender ?? '') === 'Male' ? 'Sir' : "Ma'am";
    $cell = "padding: 10px 14px; {$font} font-size: 15px; border: 1px solid #dee2e6;";
    $section = "background-color: #0066cc; color: #ffffff; padding: 12px 14px; {$font} font-size: 16px; border: 1px solid #0066cc;";
    $fmt = fn ($v) => $v ? substr((string) $v, 0, 10) : '';
    $rows = [
        ['section' => 'Travel Information'],
        ['Country', $emp->Country],
        ['City', $emp->City],
        ['Travel Objective', $emp->travel_objective],
        ['section' => 'Duration & Allowances'],
        ['Travel Start Date', $fmt($emp->travelDateStart)],
        ['Travel End Date', $fmt($emp->travelDateEnd)],
        ['Total Days', $emp->totalday . ' days'],
        ['TADA Amount', '$' . number_format((float) $emp->tadaInUSD_Final, 2) . ' USD'],
        ['Dress Allowance', $emp->DressAllowance],
    ];
    $n = 0;
@endphp
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="{{ $font }} margin: 0; padding: 0; background-color: #f4f4f4;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f4f4; padding: 20px;"><tr><td align="center">
<table width="650" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden;">
<tr><td style="background-color: #0066cc; color: #ffffff; padding: 15px; text-align: center; {{ $font }}"><h2 style="margin: 0;">International Travel Notification</h2></td></tr>
<tr><td style="padding: 35px 30px;">
<p style="{{ $font }} font-size: 15px; margin: 0 0 20px 0; color: #2c3e50;">Dear <strong>{{ $emp->EmployeeName }}</strong> {{ $salutation }},</p>
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 20px 0;"><tr>
<td style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 12px;">
<p style="{{ $font }} margin: 0; font-size: 14px; color: #856404;">Your Travel Details are: </p></td></tr></table>
<table width="100%" cellpadding="0" cellspacing="0" border="1" style="margin: 25px 0; border-collapse: collapse; border: 1px solid #dee2e6;">
@foreach ($rows as $row)
    @if (isset($row['section']))
        <tr><td colspan="2" style="{{ $section }}">{{ $row['section'] }}</td></tr>
        @php $n = 0; @endphp
    @else
        @php $bg = ($n++ % 2 === 0) ? '#ffffff' : '#f8f9fa'; @endphp
        <tr>
            <td style="{{ $cell }} color: #495057; width: 45%; background-color: {{ $bg }};">{{ $row[0] }}</td>
            <td style="{{ $cell }} color: #2c3e50; background-color: {{ $bg }};">{{ $row[1] }}</td>
        </tr>
    @endif
@endforeach
</table>
<p style="{{ $font }} margin: 25px 0 0 0; font-size: 15px; color: #2c3e50;">This module has also been successfully integrated into the SEBON MIS (<a href="http://10.10.0.199/sebonmis/index.php">http://10.10.0.199/sebonmis/index.php</a>) under the 'भ्रमण आदेश' tab.</p>
<p style="{{ $font }} margin: 25px 0 0 0; font-size: 15px; color: #2c3e50;"><strong>Regards,</strong><br>HR Section<br>Securities Board of Nepal</p>
</td></tr>
<tr><td style="background-color: #f0f0f0; padding: 15px; text-align: center;">
<p style="{{ $font }} margin: 0; font-size: 12px; color: #666666;"><em>** This is an automated mail generated from the application. Please do not reply to this email.</em></p>
</td></tr></table></td></tr></table>
</body>
</html>
