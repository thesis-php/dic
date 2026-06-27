<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\BuildError;
use Thesis\Dic\Internal\Autowiring\BindingType;
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
     * @param BindingType<T> $type
     */
    public function bind(Ref $ref, BindingType $type, string|\Stringable|\UnitEnum $qualifier): void
    {
        $qualifierAsString = self::stringifyQualifier($qualifier);

        $boundRef = $this->bindings[$type->string][$qualifierAsString] ?? null;

        if ($boundRef !== null) {
            throw BuildError::duplicateBinding($ref, $type->string, $boundRef);
        }

        $this->bindings[$type->string][$qualifierAsString] = $ref;
    }

    /**
     * @template T
     * @param BindingType<T> $type
     * @return ?Ref<T>
     */
    public function autowire(BindingType $type, string|\Stringable|\UnitEnum $qualifier): ?Ref
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
