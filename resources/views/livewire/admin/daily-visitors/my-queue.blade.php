<div
    class="my-queue-page"
    wire:poll.5s.visible
    @my-queue-panel-opened.window="$wire.$refresh()"
    @staff-list-changed.window="$wire.$refresh()"
>
    @if ($denied ?? false)
        <div class="data-panel">
            <p>You do not have access to My Visitors. Ask an admin to enable Daily Visitors → My Visitors in Manage Users.</p>
        </div>
    @else

        <section class="module-workspace__hero" style="margin-bottom: 16px;">
            <div class="module-workspace__hero-main">
                <p class="module-workspace__eyebrow">
                    <span class="module-workspace__eyebrow-dot"></span>
                    Daily Visitors
                </p>
                <h2 class="module-workspace__title">My Visitors</h2>
                <p class="module-workspace__desc" style="margin: 8px 0 0; font-size: 0.9rem; opacity: 0.85;">
                    Visitors sent to meet you. Use <strong>Please wait</strong> or <strong>Send now</strong>, then <strong>Complete</strong> with remarks.
                </p>
            </div>
        </section>

        @if ($successMessage)
            <x-admin-toast wire-property="successMessage" :seconds="6">
                {{ $successMessage }}
            </x-admin-toast>
        @endif

        @if ($errorMessage)
            <x-admin-toast type="error" title="Cannot update" wire-property="errorMessage" :seconds="6">
                {{ $errorMessage }}
            </x-admin-toast>
        @endif

        <div class="stat-grid" style="margin-bottom: 16px;">
            <article class="stat-card stat-card--amber">
                <div class="stat-card__top">
                    <p class="stat-card__label">In queue</p>
                </div>
                <p class="stat-card__value">{{ $countInQueue }}</p>
                <p class="stat-card__hint">For selected date</p>
            </article>
            <article class="stat-card stat-card--blue">
                <div class="stat-card__top">
                    <p class="stat-card__label">In meeting</p>
                </div>
                <p class="stat-card__value">{{ $countMeeting }}</p>
                <p class="stat-card__hint">For selected date</p>
            </article>
            <article class="stat-card stat-card--navy">
                <div class="stat-card__top">
                    <p class="stat-card__label">Done</p>
                </div>
                <p class="stat-card__value">{{ $countDone }}</p>
                <p class="stat-card__hint">For selected date</p>
            </article>
        </div>

        <div class="my-att-filter data-panel">
            <div class="my-att-filter__inner">
                <div class="my-att-filter__group">
                    <label class="my-att-filter__label" for="mq-filter-date">Date</label>
                    <input id="mq-filter-date" type="date" class="my-att-filter__input" style="min-width: 150px;" wire:model.live="filter_date">
                </div>
                <div class="my-att-filter__group">
                    <label class="my-att-filter__label" for="mq-filter-status">Status</label>
                    <select id="mq-filter-status" class="my-att-filter__input" style="min-width: 150px;" wire:model.live="filter_status">
                        <option value="">All statuses</option>
                        <option value="waiting">Waiting</option>
                        <option value="please_wait">Please wait</option>
                        <option value="send_now">Send now</option>
                        <option value="in_meeting">In meeting</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <button type="button" class="payment-actions__btn" wire:click="resetFilters">Today</button>
            </div>
        </div>

        <div class="data-panel">
            <div class="data-panel__head">
                <h3 class="data-panel__title">Visitors</h3>
                <span class="add-account__count">{{ $myVisitors->count() }} shown</span>
            </div>
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Purpose</th>
                            <th>Desk</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th>Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($myVisitors as $visitor)
                            <tr wire:key="my-vis-{{ $visitor->id }}-{{ $visitor->status }}">
                                <td>{{ $visitor->name }}</td>
                                <td>{{ $visitor->contact_no }}</td>
                                <td>
                                    <span style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; white-space: normal; word-break: break-word; max-width: 220px; line-height: 1.35;">
                                        {{ $visitor->purpose }}
                                    </span>
                                </td>
                                <td>{{ $visitor->desk?->name ?? '—' }}</td>
                                <td>
                                    @if ($visitor->status === 'send_now')
                                        <span class="payment-badge payment-badge--paid">{{ $visitor->statusLabel() }}</span>
                                    @elseif ($visitor->status === 'please_wait')
                                        <span class="payment-badge payment-badge--draft">{{ $visitor->statusLabel() }}</span>
                                    @elseif ($visitor->status === 'in_meeting')
                                        <span class="payment-badge payment-badge--paid">{{ $visitor->statusLabel() }}</span>
                                    @elseif ($visitor->status === 'completed')
                                        <span class="payment-badge payment-badge--paid">{{ $visitor->statusLabel() }}</span>
                                    @else
                                        <span class="payment-badge">{{ $visitor->statusLabel() }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; white-space: normal; word-break: break-word; max-width: 180px; line-height: 1.35;">
                                        {{ $visitor->remarks ?: '—' }}
                                    </span>
                                </td>
                                <td>{{ optional($visitor->created_at)->timezone(app_timezone())->format('h:i A') }}</td>
                                <td class="manage-users-actions">
                                    @if ($visitor->status === 'completed')
                                        —
                                    @else
                                    <button
                                        type="button"
                                        class="payment-actions__btn"
                                        @if ($visitor->status === 'please_wait')
                                            style="border: 2px solid #0f766e; background: #ccfbf1; color: #0f766e; font-weight: 700;"
                                        @endif
                                        wire:click="pleaseWait({{ $visitor->id }})"
                                    >
                                        Please wait
                                        @if ($visitor->status === 'please_wait')
                                            ✓
                                        @endif
                                    </button>
                                    <button
                                        type="button"
                                        class="payment-actions__btn"
                                        @if ($visitor->status === 'send_now')
                                            style="border: 2px solid #166534; background: #dcfce7; color: #166534; font-weight: 700;"
                                        @endif
                                        wire:click="sendNow({{ $visitor->id }})"
                                    >
                                        Send now
                                        @if ($visitor->status === 'send_now')
                                            ✓
                                        @endif
                                    </button>
                                    <button
                                        type="button"
                                        class="hero-btn hero-btn--primary"
                                        style="padding: 6px 10px; font-size: 0.75rem;"
                                        wire:click="startComplete({{ $visitor->id }})"
                                    >Complete</button>
                                    @endif
                                </td>
                            </tr>

                            @if ($completingId === $visitor->id && $visitor->status !== 'completed')
                                <tr wire:key="my-vis-complete-{{ $visitor->id }}">
                                    <td colspan="8" style="background: #f8fafc; padding: 12px 16px;">
                                        <label class="mu-field__label" for="vis-remarks-{{ $visitor->id }}">Remarks (required)</label>
                                        <input
                                            id="vis-remarks-{{ $visitor->id }}"
                                            type="text"
                                            class="mu-field__input"
                                            wire:model="remarks"
                                            placeholder="Short note: info given, next step, etc."
                                            maxlength="500"
                                            style="margin-top: 6px; margin-bottom: 10px;"
                                        >
                                        @error('remarks') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                                        <div style="display:flex; gap:8px;">
                                            <button type="button" class="payment-actions__btn" wire:click="cancelComplete">Cancel</button>
                                            <button type="button" class="hero-btn hero-btn--primary" wire:click="complete">Save &amp; complete</button>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="8">No visitors for this filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @endif
</div>
