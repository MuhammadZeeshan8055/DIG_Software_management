<div
    class="hotel-vouchers-page"
    x-data="{
        openForm() {
            document.body.classList.add('mu-modal-open');
            $wire.openFormModal();
        }
    }"
    x-effect="document.body.classList.toggle('mu-modal-open', !!$wire.showFormModal || !!$wire.showViewModal)"
    x-on:destroy="document.body.classList.remove('mu-modal-open')"
>
    @if ($successMessage)
        <x-admin-toast
            :title="str_starts_with($successMessage, 'Deleted') ? 'Deleted' : (str_contains($successMessage, 'updated') ? 'Updated' : 'Saved')"
            wire-property="successMessage"
            :seconds="5"
        >
            {{ $successMessage }}
        </x-admin-toast>
    @endif

    <div class="data-panel">
        <div class="data-panel__head">
            <h3 class="data-panel__title">Hotel Vouchers</h3>
            @if ($canView)
                <button type="button" class="hero-btn hero-btn--primary" @click="openForm()">
                    + Add Hotel Voucher
                </button>
            @endif
        </div>

        <div class="data-table-wrap">
            <table class="data-table hv-list-table">
                <thead>
                    <tr>
                        <th>Voucher #</th>
                        <th>Family / Group</th>
                        <th>Pax</th>
                        <th>Hotels / Stays</th>
                        <th>Nights</th>
                        <th>Issue Date</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vouchers as $voucher)
                        <tr wire:key="hv-{{ $voucher->id }}">
                            <td>
                                <div class="hv-list-voucher">
                                    <strong>{{ $voucher->voucher_number }}</strong>
                                    @if ($voucher->booking_reference)
                                        <span>{{ $voucher->booking_reference }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="hv-list-family">{{ strtoupper($voucher->family_head) }}</div>
                                @if ($voucher->group_number)
                                    <div class="hv-list-muted">Group {{ $voucher->group_number }}</div>
                                @endif
                            </td>
                            <td class="hv-list-num">{{ $voucher->paxCount() }}</td>
                            <td>
                                <div class="hv-list-stays">
                                    @forelse ($voucher->staysSummaryLines() as $line)
                                        <div>{{ $line }}</div>
                                    @empty
                                        <span class="hv-list-muted">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="hv-list-num">{{ $voucher->totalNights() ?: '—' }}</td>
                            <td>{{ optional($voucher->issue_date)->format('Y-m-d') ?: '—' }}</td>
                            <td>
                                <span class="{{ $voucher->statusBadgeClass() }}">
                                    {{ $voucher->statusLabel() }}
                                </span>
                            </td>
                            <td class="hv-list-updated">
                                {{ optional($voucher->updated_at)->format('d-M-Y, h:i a') ?: '—' }}
                            </td>
                            <td>
                                <div class="hv-list-actions">
                                    <button
                                        type="button"
                                        class="payment-actions__pill payment-actions__pill--edit"
                                        wire:click="openViewModal({{ $voucher->id }})"
                                    >
                                        View
                                    </button>
                                    <button
                                        type="button"
                                        class="payment-actions__pill payment-actions__pill--edit"
                                        wire:click="openEditModal({{ $voucher->id }})"
                                    >
                                        Edit
                                    </button>
                                    <a
                                        href="{{ route('hotel-vouchers.document', ['hotelVoucher' => $voucher, 'print' => 1]) }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="payment-actions__pill payment-actions__pill--edit"
                                    >
                                        PDF
                                    </a>
                                    <button
                                        type="button"
                                        class="payment-actions__pill payment-actions__pill--delete"
                                        @click="$dispatch('confirm-delete-hotel-vouchers', {
                                            id: {{ $voucher->id }},
                                            title: 'Delete hotel voucher?',
                                            message: @js('Delete '.$voucher->voucher_number.' for '.$voucher->family_head.'? This cannot be undone.')
                                        })"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">No hotel vouchers yet. Click “+ Add Hotel Voucher” to create one.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-confirm-delete-modal event="confirm-delete-hotel-vouchers" />

    @if ($showViewModal && $viewingVoucher)
        <div class="ticket-view-modal" wire:keydown.escape.window="closeViewModal" style="display:flex;">
            <button type="button" class="ticket-view-modal__backdrop" wire:click="closeViewModal" aria-label="Close"></button>

            <div class="ticket-view-modal__dialog ticket-view-modal__dialog--pop hotel-voucher-view-dialog" role="dialog" aria-modal="true">
                <div class="ticket-view-modal__head">
                    <div>
                        <p class="ticket-view-modal__eyebrow">Hotel voucher preview</p>
                        <h3 class="ticket-view-modal__title">{{ $viewingVoucher->voucher_number }}</h3>
                        <p class="ticket-view-modal__meta">
                            {{ $viewingVoucher->family_head }}
                            · {{ optional($viewingVoucher->issue_date)->format('Y-m-d') }}
                            · <span class="{{ $viewingVoucher->statusBadgeClass() }}">{{ $viewingVoucher->statusLabel() }}</span>
                        </p>
                    </div>
                    <div class="ticket-view-modal__head-actions">
                        <button
                            type="button"
                            class="hero-btn hero-btn--secondary"
                            wire:click="openEditModal({{ $viewingVoucher->id }})"
                        >Edit</button>
                        <a
                            href="{{ route('hotel-vouchers.document', ['hotelVoucher' => $viewingVoucher, 'print' => 1]) }}"
                            target="_blank"
                            rel="noopener"
                            class="hero-btn hero-btn--secondary ticket-view-modal__pdf-btn"
                        >Download PDF</a>
                        <button type="button" class="ticket-view-modal__close" wire:click="closeViewModal" aria-label="Close">&times;</button>
                    </div>
                </div>

                <div class="ticket-view-modal__body hotel-voucher-view-body">
                    <x-hotel-voucher-document :voucher="$viewingVoucher" />
                </div>
            </div>
        </div>
    @endif

    @if ($showFormModal)
        <div class="mu-modal" wire:keydown.escape.window="closeFormModal">
            <button type="button" class="mu-modal__backdrop" wire:click="closeFormModal" aria-label="Close"></button>

            <div
                class="mu-modal__dialog hotel-voucher-modal__dialog"
                role="dialog"
                aria-modal="true"
                aria-labelledby="hotel-voucher-form-title"
                aria-describedby="hotel-voucher-form-description"
                tabindex="-1"
                x-data="{ returnFocus: document.activeElement, destroy() { if (this.returnFocus?.isConnected) this.returnFocus.focus(); } }"
                x-init="$el.focus()"
                x-on:keydown.tab="
                    const items = [...$el.querySelectorAll('button:not(:disabled), input:not(:disabled), select:not(:disabled), textarea:not(:disabled), [tabindex=\'0\']')].filter(el => el.getClientRects().length);
                    const first = items[0], last = items[items.length - 1];
                    if ($event.shiftKey && (document.activeElement === first || document.activeElement === $el)) { $event.preventDefault(); last?.focus(); }
                    else if (!$event.shiftKey && document.activeElement === last) { $event.preventDefault(); first?.focus(); }
                "
            >
                <div class="mu-modal__head hotel-voucher-modal__head">
                    <div class="hotel-voucher-modal__head-main">
                        <span class="hotel-voucher-modal__icon" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                <path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z" stroke="currentColor" stroke-width="1.6" />
                                <path d="M14 3v5h5M9 13h6M9 17h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                            </svg>
                        </span>
                        <div>
                            <span class="hotel-voucher-modal__eyebrow">UMRAH OPERATIONS</span>
                            <h3 id="hotel-voucher-form-title" class="mu-modal__title">
                                {{ $editingId ? 'Edit hotel voucher' : 'Create hotel voucher' }}
                            </h3>
                            <p id="hotel-voucher-form-description" class="hotel-voucher-modal__subtitle">
                                {{ $editingId
                                    ? 'Update guests, stays and travel details for '.$voucher_number.'.'
                                    : 'Bring your guests, stays and travel details together in one voucher.' }}
                            </p>
                        </div>
                    </div>
                    <button type="button" class="mu-modal__close" wire:click="closeFormModal" aria-label="Close">&times;</button>
                </div>

                <form wire:submit.prevent="save" class="mu-modal__form hotel-voucher-form">
                    <div class="hv-progress">
                        <div class="hv-progress__heading">
                            <span>Step {{ $openStep ?: $maxUnlockedStep }} of 6 <span class="hv-progress__separator">/</span> {{ ['Booking details', 'Guest manifest', 'Accommodation', 'Flights', 'Ground services', 'Contacts & instructions'][($openStep ?: $maxUnlockedStep) - 1] }}</span>
                            <span class="hv-progress__required">* Required fields</span>
                        </div>
                        <div class="hv-progress__track" aria-hidden="true">
                            @for ($step = 1; $step <= 6; $step++)
                                <span class="hv-progress__segment {{ $step < $maxUnlockedStep ? 'is-complete' : '' }} {{ $step === ($openStep ?: $maxUnlockedStep) ? 'is-current' : '' }}"></span>
                            @endfor
                        </div>
                    </div>
                    <div class="hotel-voucher-form__scroll">
                        {{-- Step 1: Identity --}}
                        <section class="hv-step {{ $openStep === 1 ? 'is-open' : '' }} {{ $maxUnlockedStep >= 1 ? 'is-unlocked' : 'is-locked' }}">
                            <button
                                type="button"
                                class="hv-step__toggle"
                                wire:click="toggleStep(1)"
                                aria-expanded="{{ $openStep === 1 ? 'true' : 'false' }}"
                                aria-controls="hv-step-panel-1"
                                @disabled($maxUnlockedStep < 1)
                            >
                                <span class="hv-step__index" aria-hidden="true">@if ($maxUnlockedStep > 1) &#10003; @else 1 @endif</span>
                                <span class="hv-step__heading"><span class="hv-step__label">Voucher &amp; booking identity</span><span class="hv-step__description">Booking reference, group and voucher details</span></span>
                                <span class="hv-step__status">{{ $openStep === 1 ? 'In progress' : ($maxUnlockedStep > 1 ? 'Completed' : ($maxUnlockedStep < 1 ? 'Locked' : 'Ready')) }}</span>
                                <span class="hv-step__chevron" aria-hidden="true"></span>
                            </button>

                            @if ($openStep === 1)
                                <div id="hv-step-panel-1" class="hv-step__body">
                                    <div class="hv-note hv-note--amber">
                                        <span class="hv-note__icon" aria-hidden="true">i</span>
                                        <div>Voucher number is assigned on save. Complete each section to unlock the next.</div>
                                    </div>

                                    <div class="manage-users-form__grid hotel-voucher-form__grid hotel-voucher-form__grid--identity">
                                        <div class="mu-field">
                                            <label class="mu-field__label" for="hv-field-1">Voucher number *</label>
                                            <input id="hv-field-1" type="text" class="mu-field__input" value="{{ $voucher_number }}" readonly>
                                        </div>
                                        <div class="mu-field">
                                            <label class="mu-field__label" for="hv-field-2">Issue date *</label>
                                            <input id="hv-field-2" type="date" class="mu-field__input" wire:model="issue_date">
                                            @error('issue_date') <span class="hv-error">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="mu-field">
                                            <label class="mu-field__label" for="hv-field-3">Voucher status *</label>
                                            <select id="hv-field-3" class="mu-field__input mu-field__input--select" wire:model="status">
                                                <option value="draft">Draft</option>
                                                <option value="issued">Issued</option>
                                            </select>
                                            @error('status') <span class="hv-error">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="mu-field">
                                            <label class="mu-field__label" for="hv-field-4">Booking reference</label>
                                            <input id="hv-field-4" type="text" class="mu-field__input" wire:model="booking_reference" placeholder="Optional">
                                        </div>
                                        <div class="mu-field">
                                            <label class="mu-field__label" for="hv-field-5">Family / group head *</label>
                                            <input id="hv-field-5" type="text" class="mu-field__input" wire:model="family_head" placeholder="e.g. Mr. Tariq">
                                            @error('family_head') <span class="hv-error">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="mu-field">
                                            <label class="mu-field__label" for="hv-field-6">Group number</label>
                                            <input id="hv-field-6" type="text" class="mu-field__input" wire:model="group_number">
                                        </div>
                                        <div class="mu-field">
                                            <label class="mu-field__label" for="hv-field-7">Agent</label>
                                            <input id="hv-field-7" type="text" class="mu-field__input" wire:model="agent_customer">
                                        </div>
                                        <div class="mu-field">
                                            <label class="mu-field__label" for="hv-field-8">Package / program</label>
                                            <input id="hv-field-8" type="text" class="mu-field__input" wire:model="package_program">
                                        </div>
                                        <div class="mu-field">
                                            <label class="mu-field__label" for="hv-field-9">Country</label>
                                            <input id="hv-field-9" type="text" class="mu-field__input" wire:model="country">
                                        </div>
                                        <div class="mu-field">
                                            <label class="mu-field__label" for="hv-field-10">Visa type</label>
                                            <select id="hv-field-10" class="mu-field__input mu-field__input--select" wire:model="visa_type">
                                                <option value="Umrah Visa">Umrah Visa</option>
                                                <option value="Hajj Visa">Hajj Visa</option>
                                                <option value="Visit Visa">Visit Visa</option>
                                            </select>
                                        </div>
                                        <div class="mu-field hotel-voucher-form__span-2">
                                            <label class="mu-field__label" for="hv-field-11">Service scope</label>
                                            <input id="hv-field-11" type="text" class="mu-field__input" wire:model="service_scope">
                                        </div>
                                    </div>

                                    <div class="hv-step__actions">
                                        <button type="button" class="hero-btn hero-btn--primary" wire:click="continueToStep(2)">
                                            Continue to guests
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </section>

                        {{-- Step 2: Guests --}}
                        <section class="hv-step {{ $openStep === 2 ? 'is-open' : '' }} {{ $maxUnlockedStep >= 2 ? 'is-unlocked' : 'is-locked' }}">
                            <button
                                type="button"
                                class="hv-step__toggle"
                                wire:click="toggleStep(2)"
                                aria-expanded="{{ $openStep === 2 ? 'true' : 'false' }}"
                                aria-controls="hv-step-panel-2"
                                @disabled($maxUnlockedStep < 2)
                            >
                                <span class="hv-step__index" aria-hidden="true">@if ($maxUnlockedStep > 2) &#10003; @else 2 @endif</span>
                                <span class="hv-step__heading"><span class="hv-step__label">Mutamers / guest manifest</span><span class="hv-step__description">Passengers, passports and visa information</span></span>
                                <span class="hv-step__status">{{ $openStep === 2 ? 'In progress' : ($maxUnlockedStep > 2 ? 'Completed' : ($maxUnlockedStep < 2 ? 'Locked' : 'Ready')) }}</span>
                                <span class="hv-step__chevron" aria-hidden="true"></span>
                            </button>

                            @if ($openStep === 2)
                                <div id="hv-step-panel-2" class="hv-step__body">
                                    <div class="hv-step__toolbar">
                                        <p class="hv-step__hint">Add all passengers travelling on this voucher.</p>
                                        <button type="button" class="hero-btn hero-btn--primary hv-mini-btn" wire:click="addGuest">+ Add guest</button>
                                    </div>

                                    <div class="hv-table-wrap">
                                        <table class="hv-table">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Passenger Name *</th>
                                                    <th>Passport</th>
                                                    <th>Gender</th>
                                                    <th>Pax Type</th>
                                                    <th>Bed</th>
                                                    <th>MOFA</th>
                                                    <th>Visa</th>
                                                    <th>PNR</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($guests as $i => $guest)
                                                    <tr wire:key="guest-{{ $i }}">
                                                        <td>{{ $i + 1 }}</td>
                                                        <td>
                                                            <input type="text" class="mu-field__input" wire:model="guests.{{ $i }}.passenger_name">
                                                            @error('guests.'.$i.'.passenger_name') <span class="hv-error">{{ $message }}</span> @enderror
                                                        </td>
                                                        <td><input type="text" class="mu-field__input" wire:model="guests.{{ $i }}.passport"></td>
                                                        <td>
                                                            <select class="mu-field__input mu-field__input--select" wire:model="guests.{{ $i }}.gender">
                                                                <option>Male</option>
                                                                <option>Female</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <select class="mu-field__input mu-field__input--select" wire:model="guests.{{ $i }}.pax_type">
                                                                <option>Adult</option>
                                                                <option>Child</option>
                                                                <option>Infant</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <select class="mu-field__input mu-field__input--select" wire:model="guests.{{ $i }}.bed">
                                                                <option>Yes</option>
                                                                <option>No</option>
                                                            </select>
                                                        </td>
                                                        <td><input type="text" class="mu-field__input" wire:model="guests.{{ $i }}.mofa"></td>
                                                        <td><input type="text" class="mu-field__input" wire:model="guests.{{ $i }}.visa"></td>
                                                        <td><input type="text" class="mu-field__input" wire:model="guests.{{ $i }}.pnr"></td>
                                                        <td>
                                                            <button type="button" class="hv-row-remove" wire:click="removeGuest({{ $i }})" title="Remove">&times;</button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @error('guests') <span class="hv-error">{{ $message }}</span> @enderror

                                    <div class="hv-step__actions">
                                        <button type="button" class="hero-btn" wire:click="goToStep(1)">Back</button>
                                        <button type="button" class="hero-btn hero-btn--primary" wire:click="continueToStep(3)">
                                            Continue to accommodation
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </section>

                        {{-- Step 3: Accommodation --}}
                        <section class="hv-step {{ $openStep === 3 ? 'is-open' : '' }} {{ $maxUnlockedStep >= 3 ? 'is-unlocked' : 'is-locked' }}">
                            <button
                                type="button"
                                class="hv-step__toggle"
                                wire:click="toggleStep(3)"
                                aria-expanded="{{ $openStep === 3 ? 'true' : 'false' }}"
                                aria-controls="hv-step-panel-3"
                                @disabled($maxUnlockedStep < 3)
                            >
                                <span class="hv-step__index" aria-hidden="true">@if ($maxUnlockedStep > 3) &#10003; @else 3 @endif</span>
                                <span class="hv-step__heading"><span class="hv-step__label">Makkah &amp; Madinah accommodation</span><span class="hv-step__description">Hotel bookings, rooms and meal plans</span></span>
                                <span class="hv-step__status">{{ $openStep === 3 ? 'In progress' : ($maxUnlockedStep > 3 ? 'Completed' : ($maxUnlockedStep < 3 ? 'Locked' : 'Ready')) }}</span>
                                <span class="hv-step__chevron" aria-hidden="true"></span>
                            </button>

                            @if ($openStep === 3)
                                <div id="hv-step-panel-3" class="hv-step__body">
                                    <div class="hv-step__toolbar">
                                        <p class="hv-step__hint">Hotel classification is auto-filled where recorded; verify supplier confirmation before issue.</p>
                                        <button type="button" class="hero-btn hero-btn--primary hv-mini-btn" wire:click="addStay">+ Add stay</button>
                                    </div>

                                    @if ($errors->any())
                                        <div class="hv-note hv-note--error">
                                            <strong>Could not continue:</strong>
                                            <ul class="hv-error-list">
                                                @foreach ($errors->all() as $message)
                                                    <li>{{ $message }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    <div class="hv-table-wrap hv-table-wrap--stays">
                                        <table class="hv-table hv-table--stays">
                                            <thead>
                                                <tr>
                                                    <th>City *</th>
                                                    <th>Hotel *</th>
                                                    <th>Category</th>
                                                    <th>Room Type</th>
                                                    <th>Rooms</th>
                                                    <th>Meal</th>
                                                    <th>Distance</th>
                                                    <th>Confirmation</th>
                                                    <th>Check-in</th>
                                                    <th>Check-out</th>
                                                    <th>Nights</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($stays as $i => $stay)
                                                    <tr wire:key="stay-{{ $i }}">
                                                        <td class="hv-col-city">
                                                            <select class="mu-field__input mu-field__input--select" wire:model="stays.{{ $i }}.city">
                                                                <option value="Makkah">Makkah</option>
                                                                <option value="Madinah">Madinah</option>
                                                            </select>
                                                            @error('stays.'.$i.'.city') <span class="hv-error">{{ $message }}</span> @enderror
                                                        </td>
                                                        <td class="hv-col-hotel">
                                                            <input type="text" class="mu-field__input" wire:model="stays.{{ $i }}.hotel" list="hv-hotel-catalog" placeholder="Hotel name">
                                                            @error('stays.'.$i.'.hotel') <span class="hv-error">{{ $message }}</span> @enderror
                                                        </td>
                                                        <td><input type="text" class="mu-field__input" wire:model="stays.{{ $i }}.category" placeholder="e.g. 4★"></td>
                                                        <td class="hv-col-room">
                                                            <select class="mu-field__input mu-field__input--select" wire:model="stays.{{ $i }}.room_type">
                                                                <option value="Quad">Quad</option>
                                                                <option value="Triple">Triple</option>
                                                                <option value="Double">Double</option>
                                                                <option value="Single">Single</option>
                                                                <option value="Sharing">Sharing</option>
                                                            </select>
                                                        </td>
                                                        <td class="hv-col-num"><input type="number" min="0" class="mu-field__input" wire:model="stays.{{ $i }}.rooms"></td>
                                                        <td class="hv-col-meal">
                                                            <select class="mu-field__input mu-field__input--select" wire:model="stays.{{ $i }}.meal">
                                                                <option value="RO - Room Only">RO - Room Only</option>
                                                                <option value="BB - Bed &amp; Breakfast">BB - Bed &amp; Breakfast</option>
                                                                <option value="HB - Half Board">HB - Half Board</option>
                                                                <option value="FB - Full Board">FB - Full Board</option>
                                                            </select>
                                                        </td>
                                                        <td class="hv-col-distance"><input type="text" class="mu-field__input" wire:model="stays.{{ $i }}.distance" placeholder="e.g. 250m from Haram"></td>
                                                        <td class="hv-col-confirm"><input type="text" class="mu-field__input" wire:model="stays.{{ $i }}.confirmation" placeholder="Conf. no."></td>
                                                        <td class="hv-col-date"><input type="date" class="mu-field__input" wire:model.live="stays.{{ $i }}.check_in"></td>
                                                        <td class="hv-col-date"><input type="date" class="mu-field__input" wire:model.live="stays.{{ $i }}.check_out"></td>
                                                        <td class="hv-col-num">
                                                            <input
                                                                type="number"
                                                                min="0"
                                                                class="mu-field__input"
                                                                wire:model="stays.{{ $i }}.nights"
                                                                readonly
                                                                tabindex="-1"
                                                                title="Auto-calculated from check-in / check-out"
                                                            >
                                                        </td>
                                                        <td>
                                                            <button type="button" class="hv-row-remove" wire:click="removeStay({{ $i }})" title="Remove">&times;</button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <datalist id="hv-hotel-catalog">
                                        <option value="Makarem Ajyad"></option>
                                        <option value="Pullman ZamZam"></option>
                                        <option value="Hilton Suites"></option>
                                        <option value="Anwar Al Madinah"></option>
                                        <option value="Dar Al Eiman"></option>
                                    </datalist>

                                    <div class="hv-note">
                                        Master catalog contains Makkah and Madinah properties from economy through 5-star. Hotel classification is auto-filled where recorded; always verify the final supplier confirmation before issue.
                                    </div>

                                    <div class="hv-step__actions">
                                        <button type="button" class="hero-btn" wire:click="goToStep(2)">Back</button>
                                        <button
                                            type="button"
                                            class="hero-btn hero-btn--primary"
                                            wire:click.prevent="continueToStep(4)"
                                            wire:loading.attr="disabled"
                                            wire:target="continueToStep"
                                        >
                                            <span wire:loading.remove wire:target="continueToStep">Continue to flights</span>
                                            <span wire:loading wire:target="continueToStep">Continuing…</span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </section>

                        {{-- Step 4: Flights --}}
                        <section class="hv-step {{ $openStep === 4 ? 'is-open' : '' }} {{ $maxUnlockedStep >= 4 ? 'is-unlocked' : 'is-locked' }}">
                            <button
                                type="button"
                                class="hv-step__toggle"
                                wire:click="toggleStep(4)"
                                aria-expanded="{{ $openStep === 4 ? 'true' : 'false' }}"
                                aria-controls="hv-step-panel-4"
                                @disabled($maxUnlockedStep < 4)
                            >
                                <span class="hv-step__index" aria-hidden="true">@if ($maxUnlockedStep > 4) &#10003; @else 4 @endif</span>
                                <span class="hv-step__heading"><span class="hv-step__label">Flights</span><span class="hv-step__description">Outbound and return flight details</span></span>
                                <span class="hv-step__status">{{ $openStep === 4 ? 'In progress' : ($maxUnlockedStep > 4 ? 'Completed' : ($maxUnlockedStep < 4 ? 'Locked' : 'Ready')) }}</span>
                                <span class="hv-step__chevron" aria-hidden="true"></span>
                            </button>

                            @if ($openStep === 4)
                                <div id="hv-step-panel-4" class="hv-step__body">
                                    <div class="hv-step__toolbar">
                                        <p class="hv-step__hint">Optional — add outbound and return sectors with airport search.</p>
                                        <button type="button" class="hero-btn hero-btn--primary hv-mini-btn" wire:click="addFlight">+ Add flight</button>
                                    </div>

                                    <div class="hv-table-wrap hv-table-wrap--flights">
                                        <table class="hv-table hv-table--flights">
                                            <colgroup>
                                                <col class="hv-col-type">
                                                <col class="hv-col-airline">
                                                <col class="hv-col-flight">
                                                <col class="hv-col-sector">
                                                <col class="hv-col-datetime">
                                                <col class="hv-col-datetime">
                                                <col class="hv-col-remove">
                                            </colgroup>
                                            <thead>
                                                <tr>
                                                    <th>Type</th>
                                                    <th>Airline</th>
                                                    <th>Flight</th>
                                                    <th>Sector</th>
                                                    <th>Departure Date &amp; Time</th>
                                                    <th>Arrival Date &amp; Time</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($flights as $i => $flight)
                                                    <tr
                                                        wire:key="flight-row-{{ $i }}"
                                                        x-data="{
                                                            airports: @js($airports),
                                                            openSide: null,
                                                            query: '',
                                                            label(a) {
                                                                return (a.code || '') + ' — ' + (a.city || '') + ' — ' + (a.country || '');
                                                            },
                                                            filtered() {
                                                                const q = (this.query || '').trim().toLowerCase();
                                                                if (! q) return this.airports.slice(0, 12);
                                                                return this.airports.filter((a) => {
                                                                    const hay = [a.code, a.city, a.country, a.name].join(' ').toLowerCase();
                                                                    return hay.includes(q);
                                                                }).slice(0, 12);
                                                            },
                                                            open(side) {
                                                                this.openSide = side;
                                                                this.query = '';
                                                            },
                                                            close() {
                                                                this.openSide = null;
                                                                this.query = '';
                                                            },
                                                            pick(airport) {
                                                                const side = this.openSide;
                                                                if (! side) return;
                                                                const label = this.label(airport);
                                                                if (side === 'origin') {
                                                                    $wire.set('flights.{{ $i }}.origin', airport.code);
                                                                    $wire.set('flights.{{ $i }}.origin_label', label);
                                                                    $wire.set('flights.{{ $i }}.origin_region', airport.region || '');
                                                                } else {
                                                                    $wire.set('flights.{{ $i }}.destination', airport.code);
                                                                    $wire.set('flights.{{ $i }}.destination_label', label);
                                                                    $wire.set('flights.{{ $i }}.destination_region', airport.region || '');
                                                                }
                                                                this.close();
                                                                $wire.syncFlightSector({{ $i }});
                                                            },
                                                            swap() {
                                                                this.close();
                                                                $wire.swapFlightAirports({{ $i }});
                                                            }
                                                        }"
                                                        @click.outside="close()"
                                                    >
                                                        <td class="hv-col-type">
                                                            <select class="mu-field__input mu-field__input--select" wire:model="flights.{{ $i }}.type">
                                                                <option value="Departure">Departure</option>
                                                                <option value="Return">Return</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="text" class="mu-field__input" wire:model="flights.{{ $i }}.airline" placeholder="Airline">
                                                        </td>
                                                        <td>
                                                            <input type="text" class="mu-field__input" wire:model="flights.{{ $i }}.flight" placeholder="Flight">
                                                        </td>
                                                        <td class="hv-col-sector">
                                                            <div class="hv-sector-box">
                                                                <p class="hv-sector-box__route">
                                                                    {{ strtoupper($flight['origin_region'] ?: 'Pakistan') }}
                                                                    —
                                                                    {{ strtoupper($flight['destination_region'] ?: 'Worldwide') }}
                                                                </p>
                                                                <div class="hv-sector-box__airports">
                                                                    <div class="hv-airport-field" :class="{ 'is-open': openSide === 'origin' }">
                                                                        <button type="button" class="hv-airport-field__btn" @click="open('origin')">
                                                                            <span>{{ $flight['origin_label'] ?: 'Origin airport' }}</span>
                                                                            <span class="hv-airport-field__caret" aria-hidden="true"></span>
                                                                        </button>
                                                                        <div class="hv-airport-menu" x-show="openSide === 'origin'" x-cloak x-transition.opacity.duration.120ms>
                                                                            <input type="search" class="hv-airport-menu__search" placeholder="Search airport…" x-model="query" @click.stop>
                                                                            <template x-for="airport in filtered()" :key="airport.code">
                                                                                <button type="button" class="hv-airport-menu__item" @click="pick(airport)">
                                                                                    <strong x-text="label(airport)"></strong>
                                                                                    <span x-text="airport.name"></span>
                                                                                </button>
                                                                            </template>
                                                                        </div>
                                                                    </div>

                                                                    <button type="button" class="hv-swap-btn" @click="swap()" title="Swap origin / destination" aria-label="Swap airports">
                                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                                            <path d="M7 8h11M15 5l3 3-3 3M17 16H6M9 13l-3 3 3 3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                                                        </svg>
                                                                    </button>

                                                                    <div class="hv-airport-field" :class="{ 'is-open': openSide === 'destination' }">
                                                                        <button type="button" class="hv-airport-field__btn" @click="open('destination')">
                                                                            <span>{{ $flight['destination_label'] ?: 'Destination airport' }}</span>
                                                                            <span class="hv-airport-field__caret" aria-hidden="true"></span>
                                                                        </button>
                                                                        <div class="hv-airport-menu" x-show="openSide === 'destination'" x-cloak x-transition.opacity.duration.120ms>
                                                                            <input type="search" class="hv-airport-menu__search" placeholder="Search airport…" x-model="query" @click.stop>
                                                                            <template x-for="airport in filtered()" :key="'d-' + airport.code">
                                                                                <button type="button" class="hv-airport-menu__item" @click="pick(airport)">
                                                                                    <strong x-text="label(airport)"></strong>
                                                                                    <span x-text="airport.name"></span>
                                                                                </button>
                                                                            </template>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="hv-sector-box__code">
                                                                    <span class="hv-sector-box__code-label">Sector</span>
                                                                    <input
                                                                        type="text"
                                                                        class="mu-field__input hv-sector-box__code-input"
                                                                        wire:model="flights.{{ $i }}.sector_code"
                                                                        placeholder="e.g. ISB-JED"
                                                                        readonly
                                                                    >
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="hv-col-datetime">
                                                            <input type="datetime-local" class="mu-field__input" wire:model="flights.{{ $i }}.departure_at">
                                                        </td>
                                                        <td class="hv-col-datetime">
                                                            <input type="datetime-local" class="mu-field__input" wire:model="flights.{{ $i }}.arrival_at">
                                                        </td>
                                                        <td>
                                                            <button type="button" class="hv-row-remove" wire:click="removeFlight({{ $i }})" title="Remove">&times;</button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="hv-step__actions">
                                        <button type="button" class="hero-btn" wire:click="goToStep(3)">Back</button>
                                        <button type="button" class="hero-btn hero-btn--primary" wire:click="continueToStep(5)">
                                            Continue to transport
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </section>

                        {{-- Step 5: Transport --}}
                        <section class="hv-step {{ $openStep === 5 ? 'is-open' : '' }} {{ $maxUnlockedStep >= 5 ? 'is-unlocked' : 'is-locked' }}">
                            <button
                                type="button"
                                class="hv-step__toggle"
                                wire:click="toggleStep(5)"
                                aria-expanded="{{ $openStep === 5 ? 'true' : 'false' }}"
                                aria-controls="hv-step-panel-5"
                                @disabled($maxUnlockedStep < 5)
                            >
                                <span class="hv-step__index" aria-hidden="true">@if ($maxUnlockedStep > 5) &#10003; @else 5 @endif</span>
                                <span class="hv-step__heading"><span class="hv-step__label">Transport &amp; ground services</span><span class="hv-step__description">Transfers, vehicles and Ziyarat services</span></span>
                                <span class="hv-step__status">{{ $openStep === 5 ? 'In progress' : ($maxUnlockedStep > 5 ? 'Completed' : ($maxUnlockedStep < 5 ? 'Locked' : 'Ready')) }}</span>
                                <span class="hv-step__chevron" aria-hidden="true"></span>
                            </button>

                            @if ($openStep === 5)
                                <div id="hv-step-panel-5" class="hv-step__body">
                                    <div class="hv-step__toolbar">
                                        <p class="hv-step__hint">Optional ground transfers and Ziyarat services.</p>
                                        <button type="button" class="hero-btn hero-btn--primary hv-mini-btn" wire:click="addTransport">+ Add service</button>
                                    </div>

                                    @if (count($transport))
                                        <div class="hv-table-wrap">
                                            <table class="hv-table">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Route / Service</th>
                                                        <th>Vehicle</th>
                                                        <th>Supplier / Driver</th>
                                                        <th>Reference</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($transport as $i => $row)
                                                        <tr wire:key="transport-{{ $i }}">
                                                            <td><input type="date" class="mu-field__input" wire:model="transport.{{ $i }}.date"></td>
                                                            <td><input type="text" class="mu-field__input" wire:model="transport.{{ $i }}.route_service"></td>
                                                            <td><input type="text" class="mu-field__input" wire:model="transport.{{ $i }}.vehicle"></td>
                                                            <td><input type="text" class="mu-field__input" wire:model="transport.{{ $i }}.supplier_driver"></td>
                                                            <td><input type="text" class="mu-field__input" wire:model="transport.{{ $i }}.reference"></td>
                                                            <td>
                                                                <button type="button" class="hv-row-remove" wire:click="removeTransport({{ $i }})" title="Remove">&times;</button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="hv-empty">No transport services added yet.</p>
                                    @endif

                                    <div class="hv-step__actions">
                                        <button type="button" class="hero-btn" wire:click="goToStep(4)">Back</button>
                                        <button type="button" class="hero-btn hero-btn--primary" wire:click="continueToStep(6)">
                                            Continue to contacts
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </section>

                        {{-- Step 6: Contacts --}}
                        <section class="hv-step {{ $openStep === 6 ? 'is-open' : '' }} {{ $maxUnlockedStep >= 6 ? 'is-unlocked' : 'is-locked' }}">
                            <button
                                type="button"
                                class="hv-step__toggle"
                                wire:click="toggleStep(6)"
                                aria-expanded="{{ $openStep === 6 ? 'true' : 'false' }}"
                                aria-controls="hv-step-panel-6"
                                @disabled($maxUnlockedStep < 6)
                            >
                                <span class="hv-step__index" aria-hidden="true">@if ($maxUnlockedStep > 6) &#10003; @else 6 @endif</span>
                                <span class="hv-step__heading"><span class="hv-step__label">Contacts &amp; instructions</span><span class="hv-step__description">Helplines and notes printed on the voucher</span></span>
                                <span class="hv-step__status">{{ $openStep === 6 ? 'In progress' : ($maxUnlockedStep > 6 ? 'Completed' : ($maxUnlockedStep < 6 ? 'Locked' : 'Ready')) }}</span>
                                <span class="hv-step__chevron" aria-hidden="true"></span>
                            </button>

                            @if ($openStep === 6)
                                <div id="hv-step-panel-6" class="hv-step__body">
                                    <div class="manage-users-form__grid hotel-voucher-form__grid">
                                        <div class="mu-field">
                                            <label class="mu-field__label" for="hv-field-12">KSA helpline</label>
                                            <input id="hv-field-12" type="text" class="mu-field__input" wire:model="ksa_helpline" placeholder="+966 …">
                                        </div>
                                        <div class="mu-field">
                                            <label class="mu-field__label" for="hv-field-13">Pakistan helpline</label>
                                            <input id="hv-field-13" type="text" class="mu-field__input" wire:model="pakistan_helpline">
                                        </div>
                                        <div class="mu-field">
                                            <label class="mu-field__label" for="hv-field-14">Prepared by</label>
                                            <input id="hv-field-14" type="text" class="mu-field__input" wire:model="prepared_by">
                                        </div>
                                        <div class="mu-field hotel-voucher-form__span-3">
                                            <label class="mu-field__label" for="hv-field-15">Special instructions / inclusions</label>
                                            <textarea id="hv-field-15" class="mu-field__input hotel-voucher-form__textarea" rows="4" wire:model="special_instructions" placeholder="Notes printed on the voucher…"></textarea>
                                        </div>
                                    </div>

                                    <div class="hv-step__actions">
                                        <button type="button" class="hero-btn" wire:click="goToStep(5)">Back</button>
                                    </div>
                                </div>
                            @endif
                        </section>
                    </div>

                    <div class="mu-modal__footer">
                        <p class="hv-footer-hint">
                            @if ($editingId)
                                All steps are unlocked — edit any section, then update the voucher.
                            @elseif ($maxUnlockedStep < 6)
                                Complete all 6 steps to save your voucher.
                            @else
                                Review your details, then save your voucher.
                            @endif
                        </p>
                        <button type="button" class="hero-btn" wire:click="closeFormModal">Cancel</button>
                        <button
                            type="submit"
                            class="hero-btn hero-btn--primary"
                            @disabled($maxUnlockedStep < 6)
                            title="{{ $maxUnlockedStep < 6 ? 'Complete all steps first' : ($editingId ? 'Update voucher' : 'Save voucher') }}"
                            wire:loading.attr="disabled"
                            wire:target="save"
                        >
                            <span wire:loading.remove wire:target="save">
                                {{ $editingId ? 'Update voucher' : 'Save voucher' }}
                            </span>
                            <span wire:loading wire:target="save">
                                {{ $editingId ? 'Updating...' : 'Saving...' }}
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
