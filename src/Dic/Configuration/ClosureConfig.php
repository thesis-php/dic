<?php

declare(strict_types=1);

namespace Thesis\Dic\Configuration;

use Thesis\Dic\Internal\Arguments;
use Thesis\Dic\Internal\Arguments\ClosureArguments;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Builder;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Factory\ClosureFactory;
use Thesis\Dic\Internal\ShouldNotHappen;
use Thesis\Dic\Internal\Signature;
use Thesis\Dic\Internal\Signature\ClosureSignature;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;
use Typhoon\Type\ClosureT;
use Typhoon\Type\Parameter;
use function Typhoon\Type\stringify;

/**
 * @api
 *
 * @template-covariant T of \Closure
 * @extends FactoryConfig<T, Parameter>
 */
final class ClosureConfig extends FactoryConfig
{
    /**
     * @var Ref<callable>
     */
    private readonly Ref $functionRef;

    /**
     * @internal
     *
     * @param ClosureT<T> $type
     * @param Config<callable> $function
     */
    public function __construct(
        Builder $builder,
        Autowiring $autowiring,
        private readonly ClosureT $type,
        Config $function,
        Location $declaredAt,
    ) {
        $this->functionRef = $function;

        parent::__construct(
            builder: $builder,
            autowiring: $autowiring,
            declaredAt: $declaredAt,
            arguments: new Arguments(
                signature: $function->signature ?? throw new ShouldNotHappen('Ref does not reference a function'),
                autowiring: $autowiring,
                closureArguments: ClosureArguments::fromType($type),
            ),
        );
    }

    protected function defaultLabel(): string
    {
        return stringify($this->type);
    }

    protected ClosureSignature $signature {
        get => Signature::ofClosure($this->type);
    }

    public \ReflectionFunction $function {
        get => $this->signature->reflection;
    }

    public \ReflectionClass $class {
        get => new \ReflectionClass(\Closure::class);
    }

    protected function createFactory(): Factory
    {
        /** @var ClosureFactory<T> */
        return ClosureFactory::from(
            type: $this->type,
            function: $this->functionRef,
            arguments: $this->arguments,
        );
    }
}
