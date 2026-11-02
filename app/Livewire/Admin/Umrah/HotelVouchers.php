<?php

namespace App\Livewire\Admin\Umrah;

use App\Livewire\Concerns\ConfirmsDeletion;
use App\Models\HotelVoucher;
use Carbon\Carbon;
use Livewire\Component;

class HotelVouchers extends Component
{
    use ConfirmsDeletion;

    // Modals
    public bool $showFormModal = false;
    public bool $showViewModal = false;
    public ?int $viewingVoucherId = null;
    public ?int $editingId = null; // null = create, set = edit

    // Wizard steps (1–6)
    public int $openStep = 1;
    public int $maxUnlockedStep = 1;

    // Step 1 — identity
    public string $voucher_number = '';
    public string $issue_date = '';
    public string $status = 'draft';
    public string $booking_reference = '';
    public string $family_head = '';
    public string $group_number = '';
    public string $agent_customer = '';
    public string $package_program = '';
    public string $country = 'Pakistan';
    public string $visa_type = 'Umrah Visa';
    public string $service_scope = 'Accommodation as detailed in this voucher';

    // Step 2–5 — row data
    public array $guests = [];
    public array $stays = [];
    public array $flights = [];
    public array $transport = [];

    // Step 6 — contacts
    public string $ksa_helpline = '';
    public string $pakistan_helpline = '+92 303 5372095';
    public string $prepared_by = '';
    public string $special_instructions = '';

    public ?string $successMessage = null;

    public function mount(): void
    {
        $this->issue_date = now()->toDateString();
        $this->prepared_by = auth()->user()?->name ?? '';
        $this->resetCollections();
    }

    // ── Modals ──────────────────────────────────────────────────────

    public function openFormModal(): void
    {
        $this->guardView();
        $this->resetForm();
        $this->showViewModal = false;
        $this->showFormModal = true;
        $this->resetValidation();
    }

    public function openEditModal(int $id): void
    {
        $this->guardView();

        $voucher = HotelVoucher::query()->findOrFail($id);
        $this->fillFormFromVoucher($voucher);

        $this->showViewModal = false;
        $this->viewingVoucherId = null;
        $this->showFormModal = true;
        $this->resetValidation();
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->editingId = null;
        $this->resetValidation();
    }

    public function openViewModal(int $id): void
    {
        $this->guardView();
        $this->viewingVoucherId = $id;
        $this->showViewModal = true;
        $this->showFormModal = false;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->viewingVoucherId = null;
    }

    protected function performPendingDelete(int $id): void
    {
        $this->guardView();

        HotelVoucher::query()->whereKey($id)->delete();

        if ($this->viewingVoucherId === $id) {
            $this->closeViewModal();
        }

        if ($this->editingId === $id) {
            $this->closeFormModal();
        }

        $this->successMessage = 'Deleted hotel voucher.';
    }

    /** Load an existing voucher into the stepped form for editing. */
    protected function fillFormFromVoucher(HotelVoucher $voucher): void
    {
        $this->editingId = $voucher->id;
        $this->voucher_number = (string) $voucher->voucher_number;
        $this->issue_date = optional($voucher->issue_date)->toDateString() ?: now()->toDateString();
        $this->status = $voucher->status ?: 'draft';
        $this->booking_reference = (string) ($voucher->booking_reference ?? '');
        $this->family_head = (string) ($voucher->family_head ?? '');
        $this->group_number = (string) ($voucher->group_number ?? '');
        $this->agent_customer = (string) ($voucher->agent_customer ?? '');
        $this->package_program = (string) ($voucher->package_program ?? '');
        $this->country = (string) ($voucher->country ?: 'Pakistan');
        $this->visa_type = (string) ($voucher->visa_type ?: 'Umrah Visa');
        $this->service_scope = (string) ($voucher->service_scope ?? '');
        $this->ksa_helpline = (string) ($voucher->ksa_helpline ?? '');
        $this->pakistan_helpline = (string) ($voucher->pakistan_helpline ?? '');
        $this->prepared_by = (string) ($voucher->prepared_by ?? '');
        $this->special_instructions = (string) ($voucher->special_instructions ?? '');

        $this->guests = $this->normalizeRows($voucher->guests ?: [], $this->emptyGuest());
        $this->stays = $this->normalizeRows($voucher->stays ?: [], $this->emptyStay());
        $this->flights = $this->normalizeFlightRows($voucher->flights ?: []);
        $this->transport = $this->normalizeRows($voucher->transport ?: [], null);

        // Editing: all steps unlocked so the user can jump around
        $this->openStep = 1;
        $this->maxUnlockedStep = 6;

        foreach (array_keys($this->stays) as $index) {
            $this->recalculateStayNights($index);
        }
    }

