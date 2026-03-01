<?php

declare(strict_types=1);

namespace Thesis\DIC;

use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Binding;
use Thesis\DIC\Internal\Container;
use Typhoon\Type;
use function Thesis\DIC\Internal\Type\nativeTypeOf;

/**
 * @api
 *
 * @template-covariant T
 */
final class Scope
{
    /**
     * @var list<Binding<*>>
     */
    private array $bindings = [];

    /**
     * @internal
     *
     * @param Ref<T> $ref
     */
    public function __construct(
        private readonly Ref $ref,
        private Container $container {
            get {
                if ($this->bindings === []) {
                    return $this->container;
                }

                $autowiring = new Autowiring();

                foreach ($this->bindings as $binding) {
                    $autowiring->addBinding($binding);
                }

                $this->container = $this->container->scoped($autowiring);
                $this->bindings = [];

                return $this->container;
            }
        },
    ) {}

    /**
     * @var T
     * @phpstan-ignore generics.variance
     */
    public mixed $value {
        get => $this->container->get($this->ref);
    }

    /**
     * @template V
     * @param V $value
     * @param ?Type<contravariant V> $type
     */
    public function with(mixed $value, ?Type $type = null, \UnitEnum|\Stringable|string $qualifier = ''): static
    {
        $copy = clone $this;

        $copy->bindings[] = new Binding(
            type: $type ?? nativeTypeOf($value),
            qualifier: $qualifier,
            value: $value,
        );

        return $copy;
    }
}
