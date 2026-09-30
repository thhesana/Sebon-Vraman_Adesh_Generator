@php
    $font = "font-family: 'Times New Roman', Times, serif;";
    $salutation = ($emp->Gender ?? '') === 'Male' ? 'Sir' : "Ma'am";
    $cell = "padding: 10px 14px; {$font} font-size: 15px; border: 1px solid #dee2e6;";
    $section = "background-color: #28a745; color: #ffffff; padding: 12px 14px; {$font} font-size: 16px; border: 1px solid #28a745;";
    $fmt = fn ($v) => $v ? substr((string) $v, 0, 10) : '';
    $rows = [
        ['section' => '👤 Employee Information'],
        ['Designation', $emp->Designation],
        ['Level', $emp->LevelName],
        ['section' => '✈️ Travel Information'],
        ['District', $emp->District_name],
        ['Travel Objective', $emp->domestic_travel_objective],
        ['section' => '📅 Duration & Allowances'],
        ['Travel Start Date', $fmt($emp->domestic_travelDateStart)],
        ['Travel End Date', $fmt($emp->domestic_travelDateEnd)],
        ['Total Days', $emp->domestic_totalday . ' days'],
        ['Daily TADA Rate', 'NPR ' . number_format((float) $emp->tadaInNepali, 2)],
        ['Total TADA Amount', 'NPR ' . number_format((float) $emp->domestic_tada, 2)],
        ['20% Extra Allowance', $emp->isTwentyPercentExtra],
    ];
    $n = 0;
@endphp
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="{{ $font }} margin: 0; padding: 0; background-color: #f4f4f4;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f4f4; padding: 20px;"><tr><td align="center">
<table width="650" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden;">
<tr><td style="padding: 35px 30px;">
<p style="{{ $font }} font-size: 15px; margin: 0 0 20px 0; color: #2c3e50;">Dear {{ $emp->EmpName }} {{ $salutation }},</p>
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 20px 0;"><tr>
<td style="background-color: #d4edda; border-left: 5px solid #28a745; padding: 15px; border-radius: 5px;">
<p style="{{ $font }} margin: 0; font-size: 14px; color: #155724;">Your Travel Details are: </p></td></tr></table>
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
<p style="{{ $font }} margin: 25px 0 0 0; font-size: 15px; color: #2c3e50;">This module has also been successfully integrated into the SEBON MIS (<a href="http://10.10.0.199/sebonmis/index.php" style="color: #28a745; text-decoration: none;">http://10.10.0.199/sebonmis/index.php</a>) under the 'भ्रमण आदेश' tab.</p>
<p style="{{ $font }} margin: 25px 0 0 0; font-size: 15px; color: #2c3e50;">Regards,<br>HR Section<br>Securities Board of Nepal</p>
</td></tr>
<tr><td style="background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 3px solid #28a745;">
<p style="{{ $font }} margin: 0; font-size: 13px; color: #6c757d;"><em>** This is an automated mail generated from the application. Please do not reply to this email.</em></p>
</td></tr></table></td></tr></table>
</body>
</html>
