<?php

declare(strict_types=1);

namespace Thesis\DI\Internal;

/**
 * @internal
 * @psalm-internal Thesis\DI
 */
function describeReflectedType(?\ReflectionType $type): string
{
    if ($type === null) {
        return '';
    }

    if ($type instanceof \ReflectionNamedType) {
        $string = $type->getName();

        if ($type->allowsNull() && $string !== 'null' && $string !== 'mixed') {
            return '?' . $string;
        }

        return $string;
    }

    if ($type instanceof \ReflectionUnionType) {
        return implode('|', array_map(describeReflectedType(...), $type->getTypes()));
    }

    if ($type instanceof \ReflectionIntersectionType) {
        return implode('&', array_map(describeReflectedType(...), $type->getTypes()));
    }

    throw new \LogicException(\sprintf('%s not supported yet', $type::class));
}
