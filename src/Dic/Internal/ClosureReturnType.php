<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Typhoon\Type;
use Typhoon\Type\IntersectionT;
use Typhoon\Type\UnionT;
use Typhoon\Type\Visitor\Fallback;

/**
 * The {@see FactoryReturnType} check for a factory declared with `closure()`, whose return type is a Typhoon type:
 * tells whether some value of it is provably not an instance of the class.
 * Types that may hold an instance but prove nothing — `mixed`, `object`, object shapes, constants — pass.
 *
 * @internal
 *
 * @extends Fallback<bool>
 */
final class ClosureReturnType extends Fallback
{
    /**
     * @param \ReflectionClass<*> $class
     */
    public static function admitsOther(Type $type, \ReflectionClass $class): bool
    {
        return $type->accept(new self($class));
    }

    /**
     * @param \ReflectionClass<*> $class
     */
    private function __construct(
        private readonly \ReflectionClass $class,
    ) {}

    #[\Override]
    public function neverT(Type\NeverT $type): bool
    {
        return false;
    }

    #[\Override]
    public function mixedT(Type\MixedT $type): bool
    {
        return false;
    }

    #[\Override]
    public function objectT(Type\ObjectT $type): bool
    {
        return false;
    }

    #[\Override]
    public function objectShapeT(Type\ObjectShapeT $type): bool
    {
        return false;
    }

    #[\Override]
    public function constantT(Type\ConstantT $type): bool
    {
        return false;
    }

    #[\Override]
    public function classConstantT(Type\ClassConstantT $type): bool
    {
        return false;
    }

    #[\Override]
    public function namedObjectT(Type\NamedObjectT $type): bool
    {
        return !is_a($type->class, $this->class->name, allow_string: true);
    }

    #[\Override]
    public function closureT(Type\ClosureT $type): bool
    {
        return !is_a(\Closure::class, $this->class->name, allow_string: true);
    }

    #[\Override]
    public function unionT(UnionT $type): bool
    {
        return array_any($type->types, fn(Type $member) => $member->accept($this));
    }

    #[\Override]
    public function intersectionT(IntersectionT $type): bool
    {
        return array_all($type->types, fn(Type $member) => $member->accept($this));
    }

    /**
     * Scalars, null, void, arrays, and iterable or callable types, which may be an array or a string.
     */
    protected function fallback(Type $type): bool
    {
        return true;
    }
}
