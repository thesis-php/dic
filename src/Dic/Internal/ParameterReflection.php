<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Typhoon\Type;
use function Thesis\Formatter\formatReflectedParameter;
use function Typhoon\Type\stringify;
use const Thesis\Dic\autowire;

/**
 * @internal
 */
final class ParameterReflection
{
    public static function fromReflection(\ReflectionParameter $reflection): self
    {
        $position = $reflection->getPosition();
        \assert($position >= 0);

        $name = $reflection->name;
        \assert($name !== '');

        return new self(
            position: $position,
            name: $name,
            formattedName: formatReflectedParameter($reflection),
            type: TypeReflector::parameterType($reflection),
            hasDefault: $reflection->isDefaultValueAvailable(),
            isPassedByReference: $reflection->isPassedByReference(),
            isVariadic: $reflection->isVariadic(),
            argument: self::reflectArgument($reflection)
                ?? self::reflectArgument($reflection->getDeclaringFunction())
                ?? autowire,
        );
    }

    private static function reflectArgument(\ReflectionFunctionAbstract|\ReflectionParameter $reflection): ?ArgumentAttribute
    {
        $attributes = $reflection->getAttributes(ArgumentAttribute::class, \ReflectionAttribute::IS_INSTANCEOF);

        return match (\count($attributes)) {
            0 => null,
            1 => $attributes[0]->newInstance(),
            default => throw new \LogicException(),
        };
    }

    /**
     * @param non-negative-int $position
     */
    public static function fromSignature(int $position, Type\Parameter $parameter): self
    {
        return new self(
            position: $position,
            name: $parameter->name,
            formattedName: 'TODO',
            type: $parameter->type,
            hasDefault: $parameter->hasDefault,
            isPassedByReference: $parameter->isPassedByReference,
            isVariadic: $parameter->isVariadic,
            argument: autowire,
        );
    }

    /**
     * @var non-empty-string
     */
    public string $variable;

    /**
     * @param non-negative-int $position
     * @param ?non-empty-string $name
     * @param non-empty-string $formattedName
     */
    private function __construct(
        public readonly int $position,
        public readonly ?string $name,
        public readonly string $formattedName,
        public readonly ?Type $type,
        public readonly bool $hasDefault,
        public readonly bool $isPassedByReference,
        public readonly bool $isVariadic,
        public readonly ArgumentAttribute $argument,
    ) {
        $this->variable = '$' . ($name ?? "__p{$position}");
    }

    /**
     * @return non-empty-string
     */
    public function print(): string
    {
        $signature = $this->type === null ? '' : stringify($this->type) . ' ';

        if ($this->isPassedByReference) {
            $signature .= '&';
        }

        if ($this->isVariadic) {
            $signature .= '...';
        }

        $signature .= $this->variable;

        if ($this->hasDefault) {
            throw new \LogicException();
        }

        return $signature;
    }
}
