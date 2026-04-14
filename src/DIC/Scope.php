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
        private readonly Container $container,
    ) {}

    /**
     * @return T
     */
    public function obtain(): mixed
    {
        $autowiring = new Autowiring();

        foreach ($this->bindings as $binding) {
            $autowiring->addBinding($binding);
        }

        return $this->container->scoped($autowiring)->get($this->ref);
    }

    /**
     * @var T
     * @deprecated since 0.3.4, use {@see self::obtain()}
     */
    public mixed $value {
        get => $this->obtain();
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
