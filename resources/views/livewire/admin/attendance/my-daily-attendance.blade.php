<div class="my-att-page" wire:poll.10s.visible @my-attendance-opened.window="$wire.$refresh()">
    <section class="module-workspace__hero" style="margin-bottom: 16px;">
        <div class="module-workspace__hero-main">
            <p class="module-workspace__eyebrow">
                <span class="module-workspace__eyebrow-dot"></span>
                My attendance
            </p>
            <h2 class="module-workspace__title">My Daily Attendance</h2>
        </div>
    </section>

    <div class="my-att-filter data-panel">
        <div class="my-att-filter__inner">
            <div class="my-att-filter__group">
                <label class="my-att-filter__label" for="my-att-month">Month</label>
                <input
                    id="my-att-month"
                    type="month"
                    class="my-att-filter__input"
                    wire:model.live="month"
                >
            </div>
        </div>
    </div>

    <div class="data-panel">
        <div class="data-panel__head">
            <h3 class="data-panel__title">Daily records — {{ $monthLabel }}</h3>
        </div>
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
                        ])>
                            <td>{{ $row['date'] }}</td>
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No days to show for this month.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
