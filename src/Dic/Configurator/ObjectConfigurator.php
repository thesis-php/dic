<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator;

use Thesis\Dic\Internal\Arguments;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\ContainerBuilder;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Factory\CallAfter;
use Thesis\Dic\Internal\Factory\ChainCallAfter;
use Thesis\Dic\Internal\Factory\Constructor;
use Thesis\Dic\Internal\Factory\LazyObject;
use Thesis\Dic\Internal\ResolvedArguments;
use Thesis\Dic\Location;
use function Thesis\Formatter\formatClass;

/**
 * @api
 *
 * @template T of object
 * @extends ArgsConfigurator<T>
 */
final class ObjectConfigurator extends ArgsConfigurator
{
    /**
     * @var \ReflectionClass<T>
     */
    public readonly \ReflectionClass $reflection;

    private bool $lazy = false;

    /**
     * @var array<non-empty-string, \WeakReference<MethodConfigurator<mixed>>>
     */
    private array $methods = [];

    /**
     * @var list<Internal\ObjectCall>
     */
    private array $calls = [];

    /**
     * @internal
     *
     * @param class-string<T> $class
     */
    public function __construct(
        string $class,
        Location $declaredAt,
        protected readonly Autowiring $autowiring,
        protected readonly ContainerBuilder $containerBuilder,
    ) {
        $this->reflection = new \ReflectionClass($class);

        if (!$this->reflection->isInstantiable()) {
            throw new \LogicException(\sprintf('Class `%s` is not instantiable', $class));
        }

        $constructor = $this->reflection->getConstructor();

        parent::__construct(
            label: formatClass($class),
            declaredAt: $declaredAt,
            arguments: $constructor === null
                ? Arguments::forClassWithoutConstructor($class)
                : Arguments::forFunction($constructor),
        );

        foreach ($this->reflection->getAttributes(ClassAttribute::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $attribute->newInstance()->configure($this);
        }

        foreach ($this->reflection->getMethods() as $method) {
            if ($method->getAttributes(MethodAttribute::class, \ReflectionAttribute::IS_INSTANCEOF) !== []) {
                $this->methods[$method->name] = \WeakReference::create(new MethodConfigurator(
                    object: $this,
                    reflection: $method,
                    declaredAt: $declaredAt,
                    autowiring: $this->autowiring,
                    containerBuilder: $this->containerBuilder,
                ));
            }
        }
    }

    public function eager(): static
    {
        $this->ensureConfigurable();

        $this->lazy = false;

        return $this;
    }

    public function lazy(): static
    {
        $this->ensureConfigurable();

        $this->lazy = true;

        return $this;
    }

    /**
     * @param non-empty-string $method
     * @param array<non-negative-int|non-empty-string, mixed> $args
     */
    public function call(string $method, array $args = []): static
    {
        $this->ensureConfigurable();

        $this->calls[] = new Internal\ObjectCall(
            method: $method,
            arguments: Arguments::forFunction($this->reflection->getMethod($method), $args),
        );

        return $this;
    }

    /**
     * @param non-empty-string $method
     * @param array<non-negative-int|non-empty-string, mixed> $args
     */
    public function chainCall(string $method, array $args = []): static
    {
        $this->ensureConfigurable();

        $this->calls[] = new Internal\ObjectCall(
            method: $method,
            arguments: Arguments::forFunction($this->reflection->getMethod($method), $args),
            chain: true,
        );

        return $this;
    }

    /**
     * @param non-empty-string $name
     * @return MethodFactoryConfigurator<mixed>
     */
    public function methodAsFactory(string $name): MethodFactoryConfigurator
    {
        $this->ensureConfigurable();

        return new MethodFactoryConfigurator(
            object: $this,
            reflection: $this->reflection->getMethod($name),
            declaredAt: Location::caller(),
            autowiring: $this->autowiring,
            containerBuilder: $this->containerBuilder,
        );
    }

    /**
     * @param non-empty-string $name
     * @return MethodConfigurator<mixed>
     */
    public function method(string $name): MethodConfigurator
    {
        $this->ensureConfigurable();

        $method = ($this->methods[$name] ?? null)?->get();

        if ($method !== null) {
            return $method;
        }

        $method = new MethodConfigurator(
            object: $this,
            reflection: $this->reflection->getMethod($name),
            declaredAt: Location::caller(),
            autowiring: $this->autowiring,
            containerBuilder: $this->containerBuilder,
        );

        $this->methods[$name] = \WeakReference::create($method);

        return $method;
    }

    protected function createFactoryWithArguments(ResolvedArguments $arguments): Factory
    {
        $factory = new Constructor($this->reflection->name, $arguments->toFactory());

        foreach ($this->calls as $call) {
            $arguments = $call
                ->arguments
                ->resolve($this, $this->autowiring)
                ->toFactory();

            if ($call->chain) {
                $factory = new ChainCallAfter(
                    factory: $factory,
                    method: $call->method,
                    arguments: $arguments,
                );
            } else {
                $factory = new CallAfter(
                    factory: $factory,
                    method: $call->method,
                    arguments: $arguments,
                );
            }
        }

        if ($this->lazy) {
            $factory = new LazyObject($this->reflection, $factory);
        }

        return $factory;
    }
}
