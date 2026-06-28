<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Builder;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Test;
use Thesis\Dic\BuildError;
use Thesis\Dic\Internal\Dependency;
use function Thesis\Fixture\ref;

#[Covers(LifetimeStrategy::class)]
final class SingletonStrategyTest
{
    #[Test]
    public function resolvesToSingletonWithoutDependencies(): void
    {
        $resolution = LifetimeStrategy::Singleton->resolve(ref(), self::edges([]));

        Assert::same($resolution->strategy, LifetimeStrategy::Singleton);
        Assert::true($resolution->isSingleton);
        Assert::null($resolution->scopedPath);
    }

    #[Test]
    public function allowsSingletonAndDetachedDependencies(): void
    {
        $resolution = LifetimeStrategy::Singleton->resolve(ref(), self::edges([
            [Dependency::of(ref()), Resolution::singleton(LifetimeStrategy::Singleton)],
            [Dependency::of(ref()), Resolution::singleton(LifetimeStrategy::Detached)],
        ]));

        Assert::same($resolution->strategy, LifetimeStrategy::Singleton);
        Assert::true($resolution->isSingleton);
    }

    #[Test]
    public function allowsAnInferredCarrierHoldingOnlySingletons(): void
    {
        $resolution = LifetimeStrategy::Singleton->resolve(ref(), self::edges([
            [Dependency::of(ref()), Resolution::singleton(LifetimeStrategy::Inferred)],
        ]));

        Assert::true($resolution->isSingleton);
    }

    #[Test]
    public function rejectsAScopedDependencyAfterSingletonOnes(): void
    {
        Expect::exception(BuildError::class)
            ->withMessageContaining('cannot depend on a non-singleton service')
            ->withMessageContaining('← Scoped');

        LifetimeStrategy::Singleton->resolve(ref(), self::edges([
            [Dependency::of(ref()), Resolution::singleton(LifetimeStrategy::Singleton)],
            [Dependency::factory(ref()), Resolution::scoped(LifetimeStrategy::Scoped, [])],
        ]));
    }

    #[Test]
    public function rejectsACanBeScopedDependencyEvenWhenItResolvedToSingleton(): void
    {
        Expect::exception(BuildError::class)
            ->withMessageContaining('← CanBeScoped');

        LifetimeStrategy::Singleton->resolve(ref(), self::edges([
            [Dependency::of(ref()), Resolution::singleton(LifetimeStrategy::CanBeScoped)],
        ]));
    }

    #[Test]
    public function rejectsAnInferredCarrierHoldingAScopedServiceAndNamesTheLeaf(): void
    {
        $carrier = Dependency::of(ref())->arg('caches');
        $leaf = Dependency::of(ref())->key(0);

        Expect::exception(BuildError::class)
            ->withMessageContaining('$caches')
            ->withMessageContaining('[0]')
            ->withMessageContaining('← Scoped');

        LifetimeStrategy::Singleton->resolve(ref(), self::edges([
            [$carrier, Resolution::scoped(LifetimeStrategy::Inferred, [$leaf])],
        ]));
    }

    #[Test]
    public function failsOnTheFirstOffendingDependency(): void
    {
        $dependencies = self::edges([
            [Dependency::of(ref())->arg('first'), Resolution::scoped(LifetimeStrategy::Scoped, [])],
            [Dependency::of(ref())->arg('second'), Resolution::scoped(LifetimeStrategy::Scoped, [])],
        ]);

        try {
            LifetimeStrategy::Singleton->resolve(ref(), $dependencies);
            Assert::fail('Expected a BuildError to be thrown.');
        } catch (BuildError $error) {
            Assert::true(str_contains($error->getMessage(), '$first'));
            Assert::false(str_contains($error->getMessage(), '$second'));
        }
    }

    /**
     * @param list<array{Dependency, Resolution}> $edges
     * @return \Generator<Dependency, Resolution>
     */
    private static function edges(array $edges): \Generator
    {
        foreach ($edges as [$dependency, $resolution]) {
            yield $dependency => $resolution;
        }
    }
}
