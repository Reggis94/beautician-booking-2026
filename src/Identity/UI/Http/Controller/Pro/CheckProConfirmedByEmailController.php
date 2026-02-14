<?php

namespace App\Identity\UI\Http\Controller\Pro;

use App\Identity\Application\Query\CheckProConfirmedByEmailQuery;
use App\Identity\Application\QueryHandler\CheckProConfirmedByEmailQueryHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/pro/identity/confirmed', name: 'api_pro_identity_confirmed', methods: ['GET'])]
final class CheckProConfirmedByEmailController extends AbstractController
{
    public function __invoke(Request $request, CheckProConfirmedByEmailQueryHandler $handler): JsonResponse
    {
        $proEmail = (string) ($request->query->get('pro_email') ?? $request->query->get('proEmail') ?? '');
        if ($proEmail === '' || filter_var($proEmail, FILTER_VALIDATE_EMAIL) === false) {
            return new JsonResponse(['error' => 'Missing or invalid pro_email.'], JsonResponse::HTTP_BAD_REQUEST);
        }
        $query = new CheckProConfirmedByEmailQuery($proEmail);
        $status = $handler($query);

        return new JsonResponse(['status' => $status->value], JsonResponse::HTTP_OK);
    }
}
