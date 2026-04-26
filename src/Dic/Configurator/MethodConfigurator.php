<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator;

use Thesis\Dic\Internal\Arguments;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\ContainerBuilder;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Factory\Method;
use Thesis\Dic\Internal\ResolvedArguments;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;
use function Thesis\Formatter\formatReflectedFunction;

/**
 * @api
 *
 * @template T
 * @extends ArgsConfigurator<\Closure(mixed...): T>
 */
final class MethodConfigurator extends ArgsConfigurator
{
    /**
     * @internal
     *
     * @param Ref<object> $object
     */
    public function __construct(
        private readonly Ref $object,
        public readonly \ReflectionMethod $reflection,
        Location $declaredAt,
        protected readonly Autowiring $autowiring,
        protected readonly ContainerBuilder $containerBuilder,
    ) {
        if (!$reflection->isPublic()) {
            throw new \LogicException(\sprintf('%s is not public', formatReflectedFunction($reflection)));
        }

        parent::__construct(
            label: formatReflectedFunction($reflection),
            declaredAt: $declaredAt,
            arguments: Arguments::forFunction($reflection),
        );

        foreach ($this->reflection->getAttributes(MethodAttribute::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $attribute->newInstance()->configure($this);
        }
    }

    protected function createFactoryWithArguments(ResolvedArguments $arguments): Factory
    {
        /** @var Method<T> */
        return Method::from(
            object: $this->object,
            reflection: $this->reflection,
            arguments: $arguments,
        );
    }
}
