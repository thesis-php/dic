<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\Internal\Container\DependencyOrder;
use Thesis\Dic\Internal\Container\Disposers;
use Thesis\Dic\Internal\Container\Scope;
use Thesis\Dic\Ref;

/**
 * @internal
 *
 * @phpstan-sealed Container\Singletons|Container\Scope
 */
abstract readonly class Container
{
    use NonCopyable;

    /**
     * @var \SplObjectStorage<Ref<mixed>, mixed>
     */
    final protected \SplObjectStorage $values;

    public function __construct(
        protected DependencyOrder $dependencyOrder,
        protected Disposers $disposers,
    ) {
        $this->values = new \SplObjectStorage();
    }

    /**
     * @template T
     * @param Ref<T> $ref
     * @return T
     */
    abstract public function get(Ref $ref): mixed;

    abstract public function startScope(): Scope;

    /**
     * Disposes every created instance best-effort, each one before the instances it depends on,
     * and returns the errors raised by disposers, if any.
     *
     * @return list<\Throwable>
     */
    final public function dispose(?\Throwable $error): array
    {
        $errors = [];

        // A disposer may resolve a service that was not created yet, so repeat until nothing is left.
        while ($this->values->count() > 0) {
            foreach ($this->dependencyOrder->dependentsFirst($this->values) as $ref) {
                $errors[] = $this->disposers->dispose($ref, $this->values[$ref], $error);
                $this->values->offsetUnset($ref);
            }
        }

        return array_merge(...$errors);
    }
}
