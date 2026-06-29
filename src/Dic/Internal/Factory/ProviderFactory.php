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
 * @template-covariant T
 * @implements Factory<\Closure(): T>
 */
final readonly class ProviderFactory implements Factory
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
        $ref = $this->ref;

        return static fn() => $container->get($ref);
    }
}
