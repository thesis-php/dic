<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\Autowire;
use Thesis\Dic\BuildError;
use Thesis\Dic\DoNotAutowire;
use Thesis\Dic\Internal\Arguments\ClosureArguments;
use Thesis\Dic\Internal\Autowiring\UnsupportedBindingType;
use Thesis\Dic\Internal\Factory\ValueFactory;
use Thesis\Dic\Internal\Signature\DefaultValue;
use Thesis\Dic\Internal\Signature\Parameter;
use Thesis\Dic\Ref;
use Typhoon\Type\Parameter as ClosureParameter;
use const Thesis\Dic\autowire;
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
            ?? throw BuildError::unknownSignatureParameter($this->signature, $positionOrName);

        if ($parameter->isVariadic) {
            $this->appendVariadic($parameter, $positionOrName, $value);

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
        if ($value === []) {
            $this->variadic = [];

            return;
        }

        $parameter = $this->signature->variadicParameter
            ?? throw BuildError::unknownSignatureParameter($this->signature, true);

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

    private function appendVariadic(Parameter $parameter, int|string $positionOrName, mixed $value): void
    {
        $this->validateNestedElement($value);

        if (!\is_array($this->variadic)) {
            throw BuildError::cannotAppendToNonArrayVariadic($parameter);
        }

        if (\is_int($positionOrName)
            && !\array_key_exists($positionOrName, $this->variadic)
            && \is_string(array_key_last($this->variadic))
        ) {
            throw BuildError::positionalVariadicAfterNamed($parameter);
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
            throw BuildError::variadicNotAutowirable($parameter);
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
            throw BuildError::markersNotAllowedAsArrayElements();
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
                ?? $this->signature->autowiringMode
                ?? autowire;
        }

        if ($argument instanceof Autowire) {
            return $this->autowire($parameter, $argument);
        }

        if ($argument instanceof DoNotAutowire) {
            return $parameter->defaultValue ?? throw BuildError::cannotAutowireMarkedNotAutowired($parameter);
        }

        return ValueFactory::from($argument);
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

    /**
     * @return ValueFactory|TClosureParameter|DefaultValue
     */
    private function autowire(Parameter $parameter, Autowire $autowire): ValueFactory|ClosureParameter|DefaultValue
    {
        try {
            $bindingType = $parameter->bindingType;
        } catch (UnsupportedBindingType $error) {
            return $parameter->defaultValue ?? throw BuildError::cannotAutowireUnsupportedBindingType($parameter, $error);
        }

        $candidates = [];

        // todo think about it
        if ($autowire->qualifier === '') {
            $candidates = $this->closureArguments->autowire($parameter);
        }

        $ref = $this->autowiring->autowire($bindingType, $autowire->qualifier);

        if ($ref !== null) {
            $candidates[] = $ref;
        }

        if ($candidates === []) {
            return $parameter->defaultValue ?? throw BuildError::cannotAutowireNoCandidate($parameter);
        }

        if (\count($candidates) > 1) {
            throw BuildError::cannotAutowireAmbiguous($parameter, $candidates);
        }

        if ($candidates[0] instanceof Ref) {
            return ValueFactory::from($candidates[0]);
        }

        return $candidates[0];
    }
}
