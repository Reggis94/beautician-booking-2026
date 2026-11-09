<?php

namespace App\Services\UI\Http\Controller\Pro\Service;

use App\ProAuth\Public\AuthenticatedPro;
use App\Services\Application\Exception\ServiceCategoryNotFoundException;
use App\Services\Application\Exception\ServiceNotFoundException;
use App\Services\Application\Service\Command\UpdateServiceCommand;
use App\Services\Application\Service\CommandHandler\UpdateServiceCommandHandler;
use App\Services\Domain\Exception\InvalidServiceImageException;
use App\Services\Domain\Exception\ServiceImageLimitExceededException;
use App\Services\Domain\ValueObject\ImageUpload;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * POST /api/pro/service/edit
 *
 * Saves the whole service form of the authenticated professional (session cookie required, HTTP 401
 * otherwise): content, publication and images, in one transaction. POST, not PUT, because PHP only parses
 * multipart bodies for POST.
 *
 * When the name, description, price, duration or category changes and the service has appointments
 * (past, future, cancelled or soft-deleted), a new version replaces it and the appointments keep the old one.
 * Publication and image changes never create a version.
 *
 * multipart/form-data body (JSON is accepted when there is no new image):
 * - serviceId: required id of a not deleted service of the professional.
 * - name, description, durationMin, priceCents, categoryId, published: as for POST /api/pro/service/new.
 * - keptImageIds[]: ids of the service's current images to keep; the other current images are removed.
 * - images[]: new JPEG, PNG or WebP files of 5 MB at most each; kept plus new images must not exceed 2.
 *
 * Responses (error bodies are empty, ADR 0008):
 * - 204 saved.
 * - 404 when the service or the category is unknown, deleted, replaced or belongs to another professional.
 * - 409 when the service would have more than two images.
 * - 422 when a field or an image is invalid.
 */
#[Route('/api/pro/service/edit', name: 'api_pro_update_service', methods: ['POST'])]
final class UpdateServiceController
{
    /**
     * @param list<UploadedFile>|null $images Null when the request has no new image.
     */
    public function __invoke(
        UpdateServiceCommandHandler $handler,
        #[CurrentUser] AuthenticatedPro $authenticatedPro,
        #[MapRequestPayload] UpdateServiceCommand $command,
        #[MapUploadedFile(
            // Applied to each uploaded file; the two-image limit is a domain rule (HTTP 409).
            constraints: new Assert\Image(maxSize: '5M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp']),
            name: 'images'
        )]
        ?array $images = null
    ): JsonResponse {
        try {
            $uploads = [];
            foreach ($images ?? [] as $image) {
                $uploads[] = new ImageUpload(
                    $image->getPathname(),
                    (string) $image->getMimeType(),
                    (int) $image->getSize(),
                    $image->getClientOriginalName()
                );
            }

            $handler($command, $authenticatedPro->getId(), $uploads);
        } catch (ServiceNotFoundException | ServiceCategoryNotFoundException) {
            return new JsonResponse(null, JsonResponse::HTTP_NOT_FOUND);
        } catch (ServiceImageLimitExceededException) {
            return new JsonResponse(null, JsonResponse::HTTP_CONFLICT);
        } catch (InvalidServiceImageException) {
            return new JsonResponse(null, JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
