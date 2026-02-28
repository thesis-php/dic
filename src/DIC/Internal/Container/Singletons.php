<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\Container;

use Thesis\DIC\Internal\Container;
use Thesis\DIC\Ref;

/**
 * @internal
 */
const NULL_ = new \stdClass();

/**
 * @internal
 */
final readonly class Singletons
{
    public static function new(): self
    {
        /** @phpstan-ignore argument.type */
        return new self(new \WeakMap());
    }

    /**
     * @param \WeakMap<Ref<*>, mixed> $values
     */
    private function __construct(
        private \WeakMap $values,
    ) {}

    /**
     * @template T
     * @param Ref<T> $ref
     * @param callable(Container): T $factory
     */
    public function with(Ref $ref, callable $factory): self
    {
        $copy = clone $this;

        $copy->values->offsetSet($ref, new Factory($factory));

        return $copy;
    }

    /**
     * @param Ref<*> $ref
     */
    public function has(Ref $ref): bool
    {
        return $this->values->offsetExists($ref);
    }

    /**
     * @template T
     * @param Ref<T> $ref
     * @return T
     */
    public function get(Ref $ref, Container $container): mixed
    {
        $value = $this->values[$ref];

        if ($value instanceof Factory) {
            $value = $value($container);

            if ($value === null) {
                $value = NULL_;
            }

            $this->values->offsetSet($ref, $value);

            return $value;
        }

        if ($value === NULL_) {
            /** @phpstan-ignore return.type */
            return null;
        }

        return $value;
    }

    public function __clone(): void
    {
        /** @phpstan-ignore property.readOnlyAssignNotInConstructor */
        $this->values = clone $this->values;
    }
}
