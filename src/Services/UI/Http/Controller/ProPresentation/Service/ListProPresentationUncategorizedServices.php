<?php

namespace App\Services\UI\Http\Controller\ProPresentation\Service;

use App\Services\Application\Service\Query\ListProPresentationUncategorizedServicesQuery as Query;
use App\Services\Application\Service\QueryHandler\ListProPresentationUncategorizedServicesQueryHandler as Handler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

/**
 * GET /api/pro-presentation/service/uncategorized/list?id={proId}
 *
 * Public list of the presented professional's published, not deleted services without a category, in French
 * alphabetical order. The public page shows them after every category.
 *
 * Query parameters:
 * - id: required id of the presented professional.
 *
 * Successful response: list<{ id, name, description, price_cents, duration_min, images: [{ url }] }>
 */
#[Route(
    'api/pro-presentation/service/uncategorized/list',
    name: 'service_list_uncategorized',
    methods: ['GET']
)]
final class ListProPresentationUncategorizedServices extends AbstractController
{
    public function __invoke(
        #[MapQueryString] Query $query,
        Handler $handler
    ): JsonResponse {
        $services = [];
        foreach ($handler($query) as $service) {
            $images = [];
            foreach ($service['images'] as $image) {
                $images[] = [
                    'url' => $this->generateUrl('service_image_file', [
                        'fileName' => $image['file_name'],
                        'proId' => $query->id,
                    ]),
                ];
            }

            $service['images'] = $images;
            $services[] = $service;
        }

        return new JsonResponse($services);
    }
}
