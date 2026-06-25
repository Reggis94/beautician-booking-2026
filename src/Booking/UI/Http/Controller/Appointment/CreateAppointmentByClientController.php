<?php

namespace App\Booking\UI\Http\Controller\Appointment;

use App\Booking\Application\Command\CreateAppointmentByClientCommand;
use App\Booking\Application\CommandHandler\CreateAppointmentByClientCommandHandler;
use App\Booking\Application\Exception\AppointmentOverlapException;
use App\Booking\Application\Exception\AppointmentStartDateTimeInPastException;
use App\Booking\Application\Exception\OutsideProBusinessTimeException;
use App\Booking\Application\Exception\ServiceDoesNotBelongToProException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

/**
 * POST /api/pro-presentation/booking/appointment/create
 *
 * Creates an appointment from the public professional presentation booking flow.
 *
 * Date and timezone contract:
 * - startDateTimeLocal must be the requested service start datetime in the professional's local timezone.
 * - Send startDateTimeLocal as a local datetime string, without a timezone name or UTC offset.
 * - The API resolves the professional's timezone from proId, interprets startDateTimeLocal in that timezone,
 *   and stores the appointment start datetime in UTC.
 * - Client/browser timezone must not be used to shift the submitted datetime.
 * - Datetimes in the past are rejected after conversion to UTC.
 *
 * JSON request body:
 * - proId: required positive integer professional ID.
 *   Future: derive this from serviceId instead of requiring client input.
 *   See docs/future-improvements/booking/book-0002-derive-pro-id-from-service-for-client-appointment.md.
 * - serviceId: required positive integer service ID.
 * - startDateTimeLocal: required pro-local service start datetime string without timezone or UTC offset.
 * - lastName: required client last name.
 * - firstName: required client first name.
 * - email: required client email.
 * - extraPhone: required client phone.
 *
 * Successful response:
 * - HTTP 201 with an empty body.
 *
 * Conflict responses:
 * - HTTP 409 when the requested datetime is in the past.
 * - HTTP 409 when the service would be outside the professional's business hours.
 * - HTTP 409 when another appointment overlaps the requested service time.
 * - HTTP 400 when the service does not belong to the professional.
 */
#[Route('/api/pro-presentation/booking/appointment/create', name: 'api_booking_client_create_appointment', methods: ['POST'])]
final class CreateAppointmentByClientController
{
    public function __invoke(
        CreateAppointmentByClientCommandHandler $handler,
        #[MapRequestPayload] CreateAppointmentByClientCommand $command
    ): JsonResponse {
        try {
            $handler($command);
        } catch (AppointmentStartDateTimeInPastException $exception) {
            return new JsonResponse(
                ['error' => $exception->getMessage()],
                JsonResponse::HTTP_CONFLICT
            );
        } catch (OutsideProBusinessTimeException $exception) {
            return new JsonResponse(
                ['error' => $exception->getMessage()],
                JsonResponse::HTTP_CONFLICT
            );
        } catch (AppointmentOverlapException $exception) {
            return new JsonResponse(
                ['error' => $exception->getMessage()],
                JsonResponse::HTTP_CONFLICT
            );
        } catch (ServiceDoesNotBelongToProException $exception) {
            return new JsonResponse(
                ['error' => $exception->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        return new JsonResponse(null, JsonResponse::HTTP_CREATED);
    }
}
