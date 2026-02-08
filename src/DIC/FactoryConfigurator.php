<?php

declare(strict_types=1);

namespace Thesis\DIC;

use Thesis\DIC\Internal\Args;

/**
 * @template T
 * @extends Service<T>
 *
 * @phpstan-import-type Param from Args
 */
interface FactoryConfigurator extends Service
{
    /**
     * @param class-string $class
     */
    public function bindAs(string $class): static;

    /**
     * @param Param $param
     */
    public function arg(int|string $param, mixed $arg): static;

    /**
     * @param array<Param, mixed> $args
     */
    public function args(array $args): static;

    public function doNotAutowireArgs(): static;

    /**
     * @param Tag<T> $tag
     */
    public function tag(Tag $tag): static;

    public function transient(): static;
}
