<?php

namespace App\Support;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSetting;
use App\Models\User;
use Carbon\Carbon;

/**
 * Check-in / check-out — one place for both the gate and the portal panel.
 *
 * CHECK-IN (start):
 *   1. Is IP allowed?
 *   2. Get or create today's row for this user
 *   3. If already checked in → stop
 *   4. Save check_in_at = now
 *
 * CHECK-OUT (end):
 *   1. Is IP allowed?
 *   2. Load today's row
 *   3. Must have check-in, must not already have check-out
 *   4. Save check_out_at + worked_minutes
 *
 * Every method returns: ['ok' => true/false, 'message' => '...']
 */
class AttendancePunch
{
    /** Today's date in app timezone (Pakistan), e.g. 2026-09-05 */
    public static function todayDate(): string
    {
        return Carbon::now(app_timezone())->toDateString();
    }

    /** Today's attendance row for this user, or null if none yet. */
    public static function todayRecord(User $user): ?AttendanceRecord
    {
        return AttendanceRecord::query()
            ->where('user_id', $user->id)
            ->whereDate('work_date', self::todayDate())
            ->first();
    }

    /** Has this user already checked in today? (portal gate uses this) */
    public static function hasStartedToday(User $user): bool
    {
        $record = self::todayRecord($user);

        return $record !== null && $record->check_in_at !== null;
    }

    /**
     * CHECK-IN — save start time for today.
     *
     * @return array{ok: bool, message: string, record?: AttendanceRecord}
     */
    public static function start(User $user, string $clientIp): array
    {
        // Step 1 — office IP
        $ipError = self::officeIpError($clientIp);
        if ($ipError !== null) {
            return self::fail($ipError);
        }

        // Step 2 — today's row (create empty one if missing)
        $record = AttendanceRecord::firstOrCreate(
            [
                'user_id' => $user->id,
                'work_date' => self::todayDate(),
            ],
            [
                'check_in_at' => null,
                'check_out_at' => null,
                'worked_minutes' => 0,
                'status_color' => null,
            ]
        );

        // Step 3 — already in?
        if ($record->check_in_at !== null) {
            return self::fail('You already started your shift today.');
        }

        // Step 4 — punch in
        $record->check_in_at = Carbon::now(app_timezone());
        $record->save();

        return self::ok(
            'Shift started at '.format_datetime($record->check_in_at, 'h:i A').'.',
            $record
        );
    }

    /**
     * CHECK-OUT — save end time + worked minutes for today.
     *
     * @return array{ok: bool, message: string, record?: AttendanceRecord}
     */
    public static function end(User $user, string $clientIp): array
    {
        // Step 1 — office IP
        $ipError = self::officeIpError($clientIp);
        if ($ipError !== null) {
            return self::fail($ipError);
        }

        // Step 2 — must already have a row with check-in
        $record = self::todayRecord($user);

        if (! $record || $record->check_in_at === null) {
            return self::fail('Start your shift before ending it.');
        }

        if ($record->check_out_at !== null) {
            return self::fail('You already ended your shift today.');
        }

        // Step 3 — punch out + minutes worked (whole minutes only)
        $checkOut = Carbon::now(app_timezone());
        $seconds = max(0, (int) $record->check_in_at->diffInSeconds($checkOut));
        $minutes = intdiv($seconds, 60);

        $record->check_out_at = $checkOut;
        $record->worked_minutes = $minutes;
        $record->save();

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return self::ok(
            'Shift ended at '.format_datetime($record->check_out_at, 'h:i A')
                .' · worked '.sprintf('%dh %dm', $hours, $mins).'.',
            $record
        );
    }

    /**
     * IP rule:
     * - No office IPs saved yet → allow anyone (setup mode)
     * - Otherwise client IP must match office_ip_1, _2, or _3
     *
     * Returns null if OK, or an error string if blocked.
     */
    public static function officeIpError(string $clientIp): ?string
    {
        $settings = AttendanceSetting::query()->first();

        // First time ever — create default office hours (IPs empty = open)
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

        // Plain list of filled IPs (no collection helpers)
        $allowedIps = [];
        foreach ([$settings->office_ip_1, $settings->office_ip_2, $settings->office_ip_3] as $ip) {
            $ip = trim((string) $ip);
            if ($ip !== '') {
                $allowedIps[] = $ip;
            }
        }

        if ($allowedIps === []) {
            return null; // setup mode — allow
        }

        if (! in_array($clientIp, $allowedIps, true)) {
            return 'Check-in allowed only from an office network. Your IP: '.$clientIp;
        }

        return null;
    }

    /** @return array{ok: false, message: string} */
    protected static function fail(string $message): array
    {
        return ['ok' => false, 'message' => $message];
    }

    /** @return array{ok: true, message: string, record: AttendanceRecord} */
    protected static function ok(string $message, AttendanceRecord $record): array
    {
        return ['ok' => true, 'message' => $message, 'record' => $record];
    }
}
