<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator;

use Thesis\Dic\Internal\Arguments;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\ContainerBuilder;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Factory\Func;
use Thesis\Dic\Internal\ResolvedArguments;
use Thesis\Dic\Location;
use function Thesis\Formatter\formatReflectedFunction;

/**
 * @api
 *
 * @template T
 * @extends ArgsConfigurator<T>
 */
final class FunctionConfigurator extends ArgsConfigurator
{
    public readonly \ReflectionFunction $reflection;

    /**
     * @internal
     *
     * @phpstan-ignore missingType.callable
     */
    public function __construct(
        private readonly \Closure $function,
        Location $declaredAt,
        protected readonly Autowiring $autowiring,
        protected readonly ContainerBuilder $containerBuilder,
    ) {
        $this->reflection = new \ReflectionFunction($function);

        parent::__construct(
            label: $this->reflection->isAnonymous() ? 'function' : formatReflectedFunction($this->reflection),
            declaredAt: $declaredAt,
            arguments: Arguments::forFunction($this->reflection),
        );

        foreach ($this->reflection->getAttributes(FunctionAttribute::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $attribute->newInstance()->configure($this);
        }
    }

    protected function createFactoryWithArguments(ResolvedArguments $arguments): Factory
    {
        /** @var Factory<T> */
        return Func::from(
            function: $this->function,
            arguments: $arguments,
        );
    }
}
