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
        return format($this->value);
    }

    private bool $isSignatureSet = false;

    protected ?Signature $signature = null {
        get {
            if (!$this->isSignatureSet) {
                $this->signature = $this->resolveSignature();
                $this->isSignatureSet = true;
            }

            return $this->signature;
        }
    }

    protected null|\ReflectionFunction|\ReflectionMethod $reflectionFunction {
        get => $this->signature?->reflection;
    }

    private bool $isClassSet = false;

    protected private(set) ?\ReflectionClass $reflectionClass = null {
        get {
            if (!$this->isClassSet) {
                /** @phpstan-ignore assign.propertyType */
                $this->reflectionClass = match (true) {
                    $this->value instanceof Ref => $this->value->reflectionClass,
                    \is_object($this->value) => new \ReflectionObject($this->value),
                    default => null,
                };
                $this->isClassSet = true;
            }

            return $this->reflectionClass;
        }
    }

    protected function createFactory(): Factory
    {
        return ValueFactory::from($this->value);
    }

    private function resolveSignature(): ?Signature
    {
        if ($this->value instanceof Ref) {
            return $this->value->signature;
        }

        $value = self::unwrap($this->value, maxDepth: 2);

        // this should go before is_callable() to avoid matching Ref methods
        if (\is_array($value)
            && \count($value) === 2
            && isset($value[0]) && $value[0] instanceof Ref
            && isset($value[1]) && \is_string($value[1])
        ) {
            [$ref, $name] = $value;

            if ($ref->reflectionClass === null
                || !$ref->reflectionClass->hasMethod($name)
                || !($method = $ref->reflectionClass->getMethod($name))->isPublic()
            ) {
                return null;
            }

            return Signature::ofMethod($method);
        }

        if (\is_callable($value)) {
            return Signature::ofCallable($value);
        }

        return null;
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
