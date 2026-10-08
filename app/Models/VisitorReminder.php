<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorReminder extends Model
{
    protected $fillable = [
        'daily_visitor_id',
        'user_id',
        'remind_on',
        'note',
        'notified_at',
        'is_done',
        'done_note',
        'done_at',
    ];

    protected function casts(): array
    {
        return [
            'remind_on' => 'date',
            'notified_at' => 'datetime',
            'is_done' => 'boolean',
            'done_at' => 'datetime',
        ];
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(DailyVisitor::class, 'daily_visitor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
