<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator;

use Thesis\Dic\Exception\InvalidConfiguration;
use Thesis\Dic\Internal\Arguments;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\ResolvedArguments;
use Thesis\Dic\Location;

/**
 * @api
 *
 * @template T
 * @extends LifetimeConfigurator<T>
 */
abstract class ArgsConfigurator extends LifetimeConfigurator
{
    /**
     * @internal
     *
     * @param non-empty-string $label
     */
    public function __construct(
        string $label,
        Location $declaredAt,
        private readonly Arguments $arguments,
    ) {
        parent::__construct(
            label: $label,
            declaredAt: $declaredAt,
        );
    }

    final public function autowire(string|\Stringable|\UnitEnum $qualifier = ''): static
    {
        $this->ensureConfigurable();

        $this->arguments->autowire($qualifier);

        return $this;
    }

    final public function doNotAutowire(): static
    {
        $this->ensureConfigurable();

        $this->arguments->doNotAutowire();

        return $this;
    }

    final public function arg(int|string $param, mixed $arg): static
    {
        $this->ensureConfigurable();

        try {
            $this->arguments->set($param, $arg);
        } catch (\Throwable $error) {
            throw new InvalidConfiguration($this, $error);
        }

        return $this;
    }

    /**
     * @param array<mixed> $args
     */
    final public function args(array $args): static
    {
        $this->ensureConfigurable();

        foreach ($args as $param => $arg) {
            try {
                $this->arguments->set($param, $arg);
            } catch (\Throwable $error) {
                throw new InvalidConfiguration($this, $error);
            }
        }

        return $this;
    }

    final protected function createFactory(): Factory
    {
        return $this->createFactoryWithArguments(
            $this->arguments->resolve(
                ref: $this,
                autowiring: $this->autowiring,
            ),
        );
    }

    /**
     * @return Factory<T>
     */
    abstract protected function createFactoryWithArguments(ResolvedArguments $arguments): Factory;
}
