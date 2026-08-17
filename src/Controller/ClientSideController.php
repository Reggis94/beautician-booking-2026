<?php

namespace App\Controller;

use App\Dao\ProDao;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ClientSideController extends AbstractController
{
    public function __construct(
        private readonly ProDao $proDao,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    //Route deactivated because the route which is going to be used as home page is /glam4style
    // #[Route('/', name: 'front_home', methods: ['GET'])]
    // public function index(): Response
    // {
    //     $slug = $this->proDao->findFirstLinkSlug();
    //     if ($slug === null) {
    //         throw $this->createNotFoundException('No professional found');
    //     }

    //     return $this->redirectToRoute('front_pro_home', ['proLinkSlug' => $slug]);
    // }

    #[Route('/demo/pro', name: 'demo_front_pro_home', methods: ['GET'])]
    public function demoHome(): Response
    {
        return $this->render('front_pro/demo_home.html.twig', [
            'banner_urls' => $this->getDemoBannerUrls(),
            'demo_admin_url' => $this->generateUrl('demo_pro_admin_dashboard'),
            'demo_service_categories' => $this->fakeDemoServiceCategories(),
        ]);
    }

    #[Route('/fr/demo/pro', name: 'demo_front_pro_home_fr', methods: ['GET'])]
    public function demoHomeFr(): Response
    {
        return $this->render('front_pro/fr/demo_home.html.twig', [
            'banner_urls' => $this->getDemoBannerUrls(),
            'demo_admin_url' => $this->generateUrl('demo_pro_admin_calendar_fr'),
            'demo_service_categories' => $this->fakeDemoServiceCategoriesFr(),
            'page_locale' => 'fr',
        ]);
    }

    #[Route(
        '/fr/demo/pro/{username}',
        name: 'demo_front_pro_home_specific_user_fr',
        requirements: ['username' => '[a-zA-Z0-9_-]+'],
        methods: ['GET']
    )]
    public function demoHomeSpecificUserFr(string $username): Response
    {
        return $this->render('front_pro/fr/demo_home.html.twig', [
            'banner_urls' => $this->getDemoBannerUrls($username),
            'demo_admin_url' => $this->generateUrl('demo_pro_admin_calendar_fr', [
                'username' => $username,
            ]),
            'demo_service_categories' => $this->fakeDemoServiceCategoriesFr(),
            'page_locale' => 'fr',
        ]);
    }

    #[Route(
        '/demo/pro/{username}',
        name: 'demo_front_pro_home_specific_user',
        requirements: ['username' => '[a-zA-Z0-9_-]+'],
        methods: ['GET']
    )]
    public function demoHomeSpecificUser(string $username): Response
    {
        return $this->render('front_pro/demo_home.html.twig', [
            'banner_urls' => $this->getDemoBannerUrls($username),
            'demo_admin_url' => $this->generateUrl('demo_pro_admin_dashboard', [
                'username' => $username,
            ]),
            'demo_service_categories' => $this->fakeDemoServiceCategories(),
        ]);
    }

    #[Route('/checkout', name: 'g4s_checkout', methods: ['GET'], priority: 20)]
    public function checkout(): Response
    {
        return $this->render('landing/checkout_pending.html.twig', [
            'etsy_url' => null,
            'landing_url' => $this->generateUrl('glam4style_landing_page'),
        ]);
    }

    #[Route('/', name: 'glam4style_home', methods: ['GET'])]
    #[Route('/glam4style', name: 'glam4style_landing_page', methods: ['GET'])]
    public function glam4StyleLandingPage(): Response
    {
        return $this->render('landing/glam4style.html.twig', [
            'checkout_url' => $this->generateUrl('g4s_checkout'),
            'demo_dashboard_url' => $this->generateUrl('demo_front_pro_home'),
        ]);
    }

    #[Route(
        '/demo/pro-banners/{bannerFile}',
        name: 'demo_front_pro_banner',
        requirements: ['bannerFile' => '\d+\.png'],
        methods: ['GET'],
        priority: 20
    )]
    public function demoBanner(string $bannerFile): BinaryFileResponse
    {
        return $this->serveBanner($this->getDemoBannerPath($bannerFile));
    }

    #[Route(
        '/demo/pro-banners/{username}/{bannerFile}',
        name: 'demo_front_pro_specific_user_banner',
        requirements: ['username' => '[a-zA-Z0-9_-]+', 'bannerFile' => '\d+\.png'],
        methods: ['GET'],
        priority: 20
    )]
    public function demoSpecificUserBanner(string $username, string $bannerFile): BinaryFileResponse
    {
        return $this->serveBanner($this->getDemoBannerPath($bannerFile, $username));
    }

    #[Route(
        '/pro-banners/{proId}/{bannerFile}',
        name: 'front_pro_banner',
        requirements: ['proId' => '\d+', 'bannerFile' => '\d+\.png'],
        methods: ['GET'],
        priority: 10,
        env: 'dev'
    )]
    public function banner(int $proId, string $bannerFile): BinaryFileResponse
    {
        $bannerPath = $this->getProBannerPath($proId, $bannerFile);

        if (!is_file($bannerPath)) {
            throw $this->createNotFoundException('Banner not found');
        }

        $response = new BinaryFileResponse($bannerPath);
        $response->headers->set('Cache-Control', 'public, max-age=600');
        $response->headers->set('Content-Type', 'image/png');

        return $response;
    }

    #[Route('/{proLinkSlug}', name: 'front_pro_home', methods: ['GET'], env: 'dev')]
    public function home(string $proLinkSlug): Response
    {
        $proId = $this->proDao->findIdByLinkSlug($proLinkSlug);

        return $this->render('front_pro/home.html.twig', [
            'banner_urls' => $this->getProBannerUrls($proId),
            'pro_id' => $proId,
        ]);
    }

    /**
     * @return list<string>
     */
    private function getProBannerUrls(?int $proId): array
    {
        if ($proId === null) {
            return [];
        }

        $bannerDirectory = dirname($this->projectDir) . DIRECTORY_SEPARATOR . 'pro-banners'
            . DIRECTORY_SEPARATOR . $proId;
        $bannerPaths = glob($bannerDirectory . DIRECTORY_SEPARATOR . '*.png') ?: [];
        $bannerPaths = array_values(array_filter(
            $bannerPaths,
            static fn (string $path): bool => preg_match('/^\d+\.png$/', basename($path)) === 1
        ));

        usort(
            $bannerPaths,
            static fn (string $first, string $second): int => (int) basename($first, '.png')
                <=> (int) basename($second, '.png')
        );

        return array_map(
            fn (string $path): string => $this->generateUrl('front_pro_banner', [
                'bannerFile' => basename($path),
                'proId' => $proId,
            ]),
            $bannerPaths
        );
    }

    /**
     * @return list<string>
     */
    private function getDemoBannerUrls(string $username = ''): array
    {
        $bannerDirectory = $this->getDemoBannerDirectory($username);
        $bannerPaths = glob($bannerDirectory . DIRECTORY_SEPARATOR . '*.png') ?: [];
        $bannerPaths = array_values(array_filter(
            $bannerPaths,
            static fn (string $path): bool => preg_match('/^\d+\.png$/', basename($path)) === 1
        ));

        usort(
            $bannerPaths,
            static fn (string $first, string $second): int => (int) basename($first, '.png')
                <=> (int) basename($second, '.png')
        );

        $routeName = $username === ''
            ? 'demo_front_pro_banner'
            : 'demo_front_pro_specific_user_banner';

        return array_map(function (string $path) use ($routeName, $username): string {
            $parameters = ['bannerFile' => basename($path)];

            if ($username !== '') {
                $parameters['username'] = $username;
            }

            return $this->generateUrl($routeName, $parameters);
        }, $bannerPaths);
    }

    private function getProBannerPath(int $proId, string $bannerFile): string
    {
        return dirname($this->projectDir) . DIRECTORY_SEPARATOR . 'pro-banners'
            . DIRECTORY_SEPARATOR . $proId . DIRECTORY_SEPARATOR . $bannerFile;
    }

    private function getDemoBannerPath(string $bannerFile, string $username = ''): string
    {
        return $this->getDemoBannerDirectory($username) . DIRECTORY_SEPARATOR . $bannerFile;
    }

    private function getDemoBannerDirectory(string $username = ''): string
    {
        return dirname($this->projectDir) . DIRECTORY_SEPARATOR . 'pro-banners'
            . DIRECTORY_SEPARATOR . 'demo' . DIRECTORY_SEPARATOR . $username;
    }

    private function serveBanner(string $bannerPath): BinaryFileResponse
    {
        if (!is_file($bannerPath)) {
            throw $this->createNotFoundException('Banner not found');
        }

        $response = new BinaryFileResponse($bannerPath);
        $response->headers->set('Cache-Control', 'public, max-age=600');
        $response->headers->set('Content-Type', 'image/png');

        return $response;
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

    /**
     * @return list<array{name: string, services: list<array{id: int, name: string, description: string, durationMinutes: int, price: string}>}>
     */
    private function fakeDemoServiceCategoriesFr(): array
    {
        return [
            [
                'name' => 'Soins du visage',
                'services' => [[
                    'description' => 'Un soin sur mesure avec nettoyage doux, exfoliation et finition hydratante pour un teint lumineux.',
                    'durationMinutes' => 60,
                    'id' => 301,
                    'name' => 'Soin du visage signature',
                    'price' => '95',
                ]],
            ],
            [
                'name' => 'Sourcils et cils',
                'services' => [[
                    'description' => 'Une mise en forme précise pour définir les sourcils tout en conservant un résultat naturel et soigné.',
                    'durationMinutes' => 45,
                    'id' => 302,
                    'name' => 'Restructuration des sourcils',
                    'price' => '42',
                ]],
            ],
            [
                'name' => 'Coiffure et maquillage',
                'services' => [
                    [
                        'description' => 'Un soin gloss et un coiffage conçus pour lisser les cheveux, renforcer leur brillance et parfaire le résultat.',
                        'durationMinutes' => 90,
                        'id' => 303,
                        'name' => 'Gloss et coiffage',
                        'price' => '135',
                    ],
                    [
                        'description' => 'Un maquillage personnalisé pour une finition élégante, adaptée à la cliente et à l’occasion.',
                        'durationMinutes' => 75,
                        'id' => 304,
                        'name' => 'Séance de maquillage',
                        'price' => '120',
                    ],
                ],
            ],
        ];
    }
}
