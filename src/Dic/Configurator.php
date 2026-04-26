<?php

declare(strict_types=1);

namespace Thesis\Dic;

use Thesis\Dic\Exception\BindingTypeNotSupported;
use Thesis\Dic\Exception\ContainerAlreadyBuilt;
use Thesis\Dic\Exception\InvalidConfiguration;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\ContainerBuilder;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\NonCopyable;
use Typhoon\Type;

/**
 * @api
 *
 * @template T
 * @implements Ref<T>
 */
abstract class Configurator implements Ref
{
    use NonCopyable;

    /**
     * @var non-empty-string
     */
    private readonly string $description;

    private bool $configurable = true;

    abstract protected Autowiring $autowiring { get; }

    abstract protected ContainerBuilder $containerBuilder { get; }

    /**
     * @internal
     *
     * @param non-empty-string $label
     */
    public function __construct(
        string $label,
        Location $declaredAt,
    ) {
        $this->description = "[{$label} at {$declaredAt}]";

        $this->containerBuilder->onRegistration(function (): void {
            try {
                $factory = $this->createFactory();
            } catch (\Throwable $error) {
                throw new InvalidConfiguration($this, $error);
            }

            $this->containerBuilder->registerFactory($this, $factory);

            $this->configurable = false;
        });
    }

    /**
     * @param Type<contravariant T> $type
     * @throws BindingTypeNotSupported
     */
    final public function bind(Type $type, string|\Stringable|\UnitEnum $qualifier = ''): static
    {
        $this->ensureConfigurable();

        $this->autowiring->bind($this, $type, $qualifier);

        return $this;
    }

    /**
     * @param Tag<T> $tag
     */
    final public function tag(Tag $tag): static
    {
        $this->ensureConfigurable();

        $this->containerBuilder->tag($this, $tag);

        return $this;
    }

    /**
     * @param callable(T, ?\Throwable): void $disposer
     */
    final public function disposer(callable $disposer): static
    {
        $this->ensureConfigurable();

        $this->containerBuilder->addDisposer($this, $disposer);

        return $this;
    }

    /**
     * @return non-empty-string
     */
    final public function __toString(): string
    {
        return $this->description;
    }

    final protected function ensureConfigurable(): void
    {
        if (!$this->configurable) {
            throw new ContainerAlreadyBuilt($this);
        }
    }

    /**
     * @return Factory<T>
     */
    abstract protected function createFactory(): Factory;
}
