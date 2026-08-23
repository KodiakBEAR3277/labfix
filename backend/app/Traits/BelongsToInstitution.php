<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToInstitution
{
    /**
     * Prevent infinite recursion if the User model itself uses this trait.
     */
    protected static bool $isResolvingInstitution = false;

    protected static function bootBelongsToInstitution(): void
    {
        static::addGlobalScope('institution', function (Builder $builder) {
            $id = static::currentInstitutionId();

            if ($id !== null) {
                $builder->where($builder->getModel()->getTable() . '.institution_id', $id);
            }
        });

        static::creating(function ($model) {
            if (empty($model->institution_id)) {
                $model->institution_id = static::currentInstitutionId();
            }
        });
    }

    protected static function currentInstitutionId(): ?int
    {
        // 1. Check container binding (e.g., set via middleware or scoped job runner)
        if (app()->bound('currentInstitutionId')) {
            return app('currentInstitutionId');
        }

        // 2. Prevent recursive loops when resolving the authenticated user model
        if (static::$isResolvingInstitution) {
            return null;
        }

        static::$isResolvingInstitution = true;

        try {
            if (auth()->check()) {
                // Access institution_id from user without re-triggering full model queries where possible
                return auth()->user()->institution_id ?? null;
            }
        } finally {
            static::$isResolvingInstitution = false;
        }

        return null;
    }

    public function institution()
    {
        return $this->belongsTo(\App\Models\Institution::class);
    }
}