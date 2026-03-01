<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\AutowireableFactory;

use Thesis\DIC\Internal\AutowireableFactory;
use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Container;

/**
 * @internal
 *
 * @template-covariant T
 * @implements AutowireableFactory<T>
 */
final readonly class Call implements AutowireableFactory
{
    /**
     * @param \Closure(): T $factory
     */
    public function __construct(
        private \Closure $factory,
        private Arguments $arguments,
    ) {}

    public function autowire(Autowiring $autowiring): AutowireableFactory
    {
        return new self(
            factory: $this->factory,
            arguments: $this->arguments->autowire($autowiring),
        );
    }

    public function __invoke(Container $container): mixed
    {
        return ($this->factory)(...$this->arguments->resolve($container));
    }
}
