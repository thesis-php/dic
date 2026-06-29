<?php

declare(strict_types=1);

namespace Thesis\Dic\Configuration;

use Thesis\Dic\Ref;
use Thesis\Dic\Tag;
use Typhoon\Type\ClosureT;

/**
 * @api
 */
final class FunctionAutoconfig
{
    /**
     * @var Ref<callable>
     */
    public Ref $ref {
        get => $this->config;
    }

    public \ReflectionFunction|\ReflectionMethod $reflection {
        get => $this->config->reflection;
    }

    public readonly Attributes $attributes;

    /**
     * @internal
     *
     * @param FunctionConfig<callable> $config
     */
    public function __construct(
        private readonly FunctionConfig $config,
    ) {
        $this->attributes = new Attributes($config->reflection);
    }

    /**
     * @template C of \Closure
     * @param ClosureT<C> $type
     * @return ClosureConfig<C>
     */
    public function closure(ClosureT $type): ClosureConfig
    {
        return $this->config->closure($type);
    }

    /**
     * @param Tag<callable> $tag
     */
    public function tag(Tag $tag): static
    {
        $this->config->tag($tag);

        return $this;
    }

    /**
     * @param callable(callable, ?\Throwable): void $disposer
     */
    public function disposer(callable $disposer): static
    {
        $this->config->disposer($disposer);

        return $this;
    }
}
