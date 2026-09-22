<?php

namespace App\Store\UI\Http\Controller;

use App\Store\Application\Dao\BannerPackDaoInterface;
use App\Store\Application\Exception\BannerDirectoryNotConfiguredException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/api/store/acuity-banner-packs', name: 'api_store_acuity_banner_packs', methods: ['GET'])]
final class AcuityBannerPacksApiController
{
    public function __construct(
        private readonly BannerPackDaoInterface $bannerPackDao,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        try {
            $packs = $this->bannerPackDao->findAll();
        } catch (BannerDirectoryNotConfiguredException) {
            return new JsonResponse(
                ['error' => 'The banner template folder is not configured.'],
                JsonResponse::HTTP_SERVICE_UNAVAILABLE
            );
        }

        $packsData = [];
        foreach ($packs as $pack) {
            $bannerUrls = [];
            foreach ($pack->banners as $bannerFile) {
                $bannerUrls[] = $this->urlGenerator->generate(
                    'store_acuity_banner_file',
                    ['packId' => $pack->id, 'bannerFile' => $bannerFile]
                );
            }

            $packsData[] = [
                'id' => $pack->id,
                'name' => sprintf('Template %s', $pack->id),
                'banners' => $bannerUrls,
            ];
        }

        return new JsonResponse(['packs' => $packsData]);
    }
}
