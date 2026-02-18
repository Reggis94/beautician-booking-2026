<?php

namespace App\ProfilePro\UI\Http\Controller\Banner;

use App\ProfilePro\Application\Banner\Command\CreateUploadBannerImagesCommand;
use App\ProfilePro\Application\Banner\CommandHandler\CreateUploadBannerImagesCommandHandler;
use App\ProfilePro\Application\Banner\Dto\CreateUploadBannerImagesDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/pro/banner/create/upload-image', name: 'api_pro_banner_create_upload_image', methods: ['POST'])]
final class CreateUploadBannerImageController extends AbstractController
{
    public function __invoke(
        Request $request,
        CreateUploadBannerImagesCommandHandler $handler,
        ValidatorInterface $validator
    ): Response
    {
        $proId = (int) ($request->request->get('pro_id') ?? $request->request->get('proId') ?? 0);
        $payloadFiles = $request->files->all();
        $payloadImages = $payloadFiles['images'] ?? $payloadFiles['files'] ?? $payloadFiles;
        $payloadOrderNumbers = $request->request->all('order_numbers');
        if ($payloadOrderNumbers === []) {
            $payloadOrderNumbers = $request->request->all('orderNumbers');
        }

        $dto = new CreateUploadBannerImagesDto(
            $proId,
            $this->flattenPayloadFiles($payloadImages),
            $this->normalizeOrderNumbers($payloadOrderNumbers)
        );
        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            // TO-MONITOR-0001: Future monitoring code will need to be provided in the future.
            return new JsonResponse(['errors' => $errorMessages], JsonResponse::HTTP_BAD_REQUEST);
        }

        $command = new CreateUploadBannerImagesCommand($dto);
        try {
            $handler($command);
        } catch (\Throwable $exception) {
            // TO-MONITOR-0001: Future monitoring code will need to be provided in the future.
            return new JsonResponse(['errors' => [$exception->getMessage()]], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new Response(status: Response::HTTP_CREATED);
    }

    private function flattenPayloadFiles(mixed $payloadFiles): array
    {
        if (!is_array($payloadFiles)) {
            return [$payloadFiles];
        }

        $flatFiles = [];
        foreach ($payloadFiles as $item) {
            if (is_array($item)) {
                foreach ($this->flattenPayloadFiles($item) as $nestedItem) {
                    $flatFiles[] = $nestedItem;
                }

                continue;
            }

            $flatFiles[] = $item;
        }

        return $flatFiles;
    }

    private function normalizeOrderNumbers(mixed $payloadOrderNumbers): array
    {
        if (is_string($payloadOrderNumbers)) {
            $payloadOrderNumbers = array_map('trim', explode(',', $payloadOrderNumbers));
        }

        if (!is_array($payloadOrderNumbers)) {
            return [];
        }

        $flatValues = [];
        foreach ($payloadOrderNumbers as $value) {
            if (is_array($value)) {
                foreach ($this->normalizeOrderNumbers($value) as $nestedValue) {
                    $flatValues[] = $nestedValue;
                }

                continue;
            }

            if (is_int($value)) {
                $flatValues[] = $value;

                continue;
            }

            if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
                $flatValues[] = (int) $value;
            }
        }

        return $flatValues;
    }
}
