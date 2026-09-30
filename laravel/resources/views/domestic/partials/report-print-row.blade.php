<tr>
    <td class="tc">{{ $sn }}</td>
    <td class="tc"><span class="bs-date-print" data-ad="{{ $row->domestic_form_date?->format('Y-m-d') }}">{{ $row->domestic_form_date?->format('Y-m-d') }}</span></td>
    <td class="tc">{{ ($row->fiscalYear?->fy ?? '').'-'.($row->domestic_Chalani_id ?? '') }}</td>
    <td class="cell-strong">{{ $row->employee?->EmpNameInNepali ?? $row->EmpPersonalCode }}</td>
    <td>{{ $row->employee?->designationByName?->designationTypeInNepali ?? '-' }}</td>
    <td>{{ $row->district?->District_name_nepali ?? '-' }}</td>
    <td class="cell-objective">{{ $row->domestic_travel_objective ?? '' }}</td>
    <td class="tc"><span class="bs-date-print" data-ad="{{ $row->domestic_travelDateStart?->format('Y-m-d') }}">{{ $row->domestic_travelDateStart?->format('Y-m-d') }}</span></td>
    <td class="tc"><span class="bs-date-print" data-ad="{{ $row->domestic_travelDateEnd?->format('Y-m-d') }}">{{ $row->domestic_travelDateEnd?->format('Y-m-d') }}</span></td>
    <td class="tc"><strong>{{ number_format($row->domestic_totalday ?? 0) }}</strong></td>
    <td class="tr">{{ $row->display_rate_label }}</td>
    <td class="tr">{{ $row->tada_label }}</td>
    <td class="tc">{{ $row->domestic_isTwentyPercentExtra ? 'छ' : 'छैन' }}</td>
    <td>{{ $row->travelType?->type ?? '-' }}</td>
</tr>
