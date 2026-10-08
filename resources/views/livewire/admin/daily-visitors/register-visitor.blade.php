<div
    class="register-visitor-page"
    @daily-visitors-panel-opened.window="$wire.$refresh()"
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
                    Register a visitor, pick a desk and person to meet. Notifications come in the next step.
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
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($todayVisitors as $visitor)
                            <tr wire:key="visitor-{{ $visitor->id }}">
                                <td>{{ $visitor->name }}</td>
                                <td>{{ $visitor->contact_no }}</td>
                                <td>{{ $visitor->purpose }}</td>
                                <td>{{ $visitor->desk?->name ?? '—' }}</td>
                                <td>{{ $visitor->assignedUser?->name ?? '—' }}</td>
                                <td>{{ $visitor->statusLabel() }}</td>
                                <td>{{ optional($visitor->created_at)->timezone(app_timezone())->format('h:i A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">No visitors registered today yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @endif
</div>
