<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use function Thesis\Formatter\formatReflectedClass;

/**
 * @internal
 *
 * @template T of object = object
 */
final class ClassReflection
{
    /**
     * @template O of object
     * @param class-string<O> $class
     * @return self<O>
     */
    public static function fromClass(string $class): self
    {
        return new self(new \ReflectionClass($class));
    }

    /**
     * @var class-string<T>
     */
    public string $name { get => $this->native->name; }

    /**
     * @var non-empty-string
     */
    public string $formattedName { get => formatReflectedClass($this->native); }

    public bool $isInstantiable { get => $this->native->isInstantiable(); }

    public FunctionReflection $publicConstructor {
        get {
            $constructor = $this->native->getConstructor();

            if ($constructor === null) {
                return FunctionReflection::implicitConstructor($this->name);
            }

            if (!$constructor->isPublic()) {
                throw new \LogicException();
            }

            return FunctionReflection::fromReflection($constructor);
        }
    }

    public FunctionReflection $invoke { get => $this->publicMethod('__invoke'); }

    /**
     * @param \ReflectionClass<T> $native
     */
    private function __construct(
        public readonly \ReflectionClass $native,
    ) {}

    public function publicMethod(string $name): FunctionReflection
    {
        $method = $this->native->getMethod($name);

        if (!$method->isPublic()) {
            throw new \LogicException();
        }

        return FunctionReflection::fromReflection($method);
    }

    public function findPublicMethod(string $name): ?FunctionReflection
    {
        if (!$this->native->hasMethod($name)) {
            return null;
        }

        $method = $this->native->getMethod($name);

        if (!$method->isPublic()) {
            return null;
        }

        return FunctionReflection::fromReflection($method);
    }

    /**
     * @param callable(): T $factory
     * @return T
     */
    public function newLazyProxy(callable $factory): object
    {
        return $this->native->newLazyProxy($factory);
    }

    /**
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return $this->formattedName;
    }
}
