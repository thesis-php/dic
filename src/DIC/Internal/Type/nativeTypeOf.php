<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\Type;

if (\function_exists('Thesis\DIC\Internal\Type\nativeTypeOf')) {
    return;
}

use Typhoon\Type;

/**
 * @internal
 *
 * @template T
 * @param T $value
 * @return Type<contravariant T>
 */
function nativeTypeOf(mixed $value): Type
{
    return match (true) { // @phpstan-ignore match.unhandled, return.type
        $value === null => Type\nullT,
        $value === true => Type\trueT,
        $value === false => Type\falseT,
        \is_int($value) => Type\intT,
        \is_float($value) => Type\floatT,
        \is_string($value) => Type\stringT,
        \is_array($value) => Type\arrayT,
        \is_object($value) => Type\objectT($value::class), // @phpstan-ignore argument.type, argument.templateType
        \is_resource($value) => Type\resourceT,
    };
}
