<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Builder;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use Thesis\Dic\Configuration\ClosureConfig;
use Thesis\Dic\Configuration\FunctionAutoconfig;
use Thesis\Dic\Configuration\FunctionConfig;
use Thesis\Dic\Configuration\ObjectAutoconfig;
use Thesis\Dic\Configuration\ObjectConfig;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Builder;
use Thesis\Dic\Ref;
use Thesis\Fixture\Counter;
use Thesis\TestService;
use function Thesis\Dic\Internal\caller;
use function Typhoon\Type\closureT;
use const Typhoon\Type\intT;

#[Test]
#[Covers(Autoconfiguration::class)]
final class AutoconfigurationTest
{
    public function autoconfiguresEveryScheduledConfig(): void
    {
        $autoconfiguration = self::autoconfiguration();

        /** @var list<Ref<mixed>> $seen */
        $seen = [];
        $record = static function (ObjectAutoconfig|FunctionAutoconfig $config) use (&$seen): void {
            $seen[] = $config->ref;
        };
        $autoconfiguration->onObject($record);
        $autoconfiguration->onFunction($record);

        $object = self::objectConfig();
        $function = self::functionConfig();
        $autoconfiguration->schedule($object);
        $autoconfiguration->schedule($function);

        $autoconfiguration->start();

        Assert::count($seen, 2);
        Assert::contains($seen, $object);
        Assert::contains($seen, $function);
    }

    public function declaredAtClimbsPastTheAutoconfigWrapperToTheUserCallback(): void
    {
        $autoconfiguration = self::autoconfiguration();

        $closure = null;
        $autoconfiguration->onFunction(static function (FunctionAutoconfig $config) use (&$closure): void {
            $closure = $config->closure(closureT([], intT)); // the expected line
        });

        $autoconfiguration->schedule(self::functionConfig());
        $autoconfiguration->start();

        Assert::instanceOf($closure, ClosureConfig::class);
        Assert::same($closure->declaredAt->file, __FILE__);
        Assert::same($closure->declaredAt->line, __LINE__ - 8);
    }

    public function autoconfiguresConfigScheduledDuringTheRun(): void
    {
        $autoconfiguration = self::autoconfiguration();
        $appended = self::objectConfig();

        /** @var list<Ref<mixed>> $seen */
        $seen = [];
        $scheduled = false;
        $autoconfiguration->onObject(static function (ObjectAutoconfig $object) use (&$seen, &$scheduled, $autoconfiguration, $appended): void {
            $seen[] = $object->ref;

            if (!$scheduled) {
                $scheduled = true;
                $autoconfiguration->schedule($appended);
            }
        });

        $object = self::objectConfig();
        $autoconfiguration->schedule($object);

        $autoconfiguration->start();

        Assert::count($seen, 2);
        Assert::contains($seen, $object);
        Assert::contains($seen, $appended);
    }

    public function autoconfiguresEachConfigOnce(): void
    {
        $autoconfiguration = self::autoconfiguration();

        /** @var list<Ref<mixed>> $seen */
        $seen = [];
        $autoconfiguration->onObject(static function (ObjectAutoconfig $object) use (&$seen): void {
            $seen[] = $object->ref;
        });

        $object = self::objectConfig();
        $autoconfiguration->schedule($object);
        $autoconfiguration->schedule($object);

        $autoconfiguration->start();

        Assert::count($seen, 1);
    }

    public function unscheduledConfigIsNotAutoconfigured(): void
    {
        $autoconfiguration = self::autoconfiguration();

        /** @var list<Ref<mixed>> $seen */
        $seen = [];
        $autoconfiguration->onObject(static function (ObjectAutoconfig $object) use (&$seen): void {
            $seen[] = $object->ref;
        });

        $kept = self::objectConfig();
        $removed = self::objectConfig();
        $autoconfiguration->schedule($kept);
        $autoconfiguration->schedule($removed);
        $autoconfiguration->unschedule($removed);

        $autoconfiguration->start();

        Assert::count($seen, 1);
        Assert::contains($seen, $kept);
    }

    private static function autoconfiguration(): Autoconfiguration
    {
        return new Autoconfiguration(new Builder());
    }

    /**
     * @return ObjectConfig<Counter>
     */
    private static function objectConfig(): ObjectConfig
    {
        return new ObjectConfig(
            builder: $builder = new Builder(),
            autoconfiguration: new Autoconfiguration($builder),
            autowiring: new Autowiring(),
            reflection: new \ReflectionClass(Counter::class),
            factory: null,
            declaredAt: caller(),
        );
    }

    /**
     * @return FunctionConfig<callable>
     */
    private static function functionConfig(): FunctionConfig
    {
        $object = new ObjectConfig(
            builder: $builder = new Builder(),
            autoconfiguration: new Autoconfiguration($builder),
            autowiring: new Autowiring(),
            reflection: new \ReflectionClass(TestService::class),
            factory: null,
            declaredAt: caller(),
        );

        return $object->method('with');
    }
}
