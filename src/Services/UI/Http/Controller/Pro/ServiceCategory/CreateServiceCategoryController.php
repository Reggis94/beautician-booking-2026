<?php

namespace App\Services\UI\Http\Controller\Pro\ServiceCategory;

use App\ProAuth\Public\AuthenticatedPro;
use App\Services\Application\ServiceCategory\Command\CreateServiceCategoryCommand;
use App\Services\Application\ServiceCategory\CommandHandler\CreateServiceCategoryCommandHandler;
use App\Services\Domain\Exception\ServiceCategoryNameAlreadyUsedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * POST /api/pro/service/category/new
 *
 * Creates a category of the authenticated professional (session cookie required, HTTP 401 otherwise).
 * Categories have no publication state: a category is shown publicly once it has a published service.
 *
 * JSON body: name (required, 1 to 100 characters).
 *
 * Responses (error bodies are empty, ADR 0008):
 * - 201 created.
 * - 409 when another not deleted category of the professional has the same name (case-insensitive).
 * - 422 when the name is missing or invalid.
 */
#[Route('/api/pro/service/category/new', name: 'api_pro_create_service_category', methods: ['POST'])]
final class CreateServiceCategoryController
{
    public function __invoke(
        CreateServiceCategoryCommandHandler $handler,
        #[CurrentUser] AuthenticatedPro $authenticatedPro,
        #[MapRequestPayload] CreateServiceCategoryCommand $command
    ): JsonResponse {
        try {
            $handler($command, $authenticatedPro->getId());
        } catch (ServiceCategoryNameAlreadyUsedException) {
            return new JsonResponse(null, JsonResponse::HTTP_CONFLICT);
        }

        return new JsonResponse(null, JsonResponse::HTTP_CREATED);
    }
}
