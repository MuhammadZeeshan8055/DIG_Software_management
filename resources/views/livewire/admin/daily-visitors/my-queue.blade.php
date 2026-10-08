<div
    class="my-queue-page"
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

        <div class="data-panel">
            <div class="data-panel__head">
                <h3 class="data-panel__title">Waiting / in progress</h3>
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
                            <th>Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($myVisitors as $visitor)
                            <tr wire:key="my-vis-{{ $visitor->id }}">
                                <td>{{ $visitor->name }}</td>
                                <td>{{ $visitor->contact_no }}</td>
                                <td>{{ $visitor->purpose }}</td>
                                <td>{{ $visitor->desk?->name ?? '—' }}</td>
                                <td>{{ $visitor->statusLabel() }}</td>
                                <td>{{ optional($visitor->created_at)->timezone(app_timezone())->format('h:i A') }}</td>
                                <td class="manage-users-actions">
                                    <button
                                        type="button"
                                        class="payment-actions__btn"
                                        wire:click="pleaseWait({{ $visitor->id }})"
                                    >Please wait</button>
                                    <button
                                        type="button"
                                        class="payment-actions__btn"
                                        wire:click="sendNow({{ $visitor->id }})"
                                    >Send now</button>
                                    <button
                                        type="button"
                                        class="hero-btn hero-btn--primary"
                                        style="padding: 6px 10px; font-size: 0.75rem;"
                                        wire:click="startComplete({{ $visitor->id }})"
                                    >Complete</button>
                                </td>
                            </tr>

                            @if ($completingId === $visitor->id)
                                <tr wire:key="my-vis-complete-{{ $visitor->id }}">
                                    <td colspan="7" style="background: #f8fafc; padding: 12px 16px;">
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
                                <td colspan="7">No open visitors for you today.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @endif
</div>
