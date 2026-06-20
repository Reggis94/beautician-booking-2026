<?php

namespace App\Services\UI\Http\Controller\Pro\ServiceCategory;

use App\Services\Application\ServiceCategory\Command\CreateServiceCategoryCommand;
use App\Services\Application\ServiceCategory\CommandHandler\CreateServiceCategoryCommandHandler;
use App\Services\Application\ServiceCategory\Dto\ServiceCategoryDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/pro/service/category/new', name: 'api_pro_create_service_category', methods: ['POST'])]
final class CreateServiceCategoryController extends AbstractController
{
    public function __invoke(
        Request $request,
        CreateServiceCategoryCommandHandler $createServiceCategoryCommandHandler,
        ValidatorInterface $validator
    ) {
        // TO-IMPROVE-0002: Centralize JSON request content validation and payload parsing.
        $data = json_decode($request->getContent(), true) ?? [];
        $name = $data['name'] ?? '';
        $proId = isset($data['pro_id']) ? (int) ($data['pro_id']) : 0;
        $dto = new ServiceCategoryDto($name, $proId);
        // TO-IMPROVE-0001: Centralize DTO validation and error response mapping.
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 400);
        }
        $command = new CreateServiceCategoryCommand($dto->name, $dto->proId);
        try {
            $createServiceCategoryCommandHandler($command);
        } catch (\Exception $e) {
            return $this->json(['errors' => [$e->getMessage()]], 400);
        }

        return $this->json(null, 201);
    }
}
