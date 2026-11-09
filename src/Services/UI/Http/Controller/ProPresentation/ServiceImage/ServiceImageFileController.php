<?php

namespace App\Services\UI\Http\Controller\ProPresentation\ServiceImage;

use App\Services\Application\ServiceImage\Query\GetServiceImageFileQuery;
use App\Services\Application\ServiceImage\QueryHandler\GetServiceImageFileQueryHandler;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * GET /service-images/{proId}/{fileName}
 *
 * Serves a service image, like the professional banners are served. Only active images of the professional
 * are served; removed images return 404. File names are random and never change, so responses can be
 * cached for long.
 */
#[Route(
    '/service-images/{proId}/{fileName}',
    name: 'service_image_file',
    requirements: ['proId' => '\d+', 'fileName' => '[a-f0-9]{32}\.(jpg|png|webp)'],
    methods: ['GET'],
    priority: 10
)]
final class ServiceImageFileController
{
    public function __invoke(int $proId, string $fileName, GetServiceImageFileQueryHandler $handler): Response
    {
        $file = $handler(new GetServiceImageFileQuery($proId, $fileName));
        if ($file === null) {
            throw new NotFoundHttpException();
        }

        $response = new BinaryFileResponse($file['path']);
        $response->headers->set('Content-Type', $file['mime_type']);
        $response->headers->set('Cache-Control', 'public, max-age=86400');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
