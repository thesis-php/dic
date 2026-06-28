<?php

declare(strict_types=1);

namespace Thesis\Dic\Configuration;

use Thesis\Dic\BuildError;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Autowiring\BindingType;
use Thesis\Dic\Internal\Autowiring\UnsupportedBindingType;
use Thesis\Dic\Internal\Builder;
use Thesis\Dic\Internal\Builder\LifetimeStrategy;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Signature;
use Thesis\Dic\Location;
use Thesis\Dic\Tag;
use Typhoon\Type;

/**
 * @api
 *
 * @template-covariant T
 * @implements Autoconfig<T>
 *
 * @phpstan-sealed FactoryConfig|MethodConfig|ScopedConfig|TaggedListConfig|ValueConfig
 */
abstract class Config implements Autoconfig
{
    protected function __construct(
        protected readonly Builder $builder,
        protected readonly Autowiring $autowiring,
        public readonly Location $declaredAt,
    ) {
        $builder->register($this, $this->createFactory(...));
    }

    /**
     * @see Builder\Autoconfiguration::autoconfigure()
     */
    protected private(set) bool $isAutoconfiguring = false;

    abstract protected LifetimeStrategy $lifetimeStrategy { get; }

    abstract protected ?Signature $signature { get; }

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

    /**
     * @phpstan-ignore property.onlyWritten
     */
    private bool $isAutoconfigurable = true;

    final public function doNotAutoconfigure(): static
    {
        $this->isAutoconfigurable = false;

        return $this;
    }

    final public function tag(Tag $tag): static
    {
        $this->builder->addTag($this, $tag);

        return $this;
    }

    final public function disposer(callable $disposer): static
    {
        $this->builder->addDisposer($this, $disposer);

        return $this;
    }

    /**
     * @return Factory<T>
     */
    abstract protected function createFactory(): Factory;

    final public function __toString(): string
    {
        return \sprintf('"%s" (%s)', $this->label, $this->declaredAt);
    }
}
