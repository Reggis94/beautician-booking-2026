<?php

namespace App\Services\Application\ImageFile;

use App\Services\Application\Port\ServiceImageStorageInterface;
use App\Services\Domain\ValueObject\ImageUpload;
use Psr\Log\LoggerInterface;

/**
 * Keeps service image files on disk consistent with the service_image rows.
 *
 * Files are written or moved before the transaction commits. When the transaction fails, undo() moves new
 * uploads to the deleted folder and moves removed files back, so no row points to a missing file and no
 * file is ever removed.
 */
final class ServiceImageFiles
{
    public function __construct(
        private readonly ServiceImageStorageInterface $storage,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Stores an upload in the professional's folder under a new random name and returns that name.
     */
    public function store(int $proId, ImageUpload $upload, ServiceImageFileJournal $journal): string
    {
        $fileName = bin2hex(random_bytes(16)) . '.' . $upload->getExtension();
        $this->storage->store($upload->getSourcePath(), $proId, $fileName);
        $journal->recordStored($proId, $fileName);

        return $fileName;
    }

    /**
     * Moves the file of a removed image to the deleted folder.
     */
    public function moveToDeleted(int $proId, string $fileName, ServiceImageFileJournal $journal): void
    {
        $this->storage->moveToDeleted($proId, $fileName);
        $journal->recordMovedToDeleted($proId, $fileName);
    }

    /**
     * Reverts the filesystem changes of a failed transaction, after the database rollback.
     */
    public function undo(ServiceImageFileJournal $journal): void
    {
        foreach ($journal->getStored() as $file) {
            try {
                $this->storage->moveToDeleted($file['proId'], $file['fileName']);
            } catch (\Throwable $exception) {
                $this->logger->error('Could not move an orphan service image to the deleted folder.', [
                    'exception' => $exception,
                    'file_name' => $file['fileName'],
                ]);
            }
        }

        foreach ($journal->getMovedToDeleted() as $file) {
            try {
                $this->storage->restore($file['proId'], $file['fileName']);
            } catch (\Throwable $exception) {
                $this->logger->error('Could not restore a service image after a failed transaction.', [
                    'exception' => $exception,
                    'file_name' => $file['fileName'],
                ]);
            }
        }
    }
}
