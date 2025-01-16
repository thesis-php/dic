<?php

declare(strict_types=1);

namespace Thesis\DI\Internal;

/**
 * @internal
 */
final readonly class ReflectionStringifier
{
    public static function stringifySymbol(\ReflectionFunctionAbstract|\ReflectionClass $reflection): string
    {
        if ($reflection instanceof \ReflectionMethod) {
            return \sprintf('%s::%s', self::stringifySymbol($reflection->getDeclaringClass()), $reflection->name);
        }

        if ($reflection instanceof \ReflectionFunction) {
            \assert($reflection->name !== '');

            if ($reflection->isAnonymous()) {
                return 'function';
            }

            $class = $reflection->getClosureScopeClass();

            if ($class !== null) {
                return \sprintf('%s::%s', self::stringifySymbol($class), $reflection->name);
            }

            return $reflection->name;
        }

        if ($reflection instanceof \ReflectionClass) {
            if ($reflection->isAnonymous()) {
                return 'class';
            }

            return $reflection->name;
        }

        throw new \LogicException(\sprintf('%s not supported yet', $reflection::class));
    }

    public static function stringifyType(?\ReflectionType $type): string
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
            return implode('|', array_map(self::stringifyType(...), $type->getTypes()));
        }

        if ($type instanceof \ReflectionIntersectionType) {
            return implode('&', array_map(self::stringifyType(...), $type->getTypes()));
        }

        throw new \LogicException(\sprintf('%s not supported yet', $type::class));
    }

    private function __construct() {}
}
