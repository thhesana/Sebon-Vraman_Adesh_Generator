{{-- One employee row of the list page; the first row of a batch carries the Edit / Print buttons. --}}
<tr @class(['batch-group' => $isFirstInBatch])>
    <td>{{ $row->domestic_Batch_id }}</td>
    <td><strong>{{ $row->employee?->EmpName ?? $row->EmpPersonalCode }}</strong></td>
    <td>{{ $row->district?->District_name }}</td>
    <td>{{ $row->domestic_travel_objective }}</td>
    <td>{{ Str::upper($row->domestic_travelDateStart?->format('d-M-Y') ?? '-') }} - {{ Str::upper($row->domestic_travelDateEnd?->format('d-M-Y') ?? '-') }}</td>
    <td class="num">{{ $row->domestic_totalday }}</td>
    <td class="num">NPR {{ $row->tada_label }}</td>
    <td>
        <span class="badge {{ $row->domestic_isTwentyPercentExtra ? 'badge-soft-success' : 'badge-soft-muted' }}">{{ $row->extra_label }}</span>
    </td>
    <td>
        @if ($isFirstInBatch)
            <div class="d-flex flex-wrap gap-1">
                <a href="{{ route('domestic.edit', $row->domestic_Batch_id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                <a href="{{ route('domestic.print', $row->domestic_Batch_id) }}" class="btn btn-sm btn-secondary" target="_blank" rel="noopener">Print</a>
            </div>
        @endif
    </td>
</tr>
