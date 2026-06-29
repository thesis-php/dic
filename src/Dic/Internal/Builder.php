<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\Configuration\Config;
use Thesis\Dic\Configuration\FunctionAutoconfig;
use Thesis\Dic\Configuration\FunctionConfig;
use Thesis\Dic\Configuration\ObjectAutoconfig;
use Thesis\Dic\Configuration\ObjectConfig;
use Thesis\Dic\Internal\Builder\Autoconfiguration;
use Thesis\Dic\Internal\Builder\LifetimeStrategy;
use Thesis\Dic\Internal\Builder\Services;
use Thesis\Dic\Internal\Builder\Tags;
use Thesis\Dic\Internal\Container\Disposers;
use Thesis\Dic\Internal\Container\Factories;
use Thesis\Dic\Internal\Container\Singletons;
use Thesis\Dic\Ref;
use Thesis\Dic\Tag;
use Thesis\Dic\TaggedRefs;

/**
 * @internal
 */
final readonly class Builder
{
    use NonCopyable;

    private Autoconfiguration $autoconfiguration;

    private Services $services;

    private Factories $singletonFactories;

    private Factories $scopedFactories;

    private Tags $tags;

    private Disposers $disposers;

    public function __construct()
    {
        $this->singletonFactories = new Factories();
        $this->scopedFactories = new Factories();
        $this->services = new Services(
            singletonFactories: $this->singletonFactories,
            scopedFactories: $this->scopedFactories,
        );
        $this->autoconfiguration = new Autoconfiguration($this->services);
        $this->tags = new Tags();
        $this->disposers = new Disposers();
    }

    /**
     * @template T
     * @param Config<T> $config
     * @param \Closure(): Factory<T> $createFactory
     */
    public function register(Config $config, \Closure $createFactory, LifetimeStrategy $defaultLifetimeStrategy): void
    {
        $this->services->register($config, $createFactory, $defaultLifetimeStrategy);
    }

    /**
     * @param FunctionConfig<callable>|ObjectConfig<object> $config
     */
    public function autoconfigure(FunctionConfig|ObjectConfig $config): void
    {
        $this->autoconfiguration->autoconfigure($config);
    }

    /**
     * @param FunctionConfig<callable>|ObjectConfig<object> $config
     */
    public function doNotAutoconfigure(FunctionConfig|ObjectConfig $config): void
    {
        $this->autoconfiguration->doNotAutoconfigure($config);
    }

    /**
     * @param Ref<mixed> $ref
     */
    public function setLifetimeStrategy(Ref $ref, LifetimeStrategy $lifetimeStrategy): void
    {
        $this->services->setLifetimeStrategy($ref, $lifetimeStrategy);
    }

    /**
     * @template T
     * @param Ref<T> $ref
     * @param Tag<T> $tag
     */
    public function addTag(Ref $ref, Tag $tag): void
    {
        $this->tags->add($ref, $tag);
    }

    /**
     * @template T
     * @param Ref<T> $ref
     * @param callable(T, ?\Throwable): void $disposer
     */
    public function addDisposer(Ref $ref, callable $disposer): void
    {
        $this->disposers->add($ref, $disposer);
    }

    /**
     * @param callable(TaggedRefs): void $listener
     */
    public function onTagResolution(callable $listener): void
    {
        $this->tags->onResolution($listener);
    }

    /**
     * @param callable(FunctionAutoconfig): void $listener
     */
    public function onFunction(callable $listener): void
    {
        $this->autoconfiguration->onFunction($listener);
    }

    /**
     * @param callable(ObjectAutoconfig<object>): void $listener
     */
    public function onObject(callable $listener): void
    {
        $this->autoconfiguration->onObject($listener);
    }

    public function build(): Container
    {
        $this->autoconfiguration->start();

        $this->tags->resolve();

        $this->services->resolve();

        return new Singletons(
            singletonFactories: $this->singletonFactories,
            scopedFactories: $this->scopedFactories,
            disposers: $this->disposers,
        );
    }
}
