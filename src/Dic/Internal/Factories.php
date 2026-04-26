<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\Exception\UnknownRef;
use Thesis\Dic\Ref;

/**
 * @internal
 */
final readonly class Factories
{
    /**
     * @var \WeakMap<Ref<*>, Factory<*>>
     */
    private \WeakMap $factories;

    public function __construct()
    {
        $this->factories = new \WeakMap();
    }

    /**
     * @template T
     * @param Ref<T> $ref
     * @param Factory<T> $factory
     */
    public function register(Ref $ref, Factory $factory): void
    {
        $this->factories->offsetSet($ref, $factory);
    }

    /**
     * @template T
     * @param Ref<T> $ref
     * @return T
     */
    public function create(Ref $ref, Container $container): mixed
    {
        return ($this->factories[$ref] ?? throw new UnknownRef($ref))->create($container);
    }

    /**
     * @param Ref<*> $ref
     */
    public function remove(Ref $ref): void
    {
        $this->factories->offsetUnset($ref);
    }
}