    /** Make sure every row key exists as a string (Livewire form inputs hate nulls). */
    protected function normalizeRows(array $rows, ?array $fallback): array
    {
        if ($rows === [] && $fallback !== null) {
            return [$fallback];
        }

        $clean = [];
        foreach ($rows as $row) {
            $item = $fallback ? array_merge($fallback, $row) : $row;
            foreach ($item as $key => $value) {
                if ($value === null) {
                    $item[$key] = '';
                }
            }
            $clean[] = $item;
        }

        return array_values($clean);
    }

    protected function normalizeFlightRows(array $rows): array
    {
        $clean = [];
        foreach ($rows as $row) {
            $item = array_merge($this->emptyFlight(), $row);
            foreach (['departure_at', 'arrival_at'] as $field) {
                $item[$field] = $this->toDateTimeLocal($item[$field] ?? '');
            }
            foreach ($item as $key => $value) {
                if ($value === null) {
                    $item[$key] = '';
                }
            }
            $clean[] = $item;
        }

        return array_values($clean);
    }

    protected function toDateTimeLocal(mixed $value): string
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return '';
        }

        try {
            return Carbon::parse($text)->format('Y-m-d\TH:i');
        } catch (\Throwable) {
            return $text;
        }
    }

    /** Shared create/update payload. */
    protected function voucherPayload(): array
    {
        return [
            'issue_date' => $this->issue_date,
            'status' => $this->status,
            'booking_reference' => $this->booking_reference ?: null,
            'family_head' => trim($this->family_head),
            'group_number' => $this->group_number ?: null,
            'agent_customer' => $this->agent_customer ?: null,
            'package_program' => $this->package_program ?: null,
            'country' => $this->country ?: 'Pakistan',
            'visa_type' => $this->visa_type ?: 'Umrah Visa',
            'service_scope' => $this->service_scope ?: null,
            'guests' => array_values($this->guests),
            'stays' => array_values($this->stays),
            'flights' => array_values($this->flights),
            'transport' => array_values($this->transport),
            'ksa_helpline' => $this->ksa_helpline ?: null,
            'pakistan_helpline' => $this->pakistan_helpline ?: null,
            'prepared_by' => $this->prepared_by ?: null,
            'special_instructions' => $this->special_instructions ?: null,
        ];
    }

    // ── Steps ───────────────────────────────────────────────────────

    public function toggleStep(int $step): void
    {
        if ($step < 1 || $step > 6 || $step > $this->maxUnlockedStep) {
            return;
        }

        $this->openStep = $this->openStep === $step ? 0 : $step;
    }

    public function goToStep(int $step): void
    {
        if ($step < 1 || $step > 6 || $step > $this->maxUnlockedStep) {
            return;
        }

        $this->openStep = $step;
    }

    public function continueToStep(int $next): void
    {
        $from = $this->openStep > 0 ? $this->openStep : max(1, $next - 1);

        $this->normalizeStepData($from);
        $this->validate($this->rulesForStep($from), $this->messagesForStep($from));

        if ($next > $this->maxUnlockedStep) {
            $this->maxUnlockedStep = $next;
        }

        $this->resetValidation();
        $this->openStep = $next;
    }

    // ── Stay nights (auto from dates) ───────────────────────────────

    public function updated(string $property): void
    {
        // stays.0.check_in or stays.0.check_out → recalculate nights
        if (preg_match('/^stays\.(\d+)\.(check_in|check_out)$/', $property, $m)) {
            $this->recalculateStayNights((int) $m[1]);
        }
    }

    protected function recalculateStayNights(int $index): void
    {
        if (! isset($this->stays[$index])) {
            return;
        }

        $checkIn = $this->stays[$index]['check_in'] ?? '';
        $checkOut = $this->stays[$index]['check_out'] ?? '';

        if ($checkIn === '' || $checkOut === '') {
            $this->stays[$index]['nights'] = null;

            return;
        }

        try {
            $in = Carbon::parse($checkIn)->startOfDay();
            $out = Carbon::parse($checkOut)->startOfDay();
            $this->stays[$index]['nights'] = max(0, (int) $in->diffInDays($out, false));
        } catch (\Throwable) {
            $this->stays[$index]['nights'] = null;
        }
    }

    protected function normalizeStepData(int $step): void
    {
        if ($step !== 3) {
            return;
        }

        foreach ($this->stays as $i => $stay) {
            $rooms = $stay['rooms'] ?? null;
            $this->stays[$i]['rooms'] = ($rooms === '' || $rooms === null) ? null : (int) $rooms;

            foreach (['check_in', 'check_out', 'category', 'distance', 'confirmation'] as $field) {
                if (($stay[$field] ?? null) === '') {
                    $this->stays[$i][$field] = null;
                }
            }

            unset($this->stays[$i]['view']);
            $this->stays[$i]['hotel'] = trim((string) ($stay['hotel'] ?? ''));
            $this->stays[$i]['city'] = trim((string) ($stay['city'] ?? ''));
            $this->recalculateStayNights($i);
        }
    }

    // ── Guests / stays / flights / transport rows ───────────────────

    public function addGuest(): void
    {
        $this->guests[] = $this->emptyGuest();
    }

    public function removeGuest(int $index): void
    {
        if (count($this->guests) <= 1) {
            return;
        }

        unset($this->guests[$index]);
        $this->guests = array_values($this->guests);
    }

    public function addStay(): void
    {
        $this->stays[] = $this->emptyStay();
    }

    public function removeStay(int $index): void
    {
        if (count($this->stays) <= 1) {
            return;
        }

        unset($this->stays[$index]);
        $this->stays = array_values($this->stays);
    }

    public function addFlight(): void
    {
        $flight = $this->emptyFlight();
        $flight['type'] = count($this->flights) === 1 ? 'Return' : 'Departure';
        $this->flights[] = $flight;
    }

    public function removeFlight(int $index): void
    {
        unset($this->flights[$index]);
        $this->flights = array_values($this->flights);
    }

    public function swapFlightAirports(int $index): void
    {
        if (! isset($this->flights[$index])) {
            return;
        }

        $flight = $this->flights[$index];

        // Swap origin ↔ destination
        [$flight['origin'], $flight['destination']] = [$flight['destination'] ?? '', $flight['origin'] ?? ''];
        [$flight['origin_label'], $flight['destination_label']] = [$flight['destination_label'] ?? '', $flight['origin_label'] ?? ''];
        [$flight['origin_region'], $flight['destination_region']] = [$flight['destination_region'] ?? '', $flight['origin_region'] ?? ''];

        $flight['sector_code'] = $this->makeSectorCode($flight['origin'], $flight['destination']);
        $this->flights[$index] = $flight;
    }

    public function syncFlightSector(int $index): void
    {
        if (! isset($this->flights[$index])) {
            return;
        }

        $this->flights[$index]['sector_code'] = $this->makeSectorCode(
            $this->flights[$index]['origin'] ?? '',
            $this->flights[$index]['destination'] ?? ''
        );
    }

    protected function makeSectorCode(mixed $origin, mixed $destination): string
    {
        $from = trim((string) $origin);
        $to = trim((string) $destination);

        return ($from !== '' && $to !== '') ? strtoupper($from.'-'.$to) : '';
    }

    public function addTransport(): void
    {
        $this->transport[] = $this->emptyTransport();
    }

    public function removeTransport(int $index): void
    {
        unset($this->transport[$index]);
        $this->transport = array_values($this->transport);
    }

    // ── Save ────────────────────────────────────────────────────────

    public function save(): void
    {
        abort_unless(
            auth()->user()->canManage('hotel-vouchers', 'hotel-vouchers')
            || auth()->user()->canView('hotel-vouchers', 'hotel-vouchers'),
            403
        );

        // Validate every step; jump to the first failing step
        for ($step = 1; $step <= 6; $step++) {
            try {
                $this->validate($this->rulesForStep($step));
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->openStep = $step;
                $this->maxUnlockedStep = max($this->maxUnlockedStep, $step);
                throw $e;
            }
        }

        $data = $this->voucherPayload();

        if ($this->editingId) {
            $voucher = HotelVoucher::query()->findOrFail($this->editingId);
            $voucher->update($data);
            $this->successMessage = 'Hotel voucher updated.';
        } else {
            HotelVoucher::create(array_merge($data, [
                'voucher_number' => HotelVoucher::nextNumber(),
                'user_id' => auth()->id(),
            ]));
            $this->successMessage = 'Hotel voucher saved.';
        }

        $this->showFormModal = false;
        $this->editingId = null;
        $this->resetForm();
    }

    // ── Validation ──────────────────────────────────────────────────

    protected function rulesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'issue_date' => ['required', 'date'],
                'status' => ['required', 'in:draft,issued'],
                'family_head' => ['required', 'string', 'max:160'],
                'booking_reference' => ['nullable', 'string', 'max:120'],
                'group_number' => ['nullable', 'string', 'max:80'],
                'agent_customer' => ['nullable', 'string', 'max:160'],
                'package_program' => ['nullable', 'string', 'max:160'],
                'country' => ['nullable', 'string', 'max:80'],
                'visa_type' => ['nullable', 'string', 'max:80'],
                'service_scope' => ['nullable', 'string', 'max:500'],
            ],
            2 => [
                'guests' => ['required', 'array', 'min:1'],
                'guests.*.passenger_name' => ['required', 'string', 'max:160'],
                'guests.*.passport' => ['nullable', 'string', 'max:80'],
                'guests.*.gender' => ['nullable', 'in:Male,Female'],
                'guests.*.pax_type' => ['nullable', 'in:Adult,Child,Infant'],
                'guests.*.bed' => ['nullable', 'in:Yes,No'],
            ],
            3 => [
                'stays' => ['required', 'array', 'min:1'],
                'stays.*.city' => ['required', 'in:Makkah,Madinah'],
                'stays.*.hotel' => ['required', 'string', 'min:1', 'max:160'],
            ],
            4 => [
                'flights' => ['nullable', 'array'],
                'flights.*.type' => ['nullable', 'in:Departure,Return'],
                'flights.*.airline' => ['nullable', 'string', 'max:120'],
                'flights.*.flight' => ['nullable', 'string', 'max:80'],
            ],
            5 => [
                'transport' => ['nullable', 'array'],
                'transport.*.route_service' => ['nullable', 'string', 'max:200'],
            ],
            6 => [
                'ksa_helpline' => ['nullable', 'string', 'max:80'],
                'pakistan_helpline' => ['nullable', 'string', 'max:80'],
                'prepared_by' => ['nullable', 'string', 'max:120'],
                'special_instructions' => ['nullable', 'string', 'max:2000'],
            ],
            default => [],
        };
    }

    protected function messagesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'family_head.required' => 'Family / group head is required.',
                'issue_date.required' => 'Issue date is required.',
            ],
            2 => [
                'guests.*.passenger_name.required' => 'Passenger name is required for each guest.',
            ],
            3 => [
                'stays.*.hotel.required' => 'Hotel name is required for each stay.',
                'stays.*.city.required' => 'City is required for each stay.',
                'stays.*.city.in' => 'City must be Makkah or Madinah.',
            ],
            default => [],
        };
    }

    // ── Reset / empty rows ──────────────────────────────────────────

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->voucher_number = HotelVoucher::nextNumber();
        $this->issue_date = now()->toDateString();
        $this->status = 'draft';
        $this->booking_reference = '';
        $this->family_head = '';
        $this->group_number = '';
        $this->agent_customer = '';
        $this->package_program = '';
        $this->country = 'Pakistan';
        $this->visa_type = 'Umrah Visa';
        $this->service_scope = 'Accommodation as detailed in this voucher';
        $this->ksa_helpline = '';
        $this->pakistan_helpline = '+92 303 5372095';
        $this->prepared_by = auth()->user()?->name ?? '';
        $this->special_instructions = '';
        $this->openStep = 1;
        $this->maxUnlockedStep = 1;
        $this->resetCollections();
    }

    protected function resetCollections(): void
    {
        $this->guests = [$this->emptyGuest()];
        $this->stays = [$this->emptyStay()];
        $this->flights = $this->defaultFlights();
        $this->transport = [];
    }

    protected function defaultFlights(): array
    {
        $departure = $this->emptyFlight();
        $departure['type'] = 'Departure';
        $departure['origin'] = 'ISB';
        $departure['origin_label'] = 'ISB — Islamabad — Pakistan';
        $departure['origin_region'] = 'Pakistan';
        $departure['destination'] = 'JED';
        $departure['destination_label'] = 'JED — Jeddah — Saudi Arabia';
        $departure['destination_region'] = 'Worldwide';
        $departure['sector_code'] = 'ISB-JED';

        $return = $this->emptyFlight();
        $return['type'] = 'Return';
        $return['origin'] = 'JED';
        $return['origin_label'] = 'JED — Jeddah — Saudi Arabia';
        $return['origin_region'] = 'Worldwide';
        $return['destination'] = 'ISB';
        $return['destination_label'] = 'ISB — Islamabad — Pakistan';
        $return['destination_region'] = 'Pakistan';
        $return['sector_code'] = 'JED-ISB';

        return [$departure, $return];
    }

    protected function emptyGuest(): array
    {
        return [
            'passenger_name' => '',
            'passport' => '',
            'gender' => 'Male',
            'pax_type' => 'Adult',
            'bed' => 'Yes',
            'mofa' => '',
            'visa' => '',
            'pnr' => '',
        ];
    }

    protected function emptyStay(): array
    {
        return [
            'city' => 'Makkah',
            'hotel' => '',
            'category' => '',
            'room_type' => 'Quad',
            'rooms' => 1,
            'meal' => 'RO - Room Only',
            'distance' => '',
            'confirmation' => '',
            'check_in' => '',
            'check_out' => '',
            'nights' => null,
        ];
    }

    protected function emptyFlight(): array
    {
        return [
            'type' => 'Departure',
            'airline' => '',
            'flight' => '',
            'origin' => '',
            'origin_label' => '',
            'origin_region' => 'Pakistan',
            'destination' => '',
            'destination_label' => '',
            'destination_region' => 'Worldwide',
            'sector_code' => '',
            'departure_at' => '',
            'arrival_at' => '',
        ];
    }

    protected function emptyTransport(): array
    {
        return [
            'date' => '',
            'route_service' => '',
            'vehicle' => '',
            'supplier_driver' => '',
            'reference' => '',
        ];
    }

    protected function guardView(): void
    {
        abort_unless(auth()->user()->canView('hotel-vouchers', 'hotel-vouchers'), 403);
    }

    public function render()
    {
        $canView = auth()->user()->canView('hotel-vouchers', 'hotel-vouchers');

        $viewingVoucher = null;
        if ($canView && $this->showViewModal && $this->viewingVoucherId) {
            $viewingVoucher = HotelVoucher::query()->find($this->viewingVoucherId);
        }

        return view('livewire.admin.umrah.hotel-vouchers', [
            'vouchers' => $canView
                ? HotelVoucher::query()->latest('id')->limit(100)->get()
                : collect(),
            'canView' => $canView,
            'airports' => config('umrah_airports', []),
            'viewingVoucher' => $viewingVoucher,
        ]);
    }
}
