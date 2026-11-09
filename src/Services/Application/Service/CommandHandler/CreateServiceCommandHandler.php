<?php

namespace App\Services\Application\Service\CommandHandler;

use App\Services\Application\Exception\ServiceCategoryNotFoundException;
use App\Services\Application\ImageFile\ServiceImageFileJournal;
use App\Services\Application\ImageFile\ServiceImageFiles;
use App\Services\Application\Service\Command\CreateServiceCommand;
use App\Services\Domain\Entity\Service;
use App\Services\Domain\Exception\ServiceImageLimitExceededException;
use App\Services\Domain\Repository\ServiceCategoryRepositoryInterface;
use App\Services\Domain\Repository\ServiceRepositoryInterface;
use App\Services\Domain\ValueObject\ImageUpload;
use App\Services\Domain\ValueObject\ServiceContent;

final class CreateServiceCommandHandler
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
     * @throws ServiceCategoryNotFoundException
     * @throws ServiceImageLimitExceededException
     */
    public function __invoke(CreateServiceCommand $command, int $proId, array $uploads): void
    {
        if (count($uploads) > Service::MAX_ACTIVE_IMAGES) {
            throw new ServiceImageLimitExceededException();
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $journal = new ServiceImageFileJournal();

        $this->services->beginTransaction();

        try {
            $this->services->lockCatalog($proId);

            if (
                $command->categoryId !== null
                && $this->categories->findNotDeleted($command->categoryId, $proId) === null
            ) {
                throw new ServiceCategoryNotFoundException();
            }

            $service = Service::create(
                $proId,
                new ServiceContent(
                    $command->name,
                    $command->description,
                    $command->durationMin,
                    $command->priceCents,
                    $command->categoryId
                ),
                $command->published,
                $now
            );

            foreach ($uploads as $upload) {
                $service->attachImage($this->imageFiles->store($proId, $upload, $journal));
            }

            $this->services->save($service);
            $this->services->commit();
        } catch (\Throwable $exception) {
            $this->services->rollBack();
            $this->imageFiles->undo($journal);

            throw $exception;
        }
    }
}
