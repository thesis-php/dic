<?php

declare(strict_types=1);

namespace Thesis\Dic\Configuration;

use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Builder;
use Thesis\Dic\Internal\Builder\LifetimeStrategy;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Factory\ValueFactory;
use Thesis\Dic\Internal\Signature;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;
use function Thesis\Formatter\format;
use function Thesis\Formatter\formatReflectedFunction;

/**
 * @api
 *
 * @template T
 * @extends Config<T>
 */
final class ValueConfig extends Config
{
    /**
     * @internal
     *
     * @param T $value
     */
    public function __construct(
        Builder $builder,
        Autowiring $autowiring,
        private readonly mixed $value,
        Location $declaredAt,
    ) {
        parent::__construct($builder, $autowiring, $declaredAt);
    }

    protected LifetimeStrategy $lifetimeStrategy {
        get => LifetimeStrategy::Inferred;
    }

    protected function defaultLabel(): string
    {
        $function = $this->function;

        if ($function === null) {
            return format($this->value);
        }

        return formatReflectedFunction($function);
    }

    private bool $isSignatureSet = false;

    protected ?Signature $signature = null {
        get {
            if (!$this->isSignatureSet) {
                $this->signature = match (true) {
                    $this->value instanceof Ref => $this->signature,
                    default => Signature::ofValue(self::unwrap($this->value, maxDepth: 2)),
                };
                $this->isSignatureSet = true;
            }

            return $this->signature;
        }
    }

    public null|\ReflectionFunction|\ReflectionMethod $function {
        get => $this->signature?->reflection;
    }

    private bool $isClassSet = false;

    public private(set) ?\ReflectionClass $class = null {
        get {
            if (!$this->isClassSet) {
                /** @phpstan-ignore assign.propertyType */
                $this->class = match (true) {
                    $this->value instanceof Ref => $this->value->class,
                    \is_object($this->value) => new \ReflectionObject($this->value),
                    default => null,
                };
                $this->isClassSet = true;
            }

            return $this->class;
        }
    }

    protected function createFactory(): Factory
    {
        return ValueFactory::from($this->value);
    }

    /**
     * @param non-negative-int $maxDepth
     */
    private static function unwrap(mixed $value, int $maxDepth): mixed
    {
        if ($maxDepth === 0) {
            return $value;
        }

        if ($value instanceof self) {
            return self::unwrap($value->value, $maxDepth);
        }

        if (\is_array($value)) {
            return array_map(
                static fn(mixed $value) => self::unwrap($value, $maxDepth - 1),
                $value,
            );
        }

        return $value;
    }
}
