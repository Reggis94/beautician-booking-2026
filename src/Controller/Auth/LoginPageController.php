<?php

namespace App\Controller\Auth;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LoginPageController extends AbstractController
{
    #[Route('/login-page', name: 'login_page', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('security/login.html.twig', [
            'email' => null,
            'error' => null,
            'last_username' => '',
            'sent' => false,
        ]);
    }
}
