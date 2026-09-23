<?php

namespace App\Store\UI\Http\Controller;

use App\Store\Application\Dao\BannerPackDaoInterface;
use App\Store\Application\Exception\BannerDirectoryNotConfiguredException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/acuity-template-store/template/{idTemplate}',
    name: 'store_acuity_template_demo',
    requirements: ['idTemplate' => '[A-Za-z0-9_-]+'],
    methods: ['GET']
)]
final class AcuityTemplateDemoController extends AbstractController
{
    public function __construct(
        private readonly BannerPackDaoInterface $bannerPackDao,
    ) {
    }

    public function __invoke(string $idTemplate): Response
    {
        try {
            $packs = $this->bannerPackDao->findAll();
        } catch (BannerDirectoryNotConfiguredException $exception) {
            throw $this->createNotFoundException($exception->getMessage());
        }

        $pack = null;
        foreach ($packs as $candidate) {
            if ($candidate->id === $idTemplate) {
                $pack = $candidate;
                break;
            }
        }

        if ($pack === null) {
            throw $this->createNotFoundException('Template not found');
        }

        $bannerUrls = [];
        foreach ($pack->banners as $bannerFile) {
            $bannerUrls[] = $this->generateUrl(
                'store_acuity_banner_file',
                ['packId' => $pack->id, 'bannerFile' => $bannerFile]
            );
        }

        return $this->render('landing/store_acuity_template_demo.html.twig', [
            'template_id' => $pack->id,
            'banner_urls' => $bannerUrls,
            'store_url' => $this->generateUrl('store_acuity_banner_templates'),
            'checkout_url' => $this->generateUrl('store_acuity_banner_templates_checkout'),
            'demo_service_categories' => $this->fakeDemoServiceCategories(),
        ]);
    }

    /**
     * @return list<array{name: string, services: list<array{id: int, name: string, description: string, durationMinutes: int, price: string}>}>
     */
    private function fakeDemoServiceCategories(): array
    {
        return [
            [
                'name' => 'Face care',
                'services' => [
                    [
                        'description' => 'A tailored skin refresh with gentle cleansing, exfoliation, and finishing care for a soft glow.',
                        'durationMinutes' => 60,
                        'id' => 301,
                        'name' => 'Signature facial',
                        'price' => '95',
                    ],
                ],
            ],
            [
                'name' => 'Brows and lashes',
                'services' => [
                    [
                        'description' => 'Precise shaping and detailing to define the brows while keeping a natural, polished look.',
                        'durationMinutes' => 45,
                        'id' => 302,
                        'name' => 'Brow shaping',
                        'price' => '42',
                    ],
                ],
            ],
            [
                'name' => 'Hair and makeup',
                'services' => [
                    [
                        'description' => 'Gloss treatment and styling designed to smooth the hair, boost shine, and finish the look.',
                        'durationMinutes' => 90,
                        'id' => 303,
                        'name' => 'Hair gloss and styling',
                        'price' => '135',
                    ],
                    [
                        'description' => 'Personalized makeup application for an elegant finish suited to the client and occasion.',
                        'durationMinutes' => 75,
                        'id' => 304,
                        'name' => 'Makeup session',
                        'price' => '120',
                    ],
                ],
            ],
        ];
    }
}
