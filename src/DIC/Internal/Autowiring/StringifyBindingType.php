<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\Autowiring;

use Typhoon\Type;
use Typhoon\Type\ArrayBareT;
use Typhoon\Type\BoolT;
use Typhoon\Type\CallableBareT;
use Typhoon\Type\FalseT;
use Typhoon\Type\FloatT;
use Typhoon\Type\IntT;
use Typhoon\Type\IterableBareT;
use Typhoon\Type\MixedT;
use Typhoon\Type\NamedObjectT;
use Typhoon\Type\NullT;
use Typhoon\Type\ObjectT;
use Typhoon\Type\StringT;
use Typhoon\Type\TrueT;
use Typhoon\Type\Visitor\Fallback;
use function Thesis\Formatter\formatClass;

/**
 * @internal
 *
 * @extends Fallback<non-empty-lowercase-string>
 */
final class StringifyBindingType extends Fallback
{
    /**
     * @return lowercase-string
     */
    public static function value(mixed $value): string
    {
        if (\is_resource($value)) {
            throw new \LogicException('Resources are not supported for binding');
        }

        return strtolower(get_debug_type($value));
    }

    /**
     * @param ?class-string $self
     * @param ?class-string $static
     * @return list<non-empty-lowercase-string>
     */
    public static function reflected(?\ReflectionType $type, ?string $self, ?string $static): array
    {
        if ($type === null) {
            return ['mixed'];
        }

        if ($type instanceof \ReflectionUnionType) {
            return array_merge(...array_map(
                static fn(\ReflectionType $type) => self::reflected($type, $self, $static),
                array_values($type->getTypes()),
            ));
        }

        if (!$type instanceof \ReflectionNamedType) {
            return [];
        }

        $lowerName = strtolower($type->getName());

        if ($lowerName === '') {
            return [];
        }

        if ($lowerName === 'parent') {
            $parent = get_parent_class($self ?? throw new \LogicException('Cannot resolve parent type'));

            if ($parent === false) {
                throw new \LogicException(\sprintf('Class `%s` does not have a parent', formatClass($self)));
            }

            return [strtolower($parent)];
        }

        $asString = match ($lowerName) {
            'boolean' => 'bool',
            'integer' => 'int',
            'double' => 'float',
            'self' => strtolower($self ?? throw new \LogicException('Cannot resolve self')),
            'static' => strtolower($static ?? $self ?? throw new \LogicException('Cannot resolve static')),
            default => $lowerName,
        };

        if ($type->allowsNull() && $asString !== 'null' && $asString !== 'mixed') {
            return ['null', $asString];
        }

        return [$asString];
    }

    #[\Override]
    public function nullT(NullT $type): mixed
    {
        return 'null';
    }

    #[\Override]
    public function falseT(FalseT $type): mixed
    {
        return 'false';
    }

    #[\Override]
    public function trueT(TrueT $type): mixed
    {
        return 'true';
    }

    #[\Override]
    public function boolT(BoolT $type): mixed
    {
        return 'bool';
    }

    #[\Override]
    public function intT(IntT $type): mixed
    {
        return 'int';
    }

    #[\Override]
    public function floatT(FloatT $type): mixed
    {
        return 'float';
    }

    #[\Override]
    public function stringT(StringT $type): mixed
    {
        return 'string';
    }

    #[\Override]
    public function arrayBareT(ArrayBareT $type): mixed
    {
        return 'array';
    }

    #[\Override]
    public function objectT(ObjectT $type): mixed
    {
        return 'object';
    }

    #[\Override]
    public function namedObjectT(NamedObjectT $type): mixed
    {
        if ($type->templateArguments !== []) {
            $this->fallback($type);
        }

        return strtolower($type->class);
    }

    #[\Override]
    public function iterableBareT(IterableBareT $type): mixed
    {
        return 'iterable';
    }

    #[\Override]
    public function callableBareT(CallableBareT $type): mixed
    {
        return 'callable';
    }

    #[\Override]
    public function mixedT(MixedT $type): mixed
    {
        return 'mixed';
    }

    protected function fallback(Type $type): never
    {
        throw new BindingTypeNotSupported($type);
    }
}
