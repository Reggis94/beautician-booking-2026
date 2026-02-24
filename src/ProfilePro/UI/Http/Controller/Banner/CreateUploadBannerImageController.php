<?php

namespace App\ProfilePro\UI\Http\Controller\Banner;

use App\ProfilePro\Application\Banner\Command\CreateUploadBannerImagesCommand;
use App\ProfilePro\Application\Banner\CommandHandler\CreateUploadBannerImagesCommandHandler;
use App\ProfilePro\Application\Banner\Dto\CreateUploadBannerImagesDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
        $contentType = $request->headers->get('Content-Type', '');
        if (!str_starts_with(strtolower($contentType), 'multipart/form-data')) {
            return new JsonResponse(
                ['errors' => ['Content-Type must be multipart/form-data for banner upload.']],
                Response::HTTP_UNSUPPORTED_MEDIA_TYPE
            );
        }

        $proId = (int) ($request->request->get('pro_id') ?? 0);
        $payloadFiles = $request->files->all();
        $payloadInput = $request->request->all();

        $imagesExtraction = $this->extractMultipartImages($payloadFiles['images'] ?? []);
        if ($imagesExtraction['error'] !== null) {
            return new JsonResponse(['errors' => [$imagesExtraction['error']]], JsonResponse::HTTP_BAD_REQUEST);
        }

        $orderNumbersExtraction = $this->extractMultipartOrderNumbers($payloadInput['order_numbers'] ?? []);
        if ($orderNumbersExtraction['error'] !== null) {
            return new JsonResponse(['errors' => [$orderNumbersExtraction['error']]], JsonResponse::HTTP_BAD_REQUEST);
        }

        // TO-PRO-0004: When Banner moves to full DDD, this mapping should call a dedicated
        // Banner aggregate factory (single validation boundary) instead of splitting checks.
        $dto = new CreateUploadBannerImagesDto(
            $proId,
            $this->flattenPayloadFiles($imagesExtraction['images']),
            $this->normalizeOrderNumbers($orderNumbersExtraction['orderNumbers'])
        );
        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            // TO-MONITOR-0002: Future monitoring code will need to be provided in the future.
            return new JsonResponse(['errors' => $errorMessages], JsonResponse::HTTP_BAD_REQUEST);
        }

        $command = new CreateUploadBannerImagesCommand($dto);
        try {
            $handler($command);
        } catch (\Throwable $exception) {
            // TO-MONITOR-0002: Future monitoring code will need to be provided in the future.
            return new JsonResponse(['errors' => [$exception->getMessage()]], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new Response(status: Response::HTTP_CREATED);
    }

    private function flattenPayloadFiles(array $payloadFiles): array
    {
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

    /**
     * @param mixed $payloadImages
     * @return array{images: array, error: string|null}
     */
    private function extractMultipartImages(mixed $payloadImages): array
    {
        if ($payloadImages === [] || $payloadImages === null) {
            return ['images' => [], 'error' => null];
        }

        if (is_array($payloadImages)) {
            return ['images' => $payloadImages, 'error' => null];
        }

        if ($payloadImages instanceof UploadedFile) {
            return ['images' => [$payloadImages], 'error' => null];
        }

        return [
            'images' => [],
            'error' => 'images must be provided as multipart file fields (use repeated images[] parts).',
        ];
    }

    /**
     * @param mixed $payloadOrderNumbers
     * @return array{orderNumbers: array, error: string|null}
     */
    private function extractMultipartOrderNumbers(mixed $payloadOrderNumbers): array
    {
        if ($payloadOrderNumbers === [] || $payloadOrderNumbers === null) {
            return ['orderNumbers' => [], 'error' => null];
        }

        if (is_array($payloadOrderNumbers)) {
            return ['orderNumbers' => $payloadOrderNumbers, 'error' => null];
        }

        if (is_int($payloadOrderNumbers) || is_string($payloadOrderNumbers)) {
            return ['orderNumbers' => [$payloadOrderNumbers], 'error' => null];
        }

        return [
            'orderNumbers' => [],
            'error' => 'order_numbers must be provided as multipart fields (use repeated order_numbers[] values).',
        ];
    }

    private function normalizeOrderNumbers(array $payloadOrderNumbers): array
    {
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

            if (is_string($value)) {
                $trimmedValue = trim($value);
                if (preg_match('/^-?\d+$/', $trimmedValue) === 1) {
                    $flatValues[] = (int) $trimmedValue;
                }
            }
        }

        return $flatValues;
    }
}
