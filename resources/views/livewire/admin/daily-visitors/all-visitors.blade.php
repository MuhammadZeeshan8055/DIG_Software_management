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

        @if ($successMessage)
            <x-admin-toast
                :title="str_starts_with($successMessage, 'Deleted') ? 'Deleted' : 'Saved'"
                wire-property="successMessage"
                :seconds="5"
            >
                {{ $successMessage }}
            </x-admin-toast>
        @endif

        @if ($errorMessage)
            <x-admin-toast type="error" title="Cannot update" wire-property="errorMessage" :seconds="6">
                {{ $errorMessage }}
            </x-admin-toast>
        @endif

        <div class="my-att-filter data-panel">
            <div class="my-att-filter__inner">
                <div class="my-att-filter__group">
                    <label class="my-att-filter__label" for="av-filter-date">Date</label>
                    <input id="av-filter-date" type="date" class="my-att-filter__input" style="min-width: 150px;" wire:model.live="filter_date">
                </div>
                <div class="my-att-filter__group">
                    <label class="my-att-filter__label" for="av-filter-status">Visit status</label>
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
                    <label class="my-att-filter__label" for="av-filter-case">Case</label>
                    <select id="av-filter-case" class="my-att-filter__input" style="min-width: 160px;" wire:model.live="filter_case">
                        <option value="">All cases</option>
                        <option value="awaiting_review">Awaiting review</option>
                        <option value="pending">Case pending</option>
                        <option value="closed">Case closed</option>
                        <option value="none">No case status</option>
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
                            <th>Visit</th>
                            <th>Case</th>
                            <th>Remarks</th>
                            <th>Follow-up</th>
                            <th>Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($visitors as $visitor)
                            <tr wire:key="all-vis-{{ $visitor->id }}-{{ $visitor->status }}-{{ $visitor->case_status }}">
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
                                <td>
                                    @if ($visitor->case_status === 'awaiting_review')
                                        <span class="payment-badge payment-badge--pending">Awaiting review</span>
                                    @elseif ($visitor->case_status === 'pending')
                                        <span class="payment-badge payment-badge--draft">Case pending</span>
                                    @elseif ($visitor->case_status === 'closed')
                                        <span class="payment-badge payment-badge--paid">Case closed</span>
                                        @if ($visitor->caseClosedBy)
                                            <div style="font-size: 0.75rem; color: #64748b; margin-top: 4px;">
                                                by {{ $visitor->caseClosedBy->name }}
                                            </div>
                                        @endif
                                    @else
                                        <span style="color:#94a3b8;">—</span>
                                    @endif
                                    @if ($visitor->case_admin_note)
                                        <div style="font-size: 0.8rem; color: #475569; margin-top: 4px; max-width: 140px; white-space: normal; word-break: break-word;">
                                            {{ $visitor->case_admin_note }}
                                        </div>
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
                                <td class="manage-users-actions">
                                    @if ($visitor->status === 'completed')
                                        @if ($visitor->case_status === 'closed')
                                            <button type="button" class="payment-actions__btn" wire:click="startReopen({{ $visitor->id }})">
                                                Reopen
                                            </button>
                                        @else
                                            <button type="button" class="hero-btn hero-btn--primary" style="padding: 6px 10px; font-size: 0.75rem;" wire:click="startClose({{ $visitor->id }})">
                                                Close case
                                            </button>
                                            <button type="button" class="payment-actions__btn" wire:click="startPending({{ $visitor->id }})">
                                                Keep pending
                                            </button>
                                        @endif
                                    @endif
                                    @if (auth()->user()->isAdmin())
                                        <button
                                            type="button"
                                            class="payment-actions__btn payment-actions__btn--danger"
                                            @click="$dispatch('confirm-delete-all-visitors', {
                                                id: {{ $visitor->id }},
                                                title: 'Delete visitor?',
                                                message: 'This visitor entry and related reminders will be permanently removed.'
                                            })"
                                        >Delete</button>
                                    @endif
                                </td>
                            </tr>

                            @if ($caseVisitorId === $visitor->id)
                                <tr wire:key="all-vis-case-{{ $visitor->id }}">
                                    <td colspan="12" style="background: #f8fafc; padding: 12px 16px;">
                                        @if ($caseAction === 'close')
                                            <p style="margin: 0 0 8px; font-weight: 600;">Close case — {{ $visitor->name }}</p>
                                        @elseif ($caseAction === 'pending')
                                            <p style="margin: 0 0 8px; font-weight: 600;">Keep case pending — {{ $visitor->name }}</p>
                                        @elseif ($caseAction === 'reopen')
                                            <p style="margin: 0 0 8px; font-weight: 600;">Reopen case (pending) — {{ $visitor->name }}</p>
                                        @else
                                            <p style="margin: 0 0 8px; font-weight: 600;">Update case — {{ $visitor->name }}</p>
                                        @endif
                                        <label class="mu-field__label" for="case-note-{{ $visitor->id }}">Admin note (optional)</label>
                                        <input
                                            id="case-note-{{ $visitor->id }}"
                                            type="text"
                                            class="mu-field__input"
                                            wire:model="case_admin_note"
                                            placeholder="Short reason…"
                                            maxlength="500"
                                            style="margin-top: 6px; margin-bottom: 10px;"
                                        >
                                        @error('case_admin_note') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                                        <div style="display:flex; gap:8px;">
                                            <button type="button" class="payment-actions__btn" wire:click="cancelCaseForm">Cancel</button>
                                            <button type="button" class="hero-btn hero-btn--primary" wire:click="saveCase">Confirm</button>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="12">No visitors for this filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <x-confirm-delete-modal event="confirm-delete-all-visitors" />

    @endif
</div>
