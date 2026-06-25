<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Signature;

use Thesis\Dic\Autowire;
use Thesis\Dic\DoNotAutowire;
use Thesis\Dic\Internal\Autowiring\AutowiringType;
use Thesis\Dic\Internal\Autowiring\UnsupportedType;

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

    private null|AutowiringType|UnsupportedType $autowiringTypeCache = null;

    final public AutowiringType $autowiringType {
        /**
         * @throws UnsupportedType
         */
        get {
            if ($this->autowiringTypeCache instanceof AutowiringType) {
                return $this->autowiringTypeCache;
            }

            if ($this->autowiringTypeCache instanceof UnsupportedType) {
                throw $this->autowiringTypeCache;
            }

            try {
                return $this->autowiringTypeCache = $this->reflectAutowiringType();
            } catch (UnsupportedType $error) {
                $this->autowiringTypeCache = $error;

                throw $error;
            }
        }
    }

    /**
     * @throws UnsupportedType
     */
    abstract protected function reflectAutowiringType(): AutowiringType;

    /**
     * @return non-empty-string a function-reference rendering of this parameter, e.g. Foo\Bar::method($baz)
     */
    abstract public function __toString(): string;
}
