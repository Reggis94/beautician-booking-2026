<?php

namespace App\Services\UI\Http\Controller\ProPresentation\Service;

use App\Services\Application\Service\Query\ListProPresentationServiceForCategoryQuery as Query;
use App\Services\Application\Service\QueryHandler\ListProPresentationServiceForCategoryQueryHandler as Handler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

/**
 * GET /api/pro-presentation/service/list?id={categoryId}&proId={proId}
 *
 * Public list of the published, not deleted services of a category of the presented professional, in French
 * alphabetical order. The category and the services must belong to that professional.
 *
 * Query parameters:
 * - id: required category id.
 * - proId: required id of the presented professional.
 *
 * Successful response: list<{ id, name, description, price_cents, duration_min, images: [{ url }] }>
 */
#[Route(
    'api/pro-presentation/service/list',
    name: 'service_list_per_category',
    methods: ['GET']
)]
final class ListProPresentationServiceForCategory extends AbstractController
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
                        'proId' => $query->proId,
                    ]),
                ];
            }

            $service['images'] = $images;
            $services[] = $service;
        }

        return new JsonResponse($services);
    }
}
