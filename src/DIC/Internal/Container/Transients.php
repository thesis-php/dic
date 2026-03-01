<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\Container;

use Thesis\DIC\Internal\AutowireableFactory;
use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Container;
use Thesis\DIC\Ref;

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
     * @param \SplObjectStorage<Ref<*>, callable(Container): mixed> $factories
     */
    private function __construct(
        private \SplObjectStorage $factories,
    ) {}

    /**
     * @template T
     * @param Ref<T> $ref
     * @param callable(Container): T $factory
     */
    public function with(Ref $ref, callable $factory): self
    {
        $copy = clone $this;
        $copy->factories->offsetSet($ref, $factory);

        return $copy;
    }

    public function autowire(Autowiring $autowiring): self
    {
        $factories = clone $this->factories;

        foreach ($factories as $ref) {
            $factory = $factories[$ref];

            if ($factory instanceof AutowireableFactory) {
                $factories[$ref] = $factory->autowire($autowiring);
            }
        }

        return new self($factories);
    }

    /**
     * @param Ref<*> $ref
     */
    public function has(Ref $ref): bool
    {
        return $this->factories->offsetExists($ref);
    }

    /**
     * @template T
     * @param Ref<T> $ref
     * @return T
     */
    public function get(Ref $ref, Container $container): mixed
    {
        return ($this->factories[$ref])($container);
    }

    public function __clone(): void
    {
        /** @phpstan-ignore property.readOnlyAssignNotInConstructor */
        $this->factories = clone $this->factories;
    }
}
