<?php

namespace App\Services\UI\Http\Controller\Pro\Service;

use App\ProAuth\Public\AuthenticatedPro;
use App\Services\Application\Exception\ServiceCategoryNotFoundException;
use App\Services\Application\Service\Command\CreateServiceCommand;
use App\Services\Application\Service\CommandHandler\CreateServiceCommandHandler;
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
 * POST /api/pro/service/new
 *
 * Creates a service of the authenticated professional (session cookie required, HTTP 401 otherwise),
 * with its images, in one transaction.
 *
 * multipart/form-data body (JSON is accepted when there is no image):
 * - name: required, 1 to 50 characters.
 * - description: optional, at most 500 characters.
 * - durationMin: required, 5 to 720, a multiple of 5.
 * - priceCents: required, 0 to 1000000.
 * - categoryId: optional not deleted category of the professional; omit it for no category.
 * - published: "1" to publish, "0" (default) for a draft.
 * - images[]: up to 2 JPEG, PNG or WebP files of 5 MB at most each.
 *
 * Responses (error bodies are empty, ADR 0008):
 * - 201 created.
 * - 404 when the category is unknown, deleted or belongs to another professional.
 * - 409 when more than two images are sent.
 * - 422 when a field is invalid, or an image is not a JPEG, PNG or WebP image of 5 MB at most.
 */
#[Route('/api/pro/service/new', name: 'api_pro_create_service', methods: ['POST'])]
final class CreateServiceController
{
    /**
     * @param list<UploadedFile>|null $images Null when the request has no image.
     */
    public function __invoke(
        CreateServiceCommandHandler $handler,
        #[CurrentUser] AuthenticatedPro $authenticatedPro,
        #[MapRequestPayload] CreateServiceCommand $command,
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
        } catch (ServiceCategoryNotFoundException) {
            return new JsonResponse(null, JsonResponse::HTTP_NOT_FOUND);
        } catch (ServiceImageLimitExceededException) {
            return new JsonResponse(null, JsonResponse::HTTP_CONFLICT);
        } catch (InvalidServiceImageException) {
            return new JsonResponse(null, JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, JsonResponse::HTTP_CREATED);
    }
}
