<?php

namespace App\Controller;

use App\Dao\ProDao;
use App\Dao\LeadDao;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ClientSideController extends AbstractController
{
    #[Route('/', name: 'front_home', methods: ['GET'])]
    public function index(): Response
    {
        $slug = $this->proDao->findFirstLinkSlug();
        if ($slug === null) {
            throw $this->createNotFoundException('No professional found');
        }
        return $this->redirectToRoute('front_pro_home', ['proLinkSlug' => $slug]);
    }
    public function __construct(
        private readonly ProDao $proDao,
        private readonly LeadDao $leadDao,
    ) {}

    #[Route('/{proLinkSlug}', name:'front_pro_home', methods: ['GET','POST'])]
    public function home(string $proLinkSlug, Request $request): Response
    {
        $proId = $this->proDao->findIdByLinkSlug($proLinkSlug);

        if ($request->isMethod('POST') && $proId) {
            $firstname = trim((string) $request->request->get('firstname', ''));
            $lastname  = trim((string) $request->request->get('lastname', ''));
            $phoneCc   = trim((string) $request->request->get('phone_cc', '+33')) ?: '+33';
            $phoneLocal= trim((string) $request->request->get('phone', ''));

            if ($firstname !== '' && $lastname !== '' && $phoneCc !== '' && $phoneLocal !== '') {
                // Normalize French numbers: accept 9 digits (no 0) or 10 digits with leading 0
                $digits = preg_replace('/\D+/', '', $phoneLocal) ?? '';
                $normalizedLocal = null;
                if (preg_match('/^[1-9][0-9]{8}$/', $digits)) {
                    $normalizedLocal = $digits;
                } elseif (preg_match('/^0[1-9][0-9]{8}$/', $digits)) {
                    $normalizedLocal = substr($digits, 1);
                }

                if ($normalizedLocal === null) {
                    $this->addFlash('error', 'Numéro invalide. Saisissez 9 chiffres (sans 0) ou 10 (avec 0).');
                    return $this->redirectToRoute('front_pro_home', ['proLinkSlug' => $proLinkSlug]);
                }

                $phoneFull = trim($phoneCc . ' ' . $normalizedLocal);
                try {
                    $this->leadDao->create((int) $proId, $firstname, $lastname, $phoneFull);
                    $this->addFlash('success', 'Vos coordonnées ont été envoyées. Le professionnel vous recontactera.');
                } catch (\Throwable $e) {
                    $this->addFlash('error', 'Une erreur est survenue. Merci de réessayer.');
                }
            } else {
                $this->addFlash('error', 'Veuillez remplir prénom, nom et téléphone.');
            }

            return $this->redirectToRoute('front_pro_home', ['proLinkSlug' => $proLinkSlug]);
        }

        return $this->render('front_pro/home.html.twig', ['pro_id' => $proId]);
    }
}
