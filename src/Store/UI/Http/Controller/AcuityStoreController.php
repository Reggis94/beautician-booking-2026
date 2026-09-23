<?php

namespace App\Store\UI\Http\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/', name: 'store_acuity_banner_templates_home', methods: ['GET'])]
#[Route('/acuity-template-store', name: 'store_acuity_banner_templates', methods: ['GET'], priority: 20)]
final class AcuityStoreController extends AbstractController
{
    private const DEMO_URL_TEMPLATE_PLACEHOLDER = 'TEMPLATE_ID_PLACEHOLDER';

    public function __invoke(): Response
    {
        return $this->render('landing/store_acuity_banner_templates.html.twig', [
            'banner_packs_api_url' => $this->generateUrl('api_store_acuity_banner_packs'),
            'checkout_url' => $this->generateUrl('store_acuity_banner_templates_checkout'),
            'demo_url_template' => $this->generateUrl(
                'store_acuity_template_demo',
                ['idTemplate' => self::DEMO_URL_TEMPLATE_PLACEHOLDER]
            ),
            'demo_url_template_placeholder' => self::DEMO_URL_TEMPLATE_PLACEHOLDER,
        ]);
    }
}
