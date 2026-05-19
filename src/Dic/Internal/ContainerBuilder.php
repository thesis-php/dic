<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\Ref;
use Thesis\Dic\Tag;
use Thesis\Dic\TaggedRef;
use Thesis\Dic\Tags;

/**
 * @internal
 */
final class ContainerBuilder
{
    /**
     * @var list<TaggedRef<*, *>>
     */
    private array $taggedRefs = [];

    private readonly Factories $singletonFactories;

    private readonly Factories $scopedFactories;

    private readonly Disposers $disposers;

    /**
     * @var list<callable(Tags): void>
     */
    private array $resolveTagsListeners = [];

    /**
     * @var list<callable(): void>
     */
    private array $registrationListeners = [];

    public function __construct()
    {
        $this->singletonFactories = new Factories();
        $this->scopedFactories = new Factories();
        $this->disposers = new Disposers();
    }

    /**
     * @template T
     * @param Ref<T> $ref
     * @param Tag<T> $tag
     */
    public function addTag(Ref $ref, Tag $tag): void
    {
        $this->taggedRefs[] = new TaggedRef($ref, $tag);
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
     * @template T
     * @param Ref<T> $ref
     * @param Factory<T> $factory
     */
    public function addFactory(Ref $ref, Lifetime $lifetime, Factory $factory): void
    {
        match ($lifetime) {
            Lifetime::Scoped => $this->scopedFactories->register($ref, $factory),
            Lifetime::Singleton => $this->singletonFactories->register($ref, $factory),
        };
    }

    /**
     * @param callable(Tags): void $listener
     */
    public function onResolveTags(callable $listener): void
    {
        $this->resolveTagsListeners[] = $listener;
    }

    /**
     * @param callable(): void $listener
     */
    public function onRegistration(callable $listener): void
    {
        $this->registrationListeners[] = $listener;
    }

    public function build(): Root
    {
        $tags = new Tags($this->taggedRefs);

        while (null !== $listener = array_shift($this->resolveTagsListeners)) {
            $listener($tags);
        }

        while (null !== $listener = array_shift($this->registrationListeners)) {
            $listener();
        }

        return new Root(
            singletonFactories: $this->singletonFactories,
            scopedFactories: $this->scopedFactories,
            disposers: $this->disposers,
        );
    }
}
