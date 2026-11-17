<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DiklatContact
{
    public const KEY = 'diklat_admin_whatsapp';

    public static function number(): ?string
    {
        return DB::table('settings')->where('key', self::KEY)->value('value');
    }

    public static function normalize(string $number): string
    {
        $number = preg_replace('/[\s()\-]/', '', $number);
        if (str_starts_with($number, '+62')) {
            return substr($number, 1);
        }
        if (str_starts_with($number, '0')) {
            return '62'.substr($number, 1);
        }

        return $number;
    }

    public static function url(): ?string
    {
        $number = self::number();

        return $number && preg_match('/^628[1-9][0-9]{7,11}$/', $number)
            ? 'https://wa.me/'.$number : null;
    }
}
