<div
    class="create-invoice-page"
    @invoices-panel-opened.window="$wire.$refresh()"
    x-data="{
        previewOpen: false,
        previewLoading: false,
        previewTitle: '',
        openInvoicePreview(id, title) {
            this.previewTitle = title || 'Loading…';
            this.previewOpen = true;
            this.previewLoading = true;
            document.body.classList.add('invoice-modal-open');
            $wire.openPreview(id).then(() => { this.previewLoading = false; }).catch(() => { this.previewLoading = false; });
        },
        closeInvoicePreview() {
            this.previewOpen = false;
            this.previewLoading = false;
            document.body.classList.remove('invoice-modal-open');
            $wire.closePreview();
        },
        openInvoiceForm() {
            document.body.classList.add('invoice-modal-open');
            $wire.openFormModal();
        }
    }"
    x-effect="
        if ($wire.showPreview) {
            previewOpen = true;
            previewLoading = false;
        } else if (!previewLoading) {
            previewOpen = false;
        }
        document.body.classList.toggle('invoice-modal-open', $wire.showFormModal || previewOpen || $wire.showPreview);
    "
    x-on:destroy="document.body.classList.remove('invoice-modal-open')"
>
    <section class="module-workspace__hero" style="margin-bottom: 16px;">
        <div class="module-workspace__hero-main">
            <p class="module-workspace__eyebrow">
                <span class="module-workspace__eyebrow-dot"></span>
                Accounts
            </p>
            <h2 class="module-workspace__title">Invoices</h2>
        </div>
        <div>
            @if (auth()->user()->canView('accounts', 'invoices'))
                <button type="button" class="hero-btn hero-btn--primary" @click="openInvoiceForm()">
                    + Add Invoice
                </button>
            @endif
        </div>
    </section>

    @if ($successMessage)
        <x-admin-toast
            :title="str_starts_with($successMessage, 'Deleted') ? 'Deleted' : 'Saved'"
            wire-property="successMessage"
            :seconds="5"
        >
            {{ $successMessage }}
        </x-admin-toast>
    @endif

    <div class="data-panel">
        <div class="data-panel__head">
            <h3 class="data-panel__title">All invoices</h3>
            <span class="add-account__count">{{ $invoices->count() }} shown</span>
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
                        <th>Approval</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        @php
                            $canOpen = $invoice->isApproved() || auth()->user()->isAdmin();
                        @endphp
                        <tr
                            wire:key="inv-{{ $invoice->id }}"
                            @if ($canOpen)
                                style="cursor: pointer;"
                                @click="openInvoicePreview({{ $invoice->id }}, @js($invoice->invoice_number))"
                            @endif
                        >
                            <td>{{ $invoice->invoice_number }}</td>
                            <td>{{ $invoice->customer_name }}</td>
                            <td>Rs {{ number_format((float) $invoice->total_amount, 0) }}</td>
                            <td>Rs {{ number_format((float) $invoice->balance, 0) }}</td>
                            <td>
                                <span class="{{ $invoice->paymentBadgeClass() }}">
                                    {{ $invoice->paymentStatusLabel() }}
                                </span>
                            </td>
                            <td>
                                @if ($invoice->isApproved())
                                    <span class="payment-badge payment-badge--paid">Approved</span>
                                @else
                                    <span class="payment-badge payment-badge--draft">Awaiting approval</span>
                                @endif
                            </td>
                            <td>{{ optional($invoice->invoice_date)->format('Y-m-d') }}</td>
                            <td class="manage-users-actions" wire:click.stop>
                                @if (! $invoice->isApproved() && auth()->user()->isAdmin())
                                    <button
                                        type="button"
                                        class="hero-btn hero-btn--primary"
                                        style="padding: 6px 10px; font-size: 0.75rem;"
                                        wire:click="approveInvoice({{ $invoice->id }})"
                                    >
                                        Approve
                                    </button>
                                @endif
                                @if (auth()->user()->isAdmin())
                                    <button
                                        type="button"
                                        class="payment-actions__btn payment-actions__btn--danger"
                                        @click="$dispatch('confirm-delete-invoices', {
                                            id: {{ $invoice->id }},
                                            title: 'Delete invoice?',
                                            message: @js('Delete '.$invoice->invoice_number.' for '.$invoice->customer_name.'? Line items and payments will also be removed.')
                                        })"
                                    >
                                        Delete
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">No invoices yet. Click “+ Add Invoice” to create one.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($showFormModal)
        <div class="mu-modal" wire:keydown.escape.window="closeFormModal">
            <button type="button" class="mu-modal__backdrop" wire:click="closeFormModal" aria-label="Close"></button>

            <div class="mu-modal__dialog create-invoice-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="invoice-form-title">
                <div class="mu-modal__head">
                    <h3 id="invoice-form-title" class="mu-modal__title">Add Invoice</h3>
                    <button type="button" class="mu-modal__close" wire:click="closeFormModal" aria-label="Close">&times;</button>
                </div>

                <form wire:submit.prevent="save" class="manage-users-form mu-modal__form create-invoice-form">
                    <div class="manage-users-form__grid create-invoice-form__grid">
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

                    <h4 class="create-invoice-form__section">Customer</h4>
                    <div class="manage-users-form__grid create-invoice-form__grid">
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

                        <div class="mu-field create-invoice-form__span-2">
                            <label class="mu-field__label">Address (optional)</label>
                            <input type="text" class="mu-field__input" wire:model="customer_address">
                        </div>
                    </div>

                    <div
                        wire:ignore
                        x-data="{
                            items: @js(count($items) ? $items : [['description' => '', 'qty' => 1, 'unit_price' => '']]),
                            taxPercent: {{ (float) $tax_percent }},
                            init() {
                                if (! Array.isArray(this.items) || this.items.length === 0) {
                                    this.items = [{ description: '', qty: 1, unit_price: '' }];
                                    this.syncItems();
                                }
                            },
                            addItem() {
                                this.items.push({ description: '', qty: 1, unit_price: '' });
                                this.syncItems();
                            },
                            removeItem(index) {
                                if (this.items.length <= 1) return;
                                this.items.splice(index, 1);
                                this.syncItems();
                            },
                            syncItems() {
                                $wire.set('items', JSON.parse(JSON.stringify(this.items)));
                            },
                            syncTax() {
                                $wire.set('tax_percent', this.taxPercent);
                            },
                            rowAmount(item) {
                                return (parseFloat(item.qty) || 0) * (parseFloat(item.unit_price) || 0);
                            },
                            get subtotal() {
                                return this.items.reduce((sum, item) => sum + this.rowAmount(item), 0);
                            },
                            get taxAmount() {
                                return this.subtotal * ((parseFloat(this.taxPercent) || 0) / 100);
                            },
                            get total() {
                                return this.subtotal + this.taxAmount;
                            },
                            formatMoney(value) {
                                return Math.round(value || 0).toLocaleString('en-US');
                            }
                        }"
                    >
                        <h4 class="create-invoice-form__section">Line items</h4>
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
                                    <template x-for="(item, index) in items" :key="index">
                                        <tr>
                                            <td>
                                                <input
                                                    type="text"
                                                    class="mu-field__input"
                                                    placeholder="e.g. Visa"
                                                    x-model="item.description"
                                                    @change="syncItems()"
                                                    @blur="syncItems()"
                                                >
                                            </td>
                                            <td>
                                                <input
                                                    type="number"
                                                    min="1"
                                                    class="mu-field__input"
                                                    x-model.number="item.qty"
                                                    @change="syncItems()"
                                                >
                                            </td>
                                            <td>
                                                <input
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    class="mu-field__input"
                                                    x-model="item.unit_price"
                                                    @change="syncItems()"
                                                >
                                            </td>
                                            <td x-text="'Rs ' + formatMoney(rowAmount(item))"></td>
                                            <td>
                                                <button
                                                    type="button"
                                                    class="payment-actions__btn"
                                                    @click.prevent="removeItem(index)"
                                                >
                                                    Remove
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <button
                            type="button"
                            class="hero-btn"
                            style="margin-bottom: 16px;"
                            @click.prevent="addItem()"
                        >
                            + Add item
                        </button>

                        <div class="manage-users-form__grid create-invoice-form__totals">
                            <div class="mu-field">
                                <label class="mu-field__label">Tax %</label>
                                <input
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    class="mu-field__input"
                                    x-model.number="taxPercent"
                                    @change="syncTax()"
                                >
                            </div>
                            <div class="mu-field">
                                <label class="mu-field__label">Subtotal</label>
                                <input type="text" class="mu-field__input" :value="'Rs ' + formatMoney(subtotal)" disabled>
                            </div>
                            <div class="mu-field">
                                <label class="mu-field__label">Tax</label>
                                <input type="text" class="mu-field__input" :value="'Rs ' + formatMoney(taxAmount)" disabled>
                            </div>
                            <div class="mu-field">
                                <label class="mu-field__label">Total</label>
                                <input type="text" class="mu-field__input" :value="'Rs ' + formatMoney(total)" disabled>
                            </div>
                        </div>

                        <x-validation-errors class="import-ticket__validation-errors" />

                        <div class="mu-modal__footer" style="margin-top: 16px; display: flex; justify-content: flex-end; gap: 8px;">
                            <button type="button" class="hero-btn" wire:click="closeFormModal">Cancel</button>
                            <button
                                type="button"
                                class="hero-btn hero-btn--primary"
                                wire:loading.attr="disabled"
                                wire:target="save"
                                @click.prevent="
                                    syncItems();
                                    syncTax();
                                    $wire.save();
                                "
                            >
                                <span wire:loading.remove wire:target="save">Save &amp; Preview</span>
                                <span wire:loading wire:target="save">Saving...</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div
        class="ticket-view-modal"
        x-show="previewOpen"
        x-cloak
        x-transition:enter="ticket-modal-enter"
        x-transition:enter-start="ticket-modal-enter-start"
        x-transition:enter-end="ticket-modal-enter-end"
        x-transition:leave="ticket-modal-leave"
        x-transition:leave-start="ticket-modal-leave-start"
        x-transition:leave-end="ticket-modal-leave-end"
        @keydown.escape.window="if (previewOpen) closeInvoicePreview()"
        style="display: none;"
    >
        <button type="button" class="ticket-view-modal__backdrop" @click="closeInvoicePreview()" aria-label="Close"></button>

        <div class="ticket-view-modal__dialog ticket-view-modal__dialog--pop" role="dialog" aria-modal="true" style="max-width: 900px;">
            <div class="ticket-view-modal__head">
                <div>
                    <p class="ticket-view-modal__eyebrow">Invoice preview</p>
                    <h3 class="ticket-view-modal__title" x-text="previewTitle"></h3>
                    @if ($previewInvoice)
                        <p class="ticket-view-modal__meta" x-show="!previewLoading">
                            {{ optional($previewInvoice->invoice_date)->format('Y-m-d') }}
                            · Due {{ optional($previewInvoice->due_date)->format('Y-m-d') ?: '—' }}
                            · <span class="{{ $previewInvoice->paymentBadgeClass() }}">{{ $previewInvoice->paymentStatusLabel() }}</span>
                        </p>
                    @endif
                </div>
                <button type="button" class="ticket-view-modal__close" @click="closeInvoicePreview()" aria-label="Close">&times;</button>
            </div>

            <div class="ticket-view-modal__body" style="background: #fff; padding: 24px;">
                <div class="ticket-view-modal__loading" x-show="previewLoading" x-cloak>
                    <span class="ticket-view-modal__spinner" aria-hidden="true"></span>
                    <p>Loading invoice…</p>
                </div>

                <div x-show="!previewLoading">
                    @if ($showPreview && $previewInvoice)
                        <x-invoice-document :invoice="$previewInvoice" />

                        @if ($showPaymentForm)
                            <div class="data-panel" style="margin-top:20px; padding:12px;">
                                <h4 class="data-panel__title" style="margin-bottom:12px;">Record payment</h4>
                                <div class="manage-users-form__grid" style="margin-bottom: 12px;">
                                    <div class="mu-field">
                                        <label class="mu-field__label">Amount</label>
                                        <input type="number" min="0.01" step="0.01" class="mu-field__input" wire:model="payment_amount">
                                        @error('payment_amount') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="mu-field">
                                        <label class="mu-field__label">Date</label>
                                        <input type="date" class="mu-field__input" wire:model="payment_date">
                                        @error('payment_date') <span style="color:#b91c1c;font-size:12px;">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="mu-field" style="grid-column: 1 / -1;">
                                        <label class="mu-field__label">Note (optional)</label>
                                        <input type="text" class="mu-field__input" wire:model="payment_note">
                                    </div>
                                </div>

                                <x-receiving-account-picker
                                    :methods="$paymentMethods"
                                    :all-accounts="$allReceivingAccounts"
                                    :selected-method="$payment_method"
                                    :selected-account-id="$receiving_account_id"
                                    method-wire="payment_method"
                                    account-wire="receiving_account_id"
                                />

                                <div style="display:flex; gap:8px; margin-top:12px;">
                                    <button type="button" class="hero-btn" wire:click="$set('showPaymentForm', false)">Cancel</button>
                                    <button type="button" class="hero-btn hero-btn--primary" wire:click="recordPayment">Save payment</button>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:8px; padding:12px 16px; border-top:1px solid #e5e5e5; background:#fafafa;" x-show="!previewLoading">
                <button type="button" class="hero-btn" @click="closeInvoicePreview()">Close</button>
                @if ($previewInvoice)
                    @if (auth()->user()->isAdmin())
                        <button
                            type="button"
                            class="payment-actions__btn payment-actions__btn--danger"
                            @click="$dispatch('confirm-delete-invoices', {
                                id: {{ $previewInvoice->id }},
                                title: 'Delete invoice?',
                                message: @js('Delete '.$previewInvoice->invoice_number.' for '.$previewInvoice->customer_name.'? Line items and payments will also be removed.')
                            })"
                        >
                            Delete
                        </button>
                    @endif
                    @if ($previewInvoice->isApproved())
                        <button type="button" class="hero-btn" wire:click="openPaymentForm">+ Record payment</button>
                        <a
                            class="hero-btn hero-btn--primary"
                            href="{{ url('/invoices/'.$previewInvoice->id.'/document?print=1') }}"
                            target="_blank"
                            rel="noopener"
                        >
                            Print
                        </a>
                    @elseif (auth()->user()->isAdmin())
                        <button
                            type="button"
                            class="hero-btn hero-btn--primary"
                            wire:click="approveInvoice({{ $previewInvoice->id }})"
                        >
                            Approve invoice
                        </button>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <x-confirm-delete-modal event="confirm-delete-invoices" />
</div>
