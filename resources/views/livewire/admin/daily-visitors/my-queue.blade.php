<div
    class="my-queue-page"
    wire:poll.5s.visible
    @my-queue-panel-opened.window="$wire.$refresh()"
    @staff-list-changed.window="$wire.$refresh()"
>
    @if ($denied ?? false)
        <div class="data-panel">
            <p>You do not have access to My Visitors.</p>
        </div>
    @else

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
                <div class="stat-card__top"><p class="stat-card__label">In queue</p></div>
                <p class="stat-card__value">{{ $countInQueue }}</p>
                <p class="stat-card__hint">For selected date</p>
            </article>
            <article class="stat-card stat-card--blue">
                <div class="stat-card__top"><p class="stat-card__label">In meeting</p></div>
                <p class="stat-card__value">{{ $countMeeting }}</p>
                <p class="stat-card__hint">For selected date</p>
            </article>
            <article class="stat-card stat-card--navy">
                <div class="stat-card__top"><p class="stat-card__label">Done</p></div>
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
                                <td style="max-width: 220px; white-space: normal; word-break: break-word;">{{ $visitor->purpose }}</td>
                                <td>{{ $visitor->desk?->name ?? '—' }}</td>
                                <td>
                                    @if ($visitor->status === 'please_wait')
                                        <span class="payment-badge payment-badge--draft">{{ $visitor->statusLabel() }}</span>
                                    @elseif (in_array($visitor->status, ['send_now', 'in_meeting', 'completed'], true))
                                        <span class="payment-badge payment-badge--paid">{{ $visitor->statusLabel() }}</span>
                                    @else
                                        <span class="payment-badge">{{ $visitor->statusLabel() }}</span>
                                    @endif
                                </td>
                                <td style="max-width: 180px; white-space: normal; word-break: break-word;">{{ $visitor->remarks ?: '—' }}</td>
                                <td>{{ optional($visitor->created_at)->timezone(app_timezone())->format('h:i A') }}</td>
                                <td class="manage-users-actions">
                                    @if ($visitor->status === 'completed')
                                        @php $openReminder = $visitor->reminders->first(); @endphp
                                        <button type="button" class="payment-actions__btn" wire:click="startReminder({{ $visitor->id }})">
                                            @if ($openReminder)
                                                Reminder {{ $openReminder->remind_on->format('d M') }}
                                            @else
                                                Set reminder
                                            @endif
                                        </button>
                                    @else
                                        <button type="button" class="payment-actions__btn" wire:click="pleaseWait({{ $visitor->id }})">
                                            Please wait @if ($visitor->status === 'please_wait') ✓ @endif
                                        </button>
                                        <button type="button" class="payment-actions__btn" wire:click="sendNow({{ $visitor->id }})">
                                            Send now @if ($visitor->status === 'send_now') ✓ @endif
                                        </button>
                                        <button type="button" class="hero-btn hero-btn--primary" style="padding: 6px 10px; font-size: 0.75rem;" wire:click="startComplete({{ $visitor->id }})">Complete</button>
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
                                            placeholder="Short note…"
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

                            @if ($remindingId === $visitor->id && $visitor->status === 'completed')
                                <tr wire:key="my-vis-remind-{{ $visitor->id }}">
                                    <td colspan="8" style="background: #f8fafc; padding: 12px 16px;">
                                        <p style="margin: 0 0 10px; font-size: 0.85rem; color: #64748b;">Optional — only save if you need a follow-up.</p>
                                        <div class="manage-users-form__grid" style="margin-bottom: 10px;">
                                            <div class="mu-field">
                                                <label class="mu-field__label" for="remind-on-{{ $visitor->id }}">Remind on</label>
                                                <input
                                                    id="remind-on-{{ $visitor->id }}"
                                                    type="date"
                                                    class="mu-field__input"
                                                    wire:model="remind_on"
                                                >
                                                @error('remind_on') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="mu-field" style="grid-column: 1 / -1;">
                                                <label class="mu-field__label" for="remind-note-{{ $visitor->id }}">Note (optional)</label>
                                                <input
                                                    id="remind-note-{{ $visitor->id }}"
                                                    type="text"
                                                    class="mu-field__input"
                                                    wire:model="remind_note"
                                                    placeholder="e.g. Call back about package"
                                                    maxlength="255"
                                                >
                                                @error('remind_note') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                                            </div>
                                        </div>
                                        <div style="display:flex; gap:8px; flex-wrap: wrap;">
                                            <button type="button" class="payment-actions__btn" wire:click="cancelReminder">Cancel</button>
                                            @if ($visitor->reminders->isNotEmpty())
                                                <button type="button" class="payment-actions__btn" wire:click="removeReminder({{ $visitor->id }})" wire:confirm="Remove this reminder?">Remove reminder</button>
                                            @endif
                                            <button type="button" class="hero-btn hero-btn--primary" wire:click="saveReminder">Save reminder</button>
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
