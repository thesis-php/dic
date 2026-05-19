<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Typhoon\Type;
use Typhoon\Type\ClosureT;
use function Thesis\Formatter\formatReflectedFunction;

/**
 * @internal
 */
final readonly class FunctionReflection
{
    public static function fromCallable(callable $function): self
    {
        return self::fromReflection(new \ReflectionFunction($function(...)));
    }

    public static function fromReflection(\ReflectionFunctionAbstract $reflection): self
    {
        return new self(
            formattedName: formatReflectedFunction($reflection),
            parameters: array_map(ParameterReflection::fromReflection(...), $reflection->getParameters()),
            returnType: TypeReflector::returnType($reflection),
        );
    }

    public static function fromSignature(ClosureT $type): self
    {
        return new self(
            formattedName: 'TODO',
            parameters: array_map(
                ParameterReflection::fromSignature(...),
                array_keys($type->parameters),
                $type->parameters,
            ),
            returnType: $type->returnType,
        );
    }

    /**
     * @param class-string $class
     */
    public static function implicitConstructor(string $class): self
    {
        return new self(
            formattedName: "{$class}::[__construct()]",
            parameters: [],
            returnType: null,
        );
    }

    /**
     * @param non-empty-string $formattedName
     * @param list<ParameterReflection> $parameters
     */
    private function __construct(
        public string $formattedName,
        public array $parameters,
        public ?Type $returnType,
    ) {}

    public function getParameter(int|string $positionOrName): ParameterReflection
    {
        return $this->findParameter($positionOrName) ?? throw new \LogicException();
    }

    public function findParameter(int|string $positionOrName): ?ParameterReflection
    {
        return array_find(
            $this->parameters,
            static fn($parameter) => $parameter->position === $positionOrName || $parameter->name === $positionOrName,
        );
    }
}
