<?php

namespace App\Tests\Services\Application\Service\QueryHandler;

use App\Services\Application\Service\Query\GetProAdminServiceCatalogQuery;
use App\Services\Application\Service\QueryHandler\GetProAdminServiceCatalogQueryHandler;
use App\Services\Infrastructure\Repository\ServiceCategoryRepository;
use App\Services\Infrastructure\Repository\ServiceRepository;
use App\Tests\DatabaseTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * The ProAdmin Prestations catalog of a professional, built from the real repositories on the test database:
 * which categories and services are listed, how they are grouped, their state and their order.
 *
 * Rows are inserted in an order that differs from the expected order, so the assertions do not depend on
 * insertion order or on generated id values.
 */
final class GetProAdminServiceCatalogQueryHandlerTest extends DatabaseTestCase
{
    private const CATEGORY_KEYS = ['appointment_count', 'id', 'is_active', 'name', 'services'];
    private const SERVICE_KEYS = [
        'appointment_count',
        'category_id',
        'description',
        'duration_min',
        'id',
        'images',
        'name',
        'price_cents',
        'published',
    ];

    private GetProAdminServiceCatalogQueryHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new GetProAdminServiceCatalogQueryHandler(
            new ServiceRepository($this->connection),
            new ServiceCategoryRepository($this->connection)
        );
    }

    #[Test]
    public function it_builds_catalog_with_correct_grouping_sorting_and_states(): void
    {
        $proId = $this->insertPro('jane.doe@example.com', 'jane-doe');
        $otherProId = $this->insertPro('other.pro@example.com', 'other-pro');

        // Not deleted categories, inserted in reverse of their expected order.
        $soinsDuVisage = $this->insertCategory($proId, 'Soins du visage');
        $ongles = $this->insertCategory($proId, 'Ongles');
        $maquillage = $this->insertCategory($proId, 'Maquillage');
        $coiffure = $this->insertCategory($proId, 'Coiffure');
        $bienEtre = $this->insertCategory($proId, 'Bien-être');
        $epilation = $this->insertCategory($proId, 'Épilation');

        // Deleted category: deleting a category without keeping its services deletes them too.
        $massages = $this->insertCategory($proId, 'Massages', deleted: true);
        $this->insertService($proId, $massages, 'Californien', published: true, deleted: true);
        $this->insertService($proId, $massages, 'Abhyanga', published: false, deleted: true);

        // Active category with published services only.
        $nettoyage = $this->insertService($proId, $soinsDuVisage, 'Nettoyage', published: true);
        $hydratation = $this->insertService($proId, $soinsDuVisage, 'Hydratation', published: true);

        // Active category with published, draft and deleted services.
        $sourcils = $this->insertService($proId, $epilation, 'Sourcils', published: true);
        $epaules = $this->insertService($proId, $epilation, 'Épaules', published: false);
        $jambes = $this->insertService($proId, $epilation, 'Jambes', published: true);
        $bras = $this->insertService($proId, $epilation, 'Bras', published: false);
        $aisselles = $this->insertService($proId, $epilation, 'aisselles', published: false);
        $this->insertService($proId, $epilation, 'Maillot', published: true, deleted: true);
        $this->insertService($proId, $epilation, 'Dos', published: false, deleted: true);
        $this->insertAppointment($proId, $jambes);

        // Active category with identically named services: their ids break the tie.
        $soireeFirst = $this->insertService($proId, $maquillage, 'Soirée', published: true);
        $cours = $this->insertService($proId, $maquillage, 'Cours', published: false);
        $soireeSecond = $this->insertService($proId, $maquillage, 'Soirée', published: true);
        [$soireeLowerId, $soireeHigherId] = [min($soireeFirst, $soireeSecond), max($soireeFirst, $soireeSecond)];

        // Inactive category with drafts only.
        $vernis = $this->insertService($proId, $ongles, 'Vernis', published: false);
        $pose = $this->insertService($proId, $ongles, 'Pose', published: false);

        // Inactive category whose only service is deleted; its appointment still counts for the category.
        $coupe = $this->insertService($proId, $coiffure, 'Coupe', published: true, deleted: true);
        $this->insertAppointment($proId, $coupe);

        // Uncategorized services.
        $zen = $this->insertService($proId, null, 'Zen', published: true);
        $the = $this->insertService($proId, null, 'Thé', published: false);
        $accueil = $this->insertService($proId, null, 'Accueil', published: true);
        $bain = $this->insertService($proId, null, 'Bain', published: false);
        $this->insertService($proId, null, 'Ancien', published: true, deleted: true);
        $this->insertService($proId, null, 'Brouillon', published: false, deleted: true);

        // Another professional's catalog, with names that would sort first.
        $otherCategory = $this->insertCategory($otherProId, 'Aaa');
        $this->insertService($otherProId, $otherCategory, 'Aaa', published: true);
        $this->insertService($otherProId, null, 'Aaa', published: true);
        $this->insertService($otherProId, null, 'Aab', published: false);

        $catalog = ($this->handler)(new GetProAdminServiceCatalogQuery($proId));

        self::assertSame(['categories', 'uncategorized'], array_keys($catalog));
        self::assertSame(
            [
                // Active categories, in French order (« Épilation » sorts with « epilation »).
                ['id' => $epilation, 'name' => 'Épilation', 'is_active' => true, 'appointment_count' => 1,
                    'services' => [$jambes, $sourcils, $aisselles, $bras, $epaules]],
                ['id' => $maquillage, 'name' => 'Maquillage', 'is_active' => true, 'appointment_count' => 0,
                    'services' => [$soireeLowerId, $soireeHigherId, $cours]],
                ['id' => $soinsDuVisage, 'name' => 'Soins du visage', 'is_active' => true,
                    'appointment_count' => 0, 'services' => [$hydratation, $nettoyage]],
                // Inactive categories: no services, only a deleted service, only drafts.
                ['id' => $bienEtre, 'name' => 'Bien-être', 'is_active' => false, 'appointment_count' => 0,
                    'services' => []],
                ['id' => $coiffure, 'name' => 'Coiffure', 'is_active' => false, 'appointment_count' => 1,
                    'services' => []],
                ['id' => $ongles, 'name' => 'Ongles', 'is_active' => false, 'appointment_count' => 0,
                    'services' => [$pose, $vernis]],
            ],
            self::summarizeCategories($catalog['categories'])
        );
        self::assertSame([$accueil, $zen, $bain, $the], self::ids($catalog['uncategorized']));

        $listedServiceIds = self::ids($catalog['uncategorized']);
        foreach ($catalog['categories'] as $category) {
            self::assertSame(self::CATEGORY_KEYS, self::sortedKeys($category));

            foreach ($category['services'] as $service) {
                self::assertSame(self::SERVICE_KEYS, self::sortedKeys($service));
                self::assertSame($category['id'], $service['category_id']);
                $listedServiceIds[] = $service['id'];
            }
        }
        foreach ($catalog['uncategorized'] as $service) {
            self::assertSame(self::SERVICE_KEYS, self::sortedKeys($service));
            self::assertNull($service['category_id']);
        }
        self::assertSame($listedServiceIds, array_values(array_unique($listedServiceIds)));

        $jambesRow = $catalog['categories'][0]['services'][0];
        self::assertTrue($jambesRow['published']);
        self::assertSame(1, $jambesRow['appointment_count']);
        self::assertFalse($catalog['uncategorized'][2]['published']);
    }

    #[Test]
    public function it_returns_empty_catalog_when_no_live_data_exists(): void
    {
        $proId = $this->insertPro('jane.doe@example.com', 'jane-doe');
        $otherProId = $this->insertPro('other.pro@example.com', 'other-pro');

        $deletedCategory = $this->insertCategory($proId, 'Massages', deleted: true);
        $this->insertService($proId, $deletedCategory, 'Californien', published: true, deleted: true);
        $this->insertService($proId, $deletedCategory, 'Abhyanga', published: false, deleted: true);
        $this->insertService($proId, null, 'Ancien', published: true, deleted: true);
        $this->insertService($proId, null, 'Brouillon', published: false, deleted: true);

        // A not deleted draft of another professional is still visible to that professional only.
        $otherCategory = $this->insertCategory($otherProId, 'Soins du visage');
        $this->insertService($otherProId, $otherCategory, 'Nettoyage', published: false);
        $this->insertService($otherProId, null, 'Accueil', published: true);
        $otherCatalog = ($this->handler)(new GetProAdminServiceCatalogQuery($otherProId));
        self::assertCount(1, $otherCatalog['categories']);
        self::assertCount(1, $otherCatalog['categories'][0]['services']);
        self::assertCount(1, $otherCatalog['uncategorized']);

        self::assertSame(
            ['categories' => [], 'uncategorized' => []],
            ($this->handler)(new GetProAdminServiceCatalogQuery($proId))
        );
    }

    /**
     * @param list<array<string, mixed>> $categories
     *
     * @return list<array{id: int, name: string, is_active: bool, appointment_count: int, services: list<int>}>
     */
    private static function summarizeCategories(array $categories): array
    {
        $summary = [];
        foreach ($categories as $category) {
            $summary[] = [
                'id' => $category['id'],
                'name' => $category['name'],
                'is_active' => $category['is_active'],
                'appointment_count' => $category['appointment_count'],
                'services' => self::ids($category['services']),
            ];
        }

        return $summary;
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<int>
     */
    private static function ids(array $rows): array
    {
        return array_map(static fn (array $row): int => $row['id'], $rows);
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return list<string>
     */
    private static function sortedKeys(array $row): array
    {
        $keys = array_keys($row);
        sort($keys);

        return $keys;
    }

    private function insertPro(string $email, string $linkSlug): int
    {
        return (int) $this->connection->fetchOne(
            'INSERT INTO pro (email, link_slug) VALUES (:email, :link_slug) RETURNING id',
            ['email' => $email, 'link_slug' => $linkSlug]
        );
    }

    private function insertCategory(int $proId, string $name, bool $deleted = false): int
    {
        return (int) $this->connection->fetchOne(
            "INSERT INTO service_category (pro_id, name, deleted_at, deletion_reason)
             VALUES (
                 :pro_id,
                 :name,
                 CASE WHEN :deleted THEN NOW() END,
                 CASE WHEN :deleted THEN 'deleted' END
             )
             RETURNING id",
            ['pro_id' => $proId, 'name' => $name, 'deleted' => $deleted ? 'true' : 'false']
        );
    }

    /**
     * A draft has no publication date; a deleted service is explicitly deleted, never replaced.
     */
    private function insertService(
        int $proId,
        ?int $categoryId,
        string $name,
        bool $published,
        bool $deleted = false
    ): int {
        return (int) $this->connection->fetchOne(
            "INSERT INTO service (pro_id, category_id, name, duration_min, price_cents, published_at, deleted_at,
                                  deletion_reason)
             VALUES (
                 :pro_id,
                 :category_id,
                 :name,
                 30,
                 2500,
                 CASE WHEN :published THEN NOW() END,
                 CASE WHEN :deleted THEN NOW() END,
                 CASE WHEN :deleted THEN 'deleted' END
             )
             RETURNING id",
            [
                'pro_id' => $proId,
                'category_id' => $categoryId,
                'name' => $name,
                'published' => $published ? 'true' : 'false',
                'deleted' => $deleted ? 'true' : 'false',
            ]
        );
    }

    private function insertAppointment(int $proId, int $serviceId): void
    {
        $this->connection->executeStatement(
            "INSERT INTO appointment (pro_id, service_id, start_dt, end_dt)
             VALUES (:pro_id, :service_id, '2099-06-01 09:00:00', '2099-06-01 09:30:00')",
            ['pro_id' => $proId, 'service_id' => $serviceId]
        );
    }
}
