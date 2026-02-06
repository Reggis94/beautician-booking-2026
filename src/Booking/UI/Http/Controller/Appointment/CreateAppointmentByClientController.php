<?php

namespace App\Booking\UI\Http\Controller\Appointment;

use App\Booking\Application\Command\CreateAppointmentByClientCommand;
use App\Booking\Application\CommandHandler\CreateAppointmentByClientCommandHandler;
use App\Booking\Application\Dto\CreateAppointmentByClientDto;
use App\Booking\Application\Exception\AppointmentOverlapException;
use App\Booking\Application\Exception\OutsideProBusinessTimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/booking/appointment', name: 'api_booking_create_appointment', methods: ['POST'])]
final class CreateAppointmentByClientController
{
    public function __invoke(Request $request, CreateAppointmentByClientCommandHandler $handler): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            if (!is_array($data) || $data === []) {
                throw new \InvalidArgumentException('Payload must be a non-empty object.');
            }

            $dto = new CreateAppointmentByClientDto(
                (int) ($data['pro_id'] ?? 0),
                (int) ($data['service_id'] ?? 0),
                (string) ($data['start_dt'] ?? ''),
                (string) ($data['last_name'] ?? ''),
                (string) ($data['first_name'] ?? ''),
                (string) ($data['email'] ?? '')
            );

            $command = new CreateAppointmentByClientCommand(
                $dto->getProId(),
                $dto->getServiceId(),
                $dto->getStartDateTimeUtc(),
                $dto->getLastName(),
                $dto->getFirstName(),
                $dto->getEmail()
            );
        } catch (\Throwable $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $appointmentId = $handler($command);
        } catch (OutsideProBusinessTimeException $exception) {
            return new JsonResponse(
                ['error' => $exception->getMessage(), 'code' => 'outside_pro_schedule'],
                JsonResponse::HTTP_CONFLICT
            );
        } catch (AppointmentOverlapException $exception) {
            return new JsonResponse(
                ['error' => $exception->getMessage(), 'code' => 'appointment_overlap'],
                JsonResponse::HTTP_CONFLICT
            );
        } catch (\Throwable $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['id' => $appointmentId], JsonResponse::HTTP_CREATED);
    }
}
