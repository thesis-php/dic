<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator;

use Thesis\Dic\Autoconfigurator\MethodAttribute;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\ClassReflection;
use Thesis\Dic\Internal\ContainerBuilder;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\FunctionReflection;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;
use Typhoon\Type\ClosureT;

/**
 * @api
 *
 * @template T of \Closure = \Closure
 * @extends Ref<T>
 */
final class MethodConfigurator extends Ref
{
    /** @use Internal\Bind<T> */
    use Internal\Bind;

    /** @use Internal\Tag<T> */
    use Internal\Tag;

    /** @use Internal\Disposer<T> */
    use Internal\Disposer;

    public readonly \ReflectionMethod $reflection;

    protected FunctionReflection $internalReflection;

    /**
     * @internal
     *
     * @param Ref<object> $object
     */
    public function __construct(
        private readonly Ref $object,
        private readonly string $name,
        Location $declaredAt,
        Autowiring $autowiring,
        ContainerBuilder $containerBuilder,
    ) {
        if (!$object->internalReflection instanceof ClassReflection) {
            throw new \LogicException();
        }

        $this->internalReflection = $object->internalReflection->publicMethod($name);

        $this->reflection = $object->internalReflection->native->getMethod($name);

        parent::__construct(
            label: $this->internalReflection->formattedName,
            declaredAt: $declaredAt,
            autowiring: $autowiring,
            containerBuilder: $containerBuilder,
        );

        foreach ($this->reflection->getAttributes(MethodAttribute::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $attribute->newInstance()->configure($this);
        }
    }

    /**
     * @template C of \Closure
     * @param ClosureT<C> $signature
     * @return SignatureConfigurator<C>
     */
    public function signature(ClosureT $signature): SignatureConfigurator
    {
        return new SignatureConfigurator(
            signature: $signature,
            implementation: $this,
            declaredAt: Location::caller(),
            autowiring: $this->autowiring,
            containerBuilder: $this->containerBuilder,
        );
    }

    protected function createFactory(): Factory
    {
        /** @var Factory\Method<T> */
        return new Factory\Method(
            object: $this->object,
            name: $this->name,
        );
    }
}
