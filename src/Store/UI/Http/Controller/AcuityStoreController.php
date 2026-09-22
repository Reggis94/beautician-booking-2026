<?php

namespace App\Store\UI\Http\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/store/acuity-banner-templates', name: 'store_acuity_banner_templates', methods: ['GET'])]
final class AcuityStoreController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('landing/store_acuity_banner_templates.html.twig', [
            'banner_packs_api_url' => $this->generateUrl('api_store_acuity_banner_packs'),
        ]);
    }
}
