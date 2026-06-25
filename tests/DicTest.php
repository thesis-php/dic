<?php

declare(strict_types=1);

namespace Thesis;

use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Expect;
use Testo\Test;
use Thesis\Dic\Error\CircularDependency;
use Thesis\Dic\Error\SingletonDependsOnScoped;
use Thesis\Dic\TaggedRef;
use Thesis\Dic\TaggedRefs;
use Thesis\Fixture\Consumer;
use Thesis\Fixture\Counter;
use Thesis\Fixture\CounterAndNumbers;
use Thesis\Fixture\CounterHolder;
use Thesis\Fixture\EnGreeter;
use Thesis\Fixture\Greeter;
use Thesis\Fixture\GreeterTag;
use Thesis\Fixture\Holder;
use Thesis\Fixture\Lang;
use Thesis\Fixture\Numbers;
use Thesis\Fixture\OptionalConsumer;
use Thesis\Fixture\Pair;
use Thesis\Fixture\PriorityTag;
use Thesis\Fixture\QualifiedConsumer;
use Thesis\Fixture\RuGreeter;
use Thesis\Fixture\WithDefault;
use function Thesis\Dic\autowire;
use function Typhoon\Type\closureT;
use function Typhoon\Type\objectT;
use function Typhoon\Type\param;
use const Typhoon\Type\intT;

