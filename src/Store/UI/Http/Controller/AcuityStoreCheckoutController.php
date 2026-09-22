<?php

namespace App\Store\UI\Http\Controller;

use App\Store\Application\Dao\BannerPackDaoInterface;
use App\Store\Application\Exception\BannerDirectoryNotConfiguredException;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/store/acuity-banner-templates/checkout', name: 'store_acuity_banner_templates_checkout', methods: ['POST'])]
final class AcuityStoreCheckoutController extends AbstractController
{
    private const TEMPLATE_PRICE_USD_CENTS = 1900;

    public function __construct(
        private readonly StripeClient $stripeClient,
        private readonly BannerPackDaoInterface $bannerPackDao,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $templateId = (string) $request->request->get('template_id');

        try {
            $templateExists = $this->bannerPackDao->exists($templateId);
        } catch (BannerDirectoryNotConfiguredException $exception) {
            throw $this->createNotFoundException($exception->getMessage());
        }

        if (!$templateExists) {
            throw $this->createNotFoundException('Template not found');
        }

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

        return $this->render('landing/store_acuity_banner_templates_redirecting.html.twig', [
            'stripe_url' => $session->url,
        ]);
    }
}
