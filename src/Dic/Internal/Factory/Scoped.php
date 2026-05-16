<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Factory;

use Thesis\Dic\Internal\Container;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Ref;

/**
 * @internal
 *
 * @template-covariant T
 * @implements Factory<\Thesis\Dic\Scoped<T>>
 */
final readonly class Scoped implements Factory
{
    /**
     * @param Ref<T> $ref
     */
    public function __construct(
        private Ref $ref,
    ) {}

    public function dependencies(): iterable
    {
        yield '' => $this->ref;
    }

    public function create(Container $container): mixed
    {
        return new \Thesis\Dic\Scoped($this->ref, $container);
    }
}
