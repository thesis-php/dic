<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Autowiring;

use Typhoon\Type;
use Typhoon\Type\IntersectionT;
use Typhoon\Type\UnionT;
use Typhoon\Type\Visitor\Fallback;
use function Thesis\Formatter\formatReflectedClass;
use function Thesis\Formatter\formatReflectedParameter;

/**
 * @internal
 *
 * @extends Fallback<non-empty-lowercase-string>
 */
final class BindingTypeStringifier extends Fallback
{
    /**
     * @return non-empty-lowercase-string
     * @throws UnsupportedBindingType
     */
    public static function stringifyTyphoonType(Type $type): string
    {
        /** @var self */
        static $instance = new self();

        return $type->accept($instance);
    }

    /**
     * @return non-empty-lowercase-string
     * @throws UnsupportedBindingType
     */
    public static function stringifyParameterType(\ReflectionParameter $parameter): string
    {
        $type = $parameter->getType() ?? throw new UnsupportedBindingType(\sprintf(
            'Parameter "%s" does not have a type',
            formatReflectedParameter($parameter),
        ));

        return self::stringifyReflectionType(
            type: $type,
            self: $parameter->getDeclaringClass() ?? $parameter->getDeclaringFunction()->getClosureScopeClass(),
        );
    }

    /**
     * @param ?\ReflectionClass<*> $self
     * @return non-empty-lowercase-string
     * @throws UnsupportedBindingType
     */
    private static function stringifyReflectionType(\ReflectionType $type, ?\ReflectionClass $self): string
    {
        if ($type instanceof \ReflectionUnionType) {
            $types = array_values($type->getTypes());
            \assert($types !== []);

            return self::stringifyUnion(array_map(
                static fn(\ReflectionType $type) => self::stringifyReflectionType($type, $self),
                $types,
            ));
        }

        if ($type instanceof \ReflectionIntersectionType) {
            $types = array_values($type->getTypes());
            \assert($types !== []);

            return self::join('&', array_map(
                static fn(\ReflectionType $type) => self::stringifyReflectionType($type, $self),
                $types,
            ));
        }

        if (!$type instanceof \ReflectionNamedType) {
            throw new UnsupportedBindingType(\sprintf('Unexpected "ReflectionType" subclass "%s"', $type::class));
        }

        $name = strtolower($type->getName());

        if ($name === 'self') {
            if ($self === null) {
                throw new UnsupportedBindingType('Cannot resolve "self" type outside the class context');
            }

            return strtolower($self->name);
        }

        if ($name === 'parent') {
            if ($self === null) {
                throw new UnsupportedBindingType('Cannot resolve "parent" type outside the class context');
            }

            $parent = $self->getParentClass();

            if ($parent === false) {
                throw new UnsupportedBindingType(\sprintf('Class "%s" does not have a parent', formatReflectedClass($self)));
            }

            return strtolower($parent->name);
        }

        if ($name === 'static') {
            throw new UnsupportedBindingType('The "static" type cannot be safely autowired');
        }

        \assert($name !== '');

        if ($type->allowsNull() && $name !== 'null' && $name !== 'mixed') {
            return self::join('|', ['null', $name]);
        }

        return $name;
    }

    private function __construct() {}

    #[\Override]
    public function nullT(Type\NullT $type): string
    {
        return 'null';
    }

    #[\Override]
    public function falseT(Type\FalseT $type): string
    {
        return 'false';
    }

    #[\Override]
    public function trueT(Type\TrueT $type): string
    {
        return 'true';
    }

    #[\Override]
    public function boolT(Type\BoolT $type): string
    {
        return 'bool';
    }

    #[\Override]
    public function intT(Type\IntT $type): string
    {
        return 'int';
    }

    #[\Override]
    public function floatT(Type\FloatT $type): string
    {
        return 'float';
    }

    #[\Override]
    public function stringT(Type\StringT $type): string
    {
        return 'string';
    }

    #[\Override]
    public function arrayBareT(Type\ArrayBareT $type): string
    {
        return 'array';
    }

    #[\Override]
    public function objectT(Type\ObjectT $type): string
    {
        return 'object';
    }

    #[\Override]
    public function namedObjectT(Type\NamedObjectT $type): string
    {
        if ($type->templateArguments === []) {
            return strtolower($type->class);
        }

        $this->fallback($type);
    }

    #[\Override]
    public function iterableBareT(Type\IterableBareT $type): string
    {
        return 'iterable';
    }

    #[\Override]
    public function callableBareT(Type\CallableBareT $type): string
    {
        return 'callable';
    }

    #[\Override]
    public function mixedT(Type\MixedT $type): string
    {
        return 'mixed';
    }

    #[\Override]
    public function unionT(UnionT $type): string
    {
        return self::stringifyUnion(array_map(fn(Type $t) => $t->accept($this), $type->types));
    }

    #[\Override]
    public function intersectionT(IntersectionT $type): string
    {
        return self::join('&', array_map(fn(Type $t) => $t->accept($this), $type->types));
    }

    /**
     * `iterable` is an alias of `Traversable|array`, and PHP expands it inside a union (`iterable|int` reflects as
     * `Traversable|array|int`), while a Typhoon type keeps it: the pair is folded back, so every spelling of the type
     * gets one string.
     *
     * @param non-empty-list<non-empty-lowercase-string> $members
     * @return non-empty-lowercase-string
     */
    private static function stringifyUnion(array $members): string
    {
        if (\in_array('array', $members, true) && \in_array('traversable', $members, true)) {
            $members = [...array_diff($members, ['array', 'traversable']), 'iterable'];
        }

        return \count($members) === 1 ? $members[0] : self::join('|', $members);
    }

    /**
     * @param '|'|'&' $operator
     * @param non-empty-list<non-empty-lowercase-string> $parts
     * @return non-empty-lowercase-string
     */
    private static function join(string $operator, array $parts): string
    {
        $parts = array_unique($parts);
        sort($parts);

        return \sprintf('(%s)', implode($operator, $parts));
    }

    protected function fallback(Type $type): never
    {
        throw new UnsupportedBindingType(\sprintf('Non-native type "%s" is not supported', Type\stringify($type)));
    }
}
