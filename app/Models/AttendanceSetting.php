<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSetting extends Model
{
    protected $fillable = [
        'office_start',
        'office_end',
        'required_hours',
        'lunch_start',
        'lunch_end',
        'break_minutes',
        'office_ip_1',
        'office_ip_2',
        'office_ip_3',
    ];
}
