<?php

namespace App\Store\UI\Http\Controller;

use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/store/acuity-banner-templates/checkout', name: 'store_acuity_banner_templates_checkout', methods: ['POST'])]
final class AcuityStoreCheckoutController extends AbstractController
{
    private const TEMPLATE_PRICE_USD_CENTS = 1900;

    public function __construct(
        private readonly StripeClient $stripeClient,
    ) {
    }

    public function __invoke(Request $request): RedirectResponse
    {
        $templateId = (string) $request->request->get('template_id');

        $session = $this->stripeClient->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => self::TEMPLATE_PRICE_USD_CENTS,
                    'product_data' => [
                        'name' => sprintf('Acuity banner template "%s"', $templateId),
                    ],
                ],
            ]],
            'success_url' => $this->generateUrl(
                'store_acuity_banner_templates_checkout_success',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'cancel_url' => $this->generateUrl(
                'store_acuity_banner_templates',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ]);

        return new RedirectResponse($session->url, RedirectResponse::HTTP_SEE_OTHER);
    }
}
