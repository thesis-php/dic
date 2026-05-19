<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Factory;

use Thesis\Dic\Internal\Container;
use Thesis\Dic\Internal\Factory;

/**
 * @internal
 *
 * @implements Factory<array<non-negative-int, mixed>>
 */
final readonly class Arguments implements Factory
{
    /**
     * @param array<non-negative-int, Factory> $factories
     */
    public function __construct(
        private array $factories,
    ) {}

    public function dependencies(): iterable
    {
        foreach ($this->factories as $index => $factory) {
            foreach ($factory->dependencies() as $path => $ref) {
                yield "\${$index}{$path}" => $ref;
            }
        }
    }

    public function create(Container $container): array
    {
        return array_map(
            static fn(Factory $f) => $f->create($container),
            $this->factories,
        );
    }
}
