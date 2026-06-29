<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Builder;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use Thesis\Dic\Configuration\FunctionAutoconfig;
use Thesis\Dic\Configuration\FunctionConfig;
use Thesis\Dic\Configuration\ObjectAutoconfig;
use Thesis\Dic\Configuration\ObjectConfig;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Builder;
use Thesis\Dic\Internal\Container\Factories;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;
use Thesis\Fixture\Counter;
use Thesis\TestService;

#[Covers(Autoconfiguration::class)]
final class AutoconfigurationTest
{
    #[Test]
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
        $autoconfiguration->autoconfigure($object);
        $autoconfiguration->autoconfigure($function);

        $autoconfiguration->start();

        Assert::count($seen, 2);
        Assert::contains($seen, $object);
        Assert::contains($seen, $function);
    }

    #[Test]
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
                $autoconfiguration->autoconfigure($appended);
            }
        });

        $object = self::objectConfig();
        $autoconfiguration->autoconfigure($object);

        $autoconfiguration->start();

        Assert::count($seen, 2);
        Assert::contains($seen, $object);
        Assert::contains($seen, $appended);
    }

    #[Test]
    public function autoconfiguresEachConfigOnce(): void
    {
        $autoconfiguration = self::autoconfiguration();

        /** @var list<Ref<mixed>> $seen */
        $seen = [];
        $autoconfiguration->onObject(static function (ObjectAutoconfig $object) use (&$seen): void {
            $seen[] = $object->ref;
        });

        $object = self::objectConfig();
        $autoconfiguration->autoconfigure($object);
        $autoconfiguration->autoconfigure($object);

        $autoconfiguration->start();

        Assert::count($seen, 1);
    }

    #[Test]
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
        $autoconfiguration->autoconfigure($kept);
        $autoconfiguration->autoconfigure($removed);
        $autoconfiguration->doNotAutoconfigure($removed);

        $autoconfiguration->start();

        Assert::count($seen, 1);
        Assert::contains($seen, $kept);
    }

    private static function autoconfiguration(): Autoconfiguration
    {
        return new Autoconfiguration(new Services(
            singletonFactories: new Factories(),
            scopedFactories: new Factories(),
        ));
    }

    /**
     * @return ObjectConfig<Counter>
     */
    private static function objectConfig(): ObjectConfig
    {
        return new ObjectConfig(
            builder: new Builder(),
            autowiring: new Autowiring(),
            reflection: new \ReflectionClass(Counter::class),
            factory: null,
            declaredAt: Location::caller(),
        );
    }

    /**
     * @return FunctionConfig<callable>
     */
    private static function functionConfig(): FunctionConfig
    {
        $object = new ObjectConfig(
            builder: new Builder(),
            autowiring: new Autowiring(),
            reflection: new \ReflectionClass(TestService::class),
            factory: null,
            declaredAt: Location::caller(),
        );

        return $object->method('with');
    }
}
