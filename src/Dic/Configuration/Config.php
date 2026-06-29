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
 * @phpstan-sealed ClosureConfig|ObjectConfig|FunctionConfig|ScopedConfig|TaggedListConfig|ValueConfig
 */
abstract class Config extends Ref
{
    protected function __construct(
        protected readonly Builder $builder,
        protected readonly Autowiring $autowiring,
        public readonly Location $declaredAt,
        LifetimeStrategy $defaultLifetimeStrategy,
    ) {
        $builder->register(
            config: $this,
            createFactory: $this->createFactory(...),
            defaultLifetimeStrategy: $defaultLifetimeStrategy,
        );
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
     * @param Tag<T> $tag
     */
    final public function tag(Tag $tag): static
    {
        $this->builder->addTag($this, $tag);

        return $this;
    }

    /**
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
