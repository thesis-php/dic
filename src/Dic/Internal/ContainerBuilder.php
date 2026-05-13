<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\Autoconfigurator\CallableAutoconfigurator;
use Thesis\Dic\Autoconfigurator\ObjectAutoconfigurator;
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

    private readonly Factories $factories;

    private readonly Disposers $disposers;

    private readonly Autoconfigurators $autoconfigurators;

    /**
     * @var list<callable(CallableAutoconfigurator&ObjectAutoconfigurator): void>
     */
    private array $autoconfigurationListeners = [];

    /**
     * @var list<callable(Tags): void>
     */
    private array $resolveTagsListeners = [];

    /**
     * @var list<callable(Factories): void>
     */
    private array $registrationListeners = [];

    public function __construct()
    {
        $this->factories = new Factories();
        $this->disposers = new Disposers();
        $this->autoconfigurators = new Autoconfigurators();
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
     * @template T
     * @param Ref<T> $ref
     * @param callable(T, ?\Throwable): void $disposer
     */
    public function addDisposer(Ref $ref, callable $disposer): void
    {
        $this->disposers->add($ref, $disposer);
    }

    public function addAutoconfigurator(CallableAutoconfigurator|ObjectAutoconfigurator $autoconfigurator): void
    {
        $this->autoconfigurators->add($autoconfigurator);
    }

    /**
     * @param callable(CallableAutoconfigurator&ObjectAutoconfigurator): void $listener
     */
    public function onAutoconfiguration(callable $listener): void
    {
        $this->autoconfigurationListeners[] = $listener;
    }

    /**
     * @param callable(Tags): void $listener
     */
    public function onResolveTags(callable $listener): void
    {
        $this->resolveTagsListeners[] = $listener;
    }

    /**
     * @param callable(Factories): void $listener
     */
    public function onRegistration(callable $listener): void
    {
        $this->registrationListeners[] = $listener;
    }

    public function build(): Root
    {
        while (null !== $listener = array_shift($this->autoconfigurationListeners)) {
            $listener($this->autoconfigurators);
        }

        $tags = new Tags($this->taggedRefs);

        while (null !== $listener = array_shift($this->resolveTagsListeners)) {
            $listener($tags);
        }

        while (null !== $listener = array_shift($this->registrationListeners)) {
            $listener($this->factories);
        }

        return new Root(
            factories: $this->factories,
            disposers: $this->disposers,
        );
    }
}
