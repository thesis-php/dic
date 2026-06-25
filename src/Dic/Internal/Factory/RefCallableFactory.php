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
 * @implements Factory<T>
 */
final readonly class RefCallableFactory implements Factory
{
    /**
     * @param Ref<callable(): T> $ref
     * @param Factory<array<mixed>> $arguments
     */
    public function __construct(
        private Ref $ref,
        private Factory $arguments,
    ) {}

    public function dependencies(): iterable
    {
        yield Dependency::factory($this->ref);
        yield from $this->arguments->dependencies();
    }

    public function create(Container $container): mixed
    {
        return $container->get($this->ref)(...$this->arguments->create($container));
    }
}
