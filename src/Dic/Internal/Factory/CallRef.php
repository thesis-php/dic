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
 * @implements Factory<T>
 */
final readonly class CallRef implements Factory
{
    /**
     * @param Ref<callable(): T> $function
     */
    public function __construct(
        private Ref $function,
        private Arguments $arguments,
    ) {}

    public function dependencies(): iterable
    {
        yield '' => $this->function;
        yield from $this->arguments->dependencies();
    }

    public function create(Container $container): mixed
    {
        return $container->get($this->function)(...$this->arguments->create($container));
    }
}
