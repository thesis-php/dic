<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Factory;

use Thesis\Dic\Internal\Container;
use Thesis\Dic\Internal\Container\Scope;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Signature\DefaultValue;

/**
 * @internal
 */
final readonly class PositionalArgumentsFactory extends ArgumentsFactory
{
    /**
     * @param array<non-empty-string, ValueFactory|DefaultValue> $regular
     * @param non-empty-string $variadicName
     * @param ValueFactory<iterable<array-key, mixed>> $variadic
     */
    protected function __construct(
        private array $regular,
        private string $variadicName,
        private ValueFactory $variadic,
    ) {}

    public function dependencies(): iterable
    {
        foreach ($this->regular as $name => $regular) {
            if ($regular instanceof Factory) {
                foreach ($regular->dependencies() as $dependency) {
                    yield $dependency->arg($name);
                }
            }
        }

        foreach ($this->variadic->dependencies() as $dependency) {
            yield $dependency->arg($this->variadicName);
        }
    }

    public function create(Container|Scope $container): array
    {
        return [
            ...array_map(
                static fn(mixed $factory) => $factory->create($container),
                array_values($this->regular),
            ),
            ...$this->variadic->create($container),
        ];
    }
}
