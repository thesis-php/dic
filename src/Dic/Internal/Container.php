<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

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
     * Disposes every created instance best-effort and returns the errors raised
     * by disposers, if any.
     *
     * @return list<\Throwable>
     */
    final public function dispose(?\Throwable $error): array
    {
        $errors = [];

        $this->values->rewind();

        while ($this->values->valid()) {
            $ref = $this->values->current();
            $errors[] = $this->disposers->dispose($ref, $this->values[$ref], $error);
            // offsetUnset() advances the cursor to the next entry on its own,
            // so no next() here — calling it would skip an element.
            $this->values->offsetUnset($ref);
        }

        return array_merge(...$errors);
    }
}
