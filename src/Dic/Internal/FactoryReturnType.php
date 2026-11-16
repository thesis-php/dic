<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

/**
 * Tells whether a factory's declared return type lets it return something that is not an instance of the class
 * the object service is declared with.
 * A return type that proves nothing at build — none, `mixed`, `object`, `static` — passes:
 * the check never fails a factory that may well be right.
 *
 * @internal
 */
final readonly class FactoryReturnType
{
    /**
     * @param \ReflectionClass<*> $class
     */
    public static function contradicts(\ReflectionFunctionAbstract $factory, \ReflectionClass $class): bool
    {
        $type = $factory->getReturnType();

        if ($type === null) {
            return false;
        }

        $self = $factory instanceof \ReflectionMethod ? $factory->getDeclaringClass() : $factory->getClosureScopeClass();

        return self::admitsOther($type, $class, $self);
    }

    /**
     * True when some value of $type is provably not an instance of $class.
     *
     * @param \ReflectionClass<*> $class
     * @param ?\ReflectionClass<*> $self
     */
    private static function admitsOther(\ReflectionType $type, \ReflectionClass $class, ?\ReflectionClass $self): bool
    {
        if ($type instanceof \ReflectionUnionType) {
            return array_any($type->getTypes(), static fn(\ReflectionType $member) => self::admitsOther($member, $class, $self));
        }

        if ($type instanceof \ReflectionIntersectionType) {
            return array_all($type->getTypes(), static fn(\ReflectionType $member) => self::admitsOther($member, $class, $self));
        }

        if (!$type instanceof \ReflectionNamedType) {
            throw new ShouldNotHappen(\sprintf('Unexpected "ReflectionType" subclass "%s"', $type::class));
        }

        if ($type->allowsNull() && $type->getName() !== 'mixed') {
            // ?Foo: null is never an instance.
            return true;
        }

        return match (strtolower($type->getName())) {
            'never', 'mixed', 'object', 'static' => false,
            'self' => self::isNotA($self, $class),
            'parent' => self::isNotA(self::parentOf($self), $class),
            // A builtin type such as int or array is never a class, so is_a() rejects it too.
            default => !is_a($type->getName(), $class->name, allow_string: true),
        };
    }

    /**
     * @param ?\ReflectionClass<*> $class
     * @return ?\ReflectionClass<*>
     */
    private static function parentOf(?\ReflectionClass $class): ?\ReflectionClass
    {
        $parent = $class?->getParentClass();

        return $parent === false ? null : $parent;
    }

    /**
     * @param ?\ReflectionClass<*> $returned null when `self` or `parent` has no class to resolve to
     * @param \ReflectionClass<*> $class
     */
    private static function isNotA(?\ReflectionClass $returned, \ReflectionClass $class): bool
    {
        return $returned !== null && !is_a($returned->name, $class->name, allow_string: true);
    }
}
