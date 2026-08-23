<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use App\Traits\BelongsToInstitution;

class Setting extends Model
{
    use BelongsToInstitution;

    protected $fillable = ['institution_id', 'key', 'value', 'type', 'group', 'description'];

    // Auto-cast values based on type
    public function getValueAttribute($value)
    {
        return match($this->type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    // Helper method to get a setting value — cache key is institution-aware
    // now, since one institution's cached value must never leak into
    // another's request for the same key.
    public static function get($key, $default = null)
    {
        $institutionId = static::currentInstitutionId() ?? 'global';

        return Cache::remember("setting_{$institutionId}_{$key}", 3600, function() use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    // Helper method to set a setting value
    public static function set($key, $value, $type = 'string', $group = 'general')
    {
        $institutionId = static::currentInstitutionId() ?? 'global';

        $setting = self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type, 'group' => $group]
        );

        Cache::forget("setting_{$institutionId}_{$key}");
        return $setting;
    }

    /**
     * Seed a fresh institution with the standard set of default settings.
     * Called once, right after a new institution is created during
     * registration — without this, its Settings page has nothing to
     * display or save against.
     */
    public static function seedDefaultsFor(int $institutionId): void
    {
        $defaults = [
            ['key' => 'system_name',                  'value' => 'LabFix',   'type' => 'string',  'group' => 'general'],
            ['key' => 'support_email',                 'value' => '',         'type' => 'string',  'group' => 'general'],
            ['key' => 'support_phone',                 'value' => '',         'type' => 'string',  'group' => 'general'],
            ['key' => 'notify_user_on_assignment',      'value' => '1',        'type' => 'boolean', 'group' => 'notifications'],
            ['key' => 'notify_user_on_status_change',   'value' => '1',        'type' => 'boolean', 'group' => 'notifications'],
            ['key' => 'notify_user_on_resolution',      'value' => '1',        'type' => 'boolean', 'group' => 'notifications'],
            ['key' => 'notify_it_on_new_ticket',        'value' => '1',        'type' => 'boolean', 'group' => 'notifications'],
            ['key' => 'notify_it_on_high_priority',     'value' => '1',        'type' => 'boolean', 'group' => 'notifications'],
            ['key' => 'ticket_number_format',           'value' => 'format_1', 'type' => 'string',  'group' => 'tickets'],
            ['key' => 'default_priority',               'value' => 'medium',   'type' => 'string',  'group' => 'tickets'],
            ['key' => 'auto_close_resolved_after_days', 'value' => '7',        'type' => 'integer', 'group' => 'tickets'],
            ['key' => 'auto_escalate_after_hours',      'value' => '24',       'type' => 'integer', 'group' => 'tickets'],
            ['key' => 'allow_attachments',              'value' => '1',        'type' => 'boolean', 'group' => 'tickets'],
            ['key' => 'maintenance_mode',                'value' => '0',        'type' => 'boolean', 'group' => 'maintenance'],
            ['key' => 'maintenance_message',             'value' => '',         'type' => 'string',  'group' => 'maintenance'],
        ];

        foreach ($defaults as $default) {
            static::create(array_merge($default, ['institution_id' => $institutionId]));
        }
    }
}