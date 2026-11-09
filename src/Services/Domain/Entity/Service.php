<?php

namespace App\Services\Domain\Entity;

use App\Services\Domain\Exception\ServiceImageLimitExceededException;
use App\Services\Domain\ValueObject\DeletionReason;
use App\Services\Domain\ValueObject\ServiceContent;

/**
 * One version of a service offered by a professional (aggregate root, with its image associations).
 *
 * States: draft (no publication date), published, deleted (a deletion date, whatever the publication date).
 * A version referenced by appointments is never overwritten: it is replaced (see ServiceNewVersionDecision),
 * so appointments keep the content they were booked with.
 */
final class Service
{
    public const MAX_ACTIVE_IMAGES = 2;

    /**
     * @param list<ServiceImage> $images
     */
    private function __construct(
        private ?int $id,
        private readonly int $proId,
        private ServiceContent $content,
        private ?\DateTimeImmutable $publishedAt,
        private ?\DateTimeImmutable $deletedAt,
        private ?DeletionReason $deletionReason,
        private readonly ?int $previousVersionId,
        private array $images,
        private readonly ?int $appointmentCount
    ) {
    }

    public static function create(
        int $proId,
        ServiceContent $content,
        bool $published,
        \DateTimeImmutable $now
    ): self {
        return new self(null, $proId, $content, $published ? $now : null, null, null, null, [], 0);
    }

    /**
     * @param list<ServiceImage> $images
     * @param int|null $appointmentCount Null when the service was loaded without its appointment count.
     */
    public static function fromPersistence(
        int $id,
        int $proId,
        ServiceContent $content,
        ?\DateTimeImmutable $publishedAt,
        ?\DateTimeImmutable $deletedAt,
        ?DeletionReason $deletionReason,
        ?int $previousVersionId,
        array $images,
        ?int $appointmentCount
    ): self {
        return new self(
            $id,
            $proId,
            $content,
            $publishedAt,
            $deletedAt,
            $deletionReason,
            $previousVersionId,
            $images,
            $appointmentCount
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function identify(int $id): void
    {
        if ($this->id !== null) {
            throw new \LogicException('A persisted service cannot change its id.');
        }

        $this->id = $id;
    }

    public function getProId(): int
    {
        return $this->proId;
    }

    public function getContent(): ServiceContent
    {
        return $this->content;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function getDeletionReason(): ?DeletionReason
    {
        return $this->deletionReason;
    }

    public function getPreviousVersionId(): ?int
    {
        return $this->previousVersionId;
    }

    /**
     * @return list<ServiceImage>
     */
    public function getImages(): array
    {
        return $this->images;
    }

    /**
     * @return list<ServiceImage>
     */
    public function getActiveImages(): array
    {
        $activeImages = [];
        foreach ($this->images as $image) {
            if ($image->isActive()) {
                $activeImages[] = $image;
            }
        }

        return $activeImages;
    }

    /**
     * True when deleted_at is null: draft or published, neither explicitly deleted nor replaced.
     */
    public function isNotDeleted(): bool
    {
        return $this->deletedAt === null;
    }

    /**
     * Every appointment counts: past, future, cancelled and soft-deleted.
     *
     * @throws \LogicException When the service was loaded without its appointment count: answering false would
     *                         let a booked service be overwritten.
     */
    public function hasAppointments(): bool
    {
        if ($this->appointmentCount === null) {
            throw new \LogicException('This service was loaded without its appointment count.');
        }

        return $this->appointmentCount > 0;
    }

    public function isPublished(): bool
    {
        return $this->isNotDeleted() && $this->publishedAt !== null;
    }

    /**
     * Overwrites the content in place. Only valid when ServiceNewVersionDecision::isRequired() is false.
     */
    public function changeContent(ServiceContent $content): void
    {
        $this->assertNotDeleted();
        $this->content = $content;
    }

    /**
     * Publication never creates a new version.
     */
    public function changePublication(bool $published, \DateTimeImmutable $now): void
    {
        $this->assertNotDeleted();

        if (!$published) {
            $this->publishedAt = null;

            return;
        }

        if ($this->publishedAt === null) {
            $this->publishedAt = $now;
        }
    }

    public function delete(\DateTimeImmutable $now): void
    {
        $this->assertNotDeleted();
        $this->deletedAt = $now;
        $this->deletionReason = DeletionReason::Deleted;
    }

    /**
     * Retires this version and returns its replacement with the new content.
     *
     * The replacement keeps the publication state and takes over the active images: images are not versioned,
     * so this version keeps none.
     */
    public function replaceWith(ServiceContent $content, \DateTimeImmutable $now): self
    {
        $this->assertNotDeleted();

        if ($this->id === null) {
            throw new \LogicException('Only a persisted service can be replaced.');
        }

        $replacement = new self(
            null,
            $this->proId,
            $content,
            $this->publishedAt,
            null,
            null,
            $this->id,
            $this->getActiveImages(),
            0
        );

        $this->images = [];
        $this->deletedAt = $now;
        $this->deletionReason = DeletionReason::Replaced;

        return $replacement;
    }

    /**
     * Image changes never create a new version.
     */
    public function attachImage(string $fileName): void
    {
        $this->assertNotDeleted();

        if (count($this->getActiveImages()) >= self::MAX_ACTIVE_IMAGES) {
            throw new ServiceImageLimitExceededException();
        }

        $this->images[] = ServiceImage::attach($fileName);
    }

    /**
     * Soft-deletes an active image of this service.
     */
    public function detachImage(int $imageId, \DateTimeImmutable $now): void
    {
        $this->assertNotDeleted();

        foreach ($this->getActiveImages() as $image) {
            if ($image->getId() === $imageId) {
                $image->detach($now);
            }
        }
    }

    private function assertNotDeleted(): void
    {
        if ($this->deletedAt !== null) {
            throw new \LogicException('A deleted or replaced service version cannot change.');
        }
    }
}
