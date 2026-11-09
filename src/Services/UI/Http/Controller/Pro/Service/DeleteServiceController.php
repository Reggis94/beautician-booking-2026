<?php

namespace App\Services\UI\Http\Controller\Pro\Service;

use App\ProAuth\Public\AuthenticatedPro;
use App\Services\Application\Exception\ServiceNotFoundException;
use App\Services\Application\Service\Command\DeleteServiceCommand;
use App\Services\Application\Service\CommandHandler\DeleteServiceCommandHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * DELETE /api/pro/service/delete
 *
 * Explicitly deletes (soft delete) a service of the authenticated professional (session cookie required,
 * HTTP 401 otherwise). Appointments are neither cancelled nor changed.
 *
 * JSON body: serviceId (required).
 *
 * Responses (error bodies are empty, ADR 0008):
 * - 204 deleted.
 * - 404 when the service is unknown, already deleted or replaced, or belongs to another professional.
 * - 422 when serviceId is missing or invalid.
 */
#[Route('/api/pro/service/delete', name: 'api_pro_delete_service', methods: ['DELETE'])]
final class DeleteServiceController
{
    public function __invoke(
        DeleteServiceCommandHandler $handler,
        #[CurrentUser] AuthenticatedPro $authenticatedPro,
        #[MapRequestPayload] DeleteServiceCommand $command
    ): JsonResponse {
        try {
            $handler($command, $authenticatedPro->getId());
        } catch (ServiceNotFoundException) {
            return new JsonResponse(null, JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
