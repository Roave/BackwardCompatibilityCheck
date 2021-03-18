<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility;

use Countable;
use IteratorAggregate;

use function count;
use function iterator_to_array;

/** @template-implements IteratorAggregate<int, Change> */
final class Changes implements IteratorAggregate, Countable
{
    /**
     * @var Change[]
     *
     * @psalm-var list<Change>
     */
    private array $bufferedChanges;

    /**
     * @var iterable|Change[]|null
     *
     * @psalm-var iterable<int, Change>|null
     */
    private ?iterable $unBufferedChanges = null;

    private function __construct()
    {
    }

    public static function empty(): self
    {
        static $empty;

        if ($empty instanceof self) {
            return $empty;
        }

        $empty = new self();

        $empty->bufferedChanges = [];

        return $empty;
    }

    /**
     * @param iterable|Change[] $changes
     *
     * @psalm-param iterable<int, Change> $changes
     */
    public static function fromIterator(iterable $changes): self
    {
        $instance = new self();

        $instance->bufferedChanges   = [];
        $instance->unBufferedChanges = $changes;

        return $instance;
    }

    public static function fromList(Change ...$changes): self
    {
        $instance = new self();

        $instance->bufferedChanges = $changes;

        return $instance;
    }

    public function mergeWith(self $other): self
    {
        $instance = new self();

        $instance->bufferedChanges   = [];
        $instance->unBufferedChanges = (function () use ($other): iterable {
            foreach ($this as $change) {
                yield $change;
            }

            foreach ($other as $change) {
                yield $change;
            }
        })();

        return $instance;
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator(): iterable
    {
        foreach ($this->bufferedChanges as $change) {
            yield $change;
        }

        foreach ($this->unBufferedChanges ?? [] as $change) {
            $this->bufferedChanges[] = $change;

            yield $change;
        }

        $this->unBufferedChanges = null;
    }

    public function count(): int
    {
        return count(iterator_to_array($this));
    }
}
