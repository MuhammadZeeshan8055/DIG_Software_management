<?php

namespace App\Livewire\Admin\Accounts;

use App\Livewire\Concerns\ConfirmsDeletion;
use App\Models\UmrahSaleEntry;
use App\Services\UmrahSaleSheetImporter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class UmrahSaleReport extends Component
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

        // Always pass rules here — validateOnly() alone has no rules and causes 500
        $this->validateOnly('excelFile', [
            'excelFile' => $this->excelFileRules(),
        ], $this->excelFileMessages());
    }

    public function monthLabel(): string
    {
        $month = $this->report_month ?: now()->format('Y-m');

        try {
            return \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y');
        } catch (\Throwable $e) {
            return $month;
        }
    }

    /** Read file → ask replace if month has data → else import */
    public function requestImport(): void
    {
        $this->guardAccess();
        $this->clearMessages();
        $this->validateImportFile();

        $rows = $this->parseUploadedFile();

        if ($rows === null) {
            return;
        }

        if ($rows === []) {
            $this->errorMessage = 'No umrah rows found in this file.';

            return;
        }

        $this->report_month = $this->mainMonthFromRows($rows) ?? $this->report_month;

        try {
            $existing = UmrahSaleEntry::query()->forMonth($this->report_month)->count();
        } catch (\Throwable $e) {
            report($e);
            $this->errorMessage = 'Database table missing. Run: php artisan migrate';

            return;
        }

        if ($existing > 0) {
            $label = $this->monthLabel();
            $this->replaceConfirmTitle = 'Replace month data?';
            $this->replaceConfirmMessage = "Umrah sales already exist for {$label} ({$existing} row(s)). Replace with ".count($rows).' row(s)?';
            $this->showReplaceConfirm = true;

            return;
        }

        $this->saveRows($rows, false);
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

        $rows = $this->parseUploadedFile();

        if ($rows === null || $rows === []) {
            if ($rows === []) {
                $this->errorMessage = 'No umrah rows found in this file.';
            }

            return;
        }

        $this->saveRows($rows, true);
    }

    public function deleteEntry(int $id): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        if (UmrahSaleEntry::find($id)?->delete()) {
            $this->successMessage = 'Deleted successfully.';
        }
    }

    protected function performPendingDelete(int $id): void
    {
        $this->deleteEntry($id);
    }

    public function render()
    {
        $canView = auth()->user()->canView('accounts', 'umrah-sales-report');
        $month = $this->report_month ?: now()->format('Y-m');

        return view('livewire.admin.accounts.umrah-sale-report', [
            'denied' => ! $canView,
            'entries' => $canView
                ? UmrahSaleEntry::query()->forMonth($month)->orderBy('sale_date')->orderBy('id')->get()
                : collect(),
            'totals' => $canView
                ? UmrahSaleEntry::totalsForMonth($month)
                : [
                    'entries' => 0,
                    'payable' => 0,
                    'total_amount' => 0,
                    'received' => 0,
                    'remaining' => 0,
                    'profit' => 0,
                    'loss' => 0,
                    'agent_profit' => 0,
                ],
            'currency' => config('payment_status.currency', 'PKR'),
        ]);
    }

    private function guardAccess(): void
    {
        abort_unless(auth()->user()->canView('accounts', 'umrah-sales-report'), 403);
    }

    private function clearMessages(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;
    }

    private function excelFileRules(): array
    {
        return [
            'required',
            'max:20480',
            function (string $attribute, mixed $value, \Closure $fail): void {
                $ok = $value instanceof TemporaryUploadedFile || $value instanceof UploadedFile;

                if (! $ok) {
                    $fail('Please choose the DIG Umrah Accounts Ledger file.');

                    return;
                }

                $ext = strtolower($value->getClientOriginalExtension());

                if (! in_array($ext, ['xlsx', 'xls'], true)) {
                    $fail('Upload the ledger as .xlsx or .xls.');
                }
            },
        ];
    }

    private function excelFileMessages(): array
    {
        return [
            'excelFile.required' => 'Please choose the DIG Umrah Accounts Ledger file.',
            'excelFile.max' => 'File is too large (max 20 MB).',
        ];
    }

    private function validateImportFile(): void
    {
        $this->validate([
            'excelFile' => $this->excelFileRules(),
            'report_month' => ['required', 'date_format:Y-m'],
        ], $this->excelFileMessages());
    }

    /** @return array<int, array>|null null = read error */
    private function parseUploadedFile(): ?array
    {
        $tmpCopy = null;

        try {
            $path = $this->filePath($tmpCopy);

            return app(UmrahSaleSheetImporter::class)->parse($path);
        } catch (\Throwable $e) {
            report($e);
            $this->errorMessage = 'Could not read this file. Use the DIG Umrah Accounts Ledger (.xlsx / .xls).';

            return null;
        } finally {
            if ($tmpCopy && is_file($tmpCopy)) {
                @unlink($tmpCopy);
            }
        }
    }

    private function saveRows(array $rows, bool $replace): void
    {
        $this->guardAccess();

        $month = $this->mainMonthFromRows($rows) ?? $this->report_month;
        $this->report_month = $month;

        $filename = $this->excelFile->getClientOriginalName();
        $userId = auth()->id();

        try {
            DB::transaction(function () use ($rows, $filename, $userId, $month, $replace) {
                if ($replace) {
                    UmrahSaleEntry::query()->forMonth($month)->delete();
                }

                foreach ($rows as $row) {
                    UmrahSaleEntry::create(array_merge($row, [
                        'report_month' => $month,
                        'original_filename' => $filename,
                        'user_id' => $userId,
                    ]));
                }
            });

            $this->excelFile = null;
            $count = count($rows);
            $label = $this->monthLabel();
            $this->successMessage = $replace
                ? "Replaced {$label} with {$count} row(s)."
                : "Imported {$count} row(s) into {$label}.";
        } catch (\Throwable $e) {
            report($e);
            $this->errorMessage = 'File was read, but saving failed. Please try again.';
        }
    }

    private function mainMonthFromRows(array $rows): ?string
    {
        $counts = [];

        foreach ($rows as $row) {
            $month = substr((string) ($row['sale_date'] ?? ''), 0, 7);

            if (preg_match('/^\d{4}-\d{2}$/', $month)) {
                $counts[$month] = ($counts[$month] ?? 0) + 1;
            }
        }

        if ($counts === []) {
            return null;
        }

        arsort($counts);

        return array_key_first($counts);
    }

    private function filePath(?string &$tmpCopy): string
    {
        $path = $this->excelFile->getRealPath();
        $ext = strtolower($this->excelFile->getClientOriginalExtension() ?: '');

        if ($ext === '' || str_ends_with(strtolower($path), '.'.$ext)) {
            return $path;
        }

        $tmpCopy = sys_get_temp_dir().DIRECTORY_SEPARATOR.'umrah_import_'.uniqid().'.'.$ext;
        copy($path, $tmpCopy);

        return $tmpCopy;
    }
}
