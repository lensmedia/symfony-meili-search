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
    private array $indexes;

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->indexes);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->indexes[$offset]);
    }

    /**
     * @noinspection PhpMixedReturnTypeCanBeReducedInspection can not change interface implementation
     *
     * @return Index
     */
    public function offsetGet(mixed $offset): mixed
    {
        if (!$this->offsetExists($offset)) {
            throw new InvalidArgumentException(sprintf(
                'Index "%s" not found.',
                $offset,
            ));
        }

        return $this->indexes[$offset];
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

        $this->indexes[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->indexes[$offset]);
    }

    public function count(): int
    {
        return count($this->indexes);
    }

    // Custom
    public function isManagedIndex(string $index): bool
    {
        return $this->offsetExists($index);
    }

    public function index(string $index): Index
    {
        return $this->indexes[$index] ?? throw new IndexNotFound($index);
    }

    public function repository(string $index): MeiliSearchRepositoryInterface
    {
        return $this->indexes[$index]->repository ?? throw new IndexNotFound($index);
    }

    public function context(string $index): array
    {
        return $this->indexes[$index]->context ?? throw new IndexNotFound($index);
    }

    public function loadRepositories(iterable $repositories): void
    {
        foreach ($repositories as $repository) {
            $this->loadRepository($repository);
        }
    }

    public function loadRepository(MeiliSearchRepositoryInterface $repository): void
    {
        $this->mapRepositoryIndexesToId($repository);
    }

    private function mapRepositoryIndexesToId(MeiliSearchRepositoryInterface $repository): void
    {
        /** @var Index $index */
        foreach ($repository->indexes() as $index) {
            if (!$index->repository) {
                $index->repository = $repository;
            }

            $this->indexes[$index->id] = $index;
        }
    }
}
