<?php

namespace App\Services\UI\Http\Controller\Pro\ServiceCategory;

use App\ProAuth\Public\AuthenticatedPro;
use App\Services\Application\Exception\ServiceCategoryNotFoundException;
use App\Services\Application\ServiceCategory\Command\DeleteServiceCategoryCommand;
use App\Services\Application\ServiceCategory\CommandHandler\DeleteServiceCategoryCommandHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * DELETE /api/pro/service/category/delete
 *
 * Explicitly deletes a category of the authenticated professional (session cookie required, HTTP 401
 * otherwise), in one transaction with its services. Appointments are neither cancelled nor changed.
 *
 * JSON body:
 * - serviceCategoryId: required.
 * - keepServices: true (default) moves the services to "no category" (booked services get a new
 *   uncategorized version); false deletes them with the category.
 *
 * Responses (error bodies are empty, ADR 0008):
 * - 204 deleted.
 * - 404 when the category is unknown, already deleted or replaced, or belongs to another professional.
 * - 422 when a field is missing or invalid.
 */
#[Route('/api/pro/service/category/delete', name: 'api_pro_delete_service_category', methods: ['DELETE'])]
final class DeleteServiceCategoryController
{
    public function __invoke(
        DeleteServiceCategoryCommandHandler $handler,
        #[CurrentUser] AuthenticatedPro $authenticatedPro,
        #[MapRequestPayload] DeleteServiceCategoryCommand $command
    ): JsonResponse {
        try {
            $handler($command, $authenticatedPro->getId());
        } catch (ServiceCategoryNotFoundException) {
            return new JsonResponse(null, JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
