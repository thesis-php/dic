<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\Internal\Container\DependencyOrder;
use Thesis\Dic\Internal\Container\Disposers;
use Thesis\Dic\Internal\Container\Factories;
use Thesis\Dic\Internal\Container\InstanceFuture;
use Thesis\Dic\Ref;

/**
 * Holds the instances of one lifetime layer: the root container holds singletons, a scope holds scoped instances
 * and hands singletons over to the root.
 *
 * @internal
 */
final readonly class Container
{
    use NonCopyable;

    /**
     * @var \SplObjectStorage<Ref<mixed>, mixed>
     */
    private \SplObjectStorage $values;

    /**
     * @var \SplObjectStorage<Ref<mixed>, InstanceFuture<mixed>>
     */
    private \SplObjectStorage $futures;

    public function __construct(
        private Factories $factories,
        private Factories $scopedFactories,
        private DependencyOrder $dependencyOrder,
        private Disposers $disposers,
        private ?self $root = null,
    ) {
        $this->values = new \SplObjectStorage();
        $this->futures = new \SplObjectStorage();
    }

    /**
     * Returns the instance of $ref, creating it on first access.
     *
     * @template T
     * @param Ref<T> $ref
     * @return T
     */
    public function get(Ref $ref): mixed
    {
        if ($this->root !== null && !$this->factories->has($ref)) {
            return $this->root->get($ref);
        }

        if ($this->values->offsetExists($ref)) {
            /** @var T */
            return $this->values->offsetGet($ref);
        }

        $future = $this->futures[$ref] ?? null;

        if ($future !== null) {
            if ($future->isAwaitable) {
                /** @var T */
                return $future->await();
            }

            // Being created by a coroutine this one cannot await: create its own, but keep whichever is stored first.
            return $this->store($ref, $this->factories->create($ref, $this));
        }

        $future = new InstanceFuture();
        $this->futures->offsetSet($ref, $future);

        try {
            $value = $this->store($ref, $this->factories->create($ref, $this));
        } catch (\Throwable $error) {
            // Unregistered before settling, here and below: a registered future is never settled.
            $this->futures->offsetUnset($ref);
            $future->fail($error);

            throw $error;
        }

        $this->futures->offsetUnset($ref);
        $future->complete($value);

        return $value;
    }

    public function startScope(): self
    {
        return new self(
            factories: $this->scopedFactories,
            scopedFactories: $this->scopedFactories,
            dependencyOrder: $this->dependencyOrder,
            disposers: $this->disposers,
            root: $this->root ?? $this,
        );
    }

    /**
     * Disposes every created instance best-effort, each one before the instances it depends on,
     * and returns the errors raised by disposers, if any.
     *
     * Instances still being created in other fibers are awaited first, so that they are disposed too.
     *
     * @return list<\Throwable>
     */
    public function dispose(?\Throwable $error): array
    {
        $errors = [];

        $this->awaitCreations();

        // A disposer may resolve a service that was not created yet, so repeat until nothing is left.
        while ($this->values->count() > 0) {
            foreach ($this->dependencyOrder->dependentsFirst($this->values) as $ref) {
                $errors[] = $this->disposers->dispose($ref, $this->values[$ref], $error);
                $this->values->offsetUnset($ref);
            }

            $this->awaitCreations();
        }

        return array_merge(...$errors);
    }

    /**
     * Stores $value unless an instance of $ref was stored meanwhile by a creation that could not be awaited:
     * then that one is kept, so every caller shares it, and $value is dropped without being disposed.
     *
     * @template T
     * @param Ref<T> $ref
     * @param T $value
     * @return T
     */
    private function store(Ref $ref, mixed $value): mixed
    {
        if ($this->values->offsetExists($ref)) {
            /** @var T */
            return $this->values->offsetGet($ref);
        }

        $this->values->offsetSet($ref, $value);

        return $value;
    }

    private function awaitCreations(): void
    {
        while (($future = $this->awaitableFuture()) !== null) {
            try {
                $future->await();
            } catch (\Throwable) {
                // The creating fiber receives this error; a failed creation leaves nothing to dispose.
            }
        }
    }

    /**
     * @return ?InstanceFuture<mixed>
     */
    private function awaitableFuture(): ?InstanceFuture
    {
        foreach ($this->futures as $ref) {
            $future = $this->futures[$ref];

            if ($future->isAwaitable) {
                return $future;
            }
        }

        return null;
    }
}
