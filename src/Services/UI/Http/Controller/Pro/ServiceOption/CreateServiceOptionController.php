<?php

namespace App\Services\UI\Http\Controller\Pro\ServiceOption;

use App\Services\Application\ServiceOption\Command\CreateServiceOptionCommand;
use App\Services\Application\ServiceOption\CommandHandler\CreateServiceOptionCommandHandler;
use App\Services\Application\ServiceOption\Dto\ServiceOptionDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/pro/service/option/new', name: 'api_pro_create_service_option', methods: ['POST'])]
final class CreateServiceOptionController extends AbstractController
{
    public function __invoke(
        Request $request,
        CreateServiceOptionCommandHandler $handler,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];
        $name = $data['name'] ?? '';
        $proId = (int) $request->query->get('pro_id', $data['pro_id'] ?? 0);
        $serviceId = (int) ($data['service_id'] ?? 0);
        $priceExtraCents = array_key_exists('price_extra_cents', $data) && $data['price_extra_cents'] !== null
            ? (int) $data['price_extra_cents']
            : null;

        $dto = new ServiceOptionDto($name, $serviceId, $proId, $priceExtraCents);
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 400);
        }

        $command = new CreateServiceOptionCommand(
            $dto->proId,
            $dto->serviceId,
            $dto->name,
            $dto->priceExtraCents
        );

        try {
            $handler($command);
        } catch (\Exception $e) {
            return $this->json(['errors' => [$e->getMessage()]], 400);
        }

        return $this->json(null, 201);
    }
}