final readonly class DicTest
{
    #[Test]
    public function value(): void
    {
        $value = Dic::assemble(static fn(Dic $dic) => $dic->value(1));

        Assert::same($value, 1);
    }

    #[Test]
    public function valueRef(): void
    {
        $value = Dic::assemble(static function (Dic $dic) {
            $ref = $dic->value(1);

            return $dic->value($ref);
        });

        Assert::same($value, 1);
    }

    #[Test]
    public function valueArrayRef(): void
    {
        $value = Dic::assemble(static function (Dic $dic) {
            $ref1 = $dic->value(1);
            $ref2 = $dic->value(2);

            return $dic->value([$ref1, $ref2]);
        });

        Assert::same($value, [1, 2]);
    }

    #[Test]
    public function object(): void
    {
        $object = Dic::assemble(
            static fn(Dic $dic) => $dic->object(TestService::class),
        );

        Assert::equals($object, new TestService());
    }

    #[Test]
    public function objectStaticFactory(): void
    {
        $object = Dic::assemble(
            static fn(Dic $dic) => $dic->object(TestService::class, TestService::new(...)),
        );

        Assert::equals($object, TestService::new());
    }

    #[Test]
    public function objectMethodFactory(): void
    {
        $object = Dic::assemble(
            static fn(Dic $dic) => $dic->object(
                class: TestService::class,
                /** @phpstan-ignore argument.type */
                factory: $dic->object(TestService::class)->method('new'),
            ),
        );

        Assert::equals($object, TestService::new());
    }

    #[Test]
    public function objectCallableArrayClassMethodFactory(): void
    {
        $object = Dic::assemble(
            static fn(Dic $dic) => $dic->object(
                class: TestService::class,
                /** @phpstan-ignore argument.type */
                factory: $dic->value([
                    $dic->value(TestService::class),
                    $dic->value('new'),
                ]),
            ),
        );

        Assert::equals($object, TestService::new());
    }

    #[Test]
    public function objectCallableArrayObjectMethodFactory(): void
    {
        $object = Dic::assemble(
            static fn(Dic $dic) => $dic->object(
                class: TestService::class,
                /** @phpstan-ignore argument.type */
                factory: $dic->value([
                    $dic->object(TestService::class),
                    $dic->value('new'),
                ]),
            ),
        );

        Assert::equals($object, TestService::new());
    }

    #[Test]
    public function objectCall(): void
    {
        $value = 123;

        $object = Dic::assemble(
            static fn(Dic $dic) => $dic
                ->object(TestService::class)
                ->call('set', [$value]),
        );

        Assert::same($object->value, $value);
    }

    #[Test]
    public function dependencyDeclaredAfterDependent(): void
    {
        $object = Dic::assemble(
            static fn(Dic $dic) => $dic
                ->object(TestService::class)
                ->arg('value', $dic->object(TestService::class)),
        );

        Assert::equals($object->value, new TestService());
    }

    #[Test]
    public function circularDependency(): void
    {
        $line = __LINE__;

        Expect::exception(CircularDependency::class)
            ->withMessage(\sprintf(
                <<<'MSG'
                    Circular dependency detected:

                      Thesis\TestService (tests/DicTest.php:%1$s)
                      └─ $value → Thesis\TestService (tests/DicTest.php:%2$s)
                         └─ $value → Thesis\TestService (tests/DicTest.php:%3$s)
                            └─ $value[0] → Thesis\TestService (tests/DicTest.php:%1$s)  ← cycle

                    Break the cycle by removing one of the dependencies above.
                    MSG,
                $line + 20,
                $line + 21,
                $line + 22,
            ));

        Dic::assemble(static function (Dic $dic) {
            $a = $dic->object(TestService::class);
            $b = $dic->object(TestService::class);
            $c = $dic->object(TestService::class);

            $a->arg('value', $b);
            $b->arg('value', $c);
            $c->arg('value', [$a]);

            return $a;
        });
    }

    #[Test]
    #[ExpectException(\LogicException::class)]
    public function selfDependency(): void
    {
        Dic::assemble(static function (Dic $dic) {
            $service = $dic->object(TestService::class);

            return $service->arg('value', $service);
        });
    }

    #[Test]
    #[ExpectException(\LogicException::class)]
    public function scopedCircularDependency(): void
    {
        Dic::assemble(static function (Dic $dic) {
            $a = $dic->object(TestService::class)->scoped();
            $b = $dic->object(TestService::class)->scoped();

            $a->arg('value', $b);
            $b->arg('value', $a);

            return $a;
        });
    }

    #[Test]
    public function objectChain(): void
    {
        $value = 123;

        $object = Dic::assemble(
            static fn(Dic $dic) => $dic
                ->object(TestService::class)
                ->chain('with', [$value]),
        );

        Assert::same($object->value, $value);
    }

    #[Test]
    public function singletonScopedDependency(): void
    {
        $line = __LINE__;

        Expect::exception(SingletonDependsOnScoped::class)
            ->withMessage(\sprintf(
                <<<'MSG'
                    Singleton Thesis\TestService (tests/DicTest.php:%1$s) cannot depend on non-singleton services:

                      Thesis\TestService (tests/DicTest.php:%1$s)
                      └─ $factory → Thesis\TestService (tests/DicTest.php:%2$s)  ← Scoped

                    Make Thesis\TestService (tests/DicTest.php:%1$s) scoped (or canBeScoped()), or make these dependencies singletons.
                    MSG,
                $line + 20,
                $line + 17,
            ));

        Dic::assemble(static function (Dic $dic) {
            $scoped = $dic->object(TestService::class)->scoped();

            return $dic
                ->object(TestService::class)
                ->args([$scoped]);
        });
    }

    #[Test]
    public function autowiresConstructorByBoundType(): void
    {
        $consumer = Dic::assemble(static function (Dic $dic) {
            $dic->object(EnGreeter::class)->bind(objectT(Greeter::class));

            return $dic->object(Consumer::class);
        });

        Assert::instanceOf($consumer->greeter, EnGreeter::class);
        Assert::same($consumer->greeter->greet(), 'hello');
    }

    #[Test]
    public function bindWithQualifierSelectsImplementation(): void
    {
        $consumer = Dic::assemble(static function (Dic $dic) {
            $dic->object(EnGreeter::class)->bind(objectT(Greeter::class), 'en');
            $dic->object(RuGreeter::class)->bind(objectT(Greeter::class), 'ru');

            return $dic->object(Consumer::class)->arg('greeter', autowire('ru'));
        });

        Assert::instanceOf($consumer->greeter, RuGreeter::class);
    }

    #[Test]
    public function singletonInstanceIsSharedBetweenDependents(): void
    {
        $pair = Dic::assemble(static function (Dic $dic) {
            $dic->object(Holder::class)->bind(objectT(Holder::class));

            return $dic->object(Pair::class);
        });

        Assert::same($pair->first, $pair->second);
    }

    #[Test]
    public function defaultValueIsUsedWhenNotAutowirable(): void
    {
        $object = Dic::assemble(
            static fn(Dic $dic) => $dic->object(WithDefault::class),
        );

        Assert::same($object->number, 42);
    }

    #[Test]
    public function argOverridesDefaultValue(): void
    {
        $object = Dic::assemble(
            static fn(Dic $dic) => $dic->object(WithDefault::class)->arg('number', 7),
        );

        Assert::same($object->number, 7);
    }

    #[Test]
    public function variadicArguments(): void
    {
        $object = Dic::assemble(
            static fn(Dic $dic) => $dic->object(Numbers::class)->variadic([1, 2, 3]),
        );

        Assert::same($object->numbers, [1, 2, 3]);
    }

    #[Test]
    public function emptyVariadicDefaultsToEmptyArray(): void
    {
        $object = Dic::assemble(
            static fn(Dic $dic) => $dic->object(Numbers::class),
        );

        Assert::same($object->numbers, []);
    }

    #[Test]
    public function requireComposesSubmodule(): void
    {
        $value = Dic::assemble(
            static fn(Dic $dic) => $dic->require(
                static fn(Dic $inner) => $inner->value(42),
            ),
        );

        Assert::same($value, 42);
    }

    #[Test]
    public function lazyObjectIsUsable(): void
    {
        $counter = Dic::assemble(
            static fn(Dic $dic) => $dic->object(Counter::class)->lazy(),
        );

        Assert::instanceOf($counter, Counter::class);

        $counter->value = 5;

        Assert::same($counter->value, 5);
    }

    #[Test]
    public function scopedWrapperProducesFreshInstancePerRun(): void
    {
        $scoped = Dic::assemble(
            static fn(Dic $dic) => $dic->scoped(
                $dic->object(Counter::class)->scoped(),
            ),
        );

        $first = $scoped->run(static fn(Counter $counter) => $counter);
        $second = $scoped->run(static fn(Counter $counter) => $counter);

        Assert::notSame($first, $second);
    }

    #[Test]
    public function runInvokesDisposerWithValue(): void
    {
        $disposed = [];

        $result = Dic::run(
            static function (Dic $dic) use (&$disposed) {
                return $dic->object(Counter::class)->disposer(
                    static function (Counter $counter) use (&$disposed): void {
                        $disposed[] = $counter;
                    },
                );
            },
            static fn(Counter $counter) => $counter->value = 1,
        );

        Assert::same($result, 1);
        Assert::count($disposed, 1);
    }

    #[Test]
    public function runPassesThrownErrorToDisposerAndRethrows(): void
    {
        $thrown = new \RuntimeException('boom');
        $captured = null;
        $rethrown = null;

        try {
            Dic::run(
                static function (Dic $dic) use (&$captured) {
                    return $dic->object(Counter::class)->disposer(
                        static function (Counter $counter, ?\Throwable $error) use (&$captured): void {
                            $captured = $error;
                        },
                    );
                },
                static function (Counter $counter) use ($thrown): never {
                    throw $thrown;
                },
            );
        } catch (\RuntimeException $error) {
            $rethrown = $error;
        }

        Assert::same($rethrown, $thrown);
        Assert::same($captured, $thrown);
    }

    #[Test]
    public function taggedListCollectsTaggedServices(): void
    {
        $greeters = Dic::assemble(static function (Dic $dic) {
            $dic->object(EnGreeter::class)->tag(new GreeterTag());
            $dic->object(RuGreeter::class)->tag(new GreeterTag());

            return $dic->taggedList(GreeterTag::class);
        });

        Assert::count($greeters, 2);
        Assert::contains(
            array_map(static fn(Greeter $greeter) => $greeter->greet(), $greeters),
            'hello',
        );
    }

    #[Test]
    public function taggedListRespectsSort(): void
    {
        $greeters = Dic::assemble(static function (Dic $dic) {
            $dic->object(EnGreeter::class)->tag(new PriorityTag(2));
            $dic->object(RuGreeter::class)->tag(new PriorityTag(1));

            return $dic->taggedList(
                PriorityTag::class,
                static fn(TaggedRef $a, TaggedRef $b): int => $a->tag->priority <=> $b->tag->priority,
            );
        });

        Assert::same(
            array_map(static fn(Greeter $greeter) => $greeter->greet(), $greeters),
            ['привет', 'hello'],
        );
    }

    #[Test]
    public function onResolveTagsReceivesTaggedRefs(): void
    {
        $found = null;

        Dic::assemble(static function (Dic $dic) use (&$found) {
            $dic->object(EnGreeter::class)->tag(new GreeterTag());

            $dic->onTagResolution(static function (TaggedRefs $taggedRefs) use (&$found): void {
                $found = $taggedRefs->find(GreeterTag::class);
            });

            return $dic->value(null);
        });

        Assert::notNull($found);
        Assert::count($found, 1);
    }

    #[Test]
    public function signatureWiresFunctionArguments(): void
    {
        $signature = closureT([param(intT, name: 'n')], intT);

        $function = Dic::assemble(static function (Dic $dic) use ($signature) {
            $dic->object(EnGreeter::class)->bind(objectT(Greeter::class));

            return $dic->closure(
                $signature,
                static fn(Greeter $greeter, int $n): int => \strlen($greeter->greet()) + $n,
            );
        });

        Assert::same($function(10), 15);
    }

    #[Test]
    public function signatureWithVariadicRuntimeParameter(): void
    {
        $signature = closureT([param(intT, variadic: true, name: 'numbers')], intT);

        $function = Dic::assemble(
            static fn(Dic $dic) => $dic->closure(
                $signature,
                static fn(int ...$numbers): int => array_sum($numbers),
            ),
        );

        Assert::same($function(1, 2, 3), 6);
    }

    #[Test]
    public function attributeAutowireQualifierSelectsImplementation(): void
    {
        $consumer = Dic::assemble(static function (Dic $dic) {
            $dic->object(EnGreeter::class)->bind(objectT(Greeter::class), 'en');
            $dic->object(RuGreeter::class)->bind(objectT(Greeter::class), 'ru');

            return $dic->object(QualifiedConsumer::class);
        });

        Assert::instanceOf($consumer->greeter, RuGreeter::class);
    }

    #[Test]
    public function attributeDoNotAutowireFallsBackToDefault(): void
    {
        $consumer = Dic::assemble(
            static fn(Dic $dic) => $dic->object(OptionalConsumer::class),
        );

        Assert::null($consumer->greeter);
    }

    #[Test]
    public function bindWithEnumQualifier(): void
    {
        $consumer = Dic::assemble(static function (Dic $dic) {
            $dic->object(EnGreeter::class)->bind(objectT(Greeter::class), Lang::En);
            $dic->object(RuGreeter::class)->bind(objectT(Greeter::class), Lang::Ru);

            return $dic->object(Consumer::class)->arg('greeter', autowire(Lang::Ru));
        });

        Assert::instanceOf($consumer->greeter, RuGreeter::class);
    }

    #[Test]
    public function canBeScopedStaysSingletonWithoutScopedDependencies(): void
    {
        $scoped = Dic::assemble(
            static fn(Dic $dic) => $dic->scoped(
                $dic->object(Counter::class)->canBeScoped(),
            ),
        );

        $first = $scoped->run(static fn(Counter $counter) => $counter);
        $second = $scoped->run(static fn(Counter $counter) => $counter);

        Assert::same($first, $second);
    }

    #[Test]
    public function canBeScopedBecomesScopedWithScopedDependency(): void
    {
        $scoped = Dic::assemble(
            static fn(Dic $dic) => $dic->scoped(
                $dic
                    ->object(CounterHolder::class)
                    ->canBeScoped()
                    ->arg('counter', $dic->object(Counter::class)->scoped()),
            ),
        );

        $first = $scoped->run(static fn(CounterHolder $holder) => $holder);
        $second = $scoped->run(static fn(CounterHolder $holder) => $holder);

        Assert::notSame($first, $second);
    }

    #[Test]
    public function positionalArgument(): void
    {
        $object = Dic::assemble(
            static fn(Dic $dic) => $dic->object(WithDefault::class)->arg(0, 7),
        );

        Assert::same($object->number, 7);
    }

    #[Test]
    public function positionalFactoryWiresDependencyAndVariadic(): void
    {
        $object = Dic::assemble(static function (Dic $dic) {
            $dic->object(Counter::class)->bind(objectT(Counter::class));

            return $dic->object(CounterAndNumbers::class)->variadic([1, 2]);
        });

        Assert::instanceOf($object->counter, Counter::class);
        Assert::same($object->numbers, [1, 2]);
    }

    #[Test]
    public function eagerObjectIsUsable(): void
    {
        $counter = Dic::assemble(
            static fn(Dic $dic) => $dic->object(Counter::class)->lazy()->eager(),
        );

        Assert::instanceOf($counter, Counter::class);

        $counter->value = 3;

        Assert::same($counter->value, 3);
    }

    #[Test]
    public function scopedRunDisposesAndRethrowsOnError(): void
    {
        $thrown = new \RuntimeException('boom');
        $captured = null;
        $rethrown = null;

        $scoped = Dic::assemble(
            static function (Dic $dic) use (&$captured) {
                return $dic->scoped(
                    $dic
                        ->object(Counter::class)
                        ->scoped()
                        ->disposer(static function (Counter $counter, ?\Throwable $error) use (&$captured): void {
                            $captured = $error;
                        }),
                );
            },
        );

        try {
            $scoped->run(static function (Counter $counter) use ($thrown): never {
                throw $thrown;
            });
        } catch (\RuntimeException $error) {
            $rethrown = $error;
        }

        Assert::same($rethrown, $thrown);
        Assert::same($captured, $thrown);
    }

    #[Test]
    public function taggedListFindsByTagInstance(): void
    {
        $tag = new GreeterTag();

        $greeters = Dic::assemble(static function (Dic $dic) use ($tag) {
            $dic->object(EnGreeter::class)->tag($tag);
            $dic->object(RuGreeter::class)->tag(new GreeterTag());

            return $dic->taggedList($tag);
        });

        Assert::count($greeters, 1);
    }

    #[Test]
    public function emptyTaggedListIsEmptyArray(): void
    {
        $greeters = Dic::assemble(
            static fn(Dic $dic) => $dic->taggedList(GreeterTag::class),
        );

        Assert::same($greeters, []);
    }
}
