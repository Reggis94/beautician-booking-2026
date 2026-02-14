<?php

namespace App\Identity\UI\Http\Controller\Pro;

use App\Identity\Application\Command\RegisterProCommand;
use App\Identity\Application\CommandHandler\RegisterProCommandHandler;
use App\Identity\Application\Dto\RegisterProDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/pro/identity', name: 'api_pro_identity_create', methods: ['POST'])]
final class RegisterProController extends AbstractController
{
    public function __invoke(
        Request $request,
        RegisterProCommandHandler $handler,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];
        if (!is_array($data) || $data === []) {
            return $this->json(['errors' => ['Payload must be a non-empty object.']], 400);
        }

        $dto = new RegisterProDto(
            (string) ($data['first_name'] ?? $data['firstName'] ?? ''),
            (string) ($data['last_name'] ?? $data['lastName'] ?? ''),
            (string) ($data['email'] ?? '')
        );
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 400);
        }

        $command = new RegisterProCommand($dto->firstName, $dto->lastName, $dto->email);

        try {
            $handler($command);
        } catch (\Throwable $exception) {
            return $this->json(['errors' => [$exception->getMessage()]], 400);
        }

        return $this->json(null, 201);
    }
}
