<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\AutowireableFactory;

use Thesis\DIC\Internal\AutowireableFactory;
use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Container;

/**
 * @internal
 *
 * @template-covariant T of object
 * @implements AutowireableFactory<T>
 */
final readonly class Constructor implements AutowireableFactory
{
    /**
     * @param class-string<T> $class
     */
    public function __construct(
        private string $class,
        private Arguments $arguments,
    ) {}

    public function autowire(Autowiring $autowiring): AutowireableFactory
    {
        return new self(
            class: $this->class,
            arguments: $this->arguments->autowire($autowiring),
        );
    }

    public function __invoke(Container $container): mixed
    {
        return new ($this->class)(...$this->arguments->resolve($container));
    }
}
