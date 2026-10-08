<div
    class="my-reminders-page"
    wire:poll.10s.visible
    @reminders-panel-opened.window="$wire.$refresh()"
>
    @if ($denied ?? false)
        <div class="data-panel">
            <p>You do not have access to Reminders.</p>
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
                <div class="stat-card__top"><p class="stat-card__label">Open</p></div>
                <p class="stat-card__value">{{ $countOpen }}</p>
            </article>
            <article class="stat-card stat-card--blue">
                <div class="stat-card__top"><p class="stat-card__label">Due today / overdue</p></div>
                <p class="stat-card__value">{{ $countDue }}</p>
            </article>
            <article class="stat-card stat-card--navy">
                <div class="stat-card__top"><p class="stat-card__label">Done</p></div>
                <p class="stat-card__value">{{ $countDone }}</p>
            </article>
        </div>

        <div class="my-att-filter data-panel">
            <div class="my-att-filter__inner">
                <div class="my-att-filter__group">
                    <label class="my-att-filter__label" for="rem-filter">Show</label>
                    <select id="rem-filter" class="my-att-filter__input" style="min-width: 150px;" wire:model.live="filter">
                        <option value="open">Open</option>
                        <option value="done">Done</option>
                        <option value="all">All</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="data-panel">
            <div class="data-panel__head">
                <h3 class="data-panel__title">Reminders</h3>
                <span class="add-account__count">{{ $reminders->count() }} shown</span>
            </div>
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Visitor</th>
                            <th>Contact</th>
                            <th>Purpose</th>
                            <th>Remind on</th>
                            <th>Note</th>
                            <th>Outcome</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reminders as $reminder)
                            @php
                                $visitor = $reminder->visitor;
                                $today = now(app_timezone())->toDateString();
                                $isDue = ! $reminder->is_done && $reminder->remind_on->toDateString() <= $today;
                            @endphp
                            <tr wire:key="reminder-{{ $reminder->id }}">
                                <td>{{ $visitor?->name ?? '—' }}</td>
                                <td>{{ $visitor?->contact_no ?? '—' }}</td>
                                <td style="max-width: 200px; white-space: normal; word-break: break-word;">{{ $visitor?->purpose ?? '—' }}</td>
                                <td>{{ $reminder->remind_on->format('d M Y') }}</td>
                                <td style="max-width: 160px; white-space: normal; word-break: break-word;">{{ $reminder->note ?: '—' }}</td>
                                <td style="max-width: 180px; white-space: normal; word-break: break-word;">{{ $reminder->done_note ?: '—' }}</td>
                                <td>
                                    @if ($reminder->is_done)
                                        <span class="payment-badge payment-badge--paid">Done</span>
                                    @elseif ($isDue)
                                        <span class="payment-badge payment-badge--draft">Due</span>
                                    @else
                                        <span class="payment-badge">Upcoming</span>
                                    @endif
                                </td>
                                <td class="manage-users-actions">
                                    @if (! $reminder->is_done)
                                        <button type="button" class="hero-btn hero-btn--primary" style="padding: 6px 10px; font-size: 0.75rem;" wire:click="startMarkDone({{ $reminder->id }})">Mark done</button>
                                        <button type="button" class="payment-actions__btn" wire:click="removeReminder({{ $reminder->id }})" wire:confirm="Remove this reminder?">Remove</button>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>

                            @if ($completingId === $reminder->id && ! $reminder->is_done)
                                <tr wire:key="reminder-done-{{ $reminder->id }}">
                                    <td colspan="8" style="background: #f8fafc; padding: 12px 16px;">
                                        <label class="mu-field__label" for="done-note-{{ $reminder->id }}">What happened? (required)</label>
                                        <input
                                            id="done-note-{{ $reminder->id }}"
                                            type="text"
                                            class="mu-field__input"
                                            wire:model="done_note"
                                            placeholder="e.g. Called back — booked package"
                                            maxlength="500"
                                            style="margin-top: 6px; margin-bottom: 10px;"
                                        >
                                        @error('done_note') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                                        <div style="display:flex; gap:8px;">
                                            <button type="button" class="payment-actions__btn" wire:click="cancelMarkDone">Cancel</button>
                                            <button type="button" class="hero-btn hero-btn--primary" wire:click="saveMarkDone">Save &amp; mark done</button>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="8">No reminders for this filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @endif
</div>
