<?php

declare(strict_types=1);

namespace Thesis\DIC;

use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Container;
use Typhoon\Type;

/**
 * @api
 *
 * @template-covariant T
 */
final class Scoped
{
    private Autowiring $autowiring;

    /**
     * @internal
     *
     * @param Ref<T> $ref
     */
    public function __construct(
        private readonly Ref $ref,
        private readonly Container $container,
    ) {
        $this->autowiring = new Autowiring();
    }

    /**
     * @return T
     */
    public function resolve(): mixed
    {
        return $this->container->resolveInScope($this->ref, $this->autowiring);
    }

    /**
     * @template V
     * @param V $value
     * @param ?Type<contravariant V> $type
     */
    public function with(mixed $value, ?Type $type = null, \UnitEnum|\Stringable|string $qualifier = ''): static
    {
        $scoped = clone $this;
        $scoped->autowiring = $this->autowiring->with($value, $type, $qualifier);

        return $scoped;
    }
}
