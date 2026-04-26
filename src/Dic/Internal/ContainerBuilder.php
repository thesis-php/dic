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

    /**
     * @var list<callable(Tags): void>
     */
    private array $resolveTagsListeners = [];

    /**
     * @var list<callable(): void>
     */
    private array $registrationListeners = [];

    private readonly Factories $factories;

    private readonly Disposers $disposers;

    public function __construct()
    {
        $this->factories = new Factories();
        $this->disposers = new Disposers();
    }

    /**
     * @template T
     * @param Ref<T> $ref
     * @param Tag<T> $tag
     */
    public function tag(Ref $ref, Tag $tag): void
    {
        $this->taggedRefs[] = new TaggedRef($ref, $tag);
    }

    /**
     * @param callable(Tags): void $listener
     */
    public function onResolveTags(callable $listener): void
    {
        $this->resolveTagsListeners[] = $listener;
    }

    /**
     * @template T
     * @param Ref<T> $ref
     * @param Factory<T> $factory
     */
    public function registerFactory(Ref $ref, Factory $factory): void
    {
        $this->factories->register($ref, $factory);
    }

    /**
     * @param callable(): void $listener
     */
    public function onRegistration(callable $listener): void
    {
        $this->registrationListeners[] = $listener;
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
            factories: $this->factories,
            disposers: $this->disposers,
        );
    }
}
