<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\Autowire;
use Thesis\Dic\DoNotAutowire;
use Thesis\Dic\Error\CannotAutowire;
use Thesis\Dic\Error\InvalidArgument;
use Thesis\Dic\Internal\Arguments\ClosureArguments;
use Thesis\Dic\Internal\Autowiring\UnsupportedType;
use Thesis\Dic\Internal\Factory\ValueFactory;
use Thesis\Dic\Internal\Signature\DefaultValue;
use Thesis\Dic\Internal\Signature\Parameter;
use Thesis\Dic\Ref;
use Typhoon\Type\Parameter as ClosureParameter;
use const Thesis\Dic\doNotAutowire;

/**
 * @internal
 *
 * @template TClosureParameter of ClosureParameter = never
 */
final class Arguments
{
    /**
     * @var array<non-empty-string, mixed>
     */
    private array $regular = [];

    /**
     * @var iterable<array-key, mixed>|Ref<iterable<array-key, mixed>>|TClosureParameter
     */
    private iterable|Ref|ClosureParameter $variadic = [];

    /**
     * @param ClosureArguments<TClosureParameter> $closureArguments
     */
    public function __construct(
        private readonly Signature $signature,
        private readonly Autowiring $autowiring,
        private readonly ClosureArguments $closureArguments,
    ) {}

    private ?DoNotAutowire $autowiringMode = null;

    public function doNotAutowire(): void
    {
        $this->autowiringMode = doNotAutowire;
    }

    public function arg(int|string $positionOrName, mixed $value): void
    {
        $parameter = $this->signature->findParameter($positionOrName)
            ?? $this->signature->variadicParameter
            ?? throw new InvalidArgument("Unknown parameter {$positionOrName}");

        if ($parameter->isVariadic) {
            $this->appendVariadic($positionOrName, $value);

            return;
        }

        $this->validate($parameter, $value);

        $this->regular[$parameter->name] = $value;
    }

    /**
     * @param iterable<array-key, mixed>|Ref<iterable<array-key, mixed>>|TClosureParameter $value
     */
    public function variadic(iterable|Ref|ClosureParameter $value): void
    {
        $parameter = $this->signature->variadicParameter
            ?? throw new InvalidArgument('Cannot set a variadic: the function has no variadic parameter');

        $this->validate($parameter, $value);

        $this->variadic = $value;
    }

    /**
     * @param array<mixed> $values
     */
    public function args(array $values): void
    {
        foreach ($values as $positionOrName => $value) {
            $this->arg($positionOrName, $value);
        }
    }

    private function appendVariadic(int|string $positionOrName, mixed $value): void
    {
        $this->validateNestedElement($value);

        if (!\is_array($this->variadic)) {
            throw new InvalidArgument('Cannot append to a variadic that was set to a non-array value');
        }

        if (\is_int($positionOrName)
            && !\array_key_exists($positionOrName, $this->variadic)
            && \is_string(array_key_last($this->variadic))
        ) {
            throw new InvalidArgument('Cannot set a positional variadic element after a named one');
        }

        $this->variadic[$positionOrName] = $value;
    }

    private function validate(Parameter $parameter, mixed $value): void
    {
        if (\is_array($value)) {
            foreach ($value as $item) {
                $this->validateNestedElement($item);
            }

            return;
        }

        if ($parameter->isVariadic && $value instanceof Autowire) {
            throw new ShouldNotHappen('A variadic parameter cannot be combined with #[Autowire]');
        }

        if ($value instanceof ClosureParameter) {
            $this->closureArguments->validate($parameter, $value);
        }
    }

    private function validateNestedElement(mixed $value): void
    {
        if (\is_array($value)) {
            foreach ($value as $item) {
                $this->validateNestedElement($item);
            }

            return;
        }

        if ($value instanceof ClosureParameter || $value instanceof Autowire || $value instanceof DoNotAutowire) {
            throw new InvalidArgument('Autowire, DoNotAutowire and signature parameter markers cannot be used as array elements');
        }
    }

    /**
     * @return list<array{Parameter, ValueFactory|TClosureParameter|DefaultValue}>
     */
    public function resolveRegular(): array
    {
        return array_map(
            fn(Parameter $parameter) => [
                $parameter,
                $this->resolveRegularValue($parameter),
            ],
            $this->signature->regularParameters,
        );
    }

    /**
     * @return ?array{Parameter, ValueFactory|TClosureParameter}
     */
    public function resolveVariadic(): ?array
    {
        $parameter = $this->signature->variadicParameter;

        if ($parameter === null) {
            return null;
        }

        return [
            $parameter,
            $this->resolveVariadicValue($parameter),
        ];
    }

    /**
     * @return list<array{Parameter, ValueFactory|TClosureParameter|DefaultValue}>
     */
    public function resolve(): array
    {
        return array_map(
            fn(Parameter $parameter) => [
                $parameter,
                $parameter->isVariadic
                    ? $this->resolveVariadicValue($parameter)
                    : $this->resolveRegularValue($parameter),
            ],
            $this->signature->parameters,
        );
    }

    /**
     * @return ValueFactory|TClosureParameter|DefaultValue
     */
    private function resolveRegularValue(Parameter $parameter): ValueFactory|DefaultValue|ClosureParameter
    {
        if (\array_key_exists($parameter->name, $this->regular)) {
            $argument = $this->regular[$parameter->name];
        } else {
            $argument = $parameter->autowiringMode
                ?? $this->autowiringMode
                ?? $this->signature->autowiringMode;
        }

        if ($argument === null || $argument instanceof Autowire) {
            return $this->autowire($parameter, $argument);
        }

        if ($argument instanceof DoNotAutowire) {
            return $parameter->defaultValue ?? throw new CannotAutowire("{$parameter} is marked as not autowired and has no default value");
        }

        return ValueFactory::from($argument);
    }

    /**
     * @return ValueFactory|TClosureParameter|DefaultValue
     */
    private function autowire(Parameter $parameter, ?Autowire $autowire): ValueFactory|ClosureParameter|DefaultValue
    {
        try {
            $autowiringType = $parameter->autowiringType;
        } catch (UnsupportedType $error) {
            return $parameter->defaultValue ?? throw new CannotAutowire(
                message: "{$parameter} cannot be autowired: {$error->getMessage()}",
                previous: $error,
            );
        }

        $candidates = [];

        if ($autowire === null) {
            $candidates = $this->closureArguments->autowire($parameter);
        }

        $ref = $this->autowiring->autowire($autowiringType, $autowire->qualifier ?? '');

        if ($ref !== null) {
            $candidates[] = ValueFactory::from($ref);
        }

        return match (\count($candidates)) {
            0 => $parameter->defaultValue ?? throw new ShouldNotHappen("No autowiring candidate and no default value for {$parameter}"),
            1 => array_first($candidates),
            default => throw new ShouldNotHappen("Multiple autowiring candidates for {$parameter}"),
        };
    }

    /**
     * @return ValueFactory|TClosureParameter
     */
    private function resolveVariadicValue(Parameter $parameter): ValueFactory|ClosureParameter
    {
        if ($this->variadic instanceof ClosureParameter) {
            return $this->variadic;
        }

        if ($this->variadic === []) {
            return array_first($this->closureArguments->autowire($parameter)) ?? ValueFactory::from([]);
        }

        return ValueFactory::from($this->variadic);
    }
}
