<?php

declare(strict_types=1);

namespace Thesis\DIC;

/**
 * @template T
 * @extends Service<T>
 */
interface ValueConfigurator extends Service
{
    /**
     * @param class-string $class
     */
    public function bindAs(string $class): static;

    /**
     * @param Tag<T> $tag
     */
    public function tag(Tag $tag): static;
}
