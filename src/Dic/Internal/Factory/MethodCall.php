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
final readonly class MethodCall implements Factory
{
    /**
     * @param Ref<object> $object
     * @param non-empty-string $method
     * @param Factory<list<mixed>> $arguments
     */
    public function __construct(
        private Ref $object,
        private string $method,
        private Factory $arguments,
    ) {}

    public function create(Container $container): mixed
    {
        /** @phpstan-ignore method.dynamicName */
        return $container->get($this->object)
            ->{$this->method}(...$this->arguments->create($container));
    }
}
