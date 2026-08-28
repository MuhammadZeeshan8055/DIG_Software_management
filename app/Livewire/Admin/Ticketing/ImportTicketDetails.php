<?php

namespace App\Livewire\Admin\Ticketing;

use App\Http\Requests\ConfirmTicketImportRequest;
use App\Models\TicketImport;
use App\Services\TicketPdfParser;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImportTicketDetails extends Component
{
    use WithFileUploads;

    public $pdfFile;

    /** @var array<string, string> */
    public array $form = [];

    /** @var array<int, array<string, string>> */
    public array $flightSegments = [];

    public string $raw_pdf_text = '';

    public bool $parsed = false;

    public ?string $successMessage = null;

    public ?string $parseError = null;

    public function mount(): void
    {
        $this->resetFormFields();
    }

    public function updatedPdfFile(): void
    {
        $this->resetValidation();
        $this->successMessage = null;
        $this->parseError = null;
        $this->parsed = false;

        $this->validate([
            'pdfFile' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:'.config('ticket_import.max_pdf_size_kb', 5120)],
        ]);

        $this->parseUploadedPdf();
    }

    public function parseUploadedPdf(): void
    {
        if (! $this->pdfFile) {
            return;
        }

        try {
            $parser = app(TicketPdfParser::class);
            $path = $this->pdfFile->getRealPath();
            $text = $parser->extractText($path);
            $parsed = $parser->parse($text);

            $this->raw_pdf_text = $text;
            $this->form = array_merge($this->emptyFormFields(), $parsed['fields']);
            $this->flightSegments = $parsed['flight_segments'] ?: [app(TicketPdfParser::class)->emptySegment()];

            $this->parseError = $text === ''
                ? 'No readable text found in this PDF. Fill the fields manually below.'
                : null;
            $this->parsed = true;
        } catch (\Throwable $exception) {
            $this->parseError = 'Could not read this PDF. Fill the fields manually below.';
            $this->flightSegments = [app(TicketPdfParser::class)->emptySegment()];
            $this->parsed = true;
            report($exception);
        }
    }

    public function addFlightSegment(): void
    {
        $this->flightSegments[] = app(TicketPdfParser::class)->emptySegment();
    }

    public function removeFlightSegment(int $index): void
    {
        if (count($this->flightSegments) <= 1) {
            return;
        }

        unset($this->flightSegments[$index]);
        $this->flightSegments = array_values($this->flightSegments);
    }

    public function confirm(): void
    {
        $payload = [
            ...$this->form,
            'flight_segments' => $this->flightSegments,
        ];

        $validated = validator(
            $payload,
            (new ConfirmTicketImportRequest)->rules(),
            [],
            $this->fieldLabels()
        )->validate();

        if (! $this->pdfFile) {
            $this->addError('pdfFile', 'Please upload a PDF first.');

            return;
        }

        $storedPath = $this->pdfFile->store('ticket-imports', 'local');

        TicketImport::create([
            'user_id' => auth()->id(),
            'original_filename' => $this->pdfFile->getClientOriginalName(),
            'pdf_path' => $storedPath,
            'raw_pdf_text' => $this->raw_pdf_text,
            ...$validated,
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        $this->resetForm();
        $this->successMessage = 'Ticket details confirmed and saved.';
    }

    public function resetForm(): void
    {
        $this->reset([
            'pdfFile',
            'raw_pdf_text',
            'parsed',
            'parseError',
            'flightSegments',
        ]);

        $this->resetFormFields();
    }

    public function render()
    {
        return view('livewire.admin.ticketing.import-ticket-details', [
            'formFields' => config('ticket_import.form_fields', []),
            'segmentFields' => config('ticket_import.flight_segment_fields', []),
            'recentImports' => TicketImport::query()
                ->with('user')
                ->latest()
                ->limit(15)
                ->get(),
        ]);
    }

    /** @return array<string, string> */
    private function emptyFormFields(): array
    {
        $fields = [];

        foreach (array_keys(config('ticket_import.form_fields', [])) as $key) {
            $fields[$key] = '';
        }

        return $fields;
    }

    private function resetFormFields(): void
    {
        $this->form = $this->emptyFormFields();
        $this->flightSegments = [];
    }

    /** @return array<string, string> */
    private function fieldLabels(): array
    {
        $labels = [];

        foreach (config('ticket_import.form_fields', []) as $key => $field) {
            $labels[$key] = $field['label'] ?? $key;
        }

        $labels['flight_segments'] = 'Flight segments';

        return $labels;
    }
}
