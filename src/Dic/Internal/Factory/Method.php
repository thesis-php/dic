<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Factory;

use Thesis\Dic\Internal\Container;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Ref;

/**
 * @internal
 *
 * @template-covariant T of \Closure
 * @implements Factory<T>
 */
final readonly class Method implements Factory
{
    /**
     * @param Ref<object> $object
     */
    public function __construct(
        private Ref $object,
        private string $name,
    ) {}

    public function dependencies(): iterable
    {
        yield '' => $this->object;
    }

    public function create(Container $container): mixed
    {
        /**
         * @var T
         * @phpstan-ignore method.dynamicName
         */
        return $container->get($this->object)->{$this->name}(...);
    }
}
