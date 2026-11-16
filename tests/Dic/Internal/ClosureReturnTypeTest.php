<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;
use Thesis\Fixture\ApcuCache;
use Thesis\Fixture\Cache;
use Thesis\Fixture\Counter;
use Thesis\Fixture\RedisCache;
use Typhoon\Type;
use function Typhoon\Type\classConstantT;
use function Typhoon\Type\closureT;
use function Typhoon\Type\constantT;
use function Typhoon\Type\intersectionT;
use function Typhoon\Type\listT;
use function Typhoon\Type\nullOrT;
use function Typhoon\Type\objectShapeT;
use function Typhoon\Type\objectT;
use function Typhoon\Type\unionT;
use const Typhoon\Type\arrayT;
use const Typhoon\Type\callableT;
use const Typhoon\Type\intT;
use const Typhoon\Type\iterableT;
use const Typhoon\Type\mixedT;
use const Typhoon\Type\neverT;
use const Typhoon\Type\nonEmptyStringT;
use const Typhoon\Type\nullT;
use const Typhoon\Type\objectT;
use const Typhoon\Type\voidT;

#[Test]
#[Covers(ClosureReturnType::class)]
final class ClosureReturnTypeTest
{
    /**
     * @param class-string $class
     */
    #[DataProvider('cases')]
    public function admitsOther(Type $type, string $class, bool $expected): void
    {
        Assert::same(ClosureReturnType::admitsOther($type, new \ReflectionClass($class)), $expected);
    }

    /**
     * @return \Generator<array{Type, class-string, bool}>
     */
    public static function cases(): \Generator
    {
        yield 'same class' => [objectT(Counter::class), Counter::class, false];
        yield 'implementation of the interface' => [objectT(RedisCache::class), Cache::class, false];
        yield 'unrelated class' => [objectT(Counter::class), Cache::class, true];
        yield 'nullable' => [nullOrT(objectT(RedisCache::class)), Cache::class, true];
        yield 'union of two implementations' => [unionT(objectT(RedisCache::class), objectT(ApcuCache::class)), Cache::class, false];
        yield 'union, one does not fit' => [unionT(objectT(RedisCache::class), objectT(Counter::class)), Cache::class, true];
        yield 'intersection, one fits' => [intersectionT(objectT(Cache::class), objectT(\Countable::class)), Cache::class, false];
        yield 'intersection, none fits' => [intersectionT(objectT(\Countable::class), objectT(\Stringable::class)), Cache::class, true];
        yield 'closure, as a Closure' => [closureT(), \Closure::class, false];
        yield 'closure, unrelated' => [closureT(), Cache::class, true];
        yield 'mixed' => [mixedT, Cache::class, false];
        yield 'object' => [objectT, Cache::class, false];
        yield 'object shape' => [objectShapeT(), Cache::class, false];
        yield 'constant' => [constantT('PHP_EOL'), Cache::class, false];
        yield 'class constant' => [classConstantT(\DateTimeInterface::class, 'ATOM'), Cache::class, false];
        yield 'never' => [neverT, Cache::class, false];
        yield 'iterable, which may be an array' => [iterableT, Cache::class, true];
        yield 'callable, which may be a string' => [callableT, Cache::class, true];
        yield 'int' => [intT, Cache::class, true];
        yield 'non-empty string' => [nonEmptyStringT, Cache::class, true];
        yield 'array' => [arrayT, Cache::class, true];
        yield 'list' => [listT(), Cache::class, true];
        yield 'null' => [nullT, Cache::class, true];
        yield 'void' => [voidT, Cache::class, true];
    }
}
