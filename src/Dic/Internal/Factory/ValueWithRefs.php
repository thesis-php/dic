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
final readonly class ValueWithRefs implements Factory
{
    public function __construct(
        private mixed $value,
    ) {}

    public function dependencies(): iterable
    {
        return self::findDependencies('', $this->value);
    }

    /**
     * @return \Generator<string, Ref<*>>
     */
    private static function findDependencies(string $pathPrefix, mixed $value): \Generator
    {
        if ($value instanceof Ref) {
            yield $pathPrefix => $value;

            return;
        }

        if (\is_array($value)) {
            foreach ($value as $key => $item) {
                yield from self::findDependencies("{$pathPrefix}[{$key}]", $item);
            }
        }
    }

    public function create(Container $container): mixed
    {
        return self::resolve($this->value, $container);
    }

    private static function resolve(mixed $value, Container $container): mixed
    {
        if ($value instanceof Ref) {
            return $container->get($value);
        }

        if (\is_array($value)) {
            return array_map(
                static fn(mixed $item) => self::resolve($item, $container),
                $value,
            );
        }

        return $value;
    }
}
