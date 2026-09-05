<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPermission extends Model
{
    protected $fillable = [
        'user_id',
        'module_key',
        'feature_key',
        'access',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
