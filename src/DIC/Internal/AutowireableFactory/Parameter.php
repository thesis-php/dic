<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\AutowireableFactory;

use Thesis\DIC\Internal\Autowiring\StringifyBindingType;
use Thesis\DIC\Mapping\DoNotAutowire;
use Thesis\DIC\Mapping\Qualifier;
use Typhoon\Type;
use function Thesis\Formatter\format;
use function Thesis\Formatter\formatReflectedFunction;
use function Thesis\Formatter\formatReflectedType;

/**
 * @internal
 */
final class Parameter
{
    public function __construct(
        public readonly \ReflectionParameter $reflection,
    ) {}

    /**
     * @var non-empty-string
     */
    public string $name {
        get => $this->reflection->getName();
    }

    /**
     * @var non-negative-int
     */
    public int $position {
        /** @phpstan-ignore return.type */
        get => $this->reflection->getPosition();
    }

    /**
     * @var non-empty-string
     */
    public string $formattedName {
        get {
            $formattedFunction = formatReflectedFunction($this->reflection->getDeclaringFunction());
            $qualifier = $this->qualifier;
            $type = $this->reflection->getType();

            return \sprintf(
                '%s(%s%s$%s)',
                substr($formattedFunction, 0, -2),
                $qualifier === '' ? '' : \sprintf('#[Qualifier(%s)] ', format($qualifier)),
                $type === null ? '' : formatReflectedType($this->reflection->getType()) . ' ',
                $this->name,
            );
        }
    }

    /**
     * @var list<non-empty-lowercase-string>
     */
    public array $bindingTypes {
        get => StringifyBindingType::reflected(
            type: $this->reflection->getType(),
            self: $this->reflection->getDeclaringFunction()->getClosureScopeClass()?->name,
            static: $this->reflection->getDeclaringFunction()->getClosureCalledClass()?->name,
        );
    }

    public bool $hasDefaultValue {
        get => $this->reflection->isDefaultValueAvailable();
    }

    public mixed $defaultValue {
        get => $this->reflection->getDefaultValue();
    }

    public bool $isVariadic {
        get => $this->reflection->isVariadic();
    }

    public string|\Stringable|\UnitEnum $qualifier {
        get  => ($this->reflection->getAttributes(Qualifier::class)[0] ?? null)?->newInstance()->value ?? '';
    }

    public bool $isAutowirable {
        get  => $this->reflection->getAttributes(DoNotAutowire::class) === []
            && $this->reflection->getDeclaringFunction()->getAttributes(DoNotAutowire::class) === [];
    }
}
