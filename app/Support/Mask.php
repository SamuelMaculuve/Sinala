<?php

namespace App\Support;

/** Hides personal data in on-screen tables. Full values stay available on the edit page. */
class Mask
{
    public static function phone(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $value);
        if (strlen($digits) < 5) {
            return str_repeat('•', strlen($digits));
        }

        return substr($digits, 0, 2).str_repeat('•', strlen($digits) - 4).substr($digits, -2);
    }

    public static function email(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || ! str_contains($value, '@')) {
            return $value === '' ? null : self::text($value);
        }
        [$name, $domain] = explode('@', $value, 2);

        return mb_substr($name, 0, 2).'•••@'.$domain;
    }

    public static function text(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $length = mb_strlen($value);

        return $length <= 3 ? str_repeat('•', $length) : mb_substr($value, 0, 2).str_repeat('•', $length - 3).mb_substr($value, -1);
    }

    public static function contact(?string $phone, ?string $email = null): ?string
    {
        return self::phone($phone) ?? self::email($email);
    }
}
