<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Factory;

use Thesis\Dic\Internal\Container;
use Thesis\Dic\Internal\Dependency;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Ref;
use Thesis\Dic\Scoped;

/**
 * @internal
 *
 * @template-covariant T
 * @implements Factory<Scoped<T>>
 */
final readonly class ScopedFactory implements Factory
{
    /**
     * @param Ref<T> $ref
     */
    public function __construct(
        private Ref $ref,
    ) {}

    public function dependencies(): iterable
    {
        yield Dependency::of($this->ref);
    }

    public function create(Container $container): mixed
    {
        return new Scoped($container, $this->ref);
    }
}
