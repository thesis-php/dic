<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Container;

use Thesis\Dic\Internal\NonCopyable;
use Thesis\Dic\Internal\ShouldNotHappen;
use Thesis\Dic\Ref;

/**
 * A topological order of the services: every service is positioned after everything it depends on.
 *
 * @internal
 */
final class DependencyOrder
{
    use NonCopyable;

    /**
     * @var \WeakMap<Ref<mixed>, int>
     */
    private readonly \WeakMap $positions;

    private int $nextPosition = 0;

    public function __construct()
    {
        $this->positions = new \WeakMap();
    }

    /**
     * Positions $ref after its $dependencies, each of which must have been appended already.
     *
     * @param Ref<mixed> $ref
     * @param iterable<Ref<mixed>> $dependencies
     */
    public function append(Ref $ref, iterable $dependencies): void
    {
        if ($this->positions->offsetExists($ref)) {
            throw new ShouldNotHappen("{$ref} is already ordered");
        }

        foreach ($dependencies as $dependency) {
            if (!$this->positions->offsetExists($dependency)) {
                throw new ShouldNotHappen("Cannot order {$ref} before its dependency {$dependency}");
            }
        }

        $this->positions->offsetSet($ref, $this->nextPosition++);
    }

    /**
     * Orders $refs so that every service comes before the ones it depends on.
     *
     * @param iterable<Ref<mixed>> $refs
     * @return list<Ref<mixed>>
     */
    public function dependentsFirst(iterable $refs): array
    {
        $refsByPosition = [];

        foreach ($refs as $ref) {
            $position = $this->positions[$ref] ?? throw new ShouldNotHappen("{$ref} is not ordered");
            $refsByPosition[$position] = $ref;
        }

        krsort($refsByPosition);

        return array_values($refsByPosition);
    }
}
