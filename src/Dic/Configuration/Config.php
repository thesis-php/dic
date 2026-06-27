<?php

declare(strict_types=1);

namespace Thesis\Dic\Configuration;

use Thesis\Dic\BuildError;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Autowiring\BindingType;
use Thesis\Dic\Internal\Autowiring\UnsupportedBindingType;
use Thesis\Dic\Internal\Builder;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Location;
use Typhoon\Type;

/**
 * @api
 *
 * @template T
 * @extends Autoconfig<T>
 *
 * @phpstan-sealed ValueConfig|ClosureConfig|ObjectConfig|MethodConfig|ScopedConfig|TaggedListConfig
 */
abstract class Config extends Autoconfig
{
    protected function __construct(
        Builder $builder,
        protected readonly Autowiring $autowiring,
        public readonly Location $declaredAt,
    ) {
        parent::__construct($builder);

        $builder->register($this, $this->createFactory(...));
    }

    /**
     * @return non-empty-string
     */
    abstract protected function defaultLabel(): string;

    /**
     * @phpstan-ignore property.uninitialized
     */
    public private(set) string $label {
        get => $this->label ??= $this->defaultLabel();
    }

    /**
     * @param non-empty-string $label
     */
    final public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @param Type<contravariant T> $type
     */
    final public function bind(Type $type, string|\Stringable|\UnitEnum $qualifier = ''): static
    {
        try {
            $bindingType = BindingType::ofTyphoonType($type);
        } catch (UnsupportedBindingType $error) {
            throw BuildError::unsupportedBindingType($type, $error);
        }

        $this->autowiring->bind($this, $bindingType, $qualifier);

        return $this;
    }

    final public function doNotAutoconfigure(): static
    {
        $this->isAutoconfigurable = false;

        return $this;
    }

    /**
     * @return Factory<T>
     */
    abstract protected function createFactory(): Factory;
}
