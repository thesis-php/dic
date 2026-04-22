<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal;

use Thesis\DIC\Internal\AutowireableFactory\Parameter;
use Thesis\DIC\Internal\Autowiring\StringifyBindingType;
use Thesis\DIC\Ref;
use Typhoon\Type;

/**
 * @internal
 */
final class Autowiring
{
    private ?self $parent = null;

    public function inheritAutowiringFrom(self $autowiring): void
    {
        $this->parent = $autowiring;
    }

    /**
     * @var array<non-empty-string, mixed>
     */
    private array $bindings = [];

    /**
     * @template T
     * @param T|Ref<T> $value
     * @param ?Type<contravariant T> $type
     */
    public function bind(mixed $value, ?Type $type, string|\Stringable|\UnitEnum $qualifier): void
    {
        /** @var StringifyBindingType */
        static $stringifier = new StringifyBindingType();

        $type = $type?->accept($stringifier) ?? StringifyBindingType::value($value);

        $this->bindings[self::bindingKey($type, $qualifier)] = $value;
    }

    /**
     * @template T
     * @param T|Ref<T> $value
     * @param ?Type<contravariant T> $type
     */
    public function with(mixed $value, ?Type $type, string|\Stringable|\UnitEnum $qualifier): self
    {
        $autowiring = clone $this;
        $autowiring->bind($value, $type, $qualifier);

        return $autowiring;
    }

    /**
     * @return list<mixed>
     */
    public function autowire(Parameter $parameter): array
    {
        $candidates = [];
        $qualifier = $parameter->qualifier;

        foreach ($parameter->bindingTypes as $bindingType) {
            $key = self::bindingKey($bindingType, $qualifier);

            if (\array_key_exists($key, $this->bindings)) {
                $candidates[] = $this->bindings[$key];
            }
        }

        if ($candidates === [] && $this->parent !== null) {
            return $this->parent->autowire($parameter);
        }

        return $candidates;
    }

    /**
     * @return non-empty-string
     */
    private static function bindingKey(string $type, string|\Stringable|\UnitEnum $qualifier): string
    {
        return $type . '.' . self::stringifyQualifier($qualifier);
    }

    private static function stringifyQualifier(string|\Stringable|\UnitEnum $qualifier): string
    {
        if ($qualifier instanceof \UnitEnum) {
            return \sprintf('%s::%s', $qualifier::class, $qualifier->name);
        }

        return (string) $qualifier;
    }
}
