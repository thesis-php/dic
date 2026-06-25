<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Factory;

use Thesis\Dic\Internal\Container;
use Thesis\Dic\Internal\Container\Scope;

/**
 * @internal
 */
final readonly class NamedArgumentsFactory extends ArgumentsFactory
{
    /**
     * @param array<string, ValueFactory> $arguments
     */
    protected function __construct(
        private array $arguments,
    ) {}

    public function dependencies(): iterable
    {
        foreach ($this->arguments as $name => $argument) {
            foreach ($argument->dependencies() as $dependency) {
                yield $dependency->arg($name);
            }
        }
    }

    public function create(Container|Scope $container): array
    {
        return array_map(
            static fn(ValueFactory $factory) => $factory->create($container),
            $this->arguments,
        );
    }
}
