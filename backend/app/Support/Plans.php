<?php

namespace App\Support;

/**
 * Single source of truth for what each plan allows. This is a plain class
 * rather than a config file on purpose: it is autoloaded like any other
 * code, so it cannot go missing from config/ or be hidden by a stale
 * config cache. Keep the numbers in sync with Pricing.vue, which only
 * describes them. The values here are the ones that get enforced.
 *
 * A null max means unlimited. auto_rules gates the auto-close and
 * auto-escalate commands. knowledge_base is reserved for gating article
 * authoring later.
 */
class Plans
{
    public const ALL = [
        'free' => [
            'label'          => 'Free',
            'max_labs'       => 1,
            'max_equipment'  => 30,
            'max_users'      => 50,
            'auto_rules'     => false,
            'knowledge_base' => false,
        ],
        'standard' => [
            'label'          => 'Standard',
            'max_labs'       => 5,
            'max_equipment'  => 300,
            'max_users'      => 300,
            'auto_rules'     => true,
            'knowledge_base' => true,
        ],
        'pro' => [
            'label'          => 'Pro',
            'max_labs'       => null,
            'max_equipment'  => null,
            'max_users'      => null,
            'auto_rules'     => true,
            'knowledge_base' => true,
        ],
        'enterprise' => [
            'label'          => 'Enterprise',
            'max_labs'       => null,
            'max_equipment'  => null,
            'max_users'      => null,
            'auto_rules'     => true,
            'knowledge_base' => true,
        ],
    ];

    public static function all(): array
    {
        return self::ALL;
    }

    // Unknown or empty slugs resolve to Free, the safest default.
    public static function get(?string $slug): array
    {
        return self::ALL[$slug ?: 'free'] ?? self::ALL['free'];
    }
}