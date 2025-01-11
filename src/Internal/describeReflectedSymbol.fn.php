<?php

declare(strict_types=1);

namespace Thesis\DI\Internal;

/**
 * @internal
 * @psalm-internal Thesis\DI
 * @return non-empty-string
 */
function describeReflectedSymbol(\ReflectionFunctionAbstract|\ReflectionClass $reflection): string
{
    if ($reflection instanceof \ReflectionMethod) {
        return \sprintf('%s::%s', describeReflectedSymbol($reflection->getDeclaringClass()), $reflection->name);
    }

    if ($reflection instanceof \ReflectionFunction) {
        \assert($reflection->name !== '');

        if ($reflection->isAnonymous()) {
            return 'function';
        }

        $class = $reflection->getClosureScopeClass();

        if ($class !== null) {
            return \sprintf('%s::%s', describeReflectedSymbol($class), $reflection->name);
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
