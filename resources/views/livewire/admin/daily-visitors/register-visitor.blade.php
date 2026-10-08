<div
    class="register-visitor-page"
    wire:poll.5s.visible
    @daily-visitors-panel-opened.window="$wire.$refresh()"
    @staff-list-changed.window="$wire.$refresh()"
>
    @if ($denied ?? false)
        <div class="data-panel">
            <p>You do not have access to Daily Visitors. Ask an admin to enable it in Manage Users.</p>
        </div>
    @else

        @if ($successMessage)
            <x-admin-toast wire-property="successMessage" :seconds="6">
                {{ $successMessage }}
            </x-admin-toast>
        @endif

        @if ($errorMessage)
            <x-admin-toast type="error" title="Cannot save" wire-property="errorMessage" :seconds="6">
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
            <article class="stat-card stat-card--green">
                <div class="stat-card__top"><p class="stat-card__label">Done</p></div>
                <p class="stat-card__value">{{ $countDone }}</p>
                <p class="stat-card__hint">For selected date</p>
            </article>
        </div>

        <div class="data-panel" style="margin-bottom: 16px;">
            <div class="data-panel__head">
                <h3 class="data-panel__title">Register visitor</h3>
            </div>

            <form wire:submit="save" class="manage-users-form" style="padding: 16px;">
                <div class="manage-users-form__grid">
                    <div class="mu-field">
                        <label class="mu-field__label" for="vis-name">Name</label>
                        <input id="vis-name" type="text" class="mu-field__input" wire:model="name" placeholder="Visitor full name" maxlength="120">
                        @error('name') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                    </div>

                    <div class="mu-field">
                        <label class="mu-field__label" for="vis-contact">Contact No</label>
                        <input id="vis-contact" type="text" class="mu-field__input" wire:model="contact_no" placeholder="Phone number" maxlength="40">
                        @error('contact_no') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                    </div>

                    <div class="mu-field" style="grid-column: 1 / -1;">
                        <label class="mu-field__label" for="vis-purpose">Purpose</label>
                        <input id="vis-purpose" type="text" class="mu-field__input" wire:model="purpose" placeholder="Why are they visiting?" maxlength="255">
                        @error('purpose') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                    </div>

                    <div class="mu-field">
                        <label class="mu-field__label" for="vis-desk">Desk</label>
                        <select id="vis-desk" class="mu-field__input mu-field__input--select" wire:model.live="desk_id">
                            <option value="">Select desk…</option>
                            @foreach ($desks as $desk)
                                <option value="{{ $desk->id }}">{{ $desk->name }}</option>
                            @endforeach
                        </select>
                        @error('desk_id') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                    </div>

                    <div class="mu-field">
                        <label class="mu-field__label" for="vis-meet">Meet with</label>
                        <select id="vis-meet" class="mu-field__input mu-field__input--select" wire:model="assigned_to">
                            <option value="">Select person…</option>
                            @foreach ($staffList as $staff)
                                <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                            @endforeach
                        </select>
                        @error('assigned_to') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                        @if ($desk_id !== '' && $staffList->isEmpty())
                            <span style="color:#b91c1c;font-size:12px;">No staff on this desk yet. Set desk in Manage Users.</span>
                        @endif
                    </div>
                </div>

                <div style="margin-top: 14px;">
                    <button type="submit" class="hero-btn hero-btn--primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">Register visitor</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="my-att-filter data-panel">
            <div class="my-att-filter__inner">
                <div class="my-att-filter__group">
                    <label class="my-att-filter__label" for="filter-date">Date</label>
                    <input id="filter-date" type="date" class="my-att-filter__input" style="min-width: 150px;" wire:model.live="filter_date">
                </div>
                <div class="my-att-filter__group">
                    <label class="my-att-filter__label" for="filter-status">Status</label>
                    <select id="filter-status" class="my-att-filter__input" style="min-width: 150px;" wire:model.live="filter_status">
                        <option value="">All statuses</option>
                        <option value="waiting">Waiting</option>
                        <option value="please_wait">Please wait</option>
                        <option value="send_now">Send now</option>
                        <option value="in_meeting">In meeting</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div class="my-att-filter__group">
                    <label class="my-att-filter__label" for="filter-desk">Desk</label>
                    <select id="filter-desk" class="my-att-filter__input" style="min-width: 150px;" wire:model.live="filter_desk">
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
                <h3 class="data-panel__title">Visitors</h3>
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
                            <th>Status</th>
                            <th>Remarks</th>
                            <th>Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($visitors as $visitor)
                            <tr wire:key="visitor-{{ $visitor->id }}-{{ $visitor->status }}">
                                <td>{{ $visitor->name }}</td>
                                <td>{{ $visitor->contact_no }}</td>
                                <td style="max-width: 220px; white-space: normal; word-break: break-word;">{{ $visitor->purpose }}</td>
                                <td>{{ $visitor->desk?->name ?? '—' }}</td>
                                <td>{{ $visitor->assignedUser?->name ?? '—' }}</td>
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
                                    @if ($visitor->status === 'in_meeting')
                                        <button type="button" class="payment-actions__btn" disabled>Sent ✓</button>
                                    @elseif ($visitor->status !== 'completed')
                                        <button type="button" class="hero-btn hero-btn--primary" style="padding: 6px 10px; font-size: 0.75rem;" wire:click="markSent({{ $visitor->id }})">Mark sent</button>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">No visitors for this filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @endif
</div>
