<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\Configuration\Config;
use Thesis\Dic\Configuration\FunctionConfig;
use Thesis\Dic\Configuration\ObjectConfig;
use Thesis\Dic\Internal\Builder\Autoconfiguration;
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
        $this->autoconfiguration = new Autoconfiguration();
        $this->singletonFactories = new Factories();
        $this->scopedFactories = new Factories();
        $this->services = new Services(
            singletonFactories: $this->singletonFactories,
            scopedFactories: $this->scopedFactories,
        );
        $this->tags = new Tags();
        $this->disposers = new Disposers();
    }

    /**
     * @param callable(FunctionConfig<*>|ObjectConfig<*>): void $autoconfigurator
     */
    public function addAutoconfigurator(callable $autoconfigurator): void
    {
        $this->autoconfiguration->addAutoconfigurator($autoconfigurator);
    }

    /**
     * @template T
     * @param Config<T> $config
     * @param \Closure(): Factory<T> $createFactory
     */
    public function register(Config $config, \Closure $createFactory): void
    {
        if ($config instanceof FunctionConfig || $config instanceof ObjectConfig) {
            $this->autoconfiguration->schedule($config);
        }

        $this->services->register($config, $createFactory);
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
     * @param callable(TaggedRefs): void $listener
     */
    public function onTagResolution(callable $listener): void
    {
        $this->tags->onResolution($listener);
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

    public function build(): Container
    {
        $this->autoconfiguration->autoconfigure();

        $this->tags->resolve();

        $this->services->resolve();

        return new Singletons(
            singletonFactories: $this->singletonFactories,
            scopedFactories: $this->scopedFactories,
            disposers: $this->disposers,
        );
    }
}
