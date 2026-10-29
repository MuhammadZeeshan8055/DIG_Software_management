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

    /** @var list<string> */
    private const ALLOWED_EXTENSIONS = ['xlsx', 'xls', 'csv', 'html', 'htm'];

    public function mount(): void
    {
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

        // Validate as soon as Livewire finishes the temp upload so errors show immediately.
        $this->validateOnly('excelFile', [
            'excelFile' => $this->excelFileRules(),
        ], $this->excelFileMessages());
    }

    public function monthLabel(): string
    {
        $month = $this->report_month ?: now()->format('Y-m');

        try {
            return \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y');
        } catch (\Throwable) {
            return $month;
        }
    }

    /**
     * Validate file, then import — or ask to replace if that month already has rows.
     */
    public function requestImport(): void
    {
        abort_unless(auth()->user()->canView('accounts', 'reports'), 403);

        $this->successMessage = null;
        $this->errorMessage = null;

        $this->validateImportFile();

        $month = $this->report_month;
        $existing = SaleEntry::query()->forMonth($month)->count();

        if ($existing > 0) {
            $label = $this->monthLabel();
            $rowsLabel = $existing === 1 ? '1 row' : "{$existing} rows";

            $this->replaceConfirmTitle = 'Replace month data?';
            $this->replaceConfirmMessage = "Sales data already exists for {$label} ({$rowsLabel}). Do you want to replace it with this file?";
            $this->showReplaceConfirm = true;

            return;
        }

        $this->runImport(replace: false);
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
        $this->runImport(replace: true);
    }

    /**
     * Accept by original filename only — Excel-HTML .xls is MIME text/html and fails mimes checks.
     *
     * @return array<string, mixed>
     */
    protected function excelFileRules(): array
    {
        $allowed = self::ALLOWED_EXTENSIONS;

        return [
            'required',
            'max:20480',
            function (string $attribute, mixed $value, \Closure $fail) use ($allowed): void {
                if (! $value instanceof TemporaryUploadedFile && ! $value instanceof \Illuminate\Http\UploadedFile) {
                    $fail('Please choose the Monthly Air Ticketing Register file to import.');

                    return;
                }

                $ext = strtolower($value->getClientOriginalExtension());

                if (! in_array($ext, $allowed, true)) {
                    $fail('Upload the register as .xls, .xlsx, or .csv (Excel HTML .xls is supported).');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function excelFileMessages(): array
    {
        return [
            'excelFile.required' => 'Please choose the Monthly Air Ticketing Register file to import.',
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

            if ($rows === []) {
                $this->errorMessage = 'No sales rows found. Expected columns like Date, Passenger Name, Total Fare / Sales, Net Profit/Loss, Grand Total.';

                return;
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
                        'gross_profit' => $row['gross_profit'],
                        'loss' => $row['loss'],
                        'profit' => $row['profit'],
                        'report_month' => $month,
                        'original_filename' => $filename,
                        'user_id' => $userId,
                    ]);
                }
            });

            $count = count($rows);
            $this->excelFile = null;
            $this->successMessage = $replace
                ? "Replaced month data with {$count} row(s)."
                : "Imported {$count} row(s).";
        } catch (\Illuminate\Database\QueryException $exception) {
            report($exception);
            $this->errorMessage = 'File was read, but saving failed. Check database columns and try again.';
        } catch (\Throwable $exception) {
            report($exception);
            $this->errorMessage = 'Could not read this file. Use the Monthly Air Ticketing Register (.xls / .xlsx / .csv).';
        } finally {
            if ($tmpCopy && is_file($tmpCopy)) {
                @unlink($tmpCopy);
            }
        }
    }

    /**
     * Ensure the parser sees a path with the original extension (Livewire temp names can omit it).
     */
    protected function resolveImportPath(?string &$tmpCopy): string
    {
        $path = $this->excelFile->getRealPath();
        $ext = strtolower($this->excelFile->getClientOriginalExtension() ?: '');

        if ($ext === '' || str_ends_with(strtolower($path), '.'.$ext)) {
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
                    'gross_profit' => 0,
                    'loss' => 0,
                    'profit' => 0,
                ],
                'currency' => config('payment_status.currency', 'PKR'),
            ]);
        }

        $month = $this->report_month ?: now()->format('Y-m');

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
