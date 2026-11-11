<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Container;

use Revolt\EventLoop;
use Revolt\EventLoop\Suspension;
use Thesis\Dic\Internal\ShouldNotHappen;

/**
 * An instance being created right now, which concurrent fibers await instead of creating their own.
 *
 * Awaiting relies on the Revolt event loop, so it is only attempted while that loop runs
 * and then assumes every fiber is scheduled by it (as amphp does).
 *
 * @internal
 *
 * @template T
 */
final class InstanceFuture
{
    /**
     * Whether the current fiber can await the instance: any fiber but the creating one, while the Revolt loop runs.
     * Never without revolt/event-loop installed: creation is then not guarded.
     *
     * A dependency cycle cannot make the creating fiber ask for its own instance — every cycle is rejected at build.
     * So when it does, it is another coroutine of a scheduler that does not use fibers (Swoole), which cannot await.
     *
     * The loop runs once something has handed control to it, as amphp does on the first await, and keeps running
     * while its fibers do. Until then, fibers are not scheduled by Revolt and nothing could resume a waiting one.
     * Checked on contention only, so an uncontended creation never touches the loop.
     */
    public bool $isAwaitable {
        get => self::revoltInstalled()
            && \Fiber::getCurrent() !== $this->creator
            && EventLoop::getDriver()->isRunning();
    }

    /**
     * The fiber creating the instance, null for the main context.
     *
     * Held strongly on purpose: while the future is registered, the creating fiber cannot be garbage-collected
     * halfway through creation — which would skip every catch block and leave the future unsettled forever.
     * It can only go together with the container that registers the future, and then nobody is left to await.
     *
     * @var ?\Fiber<mixed, mixed, mixed, mixed>
     */
    private readonly ?\Fiber $creator;

    /**
     * The fibers awaiting the instance, null once it is settled.
     *
     * @var ?list<Suspension<T>>
     */
    private ?array $waiters = [];

    /**
     * Starts awaiting the instance that the current fiber is about to create.
     */
    public function __construct()
    {
        $this->creator = \Fiber::getCurrent();
    }

    /**
     * @return T
     */
    public function await(): mixed
    {
        if (!$this->isAwaitable) {
            throw new ShouldNotHappen('Only another fiber can await an instance, while the Revolt loop runs');
        }

        if ($this->waiters === null) {
            throw new ShouldNotHappen('A settled instance future cannot be awaited');
        }

        /** @var Suspension<T> $suspension */
        $suspension = EventLoop::getSuspension();
        $this->waiters[] = $suspension;

        return $suspension->suspend();
    }

    /**
     * @param T $value
     */
    public function complete(mixed $value): void
    {
        foreach ($this->settle() as $waiter) {
            $waiter->resume($value);
        }
    }

    public function fail(\Throwable $error): void
    {
        foreach ($this->settle() as $waiter) {
            $waiter->throw($error);
        }
    }

    /**
     * @return list<Suspension<T>>
     */
    private function settle(): array
    {
        $waiters = $this->waiters ?? throw new ShouldNotHappen('An instance future cannot be settled twice');
        $this->waiters = null;

        return $waiters;
    }

    private static function revoltInstalled(): bool
    {
        /** @var bool */
        static $installed = class_exists(EventLoop::class);

        return $installed;
    }
}
