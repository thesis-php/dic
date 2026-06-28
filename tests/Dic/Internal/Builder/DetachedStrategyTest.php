<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Builder;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use Thesis\Dic\Internal\Dependency;
use function Thesis\Fixture\ref;

#[Covers(LifetimeStrategy::class)]
final class DetachedStrategyTest
{
    #[Test]
    public function resolvesToSingleton(): void
    {
        $resolution = LifetimeStrategy::Detached->resolve(ref(), self::empty());

        Assert::same($resolution->strategy, LifetimeStrategy::Detached);
        Assert::true($resolution->isSingleton);
        Assert::null($resolution->scopedPath);
    }

    #[Test]
    public function ignoresDependencies(): void
    {
        $dependencies = (static function (): \Generator {
            Assert::fail('Dependencies must not be consumed.');
            yield; // @phpstan-ignore deadCode.unreachable
        })();

        $resolution = LifetimeStrategy::Detached->resolve(ref(), $dependencies);

        Assert::true($resolution->isSingleton);
    }

    /**
     * @return \Generator<Dependency, Resolution>
     */
    private static function empty(): \Generator
    {
        yield from [];
    }
}
