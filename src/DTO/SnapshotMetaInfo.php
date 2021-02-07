<?php

namespace CodeDistortion\Adapt\DTO;

use CodeDistortion\Adapt\Support\StringSupport as Str;
use DateTime;

/**
 * Store some meta-data about a snapshot file.
 */
class SnapshotMetaInfo
{
    /** @var string|null The snapshot's path. */
    public ?string $path;

    /** @var string|null The snapshot's filename. */
    public ?string $filename;

    /** @var DateTime|null When the file was last accessed. */
    public ?DateTime $accessDT;

    /** @var boolean Whether the snapshot is valid (current) on not. */
    public bool $isValid;

    /** @var callable The callback to use to get the snapshot's size. */
    public $getSizeCallback;

    /** @var integer|null The size of the snapshot file in bytes. */
    public ?int $size = null;

    /** @var callable The callback used to delete the snapshot file. */
    public $deleteCallback = null;



    /**
     * @param string        $path            The snapshot's path.
     * @param string        $filename        The snapshot's filename.
     * @param DateTime|null $accessDT        When the file was last accessed.
     * @param boolean       $isValid         Whether the snapshot is valid (current) on not.
     * @param callable      $getSizeCallback The callback to use to get the snapshot's size.
     */
    public function __construct(
        string $path,
        string $filename,
        ?DateTime $accessDT,
        bool $isValid,
        callable $getSizeCallback
    ) {
        $this->path = $path;
        $this->filename = $filename;
        $this->isValid = $isValid;
        $this->accessDT = $accessDT;
        $this->getSizeCallback = $getSizeCallback;
        return $this;
    }

    /**
     * Set the callback to delete the snapshot.
     *
     * @param callable $deleteCallback The callback to call.
     * @return $this
     */
    public function setDeleteCallback(callable $deleteCallback): self
    {
        $this->deleteCallback = $deleteCallback;
        return $this;
    }

    /**
     * Remove the snapshot if it should be removed.
     */
    public function purgeIfNeeded(): void
    {
        if ($this->shouldBePurged()) {
            $this->delete();
        }
    }

    /**
     * Determine if this snapshot should be purged or not.
     *
     * @return boolean
     */
    private function shouldBePurged(): bool
    {
        return !$this->isValid;
    }

    /**
     * Delete the snapshot.
     *
     * @return boolean
     */
    public function delete(): bool
    {
        return $this->deleteCallback ? ($this->deleteCallback)() : false;
    }

    /**
     * Get the snapshot's size.
     *
     * @return integer
     */
    public function getSize(): int
    {
        return $this->size ??= ($this->getSizeCallback)();
    }

    /**
     * Generate a readable version of this snapshot.
     *
     * @return string
     */
    public function readable(): string
    {
        return $this->path . ' ' . Str::readableSize($this->getSize());
    }
}
