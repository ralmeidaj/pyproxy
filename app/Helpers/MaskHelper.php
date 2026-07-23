<?php

namespace App\Helpers;

class MaskHelper
{
    public static function cpf(string $value): string
    {
        $clean = preg_replace('/\D/', '', $value);

        if (strlen($clean) === 11) {
            return '***.' . substr($clean, 3, 3) . '.' . substr($clean, 6, 3) . '-**';
        }

        if (strlen($clean) === 14) {
            return '**.' . substr($clean, 2, 3) . '.' . substr($clean, 5, 3) . '/' . substr($clean, 8, 4) . '-**';
        }

        return str_repeat('*', strlen($value));
    }

    public static function email(string $value): string
    {
        $parts = explode('@', $value, 2);
        if (count($parts) !== 2) {
            return str_repeat('*', strlen($value));
        }

        [$user, $domain] = $parts;
        $visible = strlen($user) >= 1 ? substr($user, 0, 1) : '';

        return $visible . str_repeat('*', max(3, strlen($user) - 1)) . '@' . $domain;
    }

    public static function phone(string $value): string
    {
        $clean = preg_replace('/\D/', '', $value);

        if (strlen($clean) === 11) {
            return '(' . substr($clean, 0, 2) . ') *****-' . substr($clean, 7);
        }

        if (strlen($clean) === 10) {
            return '(' . substr($clean, 0, 2) . ') ****-' . substr($clean, 6);
        }

        return str_repeat('*', strlen($value));
    }
}
