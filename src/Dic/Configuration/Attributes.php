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
     * @param class-string $name
     * @param int-mask-of<\ReflectionAttribute::*> $flags
     */
    public function has(string $name, int $flags = 0): bool
    {
        return $this->reflection->getAttributes($name, $flags) !== [];
    }

    /**
     * @template A of object
     * @param class-string<A> $name
     * @param int-mask-of<\ReflectionAttribute::*> $flags
     * @return ?A
     */
    public function first(string $name, int $flags = 0): ?object
    {
        return array_first($this->reflection->getAttributes($name, $flags))?->newInstance();
    }

    /**
     * @template A of object
     * @param class-string<A> $name
     * @param int-mask-of<\ReflectionAttribute::*> $flags
     * @return list<A>
     */
    public function all(string $name, int $flags = 0): array
    {
        return array_map(
            static fn(\ReflectionAttribute $attribute) => $attribute->newInstance(),
            $this->reflection->getAttributes($name, $flags),
        );
    }
}
