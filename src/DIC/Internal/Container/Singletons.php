<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\Container;

use Thesis\DIC\Internal\Container;
use Thesis\DIC\Reference;

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
     * @param \WeakMap<Reference<*>, mixed> $values
     */
    private function __construct(
        private \WeakMap $values,
    ) {}

    /**
     * @template T
     * @param Reference<T> $reference
     * @param callable(Container): T $factory
     */
    public function with(Reference $reference, callable $factory): self
    {
        $copy = clone $this;

        $copy->values->offsetSet($reference, new Factory($factory));

        return $copy;
    }

    /**
     * @param Reference<*> $reference
     */
    public function has(Reference $reference): bool
    {
        return $this->values->offsetExists($reference);
    }

    /**
     * @template T
     * @param Reference<T> $reference
     * @return T
     */
    public function get(Reference $reference, Container $container): mixed
    {
        $value = $this->values[$reference];

        if ($value instanceof Factory) {
            $value = $value($container);

            if ($value === null) {
                $value = NULL_;
            }

            $this->values->offsetSet($reference, $value);

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
