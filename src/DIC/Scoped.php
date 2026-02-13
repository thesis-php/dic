<?php

declare(strict_types=1);

namespace Thesis\DIC;

use Thesis\DIC\Internal\Binding;
use Thesis\DIC\Internal\Container;
use Typhoon\Type;
use function Thesis\DIC\Internal\Type\nativeTypeOf;

/**
 * @api
 *
 * @template-covariant T
 */
final class Scoped
{
    /**
     * @var list<Binding<*>>
     */
    private array $bindings = [];

    /**
     * @internal
     *
     * @param Reference<T> $reference
     */
    public function __construct(
        private readonly Reference $reference,
        private Container $container {
            get {
                if ($this->bindings !== []) {
                    $this->container = $this->container->scoped($this->bindings);
                    $this->bindings = [];
                }

                return $this->container;
            }
        },
    ) {}

    /**
     * @var T
     * @phpstan-ignore generics.variance
     */
    public mixed $value {
        get => $this->container->get($this->reference);
    }

    /**
     * @template V
     * @param V $value
     * @param ?Type<contravariant V> $type
     */
    public function with(mixed $value, ?Type $type = null, \UnitEnum|\Stringable|string $qualifier = ''): static
    {
        $scoped = clone $this;

        $scoped->bindings[] = new Binding(
            reference: new Value($value, Location::fromBacktrace(-1)),
            type: $type ?? nativeTypeOf($value),
            qualifier: $qualifier,
        );

        return $scoped;
    }
}
