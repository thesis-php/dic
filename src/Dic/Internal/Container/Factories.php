<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Container;

use Thesis\Dic\Error\UnknownRef;
use Thesis\Dic\Internal\Container;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\NonCopyable;
use Thesis\Dic\Internal\ShouldNotHappen;
use Thesis\Dic\Ref;

/**
 * @internal
 */
final readonly class Factories
{
    use NonCopyable;

    /**
     * @var \WeakMap<Ref<mixed>, Factory<*>>
     */
    private \WeakMap $factories;

    public function __construct()
    {
        $this->factories = new \WeakMap();
    }

    /**
     * @param Ref<mixed> $ref
     */
    public function has(Ref $ref): bool
    {
        return $this->factories->offsetExists($ref);
    }

    /**
     * @template T
     * @param Ref<T> $ref
     * @param Factory<T> $factory
     */
    public function register(Ref $ref, Factory $factory): void
    {
        if ($this->factories->offsetExists($ref)) {
            throw new ShouldNotHappen("Factory for {$ref} is already registered");
        }

        $this->factories->offsetSet($ref, $factory);
    }

    /**
     * @template T
     * @param Ref<T> $ref
     * @return T
     */
    public function create(Ref $ref, Container|Scope $container): mixed
    {
        return ($this->factories[$ref] ?? throw new UnknownRef($ref))->create($container);
    }
}
