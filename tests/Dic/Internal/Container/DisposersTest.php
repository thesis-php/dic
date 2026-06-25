<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Container;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use Thesis\Dic\Configuration\ValueConfig;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Builder;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;

#[Covers(Disposers::class)]
final class DisposersTest
{
    #[Test]
    public function disposeCallsDisposerWithValueAndError(): void
    {
        $disposers = new Disposers();
        $ref = self::ref();
        $error = new \RuntimeException('boom');
        $received = [];

        $disposers->add($ref, static function (mixed $value, ?\Throwable $error) use (&$received): void {
            $received = [$value, $error];
        });

        $disposers->dispose($ref, 'value', $error);

        Assert::same($received, ['value', $error]);
    }

    #[Test]
    public function disposersRunInRegistrationOrder(): void
    {
        $disposers = new Disposers();
        $ref = self::ref();
        $log = [];

        $disposers->add($ref, static function () use (&$log): void {
            $log[] = 'a';
        });
        $disposers->add($ref, static function () use (&$log): void {
            $log[] = 'b';
        });

        $disposers->dispose($ref, null, null);

        Assert::same($log, ['a', 'b']);
    }

    #[Test]
    public function disposeOnlyRunsDisposersForTheGivenRef(): void
    {
        $disposers = new Disposers();
        $ref = self::ref();
        $other = self::ref();
        $log = [];

        $disposers->add($other, static function () use (&$log): void {
            $log[] = 'other';
        });

        $disposers->dispose($ref, 'value', null);

        Assert::same($log, []);
    }

    /**
     * @return Ref<int>
     */
    private static function ref(): Ref
    {
        return new ValueConfig(
            builder: new Builder(),
            autowiring: new Autowiring(),
            value: 1,
            declaredAt: Location::caller(),
        );
    }
}
