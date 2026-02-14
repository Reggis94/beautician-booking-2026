<?php

namespace App\ProfilePro\UI\Http\Controller\Timezone;

use App\ProfilePro\Application\Command\UpdateTimezoneCommand;
use App\ProfilePro\Application\CommandHandler\UpdateTimezoneCommandHandler;
use App\ProfilePro\Application\Timezone\Dto\TimezoneDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/pro/timezone-location/update', name: 'api_pro_update_timezone_location', methods: ['PUT'])]
final class UpdateTimezoneLocationController extends AbstractController
{
    public function __invoke(Request $request, UpdateTimezoneCommandHandler $handler): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        try {
            $proId = $payload['pro_id'] ?? $payload['proId'] ?? '';
            $fullAddress = $payload['full_address'] ?? $payload['fullAddress'] ?? '';
            $lat = $payload['lat'] ?? '';
            $lng = $payload['lng'] ?? '';
            $timezoneDto = new TimezoneDto($proId, $fullAddress, $lat, $lng);
        } catch (\Throwable $exception) {
            // TO-MONITOR-0001: Future monitoring code will need to be provided in the future.
            return new JsonResponse(['errors' => [$exception->getMessage()]], JsonResponse::HTTP_BAD_REQUEST);
        }

        $command = new UpdateTimezoneCommand($timezoneDto);
        // TO-MONITOR-0001: Future monitoring code will need to be provided in the future.
        $handler($command);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}

