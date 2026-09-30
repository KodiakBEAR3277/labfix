<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * One place for the input guards on personal details, so the mobile API,
 * the web profile form, and (later) registration and admin user forms all
 * reject nonsense the same way.
 */
class ProfileRules
{
    // Starts with a letter (any language), then letters, spaces, periods,
    // apostrophes (straight or curly) and hyphens. No digits, emoji or symbols.
    private const NAME_PATTERN = '/^\p{L}[\p{L} .\'’-]*$/u';

    // Four or more of the same character in a row ("aaaa", "----") is not a name.
    private const REPEAT_PATTERN = '/(.)\1{3,}/u';

    // Optional leading +, then digits with spaces, hyphens or brackets. 7 to 20 chars.
    private const PHONE_PATTERN = '/^\+?[0-9][0-9\s\-()]{6,19}$/';

    // Letters, digits, hyphens and slashes only, e.g. 2021-001 or SNSU/21-1234.
    private const ID_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9\-\/]{2,49}$/';

    /**
     * Trim and collapse repeated whitespace before validation runs, so
     * "  Juan    dela   Cruz " is stored as "Juan dela Cruz".
     */
    public static function normalize(Request $request, array $fields = ['first_name', 'last_name']): void
    {
        foreach ($fields as $field) {
            if ($request->has($field) && is_string($request->input($field))) {
                $request->merge([
                    $field => preg_replace('/\s+/u', ' ', trim($request->input($field))),
                ]);
            }
        }
    }

    public static function name(): array
    {
        return ['required', 'string', 'min:2', 'max:50', 'regex:' . self::NAME_PATTERN, 'not_regex:' . self::REPEAT_PATTERN];
    }

    public static function phone(): array
    {
        return ['nullable', 'string', 'max:20', 'regex:' . self::PHONE_PATTERN];
    }

    public static function studentId(): array
    {
        return ['nullable', 'string', 'max:50', 'regex:' . self::ID_PATTERN];
    }

    public static function messages(): array
    {
        return [
            'first_name.regex'     => 'First name can only contain letters, spaces, periods, apostrophes and hyphens.',
            'last_name.regex'      => 'Last name can only contain letters, spaces, periods, apostrophes and hyphens.',
            'first_name.not_regex' => 'First name does not look like a real name.',
            'last_name.not_regex'  => 'Last name does not look like a real name.',
            'first_name.min'       => 'First name must be at least 2 characters.',
            'last_name.min'        => 'Last name must be at least 2 characters.',
            'phone.regex'          => 'Enter a valid phone number, e.g. 0912 345 6789 or +63 912 345 6789.',
            'student_staff_id.regex' => 'ID can only contain letters, numbers, hyphens and slashes (3 to 50 characters).',
        ];
    }
}