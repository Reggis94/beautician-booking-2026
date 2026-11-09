<?php

namespace App\Services\UI\Http\Controller\Pro\Service;

use App\ProAuth\Public\AuthenticatedPro;
use App\Services\Application\Service\Query\GetProAdminServiceCatalogQuery;
use App\Services\Application\Service\QueryHandler\GetProAdminServiceCatalogQueryHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * GET /api/pro/service/catalog
 *
 * The authenticated professional's services and categories for the Prestations page (session cookie
 * required, HTTP 401 otherwise). Deleted and replaced versions are not listed.
 *
 * Response:
 * {
 *   "categories": [{
 *     "id": int, "name": string,
 *     "is_active": bool (at least one published service),
 *     "appointment_count": int (appointments of any state referencing this category version),
 *     "services": [Service]
 *   }],
 *   "uncategorized": [Service]
 * }
 * Service: { "id", "name", "description", "duration_min", "price_cents", "category_id", "published",
 *            "appointment_count", "images": [{ "id": int, "url": string }] }
 *
 * Order: active categories then inactive ones, alphabetically (French); in each list, published services
 * then drafts, alphabetically.
 */
#[Route('/api/pro/service/catalog', name: 'api_pro_get_service_catalog', methods: ['GET'])]
final class GetProAdminServiceCatalogController extends AbstractController
{
    public function __invoke(
        #[CurrentUser] AuthenticatedPro $authenticatedPro,
        GetProAdminServiceCatalogQueryHandler $handler
    ): JsonResponse {
        $proId = $authenticatedPro->getId();
        $catalog = $handler(new GetProAdminServiceCatalogQuery($proId));

        $categories = [];
        foreach ($catalog['categories'] as $category) {
            $category['services'] = $this->withImageUrls($category['services'], $proId);
            $categories[] = $category;
        }

        $response = new JsonResponse([
            'categories' => $categories,
            'uncategorized' => $this->withImageUrls($catalog['uncategorized'], $proId),
        ]);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }

    /**
     * @param list<array<string, mixed>> $services
     *
     * @return list<array<string, mixed>>
     */
    private function withImageUrls(array $services, int $proId): array
    {
        $result = [];
        foreach ($services as $service) {
            $images = [];
            foreach ($service['images'] as $image) {
                $images[] = [
                    'id' => $image['id'],
                    'url' => $this->generateUrl('service_image_file', [
                        'fileName' => $image['file_name'],
                        'proId' => $proId,
                    ]),
                ];
            }

            $service['images'] = $images;
            $result[] = $service;
        }

        return $result;
    }
}
