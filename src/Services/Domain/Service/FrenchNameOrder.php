<?php

namespace App\Services\Domain\Service;

/**
 * Deterministic French alphabetical order for service and category names.
 *
 * Accents, ligatures and letter case are ignored first (« Épilation » sorts with « epilation »), numbers
 * sort naturally, then the exact names break ties. compareWithId() adds the id as the last tie-breaker.
 * Implemented without the intl extension, which is not installed.
 */
final class FrenchNameOrder
{
    private const FOLDED_CHARACTERS = [
        'à' => 'a',
        'â' => 'a',
        'ä' => 'a',
        'á' => 'a',
        'ã' => 'a',
        'å' => 'a',
        'æ' => 'ae',
        'ç' => 'c',
        'é' => 'e',
        'è' => 'e',
        'ê' => 'e',
        'ë' => 'e',
        'î' => 'i',
        'ï' => 'i',
        'í' => 'i',
        'ì' => 'i',
        'ñ' => 'n',
        'ô' => 'o',
        'ö' => 'o',
        'ó' => 'o',
        'ò' => 'o',
        'õ' => 'o',
        'œ' => 'oe',
        'ù' => 'u',
        'û' => 'u',
        'ü' => 'u',
        'ú' => 'u',
        'ÿ' => 'y',
        "\u{2019}" => "'",
    ];

    public static function compare(string $first, string $second): int
    {
        $result = strnatcmp(self::foldedKey($first), self::foldedKey($second));
        if ($result !== 0) {
            return $result;
        }

        return strcmp($first, $second);
    }

    public static function compareWithId(string $first, int $firstId, string $second, int $secondId): int
    {
        $result = self::compare($first, $second);
        if ($result !== 0) {
            return $result;
        }

        return $firstId <=> $secondId;
    }

    private static function foldedKey(string $name): string
    {
        return strtr(mb_strtolower(trim($name), 'UTF-8'), self::FOLDED_CHARACTERS);
    }
}
