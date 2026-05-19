<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator;

use Thesis\Dic\Configurator\Internal\Arguments;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\ContainerBuilder;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\FunctionReflection;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;
use Typhoon\Type\ClosureT;
use function Typhoon\Type\stringify;

/**
 * @api
 *
 * @template T of \Closure = \Closure
 * @extends Ref<T>
 */
final class SignatureConfigurator extends Ref
{
    use Internal\Lifetime;
    use Internal\Args;

    /** @use Internal\Bind<T> */
    use Internal\Bind;

    /** @use Internal\Tag<T> */
    use Internal\Tag;

    /** @use Internal\Disposer<T> */
    use Internal\Disposer;

    protected FunctionReflection $internalReflection;

    private readonly Arguments $arguments;

    /**
     * @internal
     *
     * @param Ref<callable> $implementation
     * @param ClosureT<T> $signature
     */
    public function __construct(
        private readonly ClosureT $signature,
        private readonly Ref $implementation,
        Location $declaredAt,
        Autowiring $autowiring,
        ContainerBuilder $containerBuilder,
    ) {
        $this->internalReflection = FunctionReflection::fromSignature($signature);

        if (!$implementation->internalReflection instanceof FunctionReflection) {
            throw new \LogicException();
        }

        $this->arguments = new Arguments($implementation->internalReflection);

        parent::__construct(
            label: \sprintf('%s as %s', $implementation, stringify($signature)),
            declaredAt: $declaredAt,
            autowiring: $autowiring,
            containerBuilder: $containerBuilder,
        );
    }

    protected function createFactory(): Factory
    {
        /** @var Factory\Signature<T> */
        return Factory\Signature::from(
            signature: $this->signature,
            implementation: $this->implementation,
            arguments: $this->arguments->resolveForSignature(
                signature: $this->signature,
                ref: $this,
                autowiring: $this->autowiring,
            ),
        );
    }
}
