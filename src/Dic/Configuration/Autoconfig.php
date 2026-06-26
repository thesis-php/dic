<?php

declare(strict_types=1);

namespace Thesis\Dic\Configuration;

use Thesis\Dic\Internal\Builder;
use Thesis\Dic\Internal\Lifetime;
use Thesis\Dic\Ref;
use Thesis\Dic\Tag;

/**
 * @api
 *
 * @template T
 * @extends Ref<T>
 *
 * @phpstan-sealed Config
 */
abstract class Autoconfig extends Ref
{
    protected bool $isAutoconfigurable = true;

    protected function __construct(
        protected readonly Builder $builder,
    ) {}

    final public function singleton(): static
    {
        $this->setLifetime(Lifetime::Singleton);

        return $this;
    }

    final public function canBeScoped(): static
    {
        $this->setLifetime(Lifetime::CanBeScoped);

        return $this;
    }

    final public function scoped(): static
    {
        $this->setLifetime(Lifetime::Scoped);

        return $this;
    }

    private bool $isAutoconfiguring = false;

    private function setLifetime(Lifetime $lifetime): void
    {
        if ($this->isAutoconfiguring) {
            $this->builder->setDefaultLifetime($this, $lifetime);
        } else {
            $this->builder->setLifetime($this, $lifetime);
        }
    }

    /**
     * @param Tag<T> $tag
     */
    final public function tag(Tag $tag): static
    {
        $this->builder->addTag($this, $tag);

        return $this;
    }

    /**
     * @param callable(T, ?\Throwable): void $disposer
     */
    final public function disposer(callable $disposer): static
    {
        $this->builder->addDisposer($this, $disposer);

        return $this;
    }
}
