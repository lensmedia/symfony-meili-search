<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle\Index;

use ArrayIterator;
use InvalidArgumentException;
use Lens\Bundle\MeiliSearchBundle\Exception\IndexNotFound;
use Lens\Bundle\MeiliSearchBundle\MeiliSearchRepositoryInterface;
use Traversable;

trait IndexCollectionTrait
{
    /** @var Index[] */
    private array $collection;

    private iterable $repositories;

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->collection);
    }

    public function offsetExists(mixed $offset): bool
    {
        $offset = $this->removeIndexAffixes($offset);

        return isset($this->collection[$offset]);
    }

    /**
     * @return Index
     */
    public function offsetGet(mixed $offset): mixed
    {
        $offset = $this->removeIndexAffixes($offset);

        if (!$this->offsetExists($offset)) {
            throw new InvalidArgumentException(sprintf(
                'Index "%s" not found.',
                $offset,
            ));
        }

        return $this->collection[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!is_string($offset)) {
            throw new InvalidArgumentException(sprintf(
                'Offset must be a string "%s".',
                get_debug_type($offset),
            ));
        }

        if ($this->offsetExists($offset)) {
            throw new InvalidArgumentException(sprintf(
                'Index "%s" already exists.',
                $offset,
            ));
        }

        if (!($value instanceof Index)) {
            throw new InvalidArgumentException(sprintf(
                'Value must be an index "%s".',
                get_debug_type($value),
            ));
        }

        $this->collection[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->collection[$offset]);
    }

    public function count(): int
    {
        return count($this->collection);
    }

    // Custom
    public function isManagedIndex(string $index): bool
    {
        $index = $this->removeIndexAffixes($index);

        return $this->offsetExists($index);
    }

    public function index(string $index): Index
    {
        $index = $this->removeIndexAffixes($index);

        return $this->collection[$index] ?? throw new IndexNotFound($index);
    }

    public function context(string $index): array
    {
        $index = $this->removeIndexAffixes($index);

        return $this->collection[$index]->context ?? throw new IndexNotFound($index);
    }

    public function loadRepositories(iterable $repositories): void
    {
        $this->repositories = $repositories;

        foreach ($repositories as $repository) {
            $this->loadRepository($repository);
        }
    }

    public function loadRepository(MeiliSearchRepositoryInterface $repository): void
    {
        $this->mapRepositoryIndexesToId($repository);
    }

    /** @return MeiliSearchRepositoryInterface[] */
    public function repositories(): iterable
    {
        return $this->repositories;
    }

    public function repository(string $index): MeiliSearchRepositoryInterface
    {
        $index = $this->removeIndexAffixes($index);

        return $this->collection[$index]->repository ?? throw new IndexNotFound($index);
    }

    private function mapRepositoryIndexesToId(MeiliSearchRepositoryInterface $repository): void
    {
        /** @var Index $index */
        foreach ($repository->indexes() as $index) {
            if (!$index->repository) {
                $index->repository = $repository;
            }

            $this->collection[$index->id] = $index;
        }
    }
}
