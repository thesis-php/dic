<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Container;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Test;
use Thesis\Dic\Internal\ShouldNotHappen;
use function Amp\async;
use function Amp\delay;

#[Test]
#[Covers(InstanceFuture::class)]
final class InstanceFutureTest
{
    /**
     * @var ?InstanceFuture<mixed>
     */
    private static ?InstanceFuture $future = null;

    public function keepsTheCreatingFiberAlive(): void
    {
        // The future is kept outside the fiber, so only the future can keep the fiber alive.
        $fiber = new \Fiber(static function (): void {
            self::$future = new InstanceFuture();
            \Fiber::suspend();
        });
        $fiber->start();
        $creator = \WeakReference::create($fiber);
        Assert::instanceOf(self::$future, InstanceFuture::class);

        unset($fiber);
        gc_collect_cycles();

        Assert::notNull($creator->get());

        self::$future = null;
        gc_collect_cycles();

        Assert::null($creator->get());
    }

    public function awaitableByAnyFiberButTheCreatingOne(): void
    {
        $future = new InstanceFuture();

        Assert::false($future->isAwaitable);
        Assert::true(async(static fn() => $future->isAwaitable)->await());
    }

    public function handDrivenFibersAreNotGuardedUntilTheRevoltLoopRuns(): void
    {
        $script = __DIR__ . '/../../../Fixture/Process/hand-driven-fibers.php';

        exec(\sprintf('%s %s 2>&1', escapeshellarg(PHP_BINARY), escapeshellarg($script)), $output, $exitCode);

        Assert::same($exitCode, 0);
        Assert::same($output, ['{"shared":true,"stored":true}']);
    }

    public function creatingFiberCannotAwaitItsOwnInstance(): void
    {
        $future = new InstanceFuture();

        Expect::exception(ShouldNotHappen::class)
            ->withMessageContaining('Only another fiber can await an instance');

        $future->await();
    }

    public function completeResumesEveryWaiterWithTheValue(): void
    {
        /** @var InstanceFuture<object> $future */
        $future = new InstanceFuture();
        $value = new \stdClass();
        $first = async(static fn() => $future->await());
        $second = async(static fn() => $future->await());

        delay(0); // lets the waiters start awaiting
        $future->complete($value);

        Assert::same($first->await(), $value);
        Assert::same($second->await(), $value);
    }

    public function failThrowsToEveryWaiter(): void
    {
        $future = new InstanceFuture();
        $error = new \RuntimeException('boom');
        $waiters = [
            async(static fn() => $future->await()),
            async(static fn() => $future->await()),
        ];

        delay(0);
        $future->fail($error);

        foreach ($waiters as $waiter) {
            $caught = null;

            try {
                $waiter->await();
            } catch (\RuntimeException $caught) {
            }

            Assert::same($caught, $error);
        }
    }

    public function settledInstanceCannotBeAwaited(): void
    {
        $future = new InstanceFuture();
        $future->complete(null);

        Expect::exception(ShouldNotHappen::class);

        async(static fn() => $future->await())->await();
    }

    public function cannotBeSettledTwice(): void
    {
        $future = new InstanceFuture();
        $future->complete(null);

        Expect::exception(ShouldNotHappen::class);

        $future->fail(new \RuntimeException('boom'));
    }
}
