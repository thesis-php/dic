<?php

declare(strict_types=1);

namespace Thesis\Dic\Configuration;

use Thesis\Dic\Ref;
use Thesis\Dic\Tag;

/**
 * @api
 *
 * @template-covariant T
 * @extends Ref<T>
 *
 * @phpstan-sealed Config|LifetimeConfig
 */
interface Autoconfig extends Ref
{
    /**
     * @param Tag<T> $tag
     */
    public function tag(Tag $tag): static;

    /**
     * @param callable(T, ?\Throwable): void $disposer
     */
    public function disposer(callable $disposer): static;
}
