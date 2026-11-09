<?php

namespace App\Services\Application\Port;

/**
 * Physical storage of service image files (partial hexagonal port).
 *
 * Active files are stored in services/<pro-id>/<file-name>, one folder per professional (none per service).
 * Deleted files of every professional are moved to the shared services/deleted/ folder; file names are
 * unique across professionals. Implementations never remove a file.
 */
interface ServiceImageStorageInterface
{
    /**
     * Copies the source file into the professional's folder.
     */
    public function store(string $sourcePath, int $proId, string $fileName): void;

    /**
     * Moves an active file of the professional to the deleted folder.
     */
    public function moveToDeleted(int $proId, string $fileName): void;

    /**
     * Moves a file back from the deleted folder to the professional's folder (used to undo a move when a
     * transaction fails).
     */
    public function restore(int $proId, string $fileName): void;

    /**
     * Absolute path of an active file of the professional, or null when the name is invalid or the file is missing.
     */
    public function absolutePath(int $proId, string $fileName): ?string;
}
