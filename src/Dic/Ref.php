<?php

declare(strict_types=1);

namespace Thesis\Dic;

use Thesis\Dic\Exception\ContainerAlreadyBuilt;
use Thesis\Dic\Exception\InvalidConfiguration;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\ContainerBuilder;
use Thesis\Dic\Internal\Factories;
use Thesis\Dic\Internal\Factory;

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
    abstract public Lifetime $lifetime { get; }

    /** @var null|\ReflectionFunction|\ReflectionMethod|\ReflectionClass<*> */
    abstract protected null|\ReflectionFunction|\ReflectionMethod|\ReflectionClass $reflection { get; }

    /**
     * @internal
     *
     * @param non-empty-string $label
     */
    protected function __construct(
        protected readonly string $label,
        protected readonly Location $declaredAt,
        protected readonly Autowiring $autowiring,
        protected readonly ContainerBuilder $containerBuilder,
    ) {
        $containerBuilder->onRegistration(function (Factories $factories): void {
            try {
                $factory = $this->createFactory();
            } catch (\Throwable $error) {
                throw new InvalidConfiguration($this, $error);
            }

            $factories->register($this, $factory);

            $this->configurable = false;
        });
    }

    /**
     * @return non-empty-string
     */
    final public function __toString(): string
    {
        return "[{$this->label} at {$this->declaredAt}]";
    }

    /**
     * @return Factory<T>
     */
    abstract protected function createFactory(): Factory;

    private bool $configurable = true;

    final protected function ensureConfigurable(): void
    {
        if (!$this->configurable) {
            throw new ContainerAlreadyBuilt($this);
        }
    }
}
