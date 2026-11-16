<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Autowiring;

use Typhoon\Type;
use Typhoon\Type\IntersectionT;
use Typhoon\Type\UnionT;
use Typhoon\Type\Visitor\Fallback;

/**
 * Tells whether a binding type accepts every instance of a class, so that binding an object service to a type it
 * does not satisfy fails at build instead of as a TypeError in the constructor of some consumer.
 * Expects the types {@see BindingTypeStringifier} supports.
 *
 * @internal
 *
 * @extends Fallback<bool>
 */
final class ClassAcceptance extends Fallback
{
    /**
     * @param \ReflectionClass<*> $class
     */
    public static function accepts(Type $type, \ReflectionClass $class): bool
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
    public function mixedT(Type\MixedT $type): bool
    {
        return true;
    }

    #[\Override]
    public function objectT(Type\ObjectT $type): bool
    {
        return true;
    }

    #[\Override]
    public function namedObjectT(Type\NamedObjectT $type): bool
    {
        // Same check as ObjectAutoconfig::is(), but case-insensitive: class names are, and autowiring compares them so.
        // A type that does not exist is never satisfied: no object can be an instance of it.
        return match (true) {
            class_exists($type->class) => strcasecmp($this->class->name, $type->class) === 0 || $this->class->isSubclassOf($type->class),
            interface_exists($type->class) => $this->class->implementsInterface($type->class),
            default => false,
        };
    }

    #[\Override]
    public function iterableBareT(Type\IterableBareT $type): bool
    {
        return $this->class->implementsInterface(\Traversable::class);
    }

    #[\Override]
    public function callableBareT(Type\CallableBareT $type): bool
    {
        return $this->class->hasMethod('__invoke');
    }

    #[\Override]
    public function unionT(UnionT $type): bool
    {
        return array_any($type->types, fn($member) => $member->accept($this));
    }

    #[\Override]
    public function intersectionT(IntersectionT $type): bool
    {
        return array_all($type->types, fn($member) => $member->accept($this));
    }

    /**
     * Scalars, null and array never hold an object.
     */
    protected function fallback(Type $type): bool
    {
        return false;
    }
}
