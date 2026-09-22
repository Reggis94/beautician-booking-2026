<?php

namespace App\Store\UI\Http\Controller;

use App\Store\Application\Dao\BannerPackDaoInterface;
use App\Store\Application\Exception\BannerDirectoryNotConfiguredException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/store/acuity-banners/{packId}/{bannerFile}',
    name: 'store_acuity_banner_file',
    requirements: [
        'packId' => '[A-Za-z0-9_-]+',
        'bannerFile' => '[A-Za-z0-9_-]+\.(png|jpe?g|webp|gif)',
    ],
    methods: ['GET']
)]
final class AcuityBannerFileController extends AbstractController
{
    public function __construct(
        private readonly BannerPackDaoInterface $bannerPackDao,
    ) {
    }

    public function __invoke(string $packId, string $bannerFile): Response
    {
        try {
            $path = $this->bannerPackDao->getBannerFilePath($packId, $bannerFile);
        } catch (BannerDirectoryNotConfiguredException $exception) {
            throw $this->createNotFoundException($exception->getMessage());
        }

        if (!is_file($path)) {
            throw $this->createNotFoundException('Banner not found');
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Cache-Control', 'public, max-age=600');

        return $response;
    }
}
