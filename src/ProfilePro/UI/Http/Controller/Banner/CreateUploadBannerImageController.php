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
        // ADR-0015: Banner request-body fields are now canonical snake_case only.
        // We intentionally removed camelCase aliases so clients must send `pro_id`.
        $proId = (int) ($request->request->get('pro_id') ?? 0);
        $payloadFiles = $request->files->all();
        // ADR-0015: Accept only canonical `images` multipart field for banner files.
        $payloadImages = $payloadFiles['images'] ?? [];
        // ADR-0002 + ADR-0013: We normalize to a single `array` input and avoid callback-heavy/mixed branching
        // so banner input handling stays explicit, predictable, and easy to review.
        if (!is_array($payloadImages)) {
            $payloadImages = [$payloadImages];
        }
        // ADR-0015: Only canonical snake_case `order_numbers` array is accepted.
        $payloadOrderNumbers = $request->request->all('order_numbers');

        // TO-PRO-0004: When Banner moves to full DDD, this mapping should call a dedicated
        // Banner aggregate factory (single validation boundary) instead of splitting checks.
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
        // ADR-0002 + ADR-0013: Method accepts a single type (`array`) and flattens explicitly via loops
        // to reduce mixed inputs and callback indirection in banner parsing.
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

    private function normalizeOrderNumbers(array $payloadOrderNumbers): array
    {
        // ADR-0002 + ADR-0013: Method accepts only `array` and uses explicit procedural normalization;
        // removed callback-based splitting/trimming to keep banner rules clear and reviewable.
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
