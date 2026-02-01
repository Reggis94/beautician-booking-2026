<?php

namespace App\Availability\UI\Http\Controller;

use App\Availability\Application\CommandHandler\DeleteOffFullDayCommandHandler;
use App\Availability\Application\Dto\OffFullDayDto;
use App\Availability\Domain\Application\Command\DeleteOffFullDayCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api/pro/off-full-day', name: 'deleteOffFullDay', methods: ['DELETE'])]
final class DeleteOffFullDayController
{
    public function __invoke(Request $request, DeleteOffFullDayCommandHandler $handler): JsonResponse
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

        $command = new DeleteOffFullDayCommand($dto->getProId(), $dto->getDate());

        try {
            $handler($command);
        } catch (\Throwable $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
