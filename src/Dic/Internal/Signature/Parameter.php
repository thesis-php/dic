<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Signature;

use Thesis\Dic\Autowire;
use Thesis\Dic\DoNotAutowire;
use Thesis\Dic\Internal\Autowiring\BindingType;
use Thesis\Dic\Internal\Autowiring\UnsupportedBindingType;

/**
 * @internal
 */
abstract class Parameter
{
    /**
     * @var non-negative-int
     */
    abstract public int $position { get; }

    /**
     * @var non-empty-string
     */
    abstract public string $name { get; }

    abstract public bool $isPassedByReference { get; }

    abstract public bool $isVariadic { get; }

    final public bool $hasDefaultValue { get => $this->defaultValue !== null; }

    abstract public ?DefaultValue $defaultValue { get; }

    final public bool $isOptional {
        get => $this->isVariadic || $this->hasDefaultValue;
    }

    abstract public null|Autowire|DoNotAutowire $autowiringMode { get; }

    private null|BindingType|UnsupportedBindingType $bindingTypeCache = null;

    final public BindingType $bindingType {
        /**
         * @throws UnsupportedBindingType
         */
        get {
            if ($this->bindingTypeCache instanceof BindingType) {
                return $this->bindingTypeCache;
            }

            if ($this->bindingTypeCache instanceof UnsupportedBindingType) {
                throw $this->bindingTypeCache;
            }

            try {
                return $this->bindingTypeCache = $this->inferBindingType();
            } catch (UnsupportedBindingType $error) {
                $this->bindingTypeCache = $error;

                throw $error;
            }
        }
    }

    /**
     * @throws UnsupportedBindingType
     */
    abstract protected function inferBindingType(): BindingType;

    /**
     * @return non-empty-string
     */
    abstract public function __toString(): string;
}
