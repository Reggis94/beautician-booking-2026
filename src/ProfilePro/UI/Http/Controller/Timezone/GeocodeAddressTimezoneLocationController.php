<?php

namespace App\ProfilePro\UI\Http\Controller\Timezone;

use App\ProfilePro\Application\Query\GeocodeAddressQuery;
use App\ProfilePro\Application\QueryHandler\GeocodeAddressQueryHandler;
use App\ProfilePro\Application\Timezone\Dto\GeocodeAddressDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route(
    '/api/pro/timezone-location/geocode-address',
    name: 'api_pro_geocode_timezone_location_address',
    methods: ['GET']
)]
final class GeocodeAddressTimezoneLocationController extends AbstractController
{
    public function __invoke(
        Request $request,
        GeocodeAddressQueryHandler $handler,
        ValidatorInterface $validator
    ): JsonResponse {
        $fullAddress = (string) (
            $request->query->get('full_address')
            ?? $request->query->get('fullAddress')
            ?? $request->query->get('address')
            ?? ''
        );
        $dto = new GeocodeAddressDto((string) $fullAddress);
        // TO-IMPROVE-0001: Centralize DTO validation and error response mapping.
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            // TO-MONITOR-0001: Future monitoring code will need to be provided in the future.
            return new JsonResponse(['errors' => $errorMessages], JsonResponse::HTTP_BAD_REQUEST);
        }

        $query = new GeocodeAddressQuery($dto);

        try {
            $result = $handler($query);
        } catch (\Throwable $exception) {
            // TO-MONITOR-0001: Future monitoring code will need to be provided in the future.
            return new JsonResponse(['errors' => [$exception->getMessage()]], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(
            [
                'lat' => $result['lat'],
                'lng' => $result['lng'],
            ],
            JsonResponse::HTTP_OK
        );
    }
}
