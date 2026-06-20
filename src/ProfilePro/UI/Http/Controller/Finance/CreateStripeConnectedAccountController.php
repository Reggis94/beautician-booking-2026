<?php

namespace App\ProfilePro\UI\Http\Controller\Finance;

use App\ProfilePro\Application\Command\CreateStripeConnectedAccountCommand;
use App\ProfilePro\Application\CommandHandler\CreateStripeConnectedAccountCommandHandler;
use App\ProfilePro\Application\Finance\Dto\CreateStripeConnectedAccountDto;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route(
    '/api/pro/finance/create/stripe-connected-account',
    name: 'api_pro_finance_create_stripe_connected_account',
    methods: ['POST']
)]
final class CreateStripeConnectedAccountController
{
    public function __invoke(
        Request $request,
        CreateStripeConnectedAccountCommandHandler $handler,
        ValidatorInterface $validator
    ): Response {
        // TO-IMPROVE-0002: Centralize JSON request content validation and payload parsing.
        $contentType = strtolower((string) $request->headers->get('Content-Type', ''));
        if (!str_starts_with($contentType, 'application/json')) {
            return new JsonResponse(
                ['errors' => ['Content-Type must be application/json.']],
                JsonResponse::HTTP_UNSUPPORTED_MEDIA_TYPE
            );
        }

        $payload = json_decode($request->getContent(), true) ?? [];
        if (!is_array($payload) || $payload === []) {
            return new JsonResponse(
                ['errors' => ['Payload must be a non-empty object.']],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $dto = new CreateStripeConnectedAccountDto(
            (string) ($payload['country'] ?? ''),
            (string) ($payload['account_token'] ?? '')
        );

        // TO-IMPROVE-0001: Centralize DTO validation and error response mapping.
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return new JsonResponse(['errors' => $errorMessages], JsonResponse::HTTP_BAD_REQUEST);
        }

        $command = new CreateStripeConnectedAccountCommand($dto);

        try {
            $result = $handler($command);
        } catch (ApiErrorException $exception) {
            $statusCode = $exception->getHttpStatus() ?? JsonResponse::HTTP_BAD_GATEWAY;
            $responseBody = (string) ($exception->getHttpBody() ?? '');

            if ($responseBody !== '') {
                // TO-IMPROVE-0003: Return generic Stripe errors in prod and expose details only when appropriate.
                return new Response($responseBody, $statusCode, ['Content-Type' => 'application/json']);
            }

            // TO-IMPROVE-0003: Return generic Stripe errors in prod and expose details only when appropriate.
            return new Response($exception->getMessage(), $statusCode, ['Content-Type' => 'text/plain']);
        } catch (\Throwable $exception) {
            // TO-IMPROVE-0003: Return generic errors in prod and expose details only when appropriate.
            return new JsonResponse(['errors' => [$exception->getMessage()]], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        // TO-IMPROVE-0003: Return only Stripe-derived fields that the frontend needs.
        return new JsonResponse($result, JsonResponse::HTTP_CREATED);
    }
}
