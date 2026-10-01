<div
    class="salary-slips-page"
    @salary-slips-panel-opened.window="$wire.$refresh()"
    x-effect="
        document.body.classList.toggle('mu-modal-open', !!$wire.showFormModal);
        document.body.classList.toggle('invoice-modal-open', !!$wire.showPreview);
    "
    x-on:destroy="
        document.body.classList.remove('mu-modal-open');
        document.body.classList.remove('invoice-modal-open');
    "
>
    @if (! empty($denied))
        <p class="manage-users-empty">Only admin or super admin can manage salary slips.</p>
    @else

    <section class="module-workspace__hero" style="margin-bottom: 16px;">
        <div class="module-workspace__hero-main">
            <p class="module-workspace__eyebrow">
                <span class="module-workspace__eyebrow-dot"></span>
                Attendance
            </p>
            <h2 class="module-workspace__title">Salary Slips</h2>
        </div>
        <div>
            <button type="button" class="hero-btn hero-btn--primary" wire:click="openFormModal">
                + Generate Slip
            </button>
        </div>
    </section>

    @if ($successMessage)
        <x-admin-toast wire-property="successMessage" :seconds="6">
            {{ $successMessage }}
        </x-admin-toast>
    @endif

    @if ($errorMessage)
        <x-admin-toast type="error" title="Cannot generate" wire-property="errorMessage" :seconds="6">
            {{ $errorMessage }}
        </x-admin-toast>
    @endif

    <div class="data-panel">
        <div class="data-panel__head">
            <h3 class="data-panel__title">Generated slips</h3>
            <span class="add-account__count">{{ $slips->count() }} shown</span>
        </div>
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Month</th>
                        <th>Gross</th>
                        <th>Deduction</th>
                        <th>Net pay</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($slips as $slip)
                        <tr
                            wire:key="slip-{{ $slip->id }}"
                            style="cursor: pointer;"
                            wire:click="openPreview({{ $slip->id }})"
                        >
                            <td>{{ $slip->user?->name ?? '—' }}</td>
                            <td>{{ $slip->monthLabel() }}</td>
                            <td>Rs {{ number_format((float) $slip->monthly_salary, 0) }}</td>
                            <td>Rs {{ number_format((float) $slip->deduction, 0) }}</td>
                            <td>Rs {{ number_format((float) $slip->net_pay, 0) }}</td>
                            <td>
                                <button
                                    type="button"
                                    class="payment-actions__btn"
                                    wire:click.stop="openPreview({{ $slip->id }})"
                                >View</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">No salary slips yet. Click “+ Generate Slip”.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($showFormModal)
        <div class="mu-modal" wire:keydown.escape.window="closeFormModal">
            <button type="button" class="mu-modal__backdrop" wire:click="closeFormModal" aria-label="Close"></button>

            <div class="mu-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="salary-form-title">
                <div class="mu-modal__head">
                    <h3 id="salary-form-title" class="mu-modal__title">Generate Salary Slip</h3>
                    <button type="button" class="mu-modal__close" wire:click="closeFormModal" aria-label="Close">&times;</button>
                </div>

                <form wire:submit="generate" class="manage-users-form mu-modal__form">
                    <div class="manage-users-form__body">
                        <div class="manage-users-form__grid">
                            <div class="mu-field">
                                <label class="mu-field__label" for="salary-user">Employee</label>
                                <select id="salary-user" class="mu-field__input mu-field__input--select" wire:model="userId">
                                    <option value="">Select staff…</option>
                                    @foreach ($staffUsers as $staff)
                                        <option value="{{ $staff->id }}">
                                            {{ $staff->name }}
                                            @if ($staff->monthly_salary !== null)
                                                — Rs {{ number_format((float) $staff->monthly_salary, 0) }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('userId') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                            </div>

                            <div class="mu-field">
                                <label class="mu-field__label" for="salary-month">Month</label>
                                <input id="salary-month" type="month" class="mu-field__input" wire:model="month">
                                @error('month') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                            </div>

                            <div class="mu-field">
                                <label class="mu-field__label" for="salary-bonus">Bonus (optional)</label>
                                <input
                                    id="salary-bonus"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="mu-field__input"
                                    wire:model="bonus"
                                    placeholder="e.g. 2000"
                                >
                                @error('bonus') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <p style="margin: 12px 0 0; font-size: 0.85rem; color: #64748b;">
                            Deducts absences and unpaid leave at the per-day rate.
                            Unpaid short/half day = 1/4 of daily salary.
                        </p>
                    </div>

                    <div class="mu-modal__footer">
                        <button type="button" class="payment-actions__btn" wire:click="closeFormModal">Cancel</button>
                        <button type="submit" class="hero-btn hero-btn--primary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="generate">Generate</span>
                            <span wire:loading wire:target="generate">Generating...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showPreview && $previewSlip)
        <div
            class="ticket-view-modal"
            wire:key="salary-preview-{{ $previewSlip->id }}"
        >
            <button type="button" class="ticket-view-modal__backdrop" wire:click="closePreview" aria-label="Close"></button>

            <div class="ticket-view-modal__dialog" role="dialog" aria-modal="true" style="max-width: 900px;">
                <div class="ticket-view-modal__head">
                    <div>
                        <p class="ticket-view-modal__eyebrow">Salary slip preview</p>
                        <h3 class="ticket-view-modal__title">{{ $previewSlip->user?->name }}</h3>
                        <p class="ticket-view-modal__meta">
                            {{ $previewSlip->monthLabel() }}
                            · Net Rs {{ number_format((float) $previewSlip->net_pay, 0) }}
                        </p>
                    </div>
                    <button type="button" class="ticket-view-modal__close" wire:click="closePreview" aria-label="Close">&times;</button>
                </div>

                <div class="ticket-view-modal__body" style="background: #fff; padding: 24px;">
                    <x-salary-slip-document :slip="$previewSlip" />
                </div>

                <div style="display:flex; justify-content:flex-end; gap:8px; padding:12px 16px; border-top:1px solid #e5e5e5; background:#fafafa;">
                    <button type="button" class="hero-btn" wire:click="closePreview">Close</button>
                    <a
                        class="hero-btn hero-btn--primary"
                        href="{{ url('/salary-slips/'.$previewSlip->id.'/document?print=1') }}"
                        target="_blank"
                        rel="noopener"
                    >Print / PDF</a>
                </div>
            </div>
        </div>
    @endif

    @endif
</div>
