<?php

namespace App\Services\UI\Http\Controller\Pro\Service;

use App\Services\Application\Service\Command\CreateServiceCommand;
use App\Services\Application\Service\CommandHandler\CreateServiceCommandHandler;
use App\Services\Application\Service\Dto\ServiceDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/pro/service/new', name: 'api_pro_create_service', methods: ['POST'])]
final class CreateServiceController extends AbstractController
{
    public function __invoke(
        Request $request,
        CreateServiceCommandHandler $createServiceCommandHandler,
        ValidatorInterface $validator
    ): JsonResponse {
        // TO-IMPROVE-0002: Centralize JSON request content validation and payload parsing.
        $data = json_decode($request->getContent(), true) ?? [];
        $name = $data['name'] ?? '';
        $proId = (int) $request->query->get('pro_id', $data['pro_id'] ?? 0);
        $categoryId = array_key_exists('category_id', $data) && $data['category_id'] !== null
            ? (int) ($data['category_id'])
            : null;
        $description = isset($data['description']) ? (string) $data['description'] : null;
        $durationMin = array_key_exists('duration_min', $data) && $data['duration_min'] !== null
            ? (int) $data['duration_min']
            : null;
        $priceCents = array_key_exists('price_cents', $data) && $data['price_cents'] !== null
            ? (int) $data['price_cents']
            : null;
        $dto = new ServiceDto(
            $name,
            $categoryId,
            $proId,
            $description,
            $durationMin,
            $priceCents
        );
        // TO-IMPROVE-0001: Centralize DTO validation and error response mapping.
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 400);
        }

        $command = new CreateServiceCommand(
            $dto->proId,
            $dto->categoryId,
            $dto->name,
            $dto->description,
            $dto->durationMin,
            $dto->priceCents
        );

        try {
            $createServiceCommandHandler($command);
        } catch (\Exception $e) {
            return $this->json(['errors' => [$e->getMessage()]], 400);
        }

        return $this->json(null, 201);
    }
}
