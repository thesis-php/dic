<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\AutowirableFunction;

use Thesis\DIC\Mapping\DoNotAutowire;
use Thesis\DIC\Mapping\Qualifier;
use Typhoon\Type;
use function Thesis\DIC\Internal\Type\fromReflectedType;
use function Typhoon\Formatter\format;
use function Typhoon\Formatter\formatReflectedFunction;
use function Typhoon\Formatter\formatReflectedParameter;

/**
 * @internal
 */
final class Parameter
{
    public function __construct(
        private readonly \ReflectionParameter $reflection,
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
        get => formatReflectedParameter($this->reflection);
    }

    /**
     * @var non-empty-string
     */
    public string $formattedNameWithQualifierAndType {
        get {
            $formattedFunction = formatReflectedFunction($this->reflection->getDeclaringFunction());

            $qualifier = $this->qualifier;

            return \sprintf(
                '%s(%s%s$%s)',
                substr($formattedFunction, 0, -2),
                $qualifier === '' ? '' : \sprintf('#[Qualifier(%s)] ', format($qualifier)),
                $this->type === null ? '' : Type\stringify($this->type) . ' ',
                $this->name,
            );
        }
    }

    /**
     * @phpstan-ignore property.uninitialized
     */
    public ?Type $type {
        get => $this->type ??= fromReflectedType(
            type: $this->reflection->getType(),
            self: $this->reflection->getDeclaringFunction()->getClosureScopeClass()?->name,
            static: $this->reflection->getDeclaringFunction()->getClosureCalledClass()?->name,
        );
    }

    public bool $hasDefault {
        get => $this->reflection->isDefaultValueAvailable();
    }

    public mixed $default {
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
