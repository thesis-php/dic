<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\Container;

use Thesis\DIC\Internal\AutowirableFunction;
use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Container;
use Thesis\DIC\Reference;

/**
 * @internal
 */
final readonly class Transients
{
    public static function new(): self
    {
        /** @phpstan-ignore argument.type */
        return new self(new \SplObjectStorage());
    }

    /**
     * @param \SplObjectStorage<Reference<*>, callable(Container): mixed> $factories
     */
    private function __construct(
        private \SplObjectStorage $factories,
    ) {}

    /**
     * @template T
     * @param Reference<T> $reference
     * @param callable(Container): T $factory
     */
    public function with(Reference $reference, callable $factory): self
    {
        $copy = clone $this;
        $copy->factories->offsetSet($reference, $factory);

        return $copy;
    }

    public function autowire(Autowiring $autowiring): self
    {
        $factories = clone $this->factories;

        foreach ($factories as $reference) {
            $factory = $factories[$reference];

            if ($factory instanceof AutowirableFunction) {
                $factories[$reference] = $factory->autowire($autowiring);
            }
        }

        return new self($factories);
    }

    /**
     * @param Reference<*> $reference
     */
    public function has(Reference $reference): bool
    {
        return $this->factories->offsetExists($reference);
    }

    /**
     * @template T
     * @param Reference<T> $reference
     * @return T
     */
    public function get(Reference $reference, Container $container): mixed
    {
        return ($this->factories[$reference])($container);
    }

    public function __clone(): void
    {
        /** @phpstan-ignore property.readOnlyAssignNotInConstructor */
        $this->factories = clone $this->factories;
    }
}
