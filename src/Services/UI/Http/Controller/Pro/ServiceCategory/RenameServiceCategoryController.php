<?php

namespace App\Services\UI\Http\Controller\Pro\ServiceCategory;

use App\ProAuth\Public\AuthenticatedPro;
use App\Services\Application\Exception\ServiceCategoryNotFoundException;
use App\Services\Application\ServiceCategory\Command\RenameServiceCategoryCommand;
use App\Services\Application\ServiceCategory\CommandHandler\RenameServiceCategoryCommandHandler;
use App\Services\Domain\Exception\ServiceCategoryNameAlreadyUsedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * PUT /api/pro/service/category/edit
 *
 * Renames a category of the authenticated professional (session cookie required, HTTP 401 otherwise).
 * When appointments reference the category, a renamed version replaces it: booked services are replaced
 * too and their appointments keep the old names; the other services move to the new version.
 *
 * JSON body: serviceCategoryId (required), name (required, 1 to 100 characters).
 *
 * Responses (error bodies are empty, ADR 0008):
 * - 204 saved.
 * - 404 when the category is unknown, deleted, replaced or belongs to another professional.
 * - 409 when another not deleted category of the professional has the same name (case-insensitive).
 * - 422 when a field is missing or invalid.
 */
#[Route('/api/pro/service/category/edit', name: 'api_pro_rename_service_category', methods: ['PUT'])]
final class RenameServiceCategoryController
{
    public function __invoke(
        RenameServiceCategoryCommandHandler $handler,
        #[CurrentUser] AuthenticatedPro $authenticatedPro,
        #[MapRequestPayload] RenameServiceCategoryCommand $command
    ): JsonResponse {
        try {
            $handler($command, $authenticatedPro->getId());
        } catch (ServiceCategoryNotFoundException) {
            return new JsonResponse(null, JsonResponse::HTTP_NOT_FOUND);
        } catch (ServiceCategoryNameAlreadyUsedException) {
            return new JsonResponse(null, JsonResponse::HTTP_CONFLICT);
        }

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
