<tr>
    <td class="text-muted">{{ $sn }}</td>
    <td><span class="bs-date" data-ad="{{ $row->domestic_form_date?->format('Y-m-d') }}">{{ $row->domestic_form_date?->format('Y-m-d') }}</span></td>
    <td>{{ ($row->fiscalYear?->fy ?? '').'-'.($row->domestic_Chalani_id ?? '') }}</td>
    <td><strong class="nepali" lang="ne">{{ $row->employee?->EmpNameInNepali ?? $row->EmpPersonalCode }}</strong></td>
    <td class="nepali" lang="ne">{{ $row->employee?->designationByName?->designationTypeInNepali ?? '-' }}</td>
    <td class="nepali" lang="ne">{{ $row->district?->District_name_nepali ?? '-' }}</td>
    <td class="cell-objective">{{ $row->domestic_travel_objective ?? '' }}</td>
    <td><span class="bs-date" data-ad="{{ $row->domestic_travelDateStart?->format('Y-m-d') }}">{{ $row->domestic_travelDateStart?->format('Y-m-d') }}</span></td>
    <td><span class="bs-date" data-ad="{{ $row->domestic_travelDateEnd?->format('Y-m-d') }}">{{ $row->domestic_travelDateEnd?->format('Y-m-d') }}</span></td>
    <td class="num">{{ number_format($row->domestic_totalday ?? 0) }}</td>
    <td class="num">{{ $row->display_rate_label }}</td>
    <td class="num">{{ $row->tada_label }}</td>
    <td><span class="badge nepali {{ $row->domestic_isTwentyPercentExtra ? 'badge-soft-success' : 'badge-soft-muted' }}" lang="ne">{{ $row->domestic_isTwentyPercentExtra ? 'छ' : 'छैन' }}</span></td>
    <td>{{ $row->travelType?->type ?? '-' }}</td>
</tr>
