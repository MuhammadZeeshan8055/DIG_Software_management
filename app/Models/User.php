<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_manager',
        'monthly_salary',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'monthly_salary' => 'decimal:2',
            'is_manager' => 'boolean',
        ];
    }

    public function ticketImports(): HasMany
    {
        return $this->hasMany(TicketImport::class);
    }

    public function desks(): BelongsToMany
    {
        return $this->belongsToMany(Desk::class)->withTimestamps();
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(UserPermission::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class)->latest();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' || $this->isSuperAdmin();
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    /** Staff marked as Manager — CRO can send visitors to them. */
    public function isManager(): bool
    {
        return $this->isStaff() && (bool) $this->is_manager;
    }

    /** Staff linked to the CRO desk (desk key = cro). */
    public function isCro(): bool
    {
        if (! $this->isStaff()) {
            return false;
        }

        return $this->desks()->where('desks.key', 'cro')->exists();
    }

    /**
     * Can open / read this feature screen.
     * Admin / super_admin: always yes. Staff: needs a permission row.
     */
    public function canView(string $module, string $feature): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->permissions()
            ->where('module_key', $module)
            ->where('feature_key', $feature)
            ->exists();
    }

    /**
     * Can create / update / delete on this feature.
     * Admin / super_admin: always yes. Staff: needs access = manage.
     */
    public function canManage(string $module, string $feature): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->permissions()
            ->where('module_key', $module)
            ->where('feature_key', $feature)
            ->where('access', 'manage')
            ->exists();
    }

    /**
     * Admins and super admins can open User Management.
     */
    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }
}
