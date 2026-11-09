<?php

namespace App\Services\Application\ImageFile;

/**
 * Filesystem changes made during one catalog write, so they can be undone if the transaction fails.
 */
final class ServiceImageFileJournal
{
    /**
     * @var list<array{proId: int, fileName: string}>
     */
    private array $stored = [];

    /**
     * @var list<array{proId: int, fileName: string}>
     */
    private array $movedToDeleted = [];

    public function recordStored(int $proId, string $fileName): void
    {
        $this->stored[] = ['fileName' => $fileName, 'proId' => $proId];
    }

    public function recordMovedToDeleted(int $proId, string $fileName): void
    {
        $this->movedToDeleted[] = ['fileName' => $fileName, 'proId' => $proId];
    }

    /**
     * @return list<array{proId: int, fileName: string}>
     */
    public function getStored(): array
    {
        return $this->stored;
    }

    /**
     * @return list<array{proId: int, fileName: string}>
     */
    public function getMovedToDeleted(): array
    {
        return $this->movedToDeleted;
    }
}
