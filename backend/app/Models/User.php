<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\BelongsToInstitution;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, BelongsToInstitution;

    protected $fillable = [
        'institution_id',
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'is_active',
        'is_superadmin',
        'email_notifications',
        'can_submit_tickets',
        'phone',
        'student_staff_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_superadmin' => 'boolean',
            'email_notifications' => 'boolean',
            'can_submit_tickets' => 'boolean',
        ];
    }

    // Accessor for full name
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Accessor for initials (for avatar)
    public function getInitialsAttribute(): string
    {
        return strtoupper(substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1));
    }

    // Check if user is admin
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // Check if user is IT support
    public function isITSupport(): bool
    {
        return $this->role === 'it-support';
    }

    // Check if user is staff
    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    // Check if user is student
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    // Platform-level oversight account, not tied to any one institution.
    // Not wired into scoping behavior yet — see BelongsToInstitution.
    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_superadmin;
    }
}