<?php

namespace App\Livewire\Admin\Accounts;

use App\Livewire\Concerns\ConfirmsDeletion;
use App\Models\SaleEntry;
use App\Services\SaleSheetImporter;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class SalesReport extends Component
{
    use ConfirmsDeletion;
    use WithFileUploads;

    public $excelFile;

    public string $report_month = '';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public ?int $editingId = null;

    public string $edit_profit = '';

    public function mount(): void
    {
        $this->report_month = now()->format('Y-m');
    }

    public function updatedExcelFile(): void
    {
        $this->resetValidation();
        $this->successMessage = null;
        $this->errorMessage = null;
    }

    public function importSheet(): void
    {
        abort_unless(auth()->user()->canView('accounts', 'reports'), 403);

        $this->successMessage = null;
        $this->errorMessage = null;

        $this->validate([
            'excelFile' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            'report_month' => ['required', 'date_format:Y-m'],
        ], [
            'excelFile.required' => 'Please choose an Excel file to import.',
            'excelFile.mimes' => 'Upload an .xlsx, .xls, or .csv file.',
        ]);

        try {
            $path = $this->excelFile->getRealPath();
            $rows = app(SaleSheetImporter::class)->parse($path);

            if ($rows === []) {
                $this->errorMessage = 'No sales rows found in this file. Check the header row matches DATE / PASSENGER NAME / …';

                return;
            }

            $filename = $this->excelFile->getClientOriginalName();
            $userId = auth()->id();
            $month = $this->report_month;

            DB::transaction(function () use ($rows, $filename, $userId, $month) {
                foreach ($rows as $row) {
                    SaleEntry::create([
                        ...$row,
                        'profit' => null,
                        'report_month' => $month,
                        'original_filename' => $filename,
                        'user_id' => $userId,
                    ]);
                }
            });

            $count = count($rows);
            $this->excelFile = null;
            $this->successMessage = "Imported {$count} row(s). Add profit for each entry below.";
        } catch (\Throwable $exception) {
            report($exception);
            $this->errorMessage = 'Could not read this file. Use the same columns as your monthly sales sheet.';
        }
    }

    public function startEditProfit(int $id): void
    {
        abort_unless(auth()->user()->canView('accounts', 'reports'), 403);

        $entry = SaleEntry::find($id);

        if (! $entry) {
            return;
        }

        $this->editingId = $id;
        $this->edit_profit = $entry->profit !== null ? (string) $entry->profit : '';
        $this->successMessage = null;
        $this->errorMessage = null;
    }

    public function cancelEditProfit(): void
    {
        $this->editingId = null;
        $this->edit_profit = '';
    }

    public function saveProfit(): void
    {
        abort_unless(auth()->user()->canView('accounts', 'reports'), 403);

        if (! $this->editingId) {
            return;
        }

        $this->validate([
            'edit_profit' => ['required', 'numeric'],
        ], [
            'edit_profit.required' => 'Enter the profit amount.',
        ]);

        $entry = SaleEntry::find($this->editingId);

        if (! $entry) {
            $this->cancelEditProfit();

            return;
        }

        $entry->update([
            'profit' => round((float) $this->edit_profit, 2),
        ]);

        $this->cancelEditProfit();
        $this->successMessage = 'Profit saved.';
    }

    public function askDeleteEntry(int $id): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $this->askDelete(
            $id,
            'Delete sale entry?',
            'This sales row will be permanently removed from the report.'
        );
    }

    protected function performPendingDelete(int $id): void
    {
        $this->deleteEntry($id);
    }

    public function deleteEntry(int $id): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        if ($this->editingId === $id) {
            $this->cancelEditProfit();
        }

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
                    'profit_filled' => 0,
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
