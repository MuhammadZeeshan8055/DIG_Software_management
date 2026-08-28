<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketImport extends Model
{
    protected $fillable = [
        'user_id',
        'original_filename',
        'pdf_path',
        'raw_pdf_text',
        'agency_name',
        'agency_phone',
        'booking_reference',
        'agency_pnr',
        'passenger_name',
        'frequent_flyer',
        'ticket_number',
        'flight_segments',
        'status',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'flight_segments' => 'array',
            'confirmed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
