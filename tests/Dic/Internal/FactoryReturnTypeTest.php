<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;
use Thesis\Fixture\ApcuCache;
use Thesis\Fixture\Base;
use Thesis\Fixture\Cache;
use Thesis\Fixture\Counter;
use Thesis\Fixture\Creators;
use Thesis\Fixture\ParentTyped;
use Thesis\Fixture\RedisCache;

#[Test]
#[Covers(FactoryReturnType::class)]
final class FactoryReturnTypeTest
{
    /**
     * @param class-string $class
     */
    #[DataProvider('cases')]
    public function contradicts(\ReflectionFunctionAbstract $factory, string $class, bool $expected): void
    {
        Assert::same(FactoryReturnType::contradicts($factory, new \ReflectionClass($class)), $expected);
    }

    /**
     * @return \Generator<array{\ReflectionFunctionAbstract, class-string, bool}>
     */
    public static function cases(): \Generator
    {
        yield 'no return type' => [new \ReflectionFunction(static fn() => new Counter()), Cache::class, false];
        yield 'same class' => [new \ReflectionFunction(static fn(): Counter => new Counter()), Counter::class, false];
        yield 'implementation of the interface' => [new \ReflectionFunction(static fn(): RedisCache => new RedisCache()), Cache::class, false];
        yield 'subclass' => [new \ReflectionFunction(static fn(): ParentTyped => new ParentTyped()), Base::class, false];
        yield 'unrelated class' => [new \ReflectionFunction(static fn(): Counter => new Counter()), Cache::class, true];
        yield 'nullable' => [new \ReflectionFunction(static fn(): ?RedisCache => null), Cache::class, true];
        yield 'scalar' => [new \ReflectionFunction(static fn(): int => 1), Cache::class, true];
        yield 'union of two implementations' => [new \ReflectionFunction(static fn(bool $redis): RedisCache|ApcuCache => $redis ? new RedisCache() : new ApcuCache()), Cache::class, false];
        yield 'DNF with null' => [new \ReflectionFunction(static fn(): null|(Cache&\Countable) => null), Cache::class, true];
        yield 'DNF, all fit' => [new \ReflectionFunction(static fn(bool $redis): (Cache&\Countable)|RedisCache => new RedisCache()), Cache::class, false]; // @phpstan-ignore return.unusedType
        yield 'void' => [new \ReflectionFunction(static function (): void {}), Cache::class, true];
        yield 'null' => [new \ReflectionFunction(static fn(): null => null), Cache::class, true];
        yield 'false' => [new \ReflectionFunction(static fn(): false => false), Cache::class, true];
        yield 'class that does not exist' => [new \ReflectionFunction(static fn(): Missing => throw new \LogicException()), Cache::class, true]; // @phpstan-ignore class.notFound
        yield 'first-class callable of a static method' => [new \ReflectionFunction(Creators::self(...)), Base::class, false];
        yield 'first-class callable of a static method, unrelated' => [new \ReflectionFunction(Creators::self(...)), Cache::class, true];
        yield 'union, all fit' => [new \ReflectionFunction(static fn(): RedisCache|Cache => new RedisCache()), Cache::class, false];
        yield 'union, one does not fit' => [new \ReflectionFunction(static fn(): RedisCache|Counter => new RedisCache()), Cache::class, true]; // @phpstan-ignore return.unusedType
        yield 'union with unknown' => [new \ReflectionMethod(Creators::class, 'staticOrCounter'), Counter::class, false];
        yield 'iterable, which may be an array' => [new \ReflectionFunction(static fn(): iterable => []), Cache::class, true];
        yield 'callable, which may be a string' => [new \ReflectionFunction(static fn(): callable => 'strlen'), Cache::class, true];
        yield 'union with iterable, which may be an array' => [new \ReflectionFunction(static fn(): RedisCache|iterable => new RedisCache()), Cache::class, true]; // @phpstan-ignore return.unusedType
        yield 'intersection, one fits' => [new \ReflectionFunction(static fn(): Cache&\Countable => new RedisCache()), Cache::class, false]; // @phpstan-ignore return.type
        yield 'intersection, none fits' => [new \ReflectionFunction(static fn(): \Countable&\Stringable => new RedisCache()), Cache::class, true]; // @phpstan-ignore return.type
        yield 'mixed' => [new \ReflectionFunction(static fn(): mixed => new Counter()), Cache::class, false];
        yield 'object' => [new \ReflectionFunction(static fn(): object => new Counter()), Cache::class, false];
        yield 'never' => [new \ReflectionFunction(static fn(): never => throw new \LogicException()), Cache::class, false];
        yield 'self' => [new \ReflectionMethod(Creators::class, 'self'), Base::class, false];
        yield 'self, unrelated' => [new \ReflectionMethod(Creators::class, 'self'), Cache::class, true];
        yield 'self in a closure, resolved to its class' => [new \ReflectionFunction(Creators::selfClosure()), Cache::class, true];
        yield 'static' => [new \ReflectionMethod(Creators::class, 'static'), Cache::class, false];
        yield 'parent' => [new \ReflectionMethod(Creators::class, 'parent'), Base::class, false];
        yield 'parent, not the child class' => [new \ReflectionMethod(Creators::class, 'parent'), Creators::class, true];
        yield 'self with no class to resolve to' => [new \ReflectionFunction(\Closure::bind(Creators::selfClosure(), null, null) ?? throw new \LogicException('cannot unbind')), Cache::class, false];
        yield 'parent, unrelated' => [new \ReflectionMethod(Creators::class, 'parent'), Cache::class, true];
    }
}
