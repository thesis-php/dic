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
use Thesis\Dic\Location;
use Thesis\Dic\Ref;
use Thesis\Dic\Tag;
use Typhoon\Type;

/**
 * @api
 *
 * @template-covariant T
 * @extends Ref<T>
 *
 * @phpstan-sealed ClosureConfig|ObjectConfig|FunctionConfig|ScopedConfig|TaggedListConfig|ValueConfig|ProviderConfig
 */
abstract class Config extends Ref
{
    /**
     * @param non-empty-string $label
     */
    protected function __construct(
        protected readonly Builder $builder,
        protected readonly Autowiring $autowiring,
        string $label,
        Location $declaredAt,
        LifetimeStrategy $defaultLifetimeStrategy,
    ) {
        parent::__construct($label, $declaredAt);

        $builder->register(
            config: $this,
            createFactory: $this->createFactory(...),
            defaultLifetimeStrategy: $defaultLifetimeStrategy,
        );
    }

    /**
     * Binds this service to $type so it is injected wherever that type (with optional $qualifier) is required.
     *
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
     * Attaches $tag to this service so it appears in every taggedList() that collects by that tag.
     *
     * @param Tag<T> $tag
     */
    final public function tag(Tag $tag): static
    {
        $this->builder->addTag($this, $tag);

        return $this;
    }

    /**
     * Registers a callback that runs when the service's owning scope or container is torn down.
     *
     * @param callable(T, ?\Throwable): void $disposer
     */
    final public function disposer(callable $disposer): static
    {
        $this->builder->addDisposer($this, $disposer);

        return $this;
    }

    /**
     * @return Factory<T>
     */
    abstract protected function createFactory(): Factory;
}
