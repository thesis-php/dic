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
 * @implements Factory<list<T>>
 */
final readonly class TaggedList implements Factory
{
    /**
     * @param list<Ref<T>> $refs
     */
    public function __construct(
        private array $refs,
    ) {}

    public function dependencies(): iterable
    {
        foreach ($this->refs as $index => $ref) {
            yield "[{$index}]" => $ref;
        }
    }

    public function create(Container $container): mixed
    {
        return array_map($container->get(...), $this->refs);
    }
}
