<?php

namespace App\Services\Application\Service\CommandHandler;

use App\Services\Application\Exception\ServiceCategoryNotFoundException;
use App\Services\Application\Exception\ServiceNotFoundException;
use App\Services\Application\ImageFile\ServiceImageFileJournal;
use App\Services\Application\ImageFile\ServiceImageFiles;
use App\Services\Application\Service\Command\UpdateServiceCommand;
use App\Services\Domain\Exception\ServiceImageLimitExceededException;
use App\Services\Domain\Repository\ServiceCategoryRepositoryInterface;
use App\Services\Domain\Repository\ServiceRepositoryInterface;
use App\Services\Domain\Service\ServiceNewVersionDecision;
use App\Services\Domain\ValueObject\ImageUpload;
use App\Services\Domain\ValueObject\ServiceContent;

/**
 * One editing flow for every service, with or without appointment history.
 *
 * Content changes (name, description, price, duration, category) replace a service that has appointments
 * and update one that has none. Publication and image changes always apply to the not deleted version, without
 * creating a new one. Images are not versioned: a replacement takes over the active images, and removed
 * images have their file moved to the deleted folder.
 */
final class UpdateServiceCommandHandler
{
    public function __construct(
        private readonly ServiceRepositoryInterface $services,
        private readonly ServiceCategoryRepositoryInterface $categories,
        private readonly ServiceImageFiles $imageFiles
    ) {
    }

    /**
     * @param list<ImageUpload> $uploads
     *
     * @throws ServiceNotFoundException
     * @throws ServiceCategoryNotFoundException
     * @throws ServiceImageLimitExceededException
     */
    public function __invoke(UpdateServiceCommand $command, int $proId, array $uploads): void
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $journal = new ServiceImageFileJournal();

        $this->services->beginTransaction();

        try {
            $this->services->lockCatalog($proId);

            $service = $this->services->findNotDeleted((int) $command->serviceId, $proId, withAppointmentCount: true);
            if ($service === null) {
                throw new ServiceNotFoundException();
            }

            if (
                $command->categoryId !== null
                && $this->categories->findNotDeleted($command->categoryId, $proId) === null
            ) {
                throw new ServiceCategoryNotFoundException();
            }

            $keptImageIds = $command->getKeptImageIds();
            $removedImages = [];
            foreach ($service->getActiveImages() as $image) {
                if (!in_array($image->getId(), $keptImageIds, true)) {
                    $removedImages[] = $image;
                }
            }

            $newContent = new ServiceContent(
                $command->name,
                $command->description,
                $command->durationMin,
                $command->priceCents,
                $command->categoryId
            );

            $newVersion = null;
            if (ServiceNewVersionDecision::isRequired($service, $newContent)) {
                $newVersion = $service->replaceWith($newContent, $now);
            } else {
                $service->changeContent($newContent);
            }

            // Publication and images apply to the version that stays in use, new or not.
            $current = $newVersion ?? $service;

            $current->changePublication($command->published, $now);

            foreach ($removedImages as $image) {
                $current->detachImage((int) $image->getId(), $now);
            }

            // Checked before storing anything, so a refused request leaves no file behind.
            if (count($current->getActiveImages()) + count($uploads) > $current::MAX_ACTIVE_IMAGES) {
                throw new ServiceImageLimitExceededException();
            }

            foreach ($uploads as $upload) {
                $current->attachImage($this->imageFiles->store($proId, $upload, $journal));
            }

            // The old version is saved first because the new one references it.
            $this->services->save($service);
            if ($newVersion !== null) {
                $this->services->save($newVersion);
            }

            foreach ($removedImages as $image) {
                $this->imageFiles->moveToDeleted($proId, $image->getFileName(), $journal);
            }

            $this->services->commit();
        } catch (\Throwable $exception) {
            $this->services->rollBack();
            $this->imageFiles->undo($journal);

            throw $exception;
        }
    }
}
