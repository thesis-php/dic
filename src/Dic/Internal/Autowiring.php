<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\Error\InvalidArgument;
use Thesis\Dic\Internal\Autowiring\AutowiringType;
use Thesis\Dic\Ref;

/**
 * @internal
 */
final class Autowiring
{
    /**
     * @var array<non-empty-string, array<string, Ref<mixed>>>
     */
    private array $bindings = [];

    /**
     * @template T
     * @param Ref<T> $ref
     * @param AutowiringType<T> $type
     */
    public function bind(Ref $ref, AutowiringType $type, string|\Stringable|\UnitEnum $qualifier): void
    {
        $qualifierAsString = self::stringifyQualifier($qualifier);

        $boundRef = $this->bindings[$type->string][$qualifierAsString] ?? null;

        if ($boundRef !== null) {
            throw new InvalidArgument(\sprintf(
                'Cannot bind %s to type "%s": it is already bound to %s',
                $ref,
                $type->string,
                $boundRef,
            ));
        }

        $this->bindings[$type->string][$qualifierAsString] = $ref;
    }

    /**
     * @template T
     * @param AutowiringType<T> $type
     * @return ?Ref<T>
     */
    public function autowire(AutowiringType $type, string|\Stringable|\UnitEnum $qualifier): ?Ref
    {
        /** @var ?Ref<T> */
        return $this->bindings[$type->string][self::stringifyQualifier($qualifier)] ?? null;
    }

    private static function stringifyQualifier(string|\Stringable|\UnitEnum $qualifier): string
    {
        if ($qualifier instanceof \UnitEnum) {
            return \sprintf('%s::%s', $qualifier::class, $qualifier->name);
        }

        return (string) $qualifier;
    }
}
