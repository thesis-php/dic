<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator;

use Thesis\Dic\Internal\Arguments;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\ContainerBuilder;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Factory\Call;
use Thesis\Dic\Internal\ResolvedArguments;
use Thesis\Dic\Location;
use function Thesis\Formatter\formatReflectedType;

/**
 * @api
 *
 * @template T
 * @extends ArgsConfigurator<T>
 */
final class FactoryConfigurator extends ArgsConfigurator
{
    /**
     * @internal
     *
     * @param \Closure(): T $function
     */
    public function __construct(
        private readonly \Closure $function,
        Location $declaredAt,
        protected readonly Autowiring $autowiring,
        protected readonly ContainerBuilder $containerBuilder,
    ) {
        $reflection = new \ReflectionFunction($function);

        $label = formatReflectedType($reflection->getReturnType() ?? $reflection->getTentativeReturnType());

        parent::__construct(
            label: $label === '' ? 'factory' : $label,
            declaredAt: $declaredAt,
            arguments: Arguments::forFunction($reflection),
        );
    }

    protected function createFactoryWithArguments(ResolvedArguments $arguments): Factory
    {
        return new Call(
            function: $this->function,
            arguments: $arguments->toFactory(),
        );
    }
}
