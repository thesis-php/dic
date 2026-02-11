<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal;

use Thesis\DIC;
use Typhoon\Type;
use function Typhoon\Formatter\formatReflectedParameter;

/**
 * @internal
 *
 * @phpstan-import-type Arguments from DIC
 */
final class Autowiring
{
    /**
     * @var list<array{ParameterSignature<*>, mixed}>
     */
    private array $bindings = [];

    /**
     * @template T
     * @param T $value
     * @param ?Type<contravariant T> $type
     * @param null|non-empty-string|\UnitEnum $qualifier
     * @param ?non-empty-string $name
     */
    public function bind(
        mixed $value,
        ?Type $type = null,
        null|string|\UnitEnum $qualifier = null,
        ?string $name = null,
    ): void {
        $this->bindings[] = [
            new ParameterSignature(
                type: $type ?? self::typeOf($value),
                qualifier: $qualifier,
                name: $name,
            ),
            $value,
        ];
    }

    /**
     * @param Arguments $arguments
     * @return array<non-empty-string, mixed>
     */
    public function resolveModuleArguments(\ReflectionFunctionAbstract $module, DIC $dic, array $arguments = []): array
    {
        $autowiring = clone $this;
        $autowiring->bind($dic);

        return $autowiring->resolveArguments($module, $arguments);
    }

    /**
     * @param Arguments $arguments
     * @return array<non-empty-string, mixed>
     */
    public function resolveArguments(\ReflectionFunctionAbstract $function, array $arguments = []): array
    {
        $resolved = [];

        foreach ($function->getParameters() as $index => $parameter) {
            $name = $parameter->getName();

            if ($parameter->isVariadic()) {
                continue; // todo
            }

            if (\array_key_exists($index, $arguments)) {
                if (\array_key_exists($name, $arguments)) {
                    throw new \LogicException('Ambiguous.');
                }

                $resolved[$name] = $arguments[$index];

                continue;
            }

            if (\array_key_exists($name, $arguments)) {
                $resolved[$name] = $arguments[$name];

                continue;
            }

            $resolved[$name] = $this->resolveArgument($parameter);
        }

        return $resolved;
    }

    private function resolveArgument(\ReflectionParameter $parameter): mixed
    {
        $values = array_filter(
            $this->bindings,
            static fn(array $binding) => $binding[0]->matches($parameter),
        );

        return match (\count($values)) {
            0 => $parameter->isDefaultValueAvailable()
                ? $parameter->getDefaultValue()
                : throw new \LogicException(\sprintf('Failed to autowire %s', formatReflectedParameter($parameter))),
            1 => $values[array_key_first($values)][1],
            default => throw new \LogicException('Ambiguous autowiring'),
        };
    }

    /**
     * @template T
     * @param T $value
     * @return Type<T>
     */
    private static function typeOf(mixed $value): Type
    {
        // @phpstan-ignore match.unhandled, return.type
        return match (true) {
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
}
