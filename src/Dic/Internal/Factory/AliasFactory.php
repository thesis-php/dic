<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Factory;

use Thesis\Dic\Internal\Container;
use Thesis\Dic\Internal\Dependency;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Ref;

/**
 * @internal
 *
 * @template T
 * @implements Factory<T>
 */
final readonly class AliasFactory implements Factory
{
    /**
     * @param Ref<T> $ref
     */
    public function __construct(
        public Ref $ref,
    ) {}

    public function dependencies(): iterable
    {
        return [Dependency::of($this->ref)];
    }

    public function create(Container $container): mixed
    {
        return $container->get($this->ref);
    }
}
