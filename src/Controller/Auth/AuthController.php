<?php

namespace App\Controller\Auth;

use App\Repository\UserRepository;
use App\Service\LoginLinkService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

class AuthController extends AbstractController
{
    // Keep empty because login link authenticator will intercept requests.
    #[Route('/pro/login-check', name: 'login_check', env: 'dev')]
    public function loginCheck(): Response
    {
        return new Response('200');
    }

    // See docs/symfony-certification-questions.md#symfony-question-0001-can-a-route-env-parameter-target-multiple-environments.
    #[Route('/login', name: 'login', env: 'dev')]
    public function connectLoginLink(
        LoginLinkHandlerInterface $loginLinkHandler,
        Request $request,
        UserRepository $userRepository
    ): Response {
        if ($request->isMethod('POST')) {
            $user = $userRepository->findOneBy(['email' => $request->request->get('email')]);

            if ($user) {
                $loginLinkService = new LoginLinkService($loginLinkHandler);
                $details = $loginLinkService->sendLoginLink($user, 'Voici votre lien de connexion');

                return new Response($details->getUrl());
            } else {
                $this->addFlash('error', 'Aucun utilisateur avec cet email a été trouvé');

                return $this->json(['error' => 'Aucun utilisateur avec cet email a été trouvé']);
            }
        }

        return $this->render('security/request_login_link.html.twig');
    }
}
