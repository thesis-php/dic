<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Signature;

use Thesis\Dic\Autowire;
use Thesis\Dic\BuildError;
use Thesis\Dic\DoNotAutowire;
use Thesis\Dic\Internal\Autowiring\BindingType;
use function Thesis\Formatter\formatReflectedParameter;

/**
 * @internal
 */
final class ReflectionParameter extends Parameter
{
    public function __construct(
        private readonly \ReflectionParameter $reflection,
    ) {}

    public function __toString(): string
    {
        return formatReflectedParameter($this->reflection);
    }

    public int $position {
        get => $this->reflection->getPosition();
    }

    public string $name {
        get => $this->reflection->name;
    }

    public bool $isPassedByReference {
        get => $this->reflection->isPassedByReference();
    }

    public bool $isVariadic {
        get => $this->reflection->isVariadic();
    }

    private bool $isDefaultValueSet = false;

    public ?DefaultValue $defaultValue = null {
        get {
            if (!$this->isDefaultValueSet) {
                $this->defaultValue = $this->reflection->isDefaultValueAvailable()
                    ? new ReflectionDefaultValue($this->reflection)
                    : null;
                $this->isDefaultValueSet = true;
            }

            return $this->defaultValue;
        }
    }

    protected function inferBindingType(): BindingType
    {
        return BindingType::ofParameter($this->reflection);
    }

    private bool $isAutowiringModeSet = false;

    public null|Autowire|DoNotAutowire $autowiringMode = null {
        get {
            if (!$this->isAutowiringModeSet) {
                $this->autowiringMode = self::reflectAutowiringMode();
                $this->isAutowiringModeSet = true;
            }

            return $this->autowiringMode;
        }
    }

    private function reflectAutowiringMode(): null|Autowire|DoNotAutowire
    {
        $attributes = [
            ...$this->reflection->getAttributes(Autowire::class),
            ...$this->reflection->getAttributes(DoNotAutowire::class),
        ];

        return match (\count($attributes)) {
            0 => null,
            1 => $attributes[0]->newInstance(),
            default => throw BuildError::conflictingAutowireMarkers(),
        };
    }
}
