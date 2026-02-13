<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\Type;

use Typhoon\Type;
use function Typhoon\Formatter\formatClass;

/**
 * @internal
 *
 * @param ?class-string $self
 * @param ?class-string $static
 * @return ($type is null ? null : Type)
 * @todo pass ReflectionFunction|Property|...
 */
function fromReflectedType(?\ReflectionType $type, ?string $self, ?string $static): ?Type
{
    if ($type === null) {
        return null;
    }

    if ($type instanceof \ReflectionNamedType) {
        $name = $type->getName();

        if (strtolower($name) === 'parent') {
            $parent = get_parent_class($self ?? throw new \LogicException('Cannot resolve parent type'));

            if ($parent === false) {
                throw new \LogicException(\sprintf('Class `%s` does not have a parent', formatClass($self)));
            }

            return Type\objectT($parent);
        }

        $typhoon = match (strtolower($name)) {
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
            'self' => Type\objectT($self ?? throw new \LogicException('Cannot resolve self')),
            'static' => Type\objectT($static ?? $self ?? throw new \LogicException('Cannot resolve static')),
            'iterable' => Type\iterableT,
            'callable' => Type\callableT,
            'mixed' => Type\mixedT,
            default => (class_exists($name) || interface_exists($name))
                ? Type\objectT($name)
                : throw new \LogicException(\sprintf('Type `%s` is not supported', $name)),
        };

        if ($type->allowsNull() && $typhoon !== Type\nullT && $typhoon !== Type\mixedT) {
            return Type\nullOrT($typhoon); // @phpstan-ignore argument.type
        }

        return $typhoon;
    }

    if ($type instanceof \ReflectionUnionType) {
        return Type\unionT(array_map( // @phpstan-ignore argument.type
            static fn(\ReflectionType $type) => fromReflectedType($type, $self, $static),
            array_values($type->getTypes()),
        ));
    }

    if ($type instanceof \ReflectionIntersectionType) {
        return Type\intersectionT(array_map( // @phpstan-ignore argument.type, argument.templateType
            static fn(\ReflectionType $type) => fromReflectedType($type, $self, $static),
            array_values($type->getTypes()),
        ));
    }

    throw new \LogicException(\sprintf('`%s` is not supported', $type::class));
}
