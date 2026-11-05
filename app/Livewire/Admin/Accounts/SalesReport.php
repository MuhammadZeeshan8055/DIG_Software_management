<?php

namespace App\Livewire\Admin\Accounts;

use App\Livewire\Concerns\ConfirmsDeletion;
use App\Models\SaleEntry;
use App\Services\SaleSheetImporter;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class SalesReport extends Component
{
    use ConfirmsDeletion;
    use WithFileUploads;

    public $excelFile;

    public string $report_month = '';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public bool $showReplaceConfirm = false;

    public string $replaceConfirmTitle = 'Replace month data?';

    public string $replaceConfirmMessage = '';

    // File types we allow for upload
    private const ALLOWED_EXTENSIONS = ['xlsx', 'xls', 'csv', 'html', 'htm'];

    public function mount(): void
    {
        // Default to the current month, example: 2026-11
        $this->report_month = now()->format('Y-m');
    }

    public function updatedExcelFile(): void
    {
        $this->resetValidation();
        $this->successMessage = null;
        $this->errorMessage = null;

        if (! $this->excelFile) {
            return;
        }

        // Check the file as soon as it finishes uploading
        $this->validateOnly('excelFile', [
            'excelFile' => $this->excelFileRules(),
        ], $this->excelFileMessages());
    }

    /**
     * Turn "2026-10" into "October 2026" for messages.
     */
    public function monthLabel(): string
    {
        $month = $this->report_month;

        if ($month === '') {
            $month = now()->format('Y-m');
        }

        try {
            return \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y');
        } catch (\Throwable $e) {
            return $month;
        }
    }

    /**
     * Step 1: read the file.
     * Step 2: if that month already has data, ask before replacing.
     * Step 3: otherwise import now.
     */
    public function requestImport(): void
    {
        abort_unless(auth()->user()->canView('accounts', 'reports'), 403);

        $this->successMessage = null;
        $this->errorMessage = null;

        $this->validateImportFile();

        $tmpCopy = null;

        try {
            $path = $this->resolveImportPath($tmpCopy);
            $preview = app(SaleSheetImporter::class)->parse($path);
        } catch (\Throwable $e) {
            report($e);
            $this->errorMessage = 'Could not read this file. Use the travel sales report (.xls / .xlsx / .csv).';

            return;
        } finally {
            if ($tmpCopy && is_file($tmpCopy)) {
                @unlink($tmpCopy);
            }
        }

        if (count($preview) === 0) {
            $this->errorMessage = 'No sales rows found. Expected columns: Date, Passenger Name, Sector, Airline, Payable, Received, Via.';

            return;
        }

        // Use the month found in the Excel dates (so Oct file saves under October)
        $detectedMonth = $this->mainMonthFromRows($preview);

        if ($detectedMonth !== null) {
            $this->report_month = $detectedMonth;
        }

        $month = $this->report_month;
        $existingCount = SaleEntry::query()->forMonth($month)->count();

        if ($existingCount > 0) {
            $label = $this->monthLabel();
            $fileRows = count($preview);

            if ($existingCount === 1) {
                $rowsLabel = '1 row';
            } else {
                $rowsLabel = $existingCount.' rows';
            }

            $this->replaceConfirmTitle = 'Replace month data?';
            $this->replaceConfirmMessage = "Sales data already exists for {$label} ({$rowsLabel}). Replace with {$fileRows} row(s) from this file?";
            $this->showReplaceConfirm = true;

            return;
        }

        $this->runImport(false);
    }

    public function cancelReplaceConfirm(): void
    {
        $this->showReplaceConfirm = false;
        $this->replaceConfirmMessage = '';
    }

    public function importSheetReplacing(): void
    {
        $this->showReplaceConfirm = false;
        $this->replaceConfirmMessage = '';
        $this->runImport(true);
    }

    /**
     * Rules for the uploaded Excel file.
     */
    protected function excelFileRules(): array
    {
        $allowed = self::ALLOWED_EXTENSIONS;

        return [
            'required',
            'max:20480',
            function (string $attribute, mixed $value, \Closure $fail) use ($allowed): void {
                $isTempUpload = $value instanceof TemporaryUploadedFile;
                $isNormalUpload = $value instanceof \Illuminate\Http\UploadedFile;

                if (! $isTempUpload && ! $isNormalUpload) {
                    $fail('Please choose the travel sales report file to import.');

                    return;
                }

                $ext = strtolower($value->getClientOriginalExtension());

                if (! in_array($ext, $allowed, true)) {
                    $fail('Upload the report as .xls, .xlsx, or .csv (Excel HTML .xls is supported).');
                }
            },
        ];
    }

    protected function excelFileMessages(): array
    {
        return [
            'excelFile.required' => 'Please choose the travel sales report file to import.',
            'excelFile.max' => 'File is too large (max 20 MB).',
        ];
    }

    protected function validateImportFile(): void
    {
        $this->validate([
            'excelFile' => $this->excelFileRules(),
            'report_month' => ['required', 'date_format:Y-m'],
        ], $this->excelFileMessages());
    }

    /**
     * Save all rows from the Excel file into the database.
     */
    protected function runImport(bool $replace = false): void
    {
        abort_unless(auth()->user()->canView('accounts', 'reports'), 403);

        $this->successMessage = null;
        $this->errorMessage = null;

        $this->validateImportFile();

        $month = $this->report_month;

        if ($replace === false && SaleEntry::query()->forMonth($month)->exists()) {
            $this->errorMessage = 'Sales data already exists for '.$this->monthLabel().'. Confirm replace to continue.';

            return;
        }

        $tmpCopy = null;

        try {
            $path = $this->resolveImportPath($tmpCopy);
            $rows = app(SaleSheetImporter::class)->parse($path);

            if (count($rows) === 0) {
                $this->errorMessage = 'No sales rows found. Expected columns: Date, Passenger Name, Sector, Airline, Payable, Received, Via.';

                return;
            }

            // Match the report month to the dates inside the file
            $detectedMonth = $this->mainMonthFromRows($rows);

            if ($detectedMonth !== null) {
                $this->report_month = $detectedMonth;
                $month = $detectedMonth;
            }

            $filename = $this->excelFile->getClientOriginalName();
            $userId = auth()->id();

            DB::transaction(function () use ($rows, $filename, $userId, $month, $replace) {
                if ($replace) {
                    SaleEntry::query()->forMonth($month)->delete();
                }

                foreach ($rows as $row) {
                    SaleEntry::create([
                        'sale_date' => $row['sale_date'],
                        'passenger_name' => $row['passenger_name'],
                        'sector' => $row['sector'],
                        'airline' => $row['airline'],
                        'payable' => $row['payable'],
                        'received' => $row['received'],
                        'via' => $row['via'],
                        'profit' => $row['profit'],
                        'report_month' => $month,
                        'original_filename' => $filename,
                        'user_id' => $userId,
                    ]);
                }
            });

            $count = count($rows);
            $this->excelFile = null;
            $label = $this->monthLabel();

            if ($replace) {
                $this->successMessage = "Replaced {$label} with {$count} row(s).";
            } else {
                $this->successMessage = "Imported {$count} row(s) into {$label}.";
            }
        } catch (\Illuminate\Database\QueryException $e) {
            report($e);
            $this->errorMessage = 'File was read, but saving failed. Check database columns and try again.';
        } catch (\Throwable $e) {
            report($e);
            $this->errorMessage = 'Could not read this file. Use the travel sales report (.xls / .xlsx / .csv).';
        } finally {
            if ($tmpCopy && is_file($tmpCopy)) {
                @unlink($tmpCopy);
            }
        }
    }

    /**
     * Find the most common month in the file dates.
     * Example: many rows dated 2026-10-xx → returns "2026-10"
     */
    protected function mainMonthFromRows(array $rows): ?string
    {
        $counts = [];

        foreach ($rows as $row) {
            $saleDate = (string) ($row['sale_date'] ?? '');
            $month = substr($saleDate, 0, 7);

            // Must look like 2026-10
            if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
                continue;
            }

            if (! isset($counts[$month])) {
                $counts[$month] = 0;
            }

            $counts[$month] = $counts[$month] + 1;
        }

        if (count($counts) === 0) {
            return null;
        }

        // Sort so the month with the most rows is first
        arsort($counts);

        $topMonth = array_key_first($counts);

        if (! is_string($topMonth)) {
            return null;
        }

        return $topMonth;
    }

    /**
     * Livewire temp files sometimes lose the real extension.
     * If needed, copy the file and add .xlsx / .xls / .csv back.
     */
    protected function resolveImportPath(?string &$tmpCopy): string
    {
        $path = $this->excelFile->getRealPath();
        $ext = strtolower($this->excelFile->getClientOriginalExtension() ?: '');

        if ($ext === '') {
            return $path;
        }

        if (str_ends_with(strtolower($path), '.'.$ext)) {
            return $path;
        }

        $tmpCopy = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sale_import_'.uniqid('', true).'.'.$ext;
        copy($path, $tmpCopy);

        return $tmpCopy;
    }

    protected function performPendingDelete(int $id): void
    {
        $this->deleteEntry($id);
    }

    public function deleteEntry(int $id): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $entry = SaleEntry::find($id);

        if ($entry && $entry->delete()) {
            $this->successMessage = 'Deleted successfully.';
        }
    }

    public function render()
    {
        if (! auth()->user()->canView('accounts', 'reports')) {
            return view('livewire.admin.accounts.sales-report', [
                'denied' => true,
                'entries' => collect(),
                'totals' => [
                    'entries' => 0,
                    'payable' => 0,
                    'received' => 0,
                    'profit' => 0,
                ],
                'currency' => config('payment_status.currency', 'PKR'),
            ]);
        }

        $month = $this->report_month;

        if ($month === '') {
            $month = now()->format('Y-m');
        }

        return view('livewire.admin.accounts.sales-report', [
            'denied' => false,
            'entries' => SaleEntry::query()
                ->forMonth($month)
                ->orderBy('sale_date')
                ->orderBy('id')
                ->get(),
            'totals' => SaleEntry::totalsForMonth($month),
            'currency' => config('payment_status.currency', 'PKR'),
        ]);
    }
}
