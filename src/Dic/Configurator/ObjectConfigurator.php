<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator;

use Thesis\Dic\Autoconfigurator\MethodAttribute;
use Thesis\Dic\Autoconfigurator\ObjectAttribute;
use Thesis\Dic\Configurator\Internal\Arguments;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\ClassReflection;
use Thesis\Dic\Internal\ContainerBuilder;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Factory\Constructor;
use Thesis\Dic\Internal\Factory\ObjectCall;
use Thesis\Dic\Internal\Factory\ObjectChainCall;
use Thesis\Dic\Internal\FunctionReflection;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;
use function Thesis\Formatter\formatClass;

/**
 * @api
 *
 * @template T of object = object
 * @extends Ref<T>
 */
final class ObjectConfigurator extends Ref
{
    use Internal\Lifetime;
    use Internal\Lazy;
    use Internal\Args;
    use Internal\Calls;

    /** @use Internal\Bind<T> */
    use Internal\Bind;

    /** @use Internal\Tag<T> */
    use Internal\Tag;

    /** @use Internal\Disposer<T> */
    use Internal\Disposer;

    /**
     * @var \ReflectionClass<T>
     */
    public \ReflectionClass $reflection { get => $this->internalReflection->native; }

    /**
     * @var ClassReflection<T>
     */
    protected readonly ClassReflection $internalReflection;

    private readonly Arguments $arguments;

    /**
     * @internal
     *
     * @param class-string<T> $class
     * @param null|Ref<callable(): T>|callable(): T $factory
     */
    public function __construct(
        string $class,
        private readonly mixed $factory,
        Location $declaredAt,
        Autowiring $autowiring,
        ContainerBuilder $containerBuilder,
    ) {
        $this->internalReflection = ClassReflection::fromClass($class);
        $this->arguments = self::resolveArguments($factory);

        parent::__construct(
            label: formatClass($class),
            declaredAt: $declaredAt,
            autowiring: $autowiring,
            containerBuilder: $containerBuilder,
        );

        $containerBuilder->onRegistration(function (): void {
            $this->calls = [];
        });

        foreach ($this->reflection->getAttributes(ObjectAttribute::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $attribute->newInstance()->configure($this);
        }

        foreach ($this->reflection->getMethods() as $method) {
            if ($method->getAttributes(MethodAttribute::class, \ReflectionAttribute::IS_INSTANCEOF) !== []) {
                new MethodConfigurator(
                    object: $this,
                    name: $method->name,
                    declaredAt: $declaredAt,
                    autowiring: $this->autowiring,
                    containerBuilder: $this->containerBuilder,
                );
            }
        }
    }

    /**
     * @param null|Ref<callable(): T>|callable(): T $factory
     */
    private function resolveArguments(null|Ref|callable $factory): Arguments
    {
        if ($factory === null) {
            if (!$this->internalReflection->isInstantiable) {
                throw new \LogicException("Class `{$this->internalReflection}` is not instantiable");
            }

            return new Arguments($this->internalReflection->publicConstructor);
        }

        if (!$factory instanceof Ref) {
            return new Arguments(FunctionReflection::fromCallable($factory));
        }

        $reflection = $factory->internalReflection;

        if ($reflection instanceof ClassReflection) {
            return new Arguments($reflection->invoke);
        }

        if ($reflection instanceof FunctionReflection) {
            return new Arguments($reflection);
        }

        throw new \LogicException();
    }

    public function method(string $name): MethodConfigurator
    {
        return new MethodConfigurator(
            object: $this,
            name: $name,
            declaredAt: Location::caller(),
            autowiring: $this->autowiring,
            containerBuilder: $this->containerBuilder,
        );
    }

    protected function createFactory(): Factory
    {
        $factory = $this->createBaseFactory($this->arguments->createFactory($this, $this->autowiring));

        foreach ($this->calls as $call) {
            if ($call->chain) {
                $factory = new ObjectChainCall(
                    factory: $factory,
                    method: $call->method,
                    arguments: $call->arguments->createFactory($this, $this->autowiring),
                );
            } else {
                $factory = new ObjectCall(
                    factory: $factory,
                    method: $call->method,
                    arguments: $call->arguments->createFactory($this, $this->autowiring),
                );
            }
        }

        if ($this->lazy) {
            $factory = new Factory\LazyObject(
                reflection: $this->internalReflection,
                factory: $factory,
            );
        }

        return $factory;
    }

    /**
     * @return Factory<T>
     */
    private function createBaseFactory(Factory\Arguments $arguments): Factory
    {
        if ($this->factory === null) {
            return new Constructor(
                class: $this->internalReflection->name,
                arguments: $arguments,
            );
        }

        if ($this->factory instanceof Ref) {
            return new Factory\CallRef(
                function: $this->factory,
                arguments: $arguments,
            );
        }

        return new Factory\Call(
            function: $this->factory,
            arguments: $arguments,
        );
    }
}
