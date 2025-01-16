<?php

declare(strict_types=1);

namespace Thesis\DI\Internal;

use Thesis\DI\Id;
use function Thesis\DI\objectId;

/**
 * @internal
 */
final readonly class Autowiring
{
    /**
     * @template T of object
     * @param Value<T>|Construct<T>|Call<T> $recipe
     * @return Id<T>
     */
    public function identify(Value|Construct|Call $recipe): Id
    {
        if ($recipe instanceof Value) {
            return objectId($recipe->value::class);
        }

        if ($recipe instanceof Construct) {
            return objectId($recipe->class);
        }

        $reflection = new \ReflectionFunction($recipe->function);
        $type = $reflection->getReturnType();

        if (!$type instanceof \ReflectionNamedType) {
            throw InvalidConfig::cannotInferClassFromType($type, Location::current());
        }

        $name = $type->getName();
        $class = match ($type->getName()) {
            'self' => $reflection->getClosureScopeClass()?->name,
            'static' => $reflection->getClosureCalledClass()?->name,
            'parent' => ($reflection->getClosureScopeClass()?->getParentClass() ?: null)?->name,
            default => (class_exists($name) || interface_exists($name)) ? $name : null,
        };

        if ($class === null) {
            throw InvalidConfig::cannotInferClassFromType($type, Location::current());
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
