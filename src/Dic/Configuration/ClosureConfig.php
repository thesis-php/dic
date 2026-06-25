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
 * @template T of \Closure
 * @extends Config<T>
 */
final class ClosureConfig extends Config
{
    /**
     * @var Ref<callable>
     */
    private readonly Ref $functionRef;

    /**
     * @internal
     *
     * @param ClosureT<T> $type
     * @param Ref<callable> $function
     */
    public function __construct(
        Builder $builder,
        Autowiring $autowiring,
        private readonly ClosureT $type,
        Ref $function,
        Location $declaredAt,
    ) {
        $this->functionRef = $function;
        $this->arguments = new Arguments(
            signature: $function->signature ?? throw new ShouldNotHappen('Ref does not reference a function'),
            autowiring: $autowiring,
            closureArguments: ClosureArguments::fromType($type),
        );

        parent::__construct(
            builder: $builder,
            autowiring: $autowiring,
            declaredAt: $declaredAt,
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

    /**
     * @var Arguments<Parameter>
     */
    private readonly Arguments $arguments;

    public function doNotAutowire(): static
    {
        $this->arguments->doNotAutowire();

        return $this;
    }

    public function arg(int|string $positionOrName, mixed $value): static
    {
        $this->arguments->arg($positionOrName, $value);

        return $this;
    }

    /**
     * @param iterable<array-key, mixed>|Ref<iterable<array-key, mixed>>|Parameter $variadic
     */
    public function variadic(iterable|Ref|Parameter $variadic): static
    {
        $this->arguments->variadic($variadic);

        return $this;
    }

    /**
     * @param array<mixed> $values
     */
    public function args(array $values): static
    {
        $this->arguments->args($values);

        return $this;
    }

    protected function createFactory(): Factory
    {
        /**
         * @var ClosureFactory<T>
         * @phpstan-ignore varTag.type
         */
        return ClosureFactory::from(
            type: $this->type,
            function: $this->functionRef,
            arguments: $this->arguments,
        );
    }
}
