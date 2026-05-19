<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator\Internal;

use Thesis\Dic\Autowire;
use Thesis\Dic\DoNotAutowire;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\FunctionReflection;
use Thesis\Dic\Internal\ParameterReflection;
use Thesis\Dic\Ref;
use Typhoon\Type\ClosureT;
use Typhoon\Type\Parameter;
use function Typhoon\Type\stringify;
use const Thesis\Dic\autowire;
use const Typhoon\Type\mixedT;

/**
 * @internal
 */
final class Arguments
{
    /**
     * @var array<non-negative-int, mixed>
     */
    private array $values;

    /**
     * @param array<mixed> $values
     */
    public function __construct(
        private readonly FunctionReflection $function,
        array $values = [],
    ) {
        $this->values = array_column($function->parameters, column_key: 'argumentAttribute');

        foreach ($values as $positionOrName => $value) {
            $this->set($positionOrName, $value);
        }
    }

    public function set(int|string $positionOrName, mixed $value): void
    {
        $parameter = $this->function->findParameter($positionOrName) ?? throw new \LogicException();

        $this->values[$parameter->position] = $value;
    }

    public function fill(mixed $value): void
    {
        $this->values = array_map(static fn() => $value, $this->function->parameters);
    }

    /**
     * @param Ref<*> $ref
     * @return array<non-negative-int, Factory|Parameter>
     */
    public function resolveForSignature(ClosureT $signature, Ref $ref, Autowiring $autowiring): array
    {
        return array_filter(
            array_map(
                fn(ParameterReflection $parameter) => $this->resolveArgument(
                    parameter: $parameter,
                    ref: $ref,
                    autowiring: $autowiring,
                    signatureParameters: $signature->parameters,
                ),
                $this->function->parameters,
            ),
            static fn(mixed $v) => $v !== null,
        );
    }

    /**
     * @param Ref<*> $ref
     */
    public function createFactory(Ref $ref, Autowiring $autowiring): Factory\Arguments
    {
        return new Factory\Arguments(array_filter(
            array_map(
                fn(ParameterReflection $parameter) => $this->resolveArgument(
                    parameter: $parameter,
                    ref: $ref,
                    autowiring: $autowiring,
                ),
                $this->function->parameters,
            ),
            static fn(mixed $v) => $v !== null,
        ));
    }

    /**
     * @param Ref<*> $ref
     * @param list<Parameter> $signatureParameters
     * @return ($signatureParameters is array{} ? null|Factory : null|Factory|Parameter)
     */
    private function resolveArgument(
        ParameterReflection $parameter,
        Ref $ref,
        Autowiring $autowiring,
        array $signatureParameters = [],
    ): null|Factory|Parameter {
        $value = $this->values[$parameter->position] ?? autowire;

        if ($value instanceof DoNotAutowire) {
            if ($parameter->hasDefault) {
                return null;
            }

            throw new \LogicException("Parameter `{$parameter->formattedName}` is not autowired and has no default value");
        }

        if ($value instanceof Parameter) {
            if (!\in_array($value, $signatureParameters, strict: true)) {
                throw new \LogicException();
            }

            if ($value->hasDefault && !$parameter->hasDefault) {
                throw new \LogicException();
            }

            if ($parameter->isPassedByReference && !$value->isPassedByReference) {
                throw new \LogicException();
            }

            return $value;
        }

        if ($value instanceof Autowire) {
            if ($parameter->type === null) {
                if ($parameter->hasDefault) {
                    return null;
                }

                throw new \LogicException("Required parameter `{$parameter->formattedName}` does not have a type to be autowired");
            }

            foreach ($signatureParameters as $signatureParameter) {
                if (self::matchSignature($signatureParameter, $parameter)) {
                    // todo remove from list of used parameters, take into account explicitly set
                    return $signatureParameter;
                }
            }

            $value = $autowiring->autowire($parameter->type, $value->qualifier);

            if ($value === null) {
                if ($parameter->hasDefault) {
                    return null;
                }

                throw new \LogicException("No bound autowiring services match required parameter `{$parameter->formattedName}`");
            }
        }

        self::validate($value, '$' . $parameter->name, $ref);

        return Factory\Value::from($value);
    }

    private static function matchSignature(Parameter $signature, ParameterReflection $implementation): bool
    {
        return ($signature->name === null || $signature->name === $implementation->name)
            && (!$implementation->isPassedByReference || $signature->isPassedByReference)
            && $signature->isVariadic === $implementation->isVariadic
            && stringify($signature->type) === stringify($implementation->type ?? mixedT);
    }

    /**
     * @param Ref<*> $ref
     * @param non-empty-string $path
     */
    private static function validate(mixed $value, string $path, Ref $ref): void
    {
        if (\is_array($value)) {
            array_walk($value, static fn(mixed $item, int|string $key) => self::validate(
                value: $item,
                path: $path . '.' . $key,
                ref: $ref,
            ));

            return;
        }

        if (!$value instanceof Ref) {
            return;
        }

        if ($value === $ref) {
            throw new \LogicException("Cyclic dependency: {$ref} depends on itself at {$path}");
        }
    }
}
