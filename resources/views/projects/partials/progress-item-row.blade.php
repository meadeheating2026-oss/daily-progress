<tr>
    <td class="sl-cell">{{ $index + 1 }}</td>
    <td class="activity-cell">
        <div class="item-name d-flex align-items-center justify-content-between">
            <span class="d-inline-flex align-items-center">
                {{ $item->workItem->name ?? '' }}
                @if(!empty($item->is_measurable))
                    <i class="fas fa-check-circle text-success ms-1 me-1" 
                       title="{{ __('general.measurable_item_summed') }}" 
                       style="font-size: 0.85rem; vertical-align: middle;"></i>
                @endif
            </span>
            @if(!empty($item->is_grouped) && ($item->items_count ?? 1) > 1)
                <span class="badge bg-secondary ms-1" style="font-size: 0.65rem;">
                    {{ $item->items_count }} {{ __('general.grouped') }}
                </span>
            @endif
        </div>
        @if(empty($item->is_grouped) && (!empty($item->subproject_name) || !empty($item->notes)))
            <div class="item-extra-info" @if(empty($showExtraInfo)) style="display: none;" @endif>
                @if(!empty($item->subproject_name))
                    <small class="text-muted d-block">
                        <i class="fas fa-sitemap me-1"></i>{{ $item->subproject_name }}
                    </small>
                @endif
                @if(!empty($item->notes))
                    <small class="text-info d-block">
                        <i class="fas fa-sticky-note me-1"></i>{{ $item->notes }}
                    </small>
                @endif
            </div>
        @endif
    </td>
    <td class="quantity-cell">{{ number_format($item->total_quantity, 2) }}</td>
    <td class="unit-cell">{{ strtoupper($item->workItem->unit ?? '') }}</td>

    <td class="prev-planned planned-cell">{{ number_format($item->planned_until_from_date, 2) }}</td>
    <td class="prev-planned-percent planned-cell">{{ $item->planned_until_from_date_percentage }}%</td>
    <td class="prev-actual actual-cell {{ $item->previous_qty_class }}">
        <div class="d-flex align-items-center justify-content-center">
            {{ number_format($item->previous_progress, 2) }}
            @if ($item->planned_until_from_date > 0)
                <i class="fas {{ $item->previous_qty_icon }} {{ $item->previous_qty_color }} ms-1"></i>
            @endif
        </div>
    </td>
    <td class="prev-actual actual-cell {{ $item->previous_qty_class }}">
        <div class="d-flex align-items-center justify-content-center">
            {{ $item->previous_progress_percentage }}%
            @if ($item->planned_until_from_date > 0)
                <i class="fas {{ $item->previous_qty_icon }} {{ $item->previous_qty_color }} ms-1"></i>
            @endif
        </div>
    </td>

    <td class="curr-planned planned-cell">{{ number_format($item->planned_today, 2) }}</td>
    <td class="curr-planned-percent planned-cell">{{ $item->planned_today_percentage }}%</td>
    <td class="curr-actual actual-cell {{ $item->current_qty_class }}">
        <div class="d-flex align-items-center justify-content-center">
            {{ number_format($item->current_progress, 2) }}
            @if ($item->planned_today > 0)
                <i class="fas {{ $item->current_qty_icon }} {{ $item->current_qty_color }} ms-1"></i>
            @endif
        </div>
    </td>
    <td class="curr-actual actual-cell {{ $item->current_qty_class }}">
        <div class="d-flex align-items-center justify-content-center">
            {{ $item->current_progress_percentage }}%
            @if ($item->planned_today > 0)
                <i class="fas {{ $item->current_qty_icon }} {{ $item->current_qty_color }} ms-1"></i>
            @endif
        </div>
    </td>

    <td class="comp-planned planned-cell">{{ number_format($item->planned_total_quantity, 2) }}</td>
    <td class="comp-planned-percent planned-cell">{{ $item->planned_total_percentage }}%</td>
    <td class="comp-actual actual-cell {{ $item->completed_qty_class }}">
        <div class="d-flex align-items-center justify-content-center">
            {{ number_format($item->total_completed, 2) }}
            @if ($item->planned_total_quantity > 0)
                <i class="fas {{ $item->completed_qty_icon }} {{ $item->completed_qty_color }} ms-1"></i>
            @endif
        </div>
    </td>
    <td class="comp-actual actual-cell {{ $item->completed_qty_class }}">
        <div class="d-flex align-items-center justify-content-center">
            {{ $item->total_completed_percentage }}%
            @if ($item->planned_total_quantity > 0)
                <i class="fas {{ $item->completed_qty_icon }} {{ $item->completed_qty_color }} ms-1"></i>
            @endif
        </div>
    </td>

    <td class="remaining-planned planned-cell">
        <span class="{{ $item->remaining_planned < 0 ? 'text-danger fw-bold' : '' }}">
            {{ number_format($item->remaining_planned ?? 0, 2) }}
        </span>
    </td>
    <td class="remaining-planned-percent planned-cell">
        <span class="{{ $item->remaining_planned_percentage < 0 ? 'text-danger fw-bold' : '' }}">
            {{ $item->remaining_planned_percentage ?? 0 }}%
        </span>
    </td>
    <td class="remaining-cell {{ $item->remaining_qty_class }}">
        <div class="d-flex align-items-center justify-content-center">
            <span class="{{ $item->remaining < 0 ? 'text-danger fw-bold' : '' }}">
                {{ number_format($item->remaining, 2) }}
            </span>
            @if ($item->remaining_planned > 0)
                <i class="fas {{ $item->remaining_qty_icon }} {{ $item->remaining_qty_color }} ms-1"></i>
            @endif
        </div>
    </td>
    <td class="remaining-cell {{ $item->remaining_qty_class }}">
        <div class="d-flex align-items-center justify-content-center">
            <span class="{{ $item->remaining_percentage < 0 ? 'text-danger fw-bold' : '' }}">
                {{ $item->remaining_percentage }}%
            </span>
            @if ($item->remaining_planned > 0)
                <i class="fas {{ $item->remaining_qty_icon }} {{ $item->remaining_qty_color }} ms-1"></i>
            @endif
        </div>
    </td>

    <td class="start-date-cell">
        @if ($item->start_date)
            {{ \Carbon\Carbon::parse($item->start_date)->format('d-M-y') }}
        @else
            {{ __('general.na') }}
        @endif
    </td>

    <td class="target-date-cell">
        @if ($item->end_date)
            {{ \Carbon\Carbon::parse($item->end_date)->format('d-M-y') }}
        @elseif(!empty($item->planned_end_date))
            {{ \Carbon\Carbon::parse($item->planned_end_date)->format('d-M-y') }}
        @else
            {{ __('general.na') }}
        @endif
    </td>

    <td class="progress-start-date-cell">
        @if ($item->progress_start_date)
            {{ \Carbon\Carbon::parse($item->progress_start_date)->format('d-M-y') }}
        @else
            {{ __('general.na') }}
        @endif
    </td>

    <td class="progress-end-date-cell">
        @if ($item->progress_end_date)
            {{ \Carbon\Carbon::parse($item->progress_end_date)->format('d-M-y') }}
        @else
            {{ __('general.na') }}
        @endif
    </td>
</tr>
