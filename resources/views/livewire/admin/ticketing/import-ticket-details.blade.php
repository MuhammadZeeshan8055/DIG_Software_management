<div class="import-ticket">
    @if ($successMessage)
        <div class="import-ticket__alert import-ticket__alert--success">
            {{ $successMessage }}
        </div>
    @endif

    <div class="data-panel import-ticket__panel">
        <div class="data-panel__head">
            <h3 class="data-panel__title">Import Ticket Details</h3>
            <p class="import-ticket__hint">Upload a PDF, review the auto-filled fields, then confirm.</p>
        </div>

        <div class="import-ticket__body">
            <div class="import-ticket__upload">
                <p class="import-ticket__label">Ticket PDF</p>

                <label
                    for="pdfFile"
                    @class([
                        'import-ticket__dropzone',
                        'import-ticket__dropzone--has-file' => (bool) $pdfFile,
                    ])
                >
                    <input
                        id="pdfFile"
                        type="file"
                        accept="application/pdf,.pdf"
                        wire:model="pdfFile"
                        class="import-ticket__file-input"
                    >

                    <span class="import-ticket__dropzone-icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="12" y1="18" x2="12" y2="12"/>
                            <polyline points="9 15 12 12 15 15"/>
                        </svg>
                    </span>

                    <span class="import-ticket__dropzone-text">
                        @if ($pdfFile)
                            <strong class="import-ticket__file-name">{{ $pdfFile->getClientOriginalName() }}</strong>
                            <span class="import-ticket__file-hint">PDF selected — click to replace</span>
                        @else
                            <strong class="import-ticket__file-name">Upload ticket PDF</strong>
                            <span class="import-ticket__file-hint">PDF only · up to 5 MB</span>
                        @endif
                    </span>

                    <span class="import-ticket__browse-btn">Browse</span>
                </label>

                @error('pdfFile')
                    <p class="import-ticket__error">{{ $message }}</p>
                @enderror

                <div wire:loading wire:target="pdfFile" class="import-ticket__loading">
                    <span class="import-ticket__loading-dot"></span>
                    Uploading and reading PDF...
                </div>

                @if ($pdfFile && ! $parsed)
                    <button type="button" wire:click="parseUploadedPdf" class="hero-btn hero-btn--secondary import-ticket__read-btn">
                        Read PDF
                    </button>
                @endif
            </div>

            @if ($parseError)
                <div class="import-ticket__alert import-ticket__alert--error">
                    {{ $parseError }}
                </div>
            @endif

            @if ($parsed)
                <p class="import-ticket__review-note">
                    Review the details below. Fix anything the PDF reader got wrong, then click Confirm.
                </p>

                <h4 class="import-ticket__section-title">Booking &amp; Passenger</h4>
                <div class="import-ticket__grid">
                    @foreach ($formFields as $key => $field)
                        <div @class([
                            'import-ticket__field',
                            'import-ticket__field--full' => ! empty($field['full']),
                        ])>
                            <label class="import-ticket__label" for="form_{{ $key }}">
                                {{ $field['label'] }}
                                @if (! empty($field['required']))
                                    <span class="import-ticket__required">*</span>
                                @endif
                            </label>

                            <input
                                id="form_{{ $key }}"
                                type="{{ $field['type'] ?? 'text' }}"
                                wire:model="form.{{ $key }}"
                                class="import-ticket__input"
                                @if (! empty($field['placeholder'])) placeholder="{{ $field['placeholder'] }}" @endif
                            >

                            @error($key)
                                <p class="import-ticket__error">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>

                <div class="import-ticket__segments-head">
                    <h4 class="import-ticket__section-title">Flight Information</h4>
                    <!-- <button type="button" wire:click="addFlightSegment" class="hero-btn hero-btn--secondary">
                        + Add Flight
                    </button> -->
                </div>

                @foreach ($flightSegments as $index => $segment)
                    <div class="import-ticket__segment" wire:key="segment-{{ $index }}">
                        <div class="import-ticket__segment-head">
                            <strong>Flight {{ $index + 1 }}</strong>
                            @if (count($flightSegments) > 1)
                                <button type="button" wire:click="removeFlightSegment({{ $index }})" class="import-ticket__remove">
                                    Remove
                                </button>
                            @endif
                        </div>

                        <div class="import-ticket__grid">
                            @foreach ($segmentFields as $segKey => $segLabel)
                                <div class="import-ticket__field">
                                    <label class="import-ticket__label" for="seg_{{ $index }}_{{ $segKey }}">
                                        {{ $segLabel }}
                                    </label>
                                    <input
                                        id="seg_{{ $index }}_{{ $segKey }}"
                                        type="text"
                                        wire:model="flightSegments.{{ $index }}.{{ $segKey }}"
                                        class="import-ticket__input"
                                    >
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <details class="import-ticket__raw">
                    <summary>Raw PDF text (for tracing import errors)</summary>
                    <pre>{{ $raw_pdf_text }}</pre>
                </details>

                <div class="import-ticket__actions">
                    <button type="button" wire:click="confirm" wire:loading.attr="disabled" class="hero-btn hero-btn--primary">
                        <span wire:loading.remove wire:target="confirm">Confirm Details</span>
                        <span wire:loading wire:target="confirm">Saving...</span>
                    </button>
                    <button type="button" wire:click="resetForm" class="hero-btn hero-btn--secondary">
                        Cancel
                    </button>
                </div>
            @endif
        </div>
    </div>

    <div class="data-panel import-ticket__history">
        <div class="data-panel__head">
            <h3 class="data-panel__title">Recent Confirmed Imports</h3>
        </div>

        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Passenger</th>
                        <th>Agency PNR</th>
                        <th>Flights</th>
                        <th>Confirmed By</th>
                        <th>Confirmed At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentImports as $import)
                        <tr>
                            <td>{{ $import->original_filename }}</td>
                            <td>{{ $import->passenger_name }}</td>
                            <td>{{ $import->agency_pnr ?? '—' }}</td>
                            <td>{{ count($import->flight_segments ?? []) }}</td>
                            <td>{{ $import->user?->name ?? '—' }}</td>
                            <td>{{ $import->confirmed_at?->format('d M Y, H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">No confirmed imports yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
