<?php

declare(strict_types=1);

namespace Thesis\Dic;

use Thesis\Dic\Configurator\ScopedConfigurator;
use Thesis\Dic\Exception\ContainerAlreadyBuilt;
use Thesis\Dic\Exception\InvalidConfiguration;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\ContainerBuilder;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Lifetime;

/**
 * @api
 *
 * This class must not be extended in userland.
 * Its protected API is not covered by the BC promise.
 *
 * @template-covariant T
 */
abstract class Ref
{
    private bool $resolved = false;

    final protected ?Lifetime $lifetime = null {
        set {
            $this->ensureConfigurable();

            $this->lifetime = $value;
        }
    }

    /**
     * @internal
     *
     * @param non-empty-string $label
     */
    protected function __construct(
        private readonly string $label,
        private readonly Location $declaredAt,
        protected readonly Autowiring $autowiring,
        protected readonly ContainerBuilder $containerBuilder,
        ?Lifetime $lifetime = null,
    ) {
        $this->lifetime = $lifetime;
        $this->containerBuilder->onRegistration($this->resolve(...));
    }

    /**
     * @return Factory<T>
     */
    abstract protected function createFactory(): Factory;

    final protected function ensureConfigurable(): void
    {
        if ($this->resolved) {
            throw new ContainerAlreadyBuilt($this);
        }
    }

    /**
     * @phpstan-assert Lifetime $this->lifetime
     */
    private function resolve(): void
    {
        if ($this->resolved) {
            \assert($this->lifetime !== null);

            return;
        }

        try {
            $factory = $this->createFactory();
        } catch (\Throwable $error) {
            throw new InvalidConfiguration($this, $error);
        }

        $this->resolveLifetime($factory);

        $this->containerBuilder->addFactory($this, $this->lifetime, $factory);

        $this->resolved = true;
    }

    /**
     * @var array<string, Ref<*>>
     */
    private array $transitiveScopedDependencies = [];

    /**
     * @param Factory<T> $factory
     * @phpstan-assert Lifetime $this->lifetime
     */
    private function resolveLifetime(Factory $factory): void
    {
        if ($this->lifetime === Lifetime::Scoped) {
            return;
        }

        if ($this instanceof ScopedConfigurator) {
            \assert($this->lifetime === Lifetime::Singleton);

            return;
        }

        foreach ($factory->dependencies() as $path => $ref) {
            $ref->resolve();

            if ($ref->lifetime === Lifetime::Singleton) {
                continue;
            }

            if ($ref->transitiveScopedDependencies === []) {
                $this->transitiveScopedDependencies[$path] = $ref;

                continue;
            }

            foreach ($ref->transitiveScopedDependencies as $nextPath => $scopedRef) {
                $this->transitiveScopedDependencies[$path . $nextPath] = $scopedRef;
            }
        }

        if ($this->transitiveScopedDependencies === []) {
            $this->lifetime = Lifetime::Singleton;

            return;
        }

        if ($this->lifetime === Lifetime::Singleton) {
            // todo message
            throw new \LogicException("{$this} cannot be a singleton");
        }

        $this->lifetime = Lifetime::Scoped;
    }

    /**
     * @return non-empty-string
     */
    final public function __toString(): string
    {
        return "[{$this->label} at {$this->declaredAt}]";
    }
}
