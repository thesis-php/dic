<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator;

use Thesis\Dic\Autoconfigurator\CallableAutoconfigurator;
use Thesis\Dic\Internal\Arguments;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\ContainerBuilder;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Factory\Func;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;
use function Thesis\Formatter\formatReflectedFunction;

/**
 * @api
 *
 * @template T of \Closure = \Closure
 * @extends Ref<T>
 */
final class CallableConfigurator extends Ref
{
    use Internal\Lifetime;
    use Internal\Args;
    use Internal\Autoconfigure;

    /** @use Internal\Bind<T> */
    use Internal\Bind;

    /** @use Internal\Tag<T> */
    use Internal\Tag;

    /** @use Internal\Disposer<T> */
    use Internal\Disposer;

    /**
     * @var \Closure|array{Ref<object>, string}
     */
    private \Closure|array $callable;

    public readonly \ReflectionFunction|\ReflectionMethod $reflection;

    private readonly Arguments $arguments;

    /**
     * @internal
     *
     * @param callable|array{Ref<object>, string} $callable
     */
    public function __construct(
        callable|array $callable,
        Location $declaredAt,
        Autowiring $autowiring,
        ContainerBuilder $containerBuilder,
    ) {
        $this->callable = \is_callable($callable) ? $callable(...) : $callable;
        $this->reflection = self::reflect($this->callable);

        parent::__construct(
            label: formatReflectedFunction($this->reflection),
            declaredAt: $declaredAt,
            autowiring: $autowiring,
            containerBuilder: $containerBuilder,
        );

        $this->arguments = Arguments::forFunction($this->reflection);

        $containerBuilder->onAutoconfiguration(function (CallableAutoconfigurator $autoconfigurator): void {
            if ($this->autoconfigure && $autoconfigurator->supportsCallable($this->reflection)) {
                $autoconfigurator->autoconfigureCallable($this);
            }
        });
    }

    protected function createFactory(): Factory
    {
        $arguments = $this->arguments->resolve($this, $this->autowiring);

        if ($this->callable instanceof \Closure) {
            /** @var Factory<T> */
            return Func::from(
                function: $this->callable,
                arguments: $arguments,
            );
        }

        \assert($this->reflection instanceof \ReflectionMethod);

        /** @var Factory\Method<T> */
        return Factory\Method::from(
            object: $this->callable[0],
            reflection: $this->reflection,
            arguments: $arguments,
        );
    }

    /**
     * @param \Closure|array{Ref<object>, string} $function
     */
    private static function reflect(\Closure|array $function): \ReflectionFunction|\ReflectionMethod
    {
        if ($function instanceof \Closure) {
            return new \ReflectionFunction($function);
        }

        [$object, $method] = $function;

        if (!$object->reflection instanceof \ReflectionClass) {
            throw new \LogicException("Invalid object reference {$object}");
        }

        $reflection = $object->reflection->getMethod($method);

        if (!$reflection->isPublic()) {
            throw new \LogicException(\sprintf(
                'Method `%s` must be public',
                formatReflectedFunction($reflection),
            ));
        }

        return $reflection;
    }
}
