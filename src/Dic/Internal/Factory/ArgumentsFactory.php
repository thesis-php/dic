<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Factory;

use Thesis\Dic\Internal\Arguments;
use Thesis\Dic\Internal\Factory;

/**
 * @internal
 *
 * @implements Factory<array<mixed>>
 */
abstract readonly class ArgumentsFactory implements Factory
{
    /**
     * @return Factory<array<mixed>>
     */
    final public static function from(Arguments $arguments): Factory
    {
        $variadic = $arguments->resolveVariadic();

        if ($variadic === null) {
            $named = [];

            foreach ($arguments->resolveRegular() as [$parameter, $factory]) {
                if ($factory instanceof ValueFactory) {
                    $named[$parameter->name] = $factory;
                }
            }

            return new NamedArgumentsFactory($named);
        }

        $regular = [];

        foreach ($arguments->resolveRegular() as [$parameter, $factory]) {
            $regular[$parameter->name] = $factory;
        }

        return new PositionalArgumentsFactory(
            regular: $regular,
            variadicName: $variadic[0]->name,
            variadic: $variadic[1],
        );
    }
}
