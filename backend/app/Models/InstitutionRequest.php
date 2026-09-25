<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class InstitutionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_name',
        'institution_contact_email',
        'plan',
        'first_name',
        'last_name',
        'email',
        'password',
        'status',
        'reviewed_at',
        'reviewed_by',
        'rejection_reason',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    // Hidden by default, same reasoning as User::$hidden — this is already
    // a hash by the time it lands here, not plaintext, but there's no
    // reason for it to ever appear in an Inertia prop or JSON response.
    protected $hidden = [
        'password',
    ];

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Approve this request — creates the real institution, seeds its
     * default settings, and creates its first admin account. This is the
     * only place a live institution gets created from a public sign-up;
     * AuthController::register() only ever files a request, never creates
     * one directly. All three steps happen in one transaction so nothing
     * is left half-created if any step fails.
     *
     * $this->password is passed straight through to User::create() —
     * it's already a bcrypt hash from Hash::make() at submission time,
     * and User's 'hashed' cast checks Hash::isHashed() before hashing,
     * so an already-hashed value passes through unchanged rather than
     * getting hashed a second time.
     */
    public function approve(User $reviewer): User
    {
        return DB::transaction(function () use ($reviewer) {
            $institution = Institution::create([
                'name'          => $this->institution_name,
                'contact_email' => $this->institution_contact_email,
                'plan'          => $this->plan,
            ]);

            Setting::seedDefaultsFor($institution->id);

            $user = User::create([
                'institution_id' => $institution->id,
                'first_name'     => $this->first_name,
                'last_name'      => $this->last_name,
                'email'          => $this->email,
                'role'           => 'admin',
                'password'       => $this->password,
            ]);

            $this->update([
                'status'      => 'approved',
                'reviewed_at' => now(),
                'reviewed_by' => $reviewer->id,
            ]);

            return $user;
        });
    }

    public function reject(User $reviewer, ?string $reason = null): void
    {
        $this->update([
            'status'           => 'rejected',
            'reviewed_at'      => now(),
            'reviewed_by'      => $reviewer->id,
            'rejection_reason' => $reason,
        ]);
    }
}