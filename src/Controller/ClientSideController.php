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

    #[Route('/', name: 'front_home', methods: ['GET'])]
    public function index(): Response
    {
        $slug = $this->proDao->findFirstLinkSlug();
        if ($slug === null) {
            throw $this->createNotFoundException('No professional found');
        }

        return $this->redirectToRoute('front_pro_home', ['proLinkSlug' => $slug]);
    }

    #[Route(
        '/pro-banners/{proId}/{bannerFile}',
        name: 'front_pro_banner',
        requirements: ['proId' => '\d+', 'bannerFile' => '\d+\.png'],
        methods: ['GET'],
        priority: 10
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

    #[Route('/{proLinkSlug}', name: 'front_pro_home', methods: ['GET'])]
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

    private function getProBannerPath(int $proId, string $bannerFile): string
    {
        return dirname($this->projectDir) . DIRECTORY_SEPARATOR . 'pro-banners'
            . DIRECTORY_SEPARATOR . $proId . DIRECTORY_SEPARATOR . $bannerFile;
    }
}
