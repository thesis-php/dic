<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\DoNotAutowire;
use Thesis\Dic\Internal\Signature\ClosureSignature;
use Thesis\Dic\Internal\Signature\ImplicitConstructorSignature;
use Thesis\Dic\Internal\Signature\Parameter;
use Thesis\Dic\Internal\Signature\ReflectionFunctionSignature;
use Typhoon\Type\ClosureT;

/**
 * @internal
 */
abstract class Signature
{
    final public static function ofClosure(ClosureT $type): ClosureSignature
    {
        return new ClosureSignature($type);
    }

    /**
     * @return ReflectionFunctionSignature<\ReflectionMethod>
     */
    final public static function ofMethod(\ReflectionMethod $method): ReflectionFunctionSignature
    {
        return new ReflectionFunctionSignature($method);
    }

    /**
     * @template F of \ReflectionFunction|\ReflectionMethod
     * @param F $function
     * @return ReflectionFunctionSignature<F>
     */
    final public static function ofFunction(\ReflectionFunction|\ReflectionMethod $function): ReflectionFunctionSignature
    {
        return new ReflectionFunctionSignature($function);
    }

    /**
     * @return ReflectionFunctionSignature<\ReflectionFunction>
     */
    final public static function ofCallable(callable $function): ReflectionFunctionSignature
    {
        return new ReflectionFunctionSignature(new \ReflectionFunction($function(...)));
    }

    /**
     * @param \ReflectionClass<*> $class
     * @return ?ReflectionFunctionSignature<\ReflectionMethod>
     */
    final public static function ofClass(\ReflectionClass $class): ?ReflectionFunctionSignature
    {
        if ($class->hasMethod('__invoke')) {
            return new ReflectionFunctionSignature($class->getMethod('__invoke'));
        }

        return null;
    }

    /**
     * @param \ReflectionClass<*> $class
     */
    final public static function ofConstructor(\ReflectionClass $class): self
    {
        $constructor = $class->getConstructor();

        if ($constructor === null) {
            return new ImplicitConstructorSignature();
        }

        return new ReflectionFunctionSignature($constructor);
    }

    abstract public null|\ReflectionFunction|\ReflectionMethod $reflection { get; }

    abstract public ?DoNotAutowire $autowiringMode { get; }

    /**
     * @var list<Parameter>
     */
    abstract public array $parameters { get; }

    /**
     * @var list<Parameter>
     */
    final public array $regularParameters {
        get => array_values(
            array_filter(
                $this->parameters,
                static fn(Parameter $parameter) => !$parameter->isVariadic,
            ),
        );
    }

    final public ?Parameter $variadicParameter {
        get {
            $parameter = array_last($this->parameters);

            if ($parameter !== null && $parameter->isVariadic) {
                return $parameter;
            }

            return null;
        }
    }

    final public function findParameter(int|string $positionOrName): ?Parameter
    {
        if (\is_int($positionOrName)) {
            return $this->parameters[$positionOrName] ?? null;
        }

        return array_find(
            $this->parameters,
            static fn(Parameter $p) => $p->name === $positionOrName,
        );
    }
}
