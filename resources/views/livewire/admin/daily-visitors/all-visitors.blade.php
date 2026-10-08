<div
    class="all-visitors-page"
    wire:poll.5s.visible
    @all-visitors-panel-opened.window="$wire.$refresh()"
>
    @if ($denied ?? false)
        <div class="data-panel">
            <p>Only admin or super admin can open All Visitors.</p>
        </div>
    @else

        <div class="stat-grid" style="margin-bottom: 16px;">
            <article class="stat-card stat-card--amber">
                <div class="stat-card__top"><p class="stat-card__label">In queue</p></div>
                <p class="stat-card__value">{{ $countInQueue }}</p>
                <p class="stat-card__hint">For selected date</p>
            </article>
            <article class="stat-card stat-card--blue">
                <div class="stat-card__top"><p class="stat-card__label">In meeting</p></div>
                <p class="stat-card__value">{{ $countMeeting }}</p>
                <p class="stat-card__hint">For selected date</p>
            </article>
            <article class="stat-card stat-card--green">
                <div class="stat-card__top"><p class="stat-card__label">Done</p></div>
                <p class="stat-card__value">{{ $countDone }}</p>
                <p class="stat-card__hint">For selected date</p>
            </article>
        </div>

        <div class="my-att-filter data-panel">
            <div class="my-att-filter__inner">
                <div class="my-att-filter__group">
                    <label class="my-att-filter__label" for="av-filter-date">Date</label>
                    <input id="av-filter-date" type="date" class="my-att-filter__input" style="min-width: 150px;" wire:model.live="filter_date">
                </div>
                <div class="my-att-filter__group">
                    <label class="my-att-filter__label" for="av-filter-status">Status</label>
                    <select id="av-filter-status" class="my-att-filter__input" style="min-width: 150px;" wire:model.live="filter_status">
                        <option value="">All statuses</option>
                        <option value="waiting">Waiting</option>
                        <option value="please_wait">Please wait</option>
                        <option value="send_now">Send now</option>
                        <option value="in_meeting">In meeting</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div class="my-att-filter__group">
                    <label class="my-att-filter__label" for="av-filter-desk">Desk</label>
                    <select id="av-filter-desk" class="my-att-filter__input" style="min-width: 150px;" wire:model.live="filter_desk">
                        <option value="">All desks</option>
                        @foreach ($desks as $desk)
                            <option value="{{ $desk->id }}">{{ $desk->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" class="payment-actions__btn" wire:click="resetFilters">Today</button>
            </div>
        </div>

        <div class="data-panel">
            <div class="data-panel__head">
                <h3 class="data-panel__title">All visitors</h3>
                <span class="add-account__count">{{ $visitors->count() }} shown</span>
            </div>
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Purpose</th>
                            <th>Desk</th>
                            <th>Meet with</th>
                            <th>Registered by</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th>Follow-up</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($visitors as $visitor)
                            <tr wire:key="all-vis-{{ $visitor->id }}-{{ $visitor->status }}">
                                <td>{{ $visitor->name }}</td>
                                <td>{{ $visitor->contact_no }}</td>
                                <td style="max-width: 180px; white-space: normal; word-break: break-word;">{{ $visitor->purpose }}</td>
                                <td>{{ $visitor->desk?->name ?? '—' }}</td>
                                <td>{{ $visitor->assignedUser?->name ?? '—' }}</td>
                                <td>{{ $visitor->creator?->name ?? '—' }}</td>
                                <td>
                                    @if ($visitor->status === 'please_wait')
                                        <span class="payment-badge payment-badge--draft">{{ $visitor->statusLabel() }}</span>
                                    @elseif (in_array($visitor->status, ['send_now', 'in_meeting', 'completed'], true))
                                        <span class="payment-badge payment-badge--paid">{{ $visitor->statusLabel() }}</span>
                                    @else
                                        <span class="payment-badge">{{ $visitor->statusLabel() }}</span>
                                    @endif
                                </td>
                                <td style="max-width: 140px; white-space: normal; word-break: break-word;">{{ $visitor->remarks ?: '—' }}</td>
                                <td style="max-width: 260px; white-space: normal; word-break: break-word; font-size: 0.85rem; line-height: 1.4;">
                                    @if ($visitor->reminders->isEmpty())
                                        —
                                    @else
                                        @foreach ($visitor->reminders as $reminder)
                                            <div style="margin-bottom: 6px;">
                                                <strong>{{ $reminder->remind_on->format('d M Y') }}</strong>
                                                @if ($reminder->note)
                                                    — {{ $reminder->note }}
                                                @endif
                                                <br>
                                                @if ($reminder->is_done)
                                                    Outcome: {{ $reminder->done_note ?: '—' }}
                                                    @if ($reminder->user)
                                                        <span style="color:#64748b;">(by {{ $reminder->user->name }})</span>
                                                    @endif
                                                @else
                                                    <span style="color:#b45309;">Pending</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif
                                </td>
                                <td>{{ optional($visitor->created_at)->timezone(app_timezone())->format('h:i A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10">No visitors for this filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @endif
</div>
