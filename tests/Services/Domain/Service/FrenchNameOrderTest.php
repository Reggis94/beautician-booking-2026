<?php

namespace App\Tests\Services\Domain\Service;

use App\Services\Domain\Service\FrenchNameOrder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * French alphabetical order of service and category names: accents and letter case are ignored first, then
 * the exact names break ties, then the ids.
 */
final class FrenchNameOrderTest extends TestCase
{
    #[Test]
    public function it_compares_french_names_and_uses_ids_as_tie_breaker(): void
    {
        // Each pair is listed in ascending order: [first name, first id, second name, second id].
        $ascendingPairs = [
            'alphabetical order' => ['Bras', 1, 'Jambes', 2],
            'alphabetical order whatever the ids' => ['Bras', 9, 'Jambes', 1],
            'accented first letter sorts with its base letter' => ['Dos', 1, 'École', 2],
            'accented names among themselves' => ['École', 1, 'Épilation', 2],
            'accented names after the accent' => ['Épilation', 1, 'Étude', 2],
            'accented name before a later base letter' => ['Étude', 1, 'Fard', 2],
            'letter case is ignored first' => ['aisselles', 1, 'Bras', 2],
            'ligature sorts as its letters' => ['Nettoyage', 1, 'Œil', 2],
            'numbers sort naturally' => ['Forfait 2', 1, 'Forfait 10', 2],
            'exact name breaks a case tie' => ['Soin', 1, 'soin', 2],
            'exact name breaks an accent tie' => ['Epilation', 9, 'Épilation', 1],
            'identical names are ordered by id' => ['Soirée', 1, 'Soirée', 2],
        ];

        foreach ($ascendingPairs as $case => [$firstName, $firstId, $secondName, $secondId]) {
            self::assertLessThan(
                0,
                FrenchNameOrder::compareWithId($firstName, $firstId, $secondName, $secondId),
                $case
            );
            self::assertGreaterThan(
                0,
                FrenchNameOrder::compareWithId($secondName, $secondId, $firstName, $firstId),
                $case . ' (reversed)'
            );
        }

        self::assertSame(0, FrenchNameOrder::compareWithId('Soirée', 7, 'Soirée', 7));

        // Sorting gives the same order whatever the input order.
        $expected = [
            ['aisselles', 4],
            ['Bras', 2],
            ['École', 8],
            ['Épilation', 1],
            ['Épilation', 6],
            ['Étude', 3],
            ['soin', 5],
        ];
        foreach ([array_reverse($expected), [$expected[3], $expected[6], $expected[0], $expected[5], $expected[1],
            $expected[4], $expected[2]]] as $input) {
            usort(
                $input,
                static fn (array $first, array $second): int => FrenchNameOrder::compareWithId(
                    $first[0],
                    $first[1],
                    $second[0],
                    $second[1]
                )
            );
            self::assertSame($expected, $input);
        }
    }
}
