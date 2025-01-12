<?php

declare(strict_types=1);

namespace Thesis\DI\Internal;

use Thesis\DI\Constructor;
use Thesis\DI\Factory;
use Thesis\DI\Id;
use Thesis\DI\Value;
use function Thesis\DI\objectId;

/**
 * @internal
 * @psalm-internal Thesis\DI
 */
final readonly class Autowiring
{
    /**
     * @template T
     * @param Value<T>|Constructor<T>|Factory<T> $value
     * @return Id<T>
     * @phpstan-ignore generics.notSubtype
     */
    public function identify(Value|Constructor|Factory $value): Id
    {
        if ($value instanceof Value) {
            if (\is_object($value->value)) {
                /** @phpstan-ignore return.type, argument.type, argument.templateType */
                return objectId($value->value::class);
            }

            throw InvalidConfig::cannotInferClassFromType(get_debug_type($value), $value->location);
        }

        if ($value instanceof Constructor) {
            /** @phpstan-ignore return.type, argument.type, argument.templateType */
            return objectId($value->class);
        }

        $reflection = new \ReflectionFunction($value->factory);
        $type = $reflection->getReturnType();

        if (!$type instanceof \ReflectionNamedType) {
            throw InvalidConfig::cannotInferClassFromType($type, $value->location);
        }

        $name = $type->getName();
        $class = match ($type->getName()) {
            'self' => $reflection->getClosureScopeClass()?->name,
            'static' => $reflection->getClosureCalledClass()?->name,
            'parent' => ($reflection->getClosureScopeClass()?->getParentClass() ?: null)?->name,
            default => (class_exists($name) || interface_exists($name)) ? $name : null,
        };

        if ($class === null) {
            throw InvalidConfig::cannotInferClassFromType($type, $value->location);
        }

        /** @var Id<T> */
        return objectId($class);
    }

    public function autowire(\ReflectionParameter $parameter, ModuleValues $values): ?Id
    {
        return $this->autowireType($parameter->getType(), $values);
    }

    private function autowireType(?\ReflectionType $type, ModuleValues $values): ?Id
    {
        if ($type instanceof \ReflectionNamedType) {
            $name = $type->getName();

            if (!class_exists($name) && !interface_exists($name)) {
                return null;
            }

            $id = objectId($name);

            if ($values->has($id)) {
                return $id;
            }

            return null;
        }

        if ($type instanceof \ReflectionUnionType) {
            $options = array_unique(
                array_filter(
                    array_map(
                        fn(\ReflectionType $type): ?Id => $this->autowireType($type, $values),
                        $type->getTypes(),
                    ),
                ),
                SORT_REGULAR,
            );

            return \count($options) === 1 ? $options[0] : null;
        }

        return null;
    }
}
