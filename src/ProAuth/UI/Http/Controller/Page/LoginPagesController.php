<?php

namespace App\ProAuth\UI\Http\Controller\Page;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Beautician login pages. Both pages are static shells: every authentication step is a same-origin call to the
 * ProAuth API from the browser (see docs/pro-auth-frontend-integration.md).
 *
 * The pages are served in French. English versions (templates/pro_auth/login.html.twig and
 * confirm_login.html.twig) are kept aligned but not served yet.
 *
 * The priority keeps these paths ahead of the public `/{proLinkSlug}` catch-all route.
 */
final class LoginPagesController extends AbstractController
{
    private const DASHBOARD_ROUTE = 'pro_admin_appointments_upcoming_fr';

    /**
     * Login page: requests a login link by email.
     */
    #[Route('/fr/connexion', name: 'pro_auth_login_page_fr', methods: ['GET'], priority: 10)]
    public function login(Request $request): Response
    {
        return $this->render('pro_auth/fr/login.html.twig', [
            'dashboardUrl' => $this->dashboardUrl(),
            'loggedOut' => $request->query->getBoolean('deconnexion'),
        ]);
    }

    /**
     * Confirmation page targeted by the emailed link (AUTH_LOGIN_CONFIRM_URL). The token is in the URL fragment,
     * so it never reaches this controller. Opening the page does not log in.
     */
    #[Route('/fr/connexion/confirmation', name: 'pro_auth_confirm_login_page_fr', methods: ['GET'], priority: 10)]
    public function confirm(): Response
    {
        return $this->render('pro_auth/fr/confirm_login.html.twig', [
            'dashboardUrl' => $this->dashboardUrl(),
        ]);
    }

    /**
     * The backend-connected dashboard is not loaded in every environment yet: fall back to the home page.
     */
    private function dashboardUrl(): string
    {
        try {
            return $this->generateUrl(self::DASHBOARD_ROUTE);
        } catch (RouteNotFoundException) {
            return $this->generateUrl('glam4style_home');
        }
    }
}
