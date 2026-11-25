<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
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
        'is_active',
        'member_id',
        'portal_activated_at',
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
            'is_active' => 'boolean',
            'portal_activated_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /** Roles an administrator may assign from Staff & Roles (excludes the portal-only Member role). */
    public static function staffRoles(): array
    {
        return array_filter(config('rbac.roles'), fn (array $role) => ($role['staff'] ?? true) !== false);
    }

    public function isMember(): bool
    {
        return $this->hasRole('member');
    }

    /** Where this person lands after signing in. */
    public function homeRoute(): string
    {
        return $this->isMember() ? 'portal.index' : 'dashboard';
    }

    // ---- Role-based access control ----

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $granted = config("rbac.roles.{$this->role}.permissions", []);

        return in_array('*', $granted, true) || in_array($permission, $granted, true);
    }

    public function roleLabel(): string
    {
        return config("rbac.roles.{$this->role}.label", ucfirst((string) $this->role));
    }
}
