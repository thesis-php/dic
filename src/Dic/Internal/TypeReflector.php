<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Typhoon\Type;
use function Thesis\Formatter\formatReflectedClass;

/**
 * @internal
 */
final readonly class TypeReflector
{
    public static function parameterType(\ReflectionParameter $parameter): ?Type
    {
        $type = $parameter->getType();

        if ($type === null) {
            return null;
        }

        return self::reflectionType(
            reflectionType: $type,
            self: $parameter->getDeclaringFunction()->getClosureScopeClass(),
            static: $parameter->getDeclaringFunction()->getClosureCalledClass(),
        );
    }

    /**
     * @param ?\ReflectionClass<*> $self
     * @param ?\ReflectionClass<*> $static
     */
    private static function reflectionType(
        \ReflectionType $reflectionType,
        ?\ReflectionClass $self,
        ?\ReflectionClass $static,
    ): Type {
        if ($reflectionType instanceof \ReflectionUnionType) {
            return Type\unionT(array_map( // @phpstan-ignore argument.type
                static fn(\ReflectionType $type) => self::reflectionType($reflectionType, $self, $static),
                $reflectionType->getTypes(),
            ));
        }

        if ($reflectionType instanceof \ReflectionIntersectionType) {
            return Type\intersectionT(array_map( // @phpstan-ignore argument.type, argument.templateType
                static fn(\ReflectionType $type) => self::reflectionType($reflectionType, $self, $static),
                $reflectionType->getTypes(),
            ));
        }

        \assert($reflectionType instanceof \ReflectionNamedType);

        $name = $reflectionType->getName();
        $lowerName = strtolower($name);

        if ($lowerName === 'parent') {
            if ($self === null) {
                throw new \LogicException('Cannot resolve `parent` type outside the class context');
            }

            $parent = $self->getParentClass();

            if ($parent === false) {
                throw new \LogicException(\sprintf('Class `%s` does not have a parent', formatReflectedClass($self)));
            }

            return Type\objectT($parent->name);
        }

        $type = match ($lowerName) {
            'null' => Type\nullT,
            'void' => Type\voidT,
            'never' => Type\neverT,
            'false' => Type\falseT,
            'true' => Type\trueT,
            'bool', 'boolean' => Type\boolT,
            'int', 'integer' => Type\intT,
            'float', 'double' => Type\floatT,
            'string' => Type\stringT,
            'array' => Type\arrayT,
            'object' => Type\objectT,
            'self' => Type\objectT(($self ?? throw new \LogicException('Cannot resolve `self` type outside the class context'))->name),
            'static' => Type\objectT(($static ?? throw new \LogicException('Cannot resolve `static` type outside the class context'))->name),
            'iterable' => Type\iterableT,
            'callable' => Type\callableT,
            'mixed' => Type\mixedT,
            default => (class_exists($name) || interface_exists($name))
                ? Type\objectT($name)
                : throw new \LogicException(\sprintf('Type `%s` is not supported', $name)),
        };

        if ($reflectionType->allowsNull() && $type !== Type\nullT && $type !== Type\mixedT) {
            return Type\nullOrT($type); // @phpstan-ignore argument.type
        }

        return $type;
    }

    private function __construct() {}
}
