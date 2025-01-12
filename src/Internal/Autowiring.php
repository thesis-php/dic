<?php

declare(strict_types=1);

namespace Thesis\DI\Internal;

use Thesis\DI\Constructor;
use Thesis\DI\Definition;
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
     * @param Definition<T> $definition
     * @return Id<T>
     */
    public function identify(Definition $definition): Id
    {
        if ($definition instanceof Value) {
            if (\is_object($definition->value)) {
                /** @var Id<T> */
                return objectId($definition->value::class);
            }

            throw InvalidConfig::cannotInferClassFromType(get_debug_type($definition), $definition->location);
        }

        if ($definition instanceof Constructor) {
            /** @var Id<T> */
            return objectId($definition->class);
        }

        if (!$definition instanceof Factory) {
            throw new \LogicException();
        }

        $reflection = new \ReflectionFunction($definition->factory);
        $type = $reflection->getReturnType();

        if (!$type instanceof \ReflectionNamedType) {
            throw InvalidConfig::cannotInferClassFromType($type, $definition->location);
        }

        $name = $type->getName();
        $class = match ($type->getName()) {
            'self' => $reflection->getClosureScopeClass()?->name,
            'static' => $reflection->getClosureCalledClass()?->name,
            'parent' => ($reflection->getClosureScopeClass()?->getParentClass() ?: null)?->name,
            default => (class_exists($name) || interface_exists($name)) ? $name : null,
        };

        if ($class === null) {
            throw InvalidConfig::cannotInferClassFromType($type, $definition->location);
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
