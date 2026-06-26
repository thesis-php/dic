<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Arguments;

use Thesis\Dic\Error;
use Thesis\Dic\Internal\Autowiring\BindingType;
use Thesis\Dic\Internal\Autowiring\UnsupportedBindingType;
use Thesis\Dic\Internal\Signature\Parameter;
use Typhoon\Type\ClosureT;
use Typhoon\Type\Parameter as ClosureParameter;

/**
 * @internal
 *
 * @template T of ClosureParameter
 */
final readonly class ClosureArguments
{
    /**
     * @return self<never>
     */
    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * @return self<ClosureParameter>
     */
    public static function fromType(ClosureT $closure): self
    {
        return new self($closure->parameters);
    }

    /**
     * @param list<T> $arguments
     */
    private function __construct(
        private array $arguments,
    ) {}

    public function validate(Parameter $parameter, ClosureParameter $argument): void
    {
        if (!\in_array($argument, $this->arguments, strict: true)) {
            throw Error::unknownClosureParameterMapping($parameter);
        }

        if (($argument->isVariadic || $argument->hasDefault) && !$parameter->isOptional) {
            throw Error::optionalClosureParameterMapping($parameter);
        }

        if ($argument->isPassedByReference && !$parameter->isPassedByReference) {
            throw Error::byReferenceClosureParameterMapping($parameter);
        }
    }

    /**
     * @return list<T>
     */
    public function autowire(Parameter $parameter): array
    {
        return array_values(
            array_filter(
                $this->arguments,
                static fn(ClosureParameter $argument) => self::match($parameter, $argument),
            ),
        );
    }

    private static function match(Parameter $parameter, ClosureParameter $argument): bool
    {
        if ($argument->isVariadic !== $parameter->isVariadic) {
            return false;
        }

        if ($argument->name !== null && $argument->name !== $parameter->name) {
            return false;
        }

        if ($argument->isPassedByReference && !$parameter->isPassedByReference) {
            return false;
        }

        if ($argument->hasDefault && !$parameter->hasDefaultValue) {
            return false;
        }

        try {
            return BindingType::ofTyphoonType($argument->type)->equals($parameter->bindingType);
        } catch (UnsupportedBindingType) {
            return false;
        }
    }
}
