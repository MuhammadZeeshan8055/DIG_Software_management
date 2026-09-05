<?php

namespace App\Livewire\Admin\Attendance;

use App\Models\AttendanceSetting;
use Livewire\Component;

/**
 * Admin: office hours + up to 3 IPs.
 */
class OfficeSettings extends Component
{
    public string $office_start = '10:00';
    public string $office_end = '18:00';
    public string $required_hours = '8';
    public string $lunch_start = '13:00';
    public string $lunch_end = '14:00';
    public string $break_minutes = '60';
    public string $office_ip_1 = '';
    public string $office_ip_2 = '';
    public string $office_ip_3 = '';
    public ?string $successMessage = null;

    public function mount(): void
    {
        // Always on dashboard — do not abort(403) here.
    
        // 1) Get the one settings row (or create defaults if table is empty)
        $settings = AttendanceSetting::query()->first();
    
        if (! $settings) {
            $settings = AttendanceSetting::create([
                'office_start' => '10:00:00',
                'office_end' => '18:00:00',
                'required_hours' => 8,
                'lunch_start' => '13:00:00',
                'lunch_end' => '14:00:00',
                'break_minutes' => 60,
                'office_ip_1' => null,
                'office_ip_2' => null,
                'office_ip_3' => null,
            ]);
        }
    
        // 2) Copy DB values into Livewire properties (H:i for time inputs)
        $this->office_start = substr((string) $settings->office_start, 0, 5);
        $this->office_end = substr((string) $settings->office_end, 0, 5);
        $this->required_hours = (string) $settings->required_hours;
        $this->lunch_start = $settings->lunch_start
            ? substr((string) $settings->lunch_start, 0, 5)
            : '';
        $this->lunch_end = $settings->lunch_end
            ? substr((string) $settings->lunch_end, 0, 5)
            : '';
        $this->break_minutes = (string) $settings->break_minutes;
        $this->office_ip_1 = (string) ($settings->office_ip_1 ?? '');
        $this->office_ip_2 = (string) ($settings->office_ip_2 ?? '');
        $this->office_ip_3 = (string) ($settings->office_ip_3 ?? '');
    }

    public function render()
    {
        if (! auth()->user()->canManage('attendance', 'office-settings')) {
            return view('livewire.admin.attendance.office-settings', [
                'denied' => true,
            ]);
        }

        return view('livewire.admin.attendance.office-settings', [
            'denied' => false,
        ]);
    }

    public function save(): void
    {
        if (! auth()->user()->canManage('attendance', 'office-settings')) {
            return;
        }

        $this->successMessage = null;

        $this->validate([
            'office_start' => ['required', 'date_format:H:i'],
            'office_end' => ['required', 'date_format:H:i'],
            'required_hours' => ['required', 'numeric', 'min:1', 'max:24'],
            'lunch_start' => ['nullable', 'date_format:H:i'],
            'lunch_end' => ['nullable', 'date_format:H:i'],
            'break_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'office_ip_1' => ['nullable', 'ip'],
            'office_ip_2' => ['nullable', 'ip'],
            'office_ip_3' => ['nullable', 'ip'],
        ]);

        $settings = AttendanceSetting::query()->first();

        if (! $settings) {
            return;
        }

        $settings->update([
            'office_start' => $this->office_start.':00',
            'office_end' => $this->office_end.':00',
            'required_hours' => $this->required_hours,
            'lunch_start' => $this->lunch_start !== '' ? $this->lunch_start.':00' : null,
            'lunch_end' => $this->lunch_end !== '' ? $this->lunch_end.':00' : null,
            'break_minutes' => (int) $this->break_minutes,
            'office_ip_1' => $this->office_ip_1 !== '' ? $this->office_ip_1 : null,
            'office_ip_2' => $this->office_ip_2 !== '' ? $this->office_ip_2 : null,
            'office_ip_3' => $this->office_ip_3 !== '' ? $this->office_ip_3 : null,
        ]);

        $this->successMessage = 'Office settings saved.';
    }
}