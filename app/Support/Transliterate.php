<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Converts Cyrillic (Ukrainian / Russian) names coming from Telegram into a
 * clean latin, lowercase, hyphenated slug — e.g. "Олексій Мироненко" →
 * "oleksii-myronenko". Used for readable participant profile URLs.
 */
class Transliterate
{
    /** @var array<string, string> */
    private const MAP = [
        // Ukrainian / Russian lowercase
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'h', 'ґ' => 'g', 'д' => 'd',
        'е' => 'e', 'є' => 'ie', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'y',
        'і' => 'i', 'ї' => 'i', 'й' => 'i', 'к' => 'k', 'л' => 'l', 'м' => 'm',
        'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
        'у' => 'u', 'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh',
        'щ' => 'shch', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'iu',
        'я' => 'ia', 'ʼ' => '', '\'' => '', '`' => '',
    ];

    public static function toLatin(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');

        $result = '';
        $length = mb_strlen($value, 'UTF-8');

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($value, $i, 1, 'UTF-8');
            $result .= self::MAP[$char] ?? $char;
        }

        return $result;
    }

    public static function slug(string $value): string
    {
        $slug = Str::slug(self::toLatin($value));

        return $slug !== '' ? $slug : 'user';
    }
}
