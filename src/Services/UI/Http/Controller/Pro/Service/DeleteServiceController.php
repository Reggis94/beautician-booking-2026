<?php

namespace App\Services\UI\Http\Controller\Pro\Service;

use App\Services\Application\Service\Command\DeleteServiceCommand;
use App\Services\Application\Service\CommandHandler\DeleteServiceCommandHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/pro/service/delete', name: 'api_pro_delete_service', methods: ['DELETE'])]
final class DeleteServiceController extends AbstractController
{
    public function __invoke(Request $request, DeleteServiceCommandHandler $handler): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $serviceId = (int) ($data['service_id'] ?? 0);
        $proId = (int) ($data['pro_id'] ?? 0);
        $command = new DeleteServiceCommand($serviceId, $proId);

        try {
            $handler($command);
        } catch (\Exception $e) {
            return $this->json(['errors' => [$e->getMessage()]], 403);
        }

        return $this->json(null, 204);
    }
}
