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

        <section class="module-workspace__hero" style="margin-bottom: 16px;">
            <div class="module-workspace__hero-main">
                <p class="module-workspace__eyebrow">
                    <span class="module-workspace__eyebrow-dot"></span>
                    Operations
                </p>
                <h2 class="module-workspace__title">Daily Visitors</h2>
                <p class="module-workspace__desc" style="margin: 8px 0 0; font-size: 0.9rem; opacity: 0.85;">
                    Register a visitor, pick a desk and person to meet. Today’s list updates every few seconds when status changes.
                </p>
            </div>
        </section>

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
                <div class="stat-card__top">
                    <p class="stat-card__label">In queue</p>
                </div>
                <p class="stat-card__value">{{ $countInQueue }}</p>
                <p class="stat-card__hint">Waiting / please wait / send now</p>
            </article>
            <article class="stat-card stat-card--blue">
                <div class="stat-card__top">
                    <p class="stat-card__label">In meeting</p>
                </div>
                <p class="stat-card__value">{{ $countMeeting }}</p>
                <p class="stat-card__hint">Sent to desk</p>
            </article>
            <article class="stat-card stat-card--navy">
                <div class="stat-card__top">
                    <p class="stat-card__label">Done</p>
                </div>
                <p class="stat-card__value">{{ $countDone }}</p>
                <p class="stat-card__hint">Completed today</p>
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
                            <span style="color:#b91c1c;font-size:12px;">No staff assigned to this desk yet. Set desk in Manage Users.</span>
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

        <div class="data-panel">
            <div class="data-panel__head">
                <h3 class="data-panel__title">Today’s visitors</h3>
                <span class="add-account__count">{{ $todayVisitors->count() }} shown</span>
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
                            <th>Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($todayVisitors as $visitor)
                            <tr wire:key="visitor-{{ $visitor->id }}-{{ $visitor->status }}">
                                <td>{{ $visitor->name }}</td>
                                <td>{{ $visitor->contact_no }}</td>
                                <td>
                                    <span style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; white-space: normal; word-break: break-word; max-width: 220px; line-height: 1.35;">
                                        {{ $visitor->purpose }}
                                    </span>
                                </td>
                                <td>{{ $visitor->desk?->name ?? '—' }}</td>
                                <td>{{ $visitor->assignedUser?->name ?? '—' }}</td>
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
                                <td>{{ optional($visitor->created_at)->timezone(app_timezone())->format('h:i A') }}</td>
                                <td class="manage-users-actions">
                                    @if ($visitor->status === 'in_meeting')
                                        <button
                                            type="button"
                                            class="payment-actions__btn"
                                            style="border: 2px solid #166534; background: #dcfce7; color: #166534; font-weight: 700;"
                                            disabled
                                        >Sent ✓</button>
                                    @elseif ($visitor->status !== 'completed')
                                        <button
                                            type="button"
                                            class="hero-btn hero-btn--primary"
                                            style="padding: 6px 10px; font-size: 0.75rem;"
                                            wire:click="markSent({{ $visitor->id }})"
                                        >Mark sent</button>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">No visitors registered today yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @endif
</div>
