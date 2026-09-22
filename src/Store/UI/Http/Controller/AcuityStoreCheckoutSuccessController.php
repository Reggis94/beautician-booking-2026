<?php

namespace App\Store\UI\Http\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/store/acuity-banner-templates/success', name: 'store_acuity_banner_templates_checkout_success', methods: ['GET'])]
final class AcuityStoreCheckoutSuccessController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('landing/store_acuity_banner_templates_success.html.twig', [
            'store_url' => $this->generateUrl('store_acuity_banner_templates'),
        ]);
    }
}
