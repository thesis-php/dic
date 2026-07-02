<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Autowiring;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Test;
use Thesis\Fixture\Base;
use Thesis\Fixture\Holder;
use Thesis\Fixture\ParentTyped;
use Thesis\Fixture\SelfTyped;
use Typhoon\Type;
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
use const Typhoon\Type\nonEmptyStringT;
use const Typhoon\Type\nullT;
use const Typhoon\Type\objectT;
use const Typhoon\Type\stringT;
use const Typhoon\Type\trueT;

#[Test]
#[Covers(BindingType::class)]
#[Covers(BindingTypeStringifier::class)]
final class AutowiringTypeTest
{
    #[DataProvider('typhoonTypes')]
    public function typhoon(Type $type, string $expected): void
    {
        Assert::same(BindingType::ofTyphoonType($type)->string, $expected);
    }

    /**
     * @return \Generator<array{Type, non-empty-string}>
     */
    public static function typhoonTypes(): \Generator
    {
        yield [intT, 'int'];
        yield [stringT, 'string'];
        yield [boolT, 'bool'];
        yield [floatT, 'float'];
        yield [nullT, 'null'];
        yield [mixedT, 'mixed'];
        yield [arrayT, 'array'];
        yield [callableT, 'callable'];
        yield [objectT, 'object'];
        yield [trueT, 'true'];
        yield [falseT, 'false'];
        yield [iterableT, 'iterable'];
        yield [objectT(Holder::class), strtolower(Holder::class)];
        yield [unionT(intT, nullT), '(int|null)'];
        yield [unionT(stringT, intT), '(int|string)'];
        yield [intersectionT(objectT(\Countable::class), objectT(\ArrayAccess::class)), '(arrayaccess&countable)'];
    }

    #[DataProvider('unsupportedTyphoonTypes')]
    public function unsupportedTyphoon(Type $type): void
    {
        Expect::exception(UnsupportedBindingType::class);

        BindingType::ofTyphoonType($type);
    }

    /**
     * @return \Generator<array{Type}>
     */
    public static function unsupportedTyphoonTypes(): \Generator
    {
        yield [nonEmptyStringT];
        yield [objectT(\ArrayObject::class, templateArguments: [intT])];
    }

    #[DataProvider('parameters')]
    public function parameter(\Closure $function, string $expected): void
    {
        $parameter = new \ReflectionParameter($function, 0);

        Assert::same(BindingType::ofParameter($parameter)->string, $expected);
    }

    /**
     * @return \Generator<array{\Closure, non-empty-string}>
     */
    public static function parameters(): \Generator
    {
        yield [static fn(int $p) => null, 'int'];
        yield [static fn(?int $p) => null, '(int|null)'];
        yield [static fn(int|string $p) => null, '(int|string)'];
        yield [static fn(\Countable&\ArrayAccess $p) => null, '(arrayaccess&countable)'];
        yield [static fn(Holder $p) => null, strtolower(Holder::class)];
        yield [new SelfTyped()->withSelf(...), strtolower(SelfTyped::class)];
        yield [new ParentTyped()->withParent(...), strtolower(Base::class)];
    }

    #[DataProvider('unsupportedParameters')]
    public function unsupportedParameter(\Closure $function): void
    {
        Expect::exception(UnsupportedBindingType::class);

        BindingType::ofParameter(new \ReflectionParameter($function, 0));
    }

    /**
     * @return \Generator<array{\Closure}>
     */
    public static function unsupportedParameters(): \Generator
    {
        yield 'no type' => [static fn($p) => null];
    }

    public function equalsComparesNormalizedString(): void
    {
        $typhoon = BindingType::ofTyphoonType(intT);
        $parameter = BindingType::ofParameter(new \ReflectionParameter(static fn(int $p) => null, 0));

        Assert::true($typhoon->equals($parameter));
    }
}
