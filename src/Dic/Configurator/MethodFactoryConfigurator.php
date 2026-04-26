<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator;

use Thesis\Dic\Internal\Arguments;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\ContainerBuilder;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Factory\MethodCall;
use Thesis\Dic\Internal\ResolvedArguments;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;
use function Thesis\Formatter\formatReflectedFunction;
use function Thesis\Formatter\formatReflectedType;

/**
 * @api
 *
 * @template T
 * @extends ArgsConfigurator<T>
 */
final class MethodFactoryConfigurator extends ArgsConfigurator
{
    /**
     * @internal
     *
     * @param Ref<object> $object
     */
    public function __construct(
        private readonly Ref $object,
        private readonly \ReflectionMethod $reflection,
        Location $declaredAt,
        protected readonly Autowiring $autowiring,
        protected readonly ContainerBuilder $containerBuilder,
    ) {
        if (!$reflection->isPublic()) {
            throw new \LogicException(\sprintf('%s is not public', formatReflectedFunction($reflection)));
        }

        $label = formatReflectedType($reflection->getReturnType() ?? $reflection->getTentativeReturnType());

        parent::__construct(
            label: $label === '' ? 'method factory' : $label,
            declaredAt: $declaredAt,
            arguments: Arguments::forFunction($reflection),
        );
    }

    protected function createFactoryWithArguments(ResolvedArguments $arguments): Factory
    {
        /** @var MethodCall<T> */
        return new MethodCall(
            object: $this->object,
            method: $this->reflection->name,
            arguments: $arguments->toFactory(),
        );
    }
}
