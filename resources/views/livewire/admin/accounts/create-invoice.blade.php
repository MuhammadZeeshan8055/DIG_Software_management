<div class="create-invoice-page" @create-invoice-panel-opened.window="$wire.$refresh()">
    <section class="module-workspace__hero" style="margin-bottom: 16px;">
        <div class="module-workspace__hero-main">
            <p class="module-workspace__eyebrow">
                <span class="module-workspace__eyebrow-dot"></span>
                Accounts
            </p>
            <h2 class="module-workspace__title">Create Invoice</h2>
        </div>
    </section>

    @if ($successMessage)
    <x-admin-alert type="success" wire-property="successMessage">
        {{ $successMessage }}
    </x-admin-alert>
    @endif

    <div class="data-panel" style="margin-bottom: 16px;">
        <div class="data-panel__head">
            <h3 class="data-panel__title">Invoice details</h3>
        </div>

        <form wire:submit="save" class="manage-users-form" style="padding: 16px;">
            <div class="manage-users-form__grid">
                <div class="mu-field">
                    <label class="mu-field__label">Invoice date</label>
                    <input type="date" class="mu-field__input" wire:model="invoice_date">
                    @error('invoice_date') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                </div>

                <div class="mu-field">
                    <label class="mu-field__label">Due date</label>
                    <input type="date" class="mu-field__input" wire:model="due_date">
                    @error('due_date') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                </div>

                <div class="mu-field">
                    <label class="mu-field__label">Status</label>
                    <select class="mu-field__input" wire:model="status">
                        @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                </div>

                <div class="mu-field">
                    <label class="mu-field__label">Service category</label>
                    <select class="mu-field__input" wire:model.live="service_category">
                        @foreach ($categories as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('service_category') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                </div>

                <div class="mu-field">
                    <label class="mu-field__label">Package days (optional)</label>
                    <input type="text" class="mu-field__input" wire:model.live="package_days" placeholder="e.g. 21">
                </div>

                <div class="mu-field">
                    <label class="mu-field__label">Package label</label>
                    <input type="text" class="mu-field__input" wire:model="package_label" placeholder="Umrah Package ( 21 Days )">
                    @error('package_label') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                </div>
            </div>

            <h4 class="data-panel__title" style="margin: 20px 0 12px;">Customer</h4>
            <div class="manage-users-form__grid">
                <div class="mu-field">
                    <label class="mu-field__label">Customer name</label>
                    <input type="text" class="mu-field__input" wire:model="customer_name">
                    @error('customer_name') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                </div>

                <div class="mu-field">
                    <label class="mu-field__label">Contact number</label>
                    <input type="text" class="mu-field__input" wire:model="customer_phone">
                    @error('customer_phone') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                </div>

                <div class="mu-field">
                    <label class="mu-field__label">Email (optional)</label>
                    <input type="email" class="mu-field__input" wire:model="customer_email">
                    @error('customer_email') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                </div>

                <div class="mu-field">
                    <label class="mu-field__label">CNIC / Passport (optional)</label>
                    <input type="text" class="mu-field__input" wire:model="cnic_passport">
                </div>

                <div class="mu-field">
                    <label class="mu-field__label">Reference number (optional)</label>
                    <input type="text" class="mu-field__input" wire:model="reference_number">
                </div>

                <div class="mu-field" style="grid-column: 1 / -1;">
                    <label class="mu-field__label">Address (optional)</label>
                    <input type="text" class="mu-field__input" wire:model="customer_address">
                </div>
            </div>

            <h4 class="data-panel__title" style="margin: 20px 0 12px;">Line items</h4>
            <div class="data-table-wrap" style="margin-bottom: 12px;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th style="width: 90px;">Qty</th>
                            <th style="width: 140px;">Price</th>
                            <th style="width: 140px;">Amount</th>
                            <th style="width: 70px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $index => $item)
                        <tr wire:key="item-{{ $index }}">
                            <td>
                                <input type="text" class="mu-field__input" wire:model="items.{{ $index }}.description" placeholder="e.g. Visa">
                                @error('items.'.$index.'.description') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                            </td>
                            <td>
                                <input type="number" min="1" class="mu-field__input" wire:model.live="items.{{ $index }}.qty">
                            </td>
                            <td>
                                <input type="number" min="0" step="0.01" class="mu-field__input" wire:model.live="items.{{ $index }}.unit_price">
                            </td>
                            <td>
                                Rs {{ number_format(((float) ($item['qty'] ?? 0)) * ((float) ($item['unit_price'] ?? 0)), 0) }}
                            </td>
                            <td>
                                <button type="button" class="payment-actions__btn" wire:click="removeItem({{ $index }})">Remove</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <button type="button" class="hero-btn" wire:click="addItem" style="margin-bottom: 16px;">+ Add item</button>

            <div class="manage-users-form__grid" style="max-width: 420px; margin-left: auto;">
                <div class="mu-field">
                    <label class="mu-field__label">Tax %</label>
                    <input type="number" min="0" max="100" step="0.01" class="mu-field__input" wire:model.live="tax_percent">
                </div>
                <div class="mu-field">
                    <label class="mu-field__label">Subtotal</label>
                    <input type="text" class="mu-field__input" value="Rs {{ number_format($this->subtotal, 0) }}" disabled>
                </div>
                <div class="mu-field">
                    <label class="mu-field__label">Tax</label>
                    <input type="text" class="mu-field__input" value="Rs {{ number_format($this->taxAmount, 0) }}" disabled>
                </div>
                <div class="mu-field">
                    <label class="mu-field__label">Total</label>
                    <input type="text" class="mu-field__input" value="Rs {{ number_format($this->total, 0) }}" disabled>
                </div>
            </div>

            <div style="margin-top: 16px; display: flex; justify-content: flex-end;">
                <button type="submit" class="hero-btn hero-btn--primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">Save &amp; Preview</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>

            <x-validation-errors class="import-ticket__validation-errors" />
        </form>
    </div>

    <div class="data-panel">
        <div class="data-panel__head">
            <h3 class="data-panel__title">Recent invoices</h3>
        </div>
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Invoice No</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                    <tr
                        wire:key="inv-{{ $invoice->id }}"
                        style="cursor: pointer;"
                        wire:click="openPreview({{ $invoice->id }})">
                        <td>{{ $invoice->invoice_number }}</td>
                        <td>{{ $invoice->customer_name }}</td>
                        <td>Rs {{ number_format((float) $invoice->total_amount, 0) }}</td>
                        <td>Rs {{ number_format((float) $invoice->balance, 0) }}</td>
                        <td>{{ $invoice->statusLabel() }}</td>
                        <td>{{ optional($invoice->invoice_date)->format('Y-m-d') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6">No invoices yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($showPreview && $previewInvoice)
    <div
        class="ticket-view-modal"
        wire:key="invoice-preview-{{ $previewInvoice->id }}"
        style="z-index: 80;">
        <button type="button" class="ticket-view-modal__backdrop" wire:click="closePreview" aria-label="Close"></button>

        <div class="ticket-view-modal__dialog" role="dialog" aria-modal="true" style="max-width: 900px;">
            <div class="ticket-view-modal__head">
                <div>
                    <p class="ticket-view-modal__eyebrow">Invoice preview</p>
                    <h3 class="ticket-view-modal__title">{{ $previewInvoice->invoice_number }}</h3>
                    <p class="ticket-view-modal__meta">
                        {{ optional($previewInvoice->invoice_date)->format('Y-m-d') }}
                        · Due {{ optional($previewInvoice->due_date)->format('Y-m-d') ?: '—' }}
                        · {{ $previewInvoice->statusLabel() }}
                    </p>
                </div>
                <button type="button" class="ticket-view-modal__close" wire:click="closePreview" aria-label="Close">&times;</button>
            </div>

            <div class="ticket-view-modal__body" style="background: #fff; padding: 24px;">
                {{-- Simple preview layout (print template polished in next steps) --}}
                <div style="display:flex; justify-content:space-between; gap:16px; margin-bottom:20px;">
                    <div>
                        <img src="{{ asset('images/logo-icon.png') }}" alt="DHOTHAR" style="height:48px; margin-bottom:8px;">
                        <div style="font-weight:700;">Dhothar International Group</div>
                        <div style="color:#c4a574; font-size:12px;">TRAVEL &amp; TOURS (Pvt. Ltd.)</div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-weight:700; font-size:18px;">Invoice {{ $previewInvoice->invoice_number }}</div>
                        <div style="color:#666; font-size:13px; margin-top:4px;">
                            {{ optional($previewInvoice->invoice_date)->format('Y-m-d') }}
                            · Due {{ optional($previewInvoice->due_date)->format('Y-m-d') ?: '—' }}
                            · {{ $previewInvoice->statusLabel() }}
                        </div>
                        <div style="color:#999; font-size:12px; margin-top:4px;">
                            Printed: {{ format_datetime(now(), 'M j, Y, g:i A') }}
                        </div>
                    </div>
                </div>

                <hr style="border:none; border-top:1px solid #e5e5e5; margin:16px 0;">

                <div style="margin-bottom:16px;">
                    <div style="color:#999; font-size:12px;">Billed to</div>
                    <div style="font-weight:700; font-size:16px;">{{ $previewInvoice->customer_name }}</div>
                    <div style="color:#555;">{{ $previewInvoice->package_label }}</div>
                </div>

                <table class="data-table" style="width:100%; margin-bottom:16px;">
                    <thead>
                        <tr style="background:#f3e6d4;">
                            <th>Description</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($previewInvoice->items as $row)
                        <tr>
                            <td>{{ $row->description }}</td>
                            <td>{{ $row->qty }}</td>
                            <td>Rs {{ number_format((float) $row->unit_price, 0) }}</td>
                            <td>Rs {{ number_format((float) $row->amount, 0) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div style="max-width:280px; margin-left:auto; margin-bottom:20px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                        <span>Subtotal</span>
                        <strong>Rs {{ number_format((float) $previewInvoice->subtotal, 0) }}</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                        <span>Tax ({{ rtrim(rtrim(number_format((float) $previewInvoice->tax_percent, 2), '0'), '.') }}%)</span>
                        <strong>Rs {{ number_format((float) $previewInvoice->tax_amount, 0) }}</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:18px;">
                        <span>Total</span>
                        <strong>Rs {{ number_format((float) $previewInvoice->total_amount, 0) }}</strong>
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <div style="color:#999; font-size:13px; margin-bottom:8px;">Payments received</div>
                    @forelse ($previewInvoice->payments as $payment)
                    <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:4px;">
                        <span>{{ format_datetime($payment->paid_at, 'Y-m-d') }}{{ $payment->note ? ' · '.$payment->note : '' }}</span>
                        <span>Rs {{ number_format((float) $payment->amount, 0) }}</span>
                    </div>
                    @empty
                    <div style="color:#777; font-size:13px;">No payments recorded yet</div>
                    @endforelse
                    <div style="display:flex; justify-content:space-between; margin-top:10px;">
                        <span style="color:#16a34a;">Total received</span>
                        <strong style="color:#16a34a;">Rs {{ number_format((float) $previewInvoice->paid_amount, 0) }}</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-top:6px;">
                        <span>Balance due</span>
                        <strong>Rs {{ number_format((float) $previewInvoice->balance, 0) }}</strong>
                    </div>
                </div>

                <div style="display:flex; gap:48px; margin-top:40px;">
                    <div style="flex:1;">
                        <div style="border-top:1px solid #333; margin-bottom:6px;"></div>
                        <div style="font-size:12px; color:#666;">Prepared by</div>
                    </div>
                    <div style="flex:1;">
                        <div style="border-top:1px solid #333; margin-bottom:6px;"></div>
                        <div style="font-size:12px; color:#666;">Approved by</div>
                    </div>
                </div>

                @if ($showPaymentForm)
                <div class="data-panel" style="margin-top:20px; padding:12px;">
                    <h4 class="data-panel__title" style="margin-bottom:12px;">Record payment</h4>
                    <div class="manage-users-form__grid">
                        <div class="mu-field">
                            <label class="mu-field__label">Amount</label>
                            <input type="number" min="0.01" step="0.01" class="mu-field__input" wire:model="payment_amount">
                            @error('payment_amount') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                        </div>
                        <div class="mu-field">
                            <label class="mu-field__label">Date</label>
                            <input type="date" class="mu-field__input" wire:model="payment_date">
                        </div>
                        <div class="mu-field" style="grid-column: 1 / -1;">
                            <label class="mu-field__label">Note (optional)</label>
                            <input type="text" class="mu-field__input" wire:model="payment_note">
                        </div>
                    </div>
                    <div style="display:flex; gap:8px; margin-top:12px;">
                        <button type="button" class="hero-btn" wire:click="$set('showPaymentForm', false)">Cancel</button>
                        <button type="button" class="hero-btn hero-btn--primary" wire:click="recordPayment">Save payment</button>
                    </div>
                </div>
                @endif
            </div>

            <div style="display:flex; justify-content:flex-end; gap:8px; padding:12px 16px; border-top:1px solid #e5e5e5; background:#fafafa;">
                <button type="button" class="hero-btn" wire:click="closePreview">Close</button>
                <button type="button" class="hero-btn" wire:click="openPaymentForm">+ Record payment</button>
                <a
                    class="hero-btn hero-btn--primary"
                    href="{{ url('/invoices/'.$previewInvoice->id.'/document?print=1') }}"
                    target="_blank"
                    rel="noopener">
                    Print
                </a>
            </div>
        </div>
    </div>
    @endif
</div>