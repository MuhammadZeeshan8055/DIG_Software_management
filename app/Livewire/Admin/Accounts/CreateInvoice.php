<?php

namespace App\Livewire\Admin\Accounts;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\ReceivingAccount;
use App\Support\UserNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CreateInvoice extends Component
{
    public string $invoice_date = '';
    public string $due_date = '';
    public string $status = 'pending';

    public string $service_category = 'umrah';
    public string $package_label = '';
    public string $package_days = '';

    public string $customer_name = '';
    public string $customer_phone = '';
    public string $customer_email = '';
    public string $customer_address = '';
    public string $cnic_passport = '';
    public string $reference_number = '';

    public float $tax_percent = 5;

    /** @var array<int, array{description: string, qty: int|string, unit_price: float|string}> */
    public array $items = [];

    public ?int $previewInvoiceId = null;
    public bool $showFormModal = false;
    public bool $showPreview = false;
    public bool $showPaymentForm = false;

    public string $payment_amount = '';
    public string $payment_note = '';
    public string $payment_date = '';
    public string $payment_method = 'BANK';
    public ?int $receiving_account_id = null;

    public ?string $successMessage = null;

    public function mount(): void
    {
        $this->invoice_date = now()->toDateString();
        $this->due_date = now()->toDateString();
        $this->tax_percent = (float) config('invoice.default_tax_percent', 5);
        $this->payment_date = now()->toDateString();
        $this->payment_method = config('payment_accounts.default', 'BANK');
        $this->resetItems();
        $this->syncPackageLabel();
        $this->selectFirstAccount();
    }

    public function updatedServiceCategory(): void
    {
        $this->syncPackageLabel();
    }

    public function updatedPackageDays(): void
    {
        $this->syncPackageLabel();
    }

    public function addItem(): void
    {
        $this->items = array_values([
            ...$this->items,
            [
                'description' => '',
                'qty' => 1,
                'unit_price' => '',
            ],
        ]);
    }

    public function removeItem(int $index): void
    {
        if (count($this->items) <= 1) {
            return;
        }

        $items = $this->items;
        unset($items[$index]);
        $this->items = array_values($items);
    }

    public function getSubtotalProperty(): float
    {
        $sum = 0;
        foreach ($this->items as $item) {
            $qty = (float) ($item['qty'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            $sum += $qty * $price;
        }

        return round($sum, 2);
    }

    public function getTaxAmountProperty(): float
    {
        return round($this->subtotal * ((float) $this->tax_percent / 100), 2);
    }

    public function getTotalProperty(): float
    {
        return round($this->subtotal + $this->taxAmount, 2);
    }

    public function openFormModal(): void
    {
        abort_unless(auth()->user()->canView('accounts', 'invoices'), 403);

        $this->resetForm();
        $this->items = [
            ['description' => '', 'qty' => 1, 'unit_price' => ''],
        ];
        $this->showFormModal = true;
        $this->showPreview = false;
        $this->showPaymentForm = false;
        $this->resetValidation();
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetValidation();
    }

    public function save(): void
    {
        abort_unless(auth()->user()->canView('accounts', 'invoices'), 403);

        $this->validate($this->rules());

        $invoice = DB::transaction(function () {
            $invoice = Invoice::create([
                'invoice_number' => Invoice::nextNumber(),
                'invoice_date' => $this->invoice_date,
                'due_date' => $this->due_date ?: null,
                'status' => $this->status,
                'service_category' => $this->service_category,
                'package_label' => $this->package_label ?: $this->defaultPackageLabel(),
                'customer_name' => trim($this->customer_name),
                'customer_phone' => trim($this->customer_phone),
                'customer_email' => $this->customer_email ?: null,
                'customer_address' => $this->customer_address ?: null,
                'cnic_passport' => $this->cnic_passport ?: null,
                'reference_number' => $this->reference_number ?: null,
                'tax_percent' => $this->tax_percent,
                'user_id' => auth()->id(),
            ]);

            foreach ($this->items as $i => $item) {
                $qty = (int) $item['qty'];
                $price = (float) $item['unit_price'];
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => trim($item['description']),
                    'qty' => $qty,
                    'unit_price' => $price,
                    'amount' => round($qty * $price, 2),
                    'sort_order' => $i,
                ]);
            }

            $invoice->recalculateTotals();

            return $invoice->fresh(['items', 'payments', 'user']);
        });

        $this->showFormModal = false;
        $this->showPreview = false;
        $this->previewInvoiceId = null;
        $this->successMessage = 'Invoice '.$invoice->invoice_number.' created. Waiting for admin approval.';
        $this->resetForm();

        $creatorName = auth()->user()->name;
        $admins = UserNotifier::admins()
            ->reject(fn ($admin) => (int) $admin->id === (int) auth()->id());

        UserNotifier::send(
            $admins,
            'invoice_approval',
            'Invoice approval needed',
            $creatorName.' created '.$invoice->invoice_number.' for '.$invoice->customer_name.'.',
            'accounts',
            'invoices'
        );
    }

    public function openPreview(int $id): void
    {
        abort_unless(auth()->user()->canView('accounts', 'invoices'), 403);

        $invoice = Invoice::findOrFail($id);
        abort_unless($invoice->isApproved() || auth()->user()->isAdmin(), 403);

        $this->previewInvoiceId = $id;
        $this->showPreview = true;
        $this->showPaymentForm = false;
    }

    public function approveInvoice(int $id): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $invoice = Invoice::with('user')->findOrFail($id);

        if ($invoice->isApproved()) {
            $this->successMessage = 'Invoice '.$invoice->invoice_number.' is already approved.';

            return;
        }

        $invoice->approve(auth()->user());

        if ($invoice->user && (int) $invoice->user->id !== (int) auth()->id()) {
            UserNotifier::send(
                $invoice->user,
                'invoice_decision',
                'Invoice approved',
                $invoice->invoice_number.' for '.$invoice->customer_name.' was approved.',
                'accounts',
                'invoices'
            );
        }

        $this->successMessage = 'Invoice '.$invoice->invoice_number.' approved.';
    }

    public function closePreview(): void
    {
        $this->showPreview = false;
        $this->showPaymentForm = false;
        $this->previewInvoiceId = null;
    }

    public function openPaymentForm(): void
    {
        $invoice = Invoice::findOrFail($this->previewInvoiceId);
        abort_unless($invoice->isApproved(), 403);

        $this->showPaymentForm = true;
        $this->payment_amount = '';
        $this->payment_note = '';
        $this->payment_date = now()->toDateString();
        $this->payment_method = config('payment_accounts.default', 'BANK');
        $this->selectFirstAccount();
        $this->resetValidation();
    }

    public function recordPayment(): void
    {
        abort_unless(auth()->user()->canView('accounts', 'invoices'), 403);

        $invoice = Invoice::findOrFail($this->previewInvoiceId);
        abort_unless($invoice->isApproved(), 403);

        $this->validate([
            'payment_amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_note' => ['nullable', 'string', 'max:255'],
            'receiving_account_id' => [
                'required',
                Rule::exists('receiving_accounts', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ], [
            'receiving_account_id.required' => 'Please select a payment account.',
            'receiving_account_id.exists' => 'The selected payment account is not valid.',
        ]);

        InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'amount' => (float) $this->payment_amount,
            'paid_at' => $this->payment_date.' '.now()->format('H:i:s'),
            'note' => $this->payment_note ?: null,
            'user_id' => auth()->id(),
            'receiving_account_id' => $this->receiving_account_id,
        ]);

        $invoice->recalculateTotals();
        $this->showPaymentForm = false;
        $this->successMessage = 'Payment recorded.';
    }

    public function render()
    {
        if (! auth()->user()->canView('accounts', 'invoices')) {
            return view('livewire.admin.accounts.create-invoice', [
                'categories' => [],
                'statuses' => [],
                'invoices' => collect(),
                'previewInvoice' => null,
                'paymentMethods' => [],
                'allReceivingAccounts' => [],
            ]);
        }

        $previewInvoice = null;
        if ($this->previewInvoiceId) {
            $previewInvoice = Invoice::with(['items', 'payments.receivingAccount', 'user'])
                ->find($this->previewInvoiceId);
        }

        return view('livewire.admin.accounts.create-invoice', [
            'categories' => config('invoice.categories', []),
            'statuses' => config('invoice.statuses', []),
            'invoices' => Invoice::query()->latest()->limit(20)->get(),
            'previewInvoice' => $previewInvoice,
            'paymentMethods' => config('payment_accounts.options', []),
            'allReceivingAccounts' => ReceivingAccount::pickerOptions(),
        ]);
    }

    protected function rules(): array
    {
        return [
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', 'in:'.implode(',', array_keys(config('invoice.statuses', [])))],
            'service_category' => ['required', 'in:'.implode(',', array_keys(config('invoice.categories', [])))],
            'package_label' => ['nullable', 'string', 'max:255'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_address' => ['nullable', 'string'],
            'cnic_passport' => ['nullable', 'string', 'max:100'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function syncPackageLabel(): void
    {
        $this->package_label = $this->defaultPackageLabel();
    }

    protected function defaultPackageLabel(): string
    {
        $name = config('invoice.categories.'.$this->service_category, 'Package');
        $days = trim($this->package_days);

        if ($days !== '') {
            return $name.' Package ( '.$days.' Days )';
        }

        return $name.' Package';
    }

    protected function resetItems(): void
    {
        $this->items = [
            ['description' => '', 'qty' => 1, 'unit_price' => ''],
        ];
    }

    protected function resetForm(bool $keepPreview = false): void
    {
        $this->status = 'pending';
        $this->service_category = 'umrah';
        $this->package_days = '';
        $this->customer_name = '';
        $this->customer_phone = '';
        $this->customer_email = '';
        $this->customer_address = '';
        $this->cnic_passport = '';
        $this->reference_number = '';
        $this->tax_percent = (float) config('invoice.default_tax_percent', 5);
        $this->invoice_date = now()->toDateString();
        $this->due_date = now()->toDateString();
        $this->resetItems();
        $this->syncPackageLabel();

        if (! $keepPreview) {
            $this->previewInvoiceId = null;
            $this->showPreview = false;
            $this->showFormModal = false;
        }
    }

    private function selectFirstAccount(): void
    {
        $this->receiving_account_id = ReceivingAccount::query()
            ->active()
            ->where('method', $this->payment_method)
            ->orderBy('name')
            ->value('id');
    }
}