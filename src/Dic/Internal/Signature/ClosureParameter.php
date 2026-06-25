<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Signature;

use Thesis\Dic\Autowire;
use Thesis\Dic\DoNotAutowire;
use Thesis\Dic\Internal\Autowiring\AutowiringType;
use Typhoon\Type;

/**
 * @internal
 */
final class ClosureParameter extends Parameter
{
    public bool $isPassedByReference {
        get => $this->parameter->isPassedByReference;
    }

    public bool $isVariadic {
        get => $this->parameter->isVariadic;
    }

    public ?DefaultValue $defaultValue {
        get => $this->parameter->hasDefault ? ClosureDefaultValue::Value : null;
    }

    public null|Autowire|DoNotAutowire $autowiringMode {
        get => null;
    }

    protected function reflectAutowiringType(): AutowiringType
    {
        return AutowiringType::ofTyphoonType($this->parameter->type);
    }

    /**
     * @param non-negative-int $position
     * @param non-empty-string $name
     */
    public function __construct(
        public readonly int $position,
        public readonly string $name,
        public readonly Type\Parameter $parameter,
    ) {}

    /**
     * Emulate the {@see formatReflectedParameter()} shape without building the
     * closure's ReflectionFunction.
     */
    public function __toString(): string
    {
        return \sprintf('function($%s)', $this->name);
    }
}
