<?php

namespace App\Services\UI\Http\Controller\Pro\Service;

use App\Services\Application\Service\Command\UpdateServiceCommand;
use App\Services\Application\Service\CommandHandler\UpdateServiceCommandHandler;
use App\Services\Application\Service\Dto\ServiceDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/pro/service/edit', name: 'api_pro_update_service', methods: ['PUT'])]
final class UpdateServiceController extends AbstractController
{
    public function __invoke(
        Request $request,
        UpdateServiceCommandHandler $updateServiceCommandHandler,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];
        $serviceId = (int) ($data['service_id'] ?? 0);
        $name = $data['name'] ?? '';
        $proId = (int) $request->query->get('pro_id', $data['pro_id'] ?? 0);
        $categoryId = array_key_exists('category_id', $data) && $data['category_id'] !== null
            ? (int) $data['category_id']
            : null;
        $description = isset($data['description']) ? (string) $data['description'] : null;
        $durationMin = array_key_exists('duration_min', $data) && $data['duration_min'] !== null
            ? (int) $data['duration_min']
            : null;
        $priceCents = array_key_exists('price_cents', $data) && $data['price_cents'] !== null
            ? (int) $data['price_cents']
            : null;

        $errors = [];
        if ($serviceId <= 0) {
            $errors[] = 'Service id must be positive.';
        }

        $dto = new ServiceDto(
            $name,
            $categoryId,
            $proId,
            $description,
            $durationMin,
            $priceCents
        );
        $violations = $validator->validate($dto);
        if (count($violations) > 0) {
            foreach ($violations as $error) {
                $errors[] = $error->getMessage();
            }
        }

        if (count($errors) > 0) {
            return $this->json(['errors' => $errors], 400);
        }

        $command = new UpdateServiceCommand(
            $serviceId,
            $dto->proId,
            $dto->categoryId,
            $dto->name,
            $dto->description,
            $dto->durationMin,
            $dto->priceCents
        );

        try {
            $updateServiceCommandHandler($command);
        } catch (\Exception $e) {
            return $this->json(['errors' => [$e->getMessage()]], 400);
        }

        return $this->json(null, 200);
    }
}
