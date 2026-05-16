<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator;

use Thesis\Dic\Autoconfigurator\CallableAutoconfigurator;
use Thesis\Dic\Internal\Arguments;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\ContainerBuilder;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;
use function Thesis\Formatter\formatFunction;

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

    public readonly \ReflectionFunction|\ReflectionMethod $reflection;

    private readonly Arguments $arguments;

    /**
     * @internal
     */
    public function __construct(
        callable $callable,
        Location $declaredAt,
        Autowiring $autowiring,
        ContainerBuilder $containerBuilder,
    ) {
        parent::__construct(
            label: formatFunction($callable),
            declaredAt: $declaredAt,
            autowiring: $autowiring,
            containerBuilder: $containerBuilder,
        );

        $this->reflection = new \ReflectionFunction($callable(...));
        $this->arguments = Arguments::forFunction($this->reflection);

        $containerBuilder->onAutoconfiguration(function (CallableAutoconfigurator $autoconfigurator): void {
            if ($this->autoconfigure && $autoconfigurator->supportsCallable($this->reflection)) {
                $autoconfigurator->autoconfigureCallable($this);
            }
        });
    }

    protected function createFactory(): Factory
    {
        throw new \LogicException();
    }
}
