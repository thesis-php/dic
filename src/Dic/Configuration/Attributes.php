<?php

declare(strict_types=1);

namespace Thesis\Dic\Configuration;

/**
 * @api
 */
final readonly class Attributes
{
    /**
     * @internal
     *
     * @param \ReflectionClass<*>|\ReflectionFunctionAbstract $reflection
     */
    public function __construct(
        private \ReflectionClass|\ReflectionFunctionAbstract $reflection,
    ) {}

    /**
     * @param class-string $class
     * @param int-mask-of<\ReflectionAttribute::*> $flags
     */
    public function has(string $class, int $flags = 0): bool
    {
        return $this->reflection->getAttributes($class, $flags) !== [];
    }

    /**
     * @template A of object
     * @param class-string<A> $class
     * @param int-mask-of<\ReflectionAttribute::*> $flags
     * @return ?A
     */
    public function find(string $class, int $flags = 0): ?object
    {
        return array_first($this->reflection->getAttributes($class, $flags))?->newInstance();
    }

    /**
     * @template A of object
     * @param class-string<A> $class
     * @param int-mask-of<\ReflectionAttribute::*> $flags
     * @return list<A>
     */
    public function all(string $class, int $flags = 0): array
    {
        return array_map(
            static fn(\ReflectionAttribute $attribute) => $attribute->newInstance(),
            $this->reflection->getAttributes($class, $flags),
        );
    }
}
