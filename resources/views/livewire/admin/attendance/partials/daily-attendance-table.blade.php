@php
    /** @var list<array> $rows */
@endphp
<div class="data-table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Check-in</th>
                <th>Check-out</th>
                <th>Worked</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr @class([
                    'my-att-row--ok' => $row['row'] === 'ok' || $row['row'] === 'leave-ok',
                    'my-att-row--bad' => $row['row'] === 'incomplete' || $row['row'] === 'leave-pending',
                    'my-att-row--holiday' => $row['row'] === 'holiday',
                ])>
                    <td>{{ $row['date'] }}</td>
                    @if ($row['row'] === 'holiday')
                        <td colspan="4" class="my-att-holiday-cell">
                            <span class="my-att-status my-att-status--holiday">
                                Holiday · {{ $row['status'] }} · Off
                            </span>
                        </td>
                    @else
                        <td>
                            {{ $row['check_in'] }}
                            @if (! empty($row['is_late']))
                                <span class="my-att-flag my-att-flag--late">Late</span>
                            @endif
                        </td>
                        <td>
                            {{ $row['check_out'] }}
                            @if (! empty($row['is_early']))
                                <span class="my-att-flag my-att-flag--early">Early</span>
                            @endif
                        </td>
                        <td>{{ $row['worked'] }}</td>
                        <td>
                            @if ($row['row'] === 'ok' || $row['row'] === 'leave-ok')
                                <span class="my-att-status my-att-status--green">{{ $row['status'] }}</span>
                            @elseif ($row['row'] === 'incomplete' || $row['row'] === 'leave-pending')
                                <span class="my-att-status my-att-status--red">{{ $row['status'] }}</span>
                            @else
                                <span class="my-att-status">{{ $row['status'] }}</span>
                            @endif
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="5">No days to show for this month.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
