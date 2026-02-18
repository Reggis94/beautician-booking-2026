<?php

namespace App\ProfilePro\Application\Banner\CommandHandler;

use App\ProfilePro\Application\Banner\Command\CreateUploadBannerImagesCommand;
use App\ProfilePro\Application\Banner\File\BannerFileManagerInterface;
use App\ProfilePro\Application\Banner\File\BannerImageMetadata;
use App\ProfilePro\Application\Banner\Repository\ProBannerRepositoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class CreateUploadBannerImagesCommandHandler
{
    public function __construct(
        private readonly ProBannerRepositoryInterface $proBannerRepository,
        private readonly BannerFileManagerInterface $bannerFileManager
    ) {
    }

    public function __invoke(CreateUploadBannerImagesCommand $command): void
    {
        $dto = $command->getDto();
        $proId = (int) $dto->proId;
        $images = $dto->images;
        $orderNumbers = $dto->orderNumbers;

        if ($images === []) {
            return;
        }

        $this->proBannerRepository->assertProExists($proId);
        if ($this->proBannerRepository->countActiveForPro($proId) > 0) {
            throw new \DomainException('banners already exist for this pro.');
        }

        $preparedImages = [];
        foreach ($images as $image) {
            if (!$image instanceof UploadedFile) {
                throw new \InvalidArgumentException('each item must be an uploaded file.');
            }

            $preparedImages[] = $image;
        }

        if (count($preparedImages) > 20) {
            throw new \DomainException('a pro can only have up to 20 banner images.');
        }

        if (count($preparedImages) !== count($orderNumbers)) {
            throw new \DomainException('order_numbers must have the same number of items as images.');
        }

        if (count(array_unique($orderNumbers)) !== count($orderNumbers)) {
            throw new \DomainException('order_numbers must be unique.');
        }

        foreach ($orderNumbers as $orderNumber) {
            if (!is_int($orderNumber) || $orderNumber < 1 || $orderNumber > 20) {
                throw new \DomainException('each order number must be between 1 and 20.');
            }
        }

        $preparedImageEntries = [];
        foreach ($preparedImages as $index => $image) {
            $preparedImageEntries[] = [
                'file' => $image,
                'orderNumber' => $orderNumbers[$index],
            ];
        }

        $commitId = $this->generateCommitId();
        $this->bannerFileManager->stageAll($preparedImageEntries, $proId, $commitId);
        $stagedMetadata = $this->bannerFileManager->getStagedMetadata();
        if (count($stagedMetadata) !== count($preparedImageEntries)) {
            throw new \RuntimeException('Staged banner metadata is incomplete.');
        }

        $sortedMetadata = $this->sortAndValidateMetadataForCommit($stagedMetadata, $proId, $commitId);
        $this->bannerFileManager->publishStaged();

        $transactionStarted = false;
        try {
            $this->proBannerRepository->beginTransaction();
            $transactionStarted = true;

            $this->proBannerRepository->assertProExistsForUpdate($proId);
            $activeBanners = $this->proBannerRepository->countActiveForPro($proId);
            if ($activeBanners > 0) {
                throw new \DomainException('banners already exist for this pro.');
            }

            foreach ($sortedMetadata as $metadata) {
                if (!$metadata instanceof BannerImageMetadata) {
                    throw new \RuntimeException('Invalid staged banner metadata.');
                }

                $this->proBannerRepository->create(
                    $proId,
                    $metadata->orderNumber,
                    $metadata->finalKey,
                    $commitId
                );
            }

            $this->proBannerRepository->commit();
            $transactionStarted = false;
        } catch (\Throwable $exception) {
            if ($transactionStarted) {
                $this->proBannerRepository->rollBack();
            }

            throw $exception;
        }
    }

    private function generateCommitId(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * @param BannerImageMetadata[] $stagedMetadata
     * @return BannerImageMetadata[]
     */
    private function sortAndValidateMetadataForCommit(array $stagedMetadata, int $proId, string $commitId): array
    {
        $commitPrefix = $proId . '/' . $commitId . '/';

        foreach ($stagedMetadata as $metadata) {
            if (!$metadata instanceof BannerImageMetadata) {
                throw new \RuntimeException('Invalid staged banner metadata.');
            }

            if (!str_starts_with($metadata->finalKey, $commitPrefix)) {
                throw new \RuntimeException('Invalid staged banner commit mapping.');
            }
        }
        
        usort(
            $stagedMetadata,
            static fn (BannerImageMetadata $left, BannerImageMetadata $right): int => $left->orderNumber <=> $right->orderNumber
        );

        return $stagedMetadata;
    }
}
