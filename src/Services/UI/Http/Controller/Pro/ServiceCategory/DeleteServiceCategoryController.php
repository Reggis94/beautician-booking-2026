<?php

namespace App\Services\UI\Http\Controller\Pro\ServiceCategory;

use App\Services\Application\ServiceCategory\Command\DeleteServiceCategoryCommand;
use App\Services\Application\ServiceCategory\CommandHandler\DeleteServiceCategoryCommandHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

// See docs/future-improvements/verify-authenticated-user-owns-service-resources.md.
#[Route('/api/pro/service/category/delete', name: 'api_pro_delete_service_category', methods: ['DELETE'])]
final class DeleteServiceCategoryController extends AbstractController
{
    public function __invoke(Request $request, DeleteServiceCategoryCommandHandler $handler): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $serviceCategoryId = (int) ($data['service_category_id']);
        $proId = (int) ($data['pro_id']);
        $command = new DeleteServiceCategoryCommand($serviceCategoryId, $proId);
        try {
            $handler($command);
        } catch (\Exception $e) {
            return $this->json(['errors' => [$e->getMessage()]], 403);
        }

        return $this->json(null, 204);
    }
}
