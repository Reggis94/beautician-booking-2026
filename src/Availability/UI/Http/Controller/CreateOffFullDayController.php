<?php

namespace App\Availability\UI\Http\Controller;

use App\Availability\Application\CommandHandler\CreateOffFullDayCommandHandler;
use App\Availability\Application\Dto\OffFullDayDto;
use App\Availability\Domain\Application\Command\CreateOffFullDayCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api/pro/off-full-day', name: 'createOffFullDay', methods: ['POST'])]
final class CreateOffFullDayController
{
    public function __invoke(Request $request, CreateOffFullDayCommandHandler $handler): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            if (!is_array($data)) {
                throw new \InvalidArgumentException('Payload must be an object.');
            }

            $dto = OffFullDayDto::fromArray($data);
        } catch (\Throwable $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $command = new CreateOffFullDayCommand($dto->getProId(), $dto->getDate());

        try {
            $handler($command);
        } catch (\Throwable $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(null, JsonResponse::HTTP_CREATED);
    }
}
