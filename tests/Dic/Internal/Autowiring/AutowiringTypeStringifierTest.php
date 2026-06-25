<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Autowiring;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Test;
use Thesis\Fixture\Holder;
use Thesis\Fixture\SelfTyped;
use function Typhoon\Type\intersectionT;
use function Typhoon\Type\objectT;
use function Typhoon\Type\unionT;
use const Typhoon\Type\arrayT;
use const Typhoon\Type\boolT;
use const Typhoon\Type\callableT;
use const Typhoon\Type\falseT;
use const Typhoon\Type\floatT;
use const Typhoon\Type\intT;
use const Typhoon\Type\iterableT;
use const Typhoon\Type\mixedT;
use const Typhoon\Type\nullT;
use const Typhoon\Type\stringT;
use const Typhoon\Type\trueT;

#[Covers(AutowiringTypeStringifier::class)]
final class AutowiringTypeStringifierTest
{
    #[Test]
    public function stringifiesScalarTypes(): void
    {
        Assert::same(AutowiringTypeStringifier::stringifyTyphoonType(intT), 'int');
        Assert::same(AutowiringTypeStringifier::stringifyTyphoonType(stringT), 'string');
        Assert::same(AutowiringTypeStringifier::stringifyTyphoonType(boolT), 'bool');
        Assert::same(AutowiringTypeStringifier::stringifyTyphoonType(floatT), 'float');
        Assert::same(AutowiringTypeStringifier::stringifyTyphoonType(nullT), 'null');
        Assert::same(AutowiringTypeStringifier::stringifyTyphoonType(mixedT), 'mixed');
        Assert::same(AutowiringTypeStringifier::stringifyTyphoonType(arrayT), 'array');
        Assert::same(AutowiringTypeStringifier::stringifyTyphoonType(callableT), 'callable');
        Assert::same(AutowiringTypeStringifier::stringifyTyphoonType(\Typhoon\Type\objectT), 'object');
    }

    #[Test]
    public function stringifiesBooleanLiteralAndIterableTypes(): void
    {
        Assert::same(AutowiringTypeStringifier::stringifyTyphoonType(trueT), 'true');
        Assert::same(AutowiringTypeStringifier::stringifyTyphoonType(falseT), 'false');
        Assert::same(AutowiringTypeStringifier::stringifyTyphoonType(iterableT), 'iterable');
    }

    #[Test]
    public function stringifiesNullableUnion(): void
    {
        Assert::same(
            AutowiringTypeStringifier::stringifyTyphoonType(unionT(intT, nullT)),
            '(int|null)',
        );
    }

    #[Test]
    public function resolvesSelfReflectionType(): void
    {
        $parameter = new \ReflectionParameter([SelfTyped::class, 'withSelf'], 0);

        Assert::same(
            AutowiringTypeStringifier::stringifyParameterType($parameter),
            strtolower(SelfTyped::class),
        );
    }

    #[Test]
    public function stringifiesNamedObjectAsLowercaseClass(): void
    {
        Assert::same(
            AutowiringTypeStringifier::stringifyTyphoonType(objectT(Holder::class)),
            strtolower(Holder::class),
        );
    }

    #[Test]
    public function stringifiesUnionSortedAndDeduplicated(): void
    {
        Assert::same(
            AutowiringTypeStringifier::stringifyTyphoonType(unionT(stringT, intT)),
            '(int|string)',
        );
    }

    #[Test]
    public function stringifiesIntersectionSorted(): void
    {
        Assert::same(
            AutowiringTypeStringifier::stringifyTyphoonType(
                intersectionT(objectT(\Countable::class), objectT(\ArrayAccess::class)),
            ),
            '(arrayaccess&countable)',
        );
    }

    #[Test]
    public function rejectsUnsupportedType(): void
    {
        Expect::exception(UnsupportedType::class);

        AutowiringTypeStringifier::stringifyTyphoonType(objectT(\ArrayObject::class, [intT]));
    }

    #[Test]
    public function stringifiesReflectionNamedType(): void
    {
        Assert::same(self::reflect(static fn(int $x) => null), 'int');
    }

    #[Test]
    public function stringifiesReflectionNullableType(): void
    {
        Assert::same(self::reflect(static fn(?int $x) => null), '(int|null)');
    }

    #[Test]
    public function stringifiesReflectionUnionType(): void
    {
        Assert::same(self::reflect(static fn(int|string $x) => null), '(int|string)');
    }

    private static function reflect(callable $function): string
    {
        $parameters = new \ReflectionFunction($function(...))->getParameters();
        \assert(isset($parameters[0]));

        return AutowiringTypeStringifier::stringifyParameterType($parameters[0]);
    }
}
