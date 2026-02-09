<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal;

use Thesis\DIC;
use Thesis\DIC\Qualifier;
use function Typhoon\Formatter\formatReflectedParameter;
use function Typhoon\Formatter\formatReflectedType;

const UNDEFINED = new \stdClass();

/**
 * @internal
 * @phpstan-import-type Arguments from DIC
 */
final class Autowiring
{
    /**
     * @var array<non-empty-string, mixed>
     */
    private array $bindings = [];

    /**
     * @param ?class-string $class
     */
    public function qualifyObject(object $object, ?string $class = null, string|\UnitEnum $qualifier = ''): void
    {
        $this->bindings[self::bindingKey($class ?? $object::class, $qualifier)] = $object;
    }

    /**
     * @param non-empty-string|\UnitEnum $qualifier
     */
    public function qualify(mixed $value, string|\UnitEnum $qualifier): void
    {
        $this->bindings[self::bindingKey(get_debug_type($value), $qualifier)] = $value;
    }

    /**
     * @param Arguments $arguments
     * @return array<non-empty-string, mixed>
     */
    public function resolveArguments(\ReflectionFunctionAbstract $function, array $arguments = []): array
    {
        $resolved = [];

        foreach ($function->getParameters() as $index => $parameter) {
            $name = $parameter->getName();

            if ($parameter->isVariadic()) {
                continue; // todo
            }

            if (\array_key_exists($index, $arguments)) {
                if (\array_key_exists($name, $arguments)) {
                    throw new \LogicException('Ambiguous.');
                }

                $resolved[$name] = $arguments[$index];

                continue;
            }

            if (\array_key_exists($name, $arguments)) {
                $resolved[$name] = $arguments[$name];

                continue;
            }

            $resolved[$name] = $this->resolveArgument($parameter);
        }

        return $resolved;
    }

    private function resolveArgument(\ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if ($type !== null) {
            $qualifier = self::resolveQualifier($parameter->getAttributes(Qualifier::class));
            $value = $this->autowire($type, $qualifier);

            if ($value !== UNDEFINED) {
                return $value;
            }
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new \LogicException(\sprintf('Failed to autowire %s', formatReflectedParameter($parameter)));
    }

    private function autowire(\ReflectionType $type, string|\UnitEnum $qualifier): mixed
    {
        if ($type instanceof \ReflectionNamedType) {
            $key = self::bindingKey($type->getName(), $qualifier);

            if (\array_key_exists($key, $this->bindings)) {
                return $this->bindings[$key];
            }

            return UNDEFINED;
        }

        if ($type instanceof \ReflectionUnionType) {
            $values = array_filter(
                array_map(
                    fn(\ReflectionType $type) => $this->autowire($type, $qualifier),
                    $type->getTypes(),
                ),
                static fn(mixed $value): bool => $value !== UNDEFINED,
            );

            return match (\count($values)) {
                0 => UNDEFINED,
                1 => $values[array_key_first($values)],
                default => throw new \LogicException('Ambiguous'),
            };
        }

        // todo

        throw new \LogicException(\sprintf('Cannot autowire %s', formatReflectedType($type)));
    }

    /**
     * @param list<\ReflectionAttribute<Qualifier>> $attributes
     */
    private static function resolveQualifier(array $attributes): string|\UnitEnum
    {
        return match (\count($attributes)) {
            0 => '',
            1 => $attributes[0]->newInstance()->qualifier,
            default => throw new \LogicException(),
        };
    }

    /**
     * @return non-empty-string
     */
    private static function bindingKey(string $type, string|\UnitEnum $qualifier): string
    {
        if ($qualifier instanceof \UnitEnum) {
            $qualifier = $qualifier::class . '::' . $qualifier->name;
        }

        return $type . '@' . $qualifier;
    }
}
