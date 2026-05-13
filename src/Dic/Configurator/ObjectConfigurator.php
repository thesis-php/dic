<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator;

use Thesis\Dic\Autoconfigurator\CallableAutoconfigurator;
use Thesis\Dic\Autoconfigurator\ObjectAutoconfigurator;
use Thesis\Dic\Internal\Arguments;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\ContainerBuilder;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Factory\Constructor;
use Thesis\Dic\Internal\Factory\ObjectCall;
use Thesis\Dic\Internal\Factory\ObjectChainCall;
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
    use Internal\Autoconfigure;

    /** @use Internal\Bind<T> */
    use Internal\Bind;

    /** @use Internal\Tag<T> */
    use Internal\Tag;

    /** @use Internal\Disposer<T> */
    use Internal\Disposer;

    /**
     * @var \ReflectionClass<T>
     */
    public readonly \ReflectionClass $reflection;

    private readonly Arguments $arguments;

    /**
     * @var array<string, CallableConfigurator>
     */
    private array $methods = [];

    /**
     * @internal
     *
     * @param class-string<T> $class
     */
    public function __construct(
        string $class,
        Location $declaredAt,
        Autowiring $autowiring,
        ContainerBuilder $containerBuilder,
    ) {
        $this->reflection = new \ReflectionClass($class);

        if (!$this->reflection->isInstantiable()) {
            throw new \LogicException(\sprintf('Class `%s` is not instantiable', $class));
        }

        parent::__construct(
            label: formatClass($class),
            declaredAt: $declaredAt,
            autowiring: $autowiring,
            containerBuilder: $containerBuilder,
        );

        $constructor = $this->reflection->getConstructor();

        $this->arguments = $constructor === null
            ? Arguments::forClassWithoutConstructor($class)
            : Arguments::forFunction($constructor);

        $containerBuilder->onAutoconfiguration(function (CallableAutoconfigurator&ObjectAutoconfigurator $autoconfigurator): void {
            if (!$this->autoconfigure) {
                return;
            }

            if ($autoconfigurator->supportsObject($this->reflection)) {
                $autoconfigurator->autoconfigureObject($this);
            }

            foreach ($this->reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $methodReflection) {
                if ($autoconfigurator->supportsCallable($methodReflection)) {
                    // this ensures that method is autoconfigured
                    $this->method($methodReflection->name);
                }
            }
        });

        $containerBuilder->onRegistration(function (): void {
            $this->calls = [];
            $this->methods = [];
        });
    }

    public function method(string $name): CallableConfigurator
    {
        return $this->methods[$name] ??= new CallableConfigurator(
            callable: [$this, $name],
            declaredAt: Location::caller(),
            autowiring: $this->autowiring,
            containerBuilder: $this->containerBuilder,
        );
    }

    protected function createFactory(): Factory
    {
        $factory = new Constructor(
            class: $this->reflection->name,
            arguments: $this->buildArguments($this->arguments),
        );

        foreach ($this->calls as $call) {
            if ($call->chain) {
                $factory = new ObjectChainCall(
                    factory: $factory,
                    method: $call->method,
                    arguments: $this->buildArguments($call->arguments),
                );
            } else {
                $factory = new ObjectCall(
                    factory: $factory,
                    method: $call->method,
                    arguments: $this->buildArguments($call->arguments),
                );
            }
        }

        if ($this->lazy) {
            $factory = new Factory\LazyObject(
                reflection: $this->reflection,
                factory: $factory,
            );
        }

        return $factory;
    }

    /**
     * @return Factory<list<mixed>>
     */
    private function buildArguments(Arguments $arguments): Factory
    {
        return $arguments
            ->resolve($this, $this->autowiring)
            ->toFactory();
    }
}
