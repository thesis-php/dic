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
final readonly class Value implements Factory
{
    /**
     * @template V
     * @param V $value
     * @return Factory<V>
     */
    public static function from(mixed $value): Factory
    {
        if (self::hasRefs($value)) {
            /** @var ValueWithRefs<V> */
            return new ValueWithRefs($value);
        }

        return new self($value);
    }

    private static function hasRefs(mixed $value): bool
    {
        if ($value instanceof Ref) {
            return true;
        }

        if (\is_array($value)) {
            return array_any($value, self::hasRefs(...));
        }

        return false;
    }

    /**
     * @param T $value
     */
    private function __construct(
        public mixed $value,
    ) {}

    public function create(Container $container): mixed
    {
        return $this->value;
    }
}
