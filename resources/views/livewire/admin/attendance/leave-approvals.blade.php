<div class="leave-approvals-page" wire:poll.10s.visible>
    @if ($denied ?? false)
        <div class="data-panel">
            <p>Only admin or super admin can approve leave.</p>
        </div>
    @else

        <section class="module-workspace__hero" style="margin-bottom: 16px;">
            <div class="module-workspace__hero-main">
                <p class="module-workspace__eyebrow">
                    <span class="module-workspace__eyebrow-dot"></span>
                    Leave
                </p>
                <h2 class="module-workspace__title">Leave Approvals</h2>
                <p class="module-workspace__desc" style="margin: 8px 0 0; font-size: 0.9rem; opacity: 0.85;">
                    Approve or reject staff leave. Your name is stored as the approver.
                </p>
            </div>
        </section>

        @if ($successMessage)
            <x-admin-toast wire-property="successMessage" :seconds="6">
                {{ $successMessage }}
            </x-admin-toast>
        @endif

        @if ($errorMessage)
            <x-admin-toast type="error" title="Failed" wire-property="errorMessage" :seconds="6">
                {{ $errorMessage }}
            </x-admin-toast>
        @endif

        <div class="data-panel">
            <div class="data-panel__head">
                <h3 class="data-panel__title">Pending requests</h3>
            </div>
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Type</th>
                            <th>Dates</th>
                            <th>Days</th>
                            <th>Reason</th>
                            <th>Submitted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pending as $req)
                            <tr wire:key="pending-leave-{{ $req->id }}">
                                <td>{{ $req->user?->name ?? '—' }}</td>
                                <td>{{ $req->leave_type === 'half' ? 'Half day' : 'Full day' }}</td>
                                <td>
                                    @if ($req->leave_type === 'half' || $req->from_date->equalTo($req->to_date))
                                        {{ format_date($req->from_date, 'd M Y') }}
                                    @else
                                        {{ format_date($req->from_date, 'd M Y') }}
                                        –
                                        {{ format_date($req->to_date, 'd M Y') }}
                                    @endif
                                </td>
                                <td>
                                    @if ($req->leave_type === 'half')
                                        {{ $req->half_days }} half
                                    @else
                                        {{ $req->full_days }} full
                                    @endif
                                </td>
                                <td>{{ $req->reason ?: '—' }}</td>
                                <td>{{ format_datetime($req->created_at, 'd M Y, h:i A') }}</td>
                                <td>
                                    <div class="leave-approvals__actions">
                                        <button
                                            type="button"
                                            class="leave-approvals__btn leave-approvals__btn--ok"
                                            wire:click="approve({{ $req->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="approve({{ $req->id }})"
                                        >Approve</button>
                                        <button
                                            type="button"
                                            class="leave-approvals__btn leave-approvals__btn--no"
                                            wire:click="reject({{ $req->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="reject({{ $req->id }})"
                                        >Reject</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">No pending leave requests.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="data-panel" style="margin-top: 16px;">
            <div class="data-panel__head">
                <h3 class="data-panel__title">Recent decisions</h3>
            </div>
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Type</th>
                            <th>Dates</th>
                            <th>Status</th>
                            <th>Decided by</th>
                            <th>When</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recent as $req)
                            <tr
                                wire:key="recent-leave-{{ $req->id }}"
                                x-data="{ status: @js($req->status) }"
                                x-bind:class="{ 'leave-row--rejected': status === 'rejected' }"
                            >
                                <td>{{ $req->user?->name ?? '—' }}</td>
                                <td>{{ $req->leave_type === 'half' ? 'Half day' : 'Full day' }}</td>
                                <td>
                                    @if ($req->leave_type === 'half' || $req->from_date->equalTo($req->to_date))
                                        {{ format_date($req->from_date, 'd M Y') }}
                                    @else
                                        {{ format_date($req->from_date, 'd M Y') }}
                                        –
                                        {{ format_date($req->to_date, 'd M Y') }}
                                    @endif
                                </td>
                                <td>{{ ucfirst($req->status) }}</td>
                                <td>{{ $req->approver?->name ?? '—' }}</td>
                                <td>{{ format_datetime($req->updated_at, 'd M Y, h:i A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">No decisions yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
