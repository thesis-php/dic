<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Autowiring;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;
use Thesis\Fixture\Base;
use Thesis\Fixture\Cache;
use Thesis\Fixture\Counter;
use Thesis\Fixture\CounterList;
use Thesis\Fixture\ParentTyped;
use Thesis\Fixture\RedisCache;
use Typhoon\Type;
use function Typhoon\Type\intersectionT;
use function Typhoon\Type\nullOrT;
use function Typhoon\Type\objectT;
use function Typhoon\Type\unionT;
use const Typhoon\Type\arrayT;
use const Typhoon\Type\callableT;
use const Typhoon\Type\intT;
use const Typhoon\Type\iterableT;
use const Typhoon\Type\mixedT;
use const Typhoon\Type\nullT;
use const Typhoon\Type\objectT;

#[Test]
#[Covers(ClassAcceptance::class)]
final class ClassAcceptanceTest
{
    /**
     * @param class-string $class
     */
    #[DataProvider('cases')]
    public function accepts(Type $type, string $class, bool $expected): void
    {
        Assert::same(ClassAcceptance::accepts($type, new \ReflectionClass($class)), $expected);
    }

    /**
     * @return \Generator<array{Type, class-string, bool}>
     */
    public static function cases(): \Generator
    {
        yield 'same class' => [objectT(RedisCache::class), RedisCache::class, true];
        yield 'same class, other case' => [objectT('thesis\fixture\rediscache'), RedisCache::class, true];
        yield 'interface itself' => [objectT(Cache::class), Cache::class, true];
        yield 'interface, other case' => [objectT('thesis\fixture\cache'), RedisCache::class, true];
        yield 'abstract class itself' => [objectT(Base::class), Base::class, true];
        yield 'type that does not exist' => [objectT('Thesis\Fixture\Missing'), Counter::class, false]; // @phpstan-ignore argument.type
        yield 'parent class' => [objectT(Base::class), ParentTyped::class, true];
        yield 'unrelated class' => [objectT(Base::class), Counter::class, false];
        yield 'implemented interface' => [objectT(Cache::class), RedisCache::class, true];
        yield 'unrelated interface' => [objectT(Cache::class), Counter::class, false];
        yield 'object' => [objectT, Counter::class, true];
        yield 'mixed' => [mixedT, Counter::class, true];
        yield 'nullable interface' => [nullOrT(objectT(Cache::class)), RedisCache::class, true];
        yield 'nullable unrelated' => [nullOrT(objectT(Cache::class)), Counter::class, false];
        yield 'union with a match' => [unionT(objectT(Counter::class), objectT(Cache::class)), RedisCache::class, true];
        yield 'intersection, all match' => [intersectionT(objectT(Cache::class), objectT(RedisCache::class)), RedisCache::class, true];
        yield 'intersection, one misses' => [intersectionT(objectT(Cache::class), objectT(\Countable::class)), RedisCache::class, false];
        yield 'iterable, traversable' => [iterableT, CounterList::class, true];
        yield 'iterable, plain' => [iterableT, Counter::class, false];
        yield 'callable, invokable' => [callableT, \Closure::class, true];
        yield 'callable, plain' => [callableT, Counter::class, false];
        yield 'null' => [nullT, Counter::class, false];
        yield 'int' => [intT, Counter::class, false];
        yield 'array' => [arrayT, Counter::class, false];
    }
}
