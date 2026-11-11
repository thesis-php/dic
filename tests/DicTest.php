<?php

declare(strict_types=1);

namespace Thesis;

use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Expect;
use Testo\Test;
use Thesis\Dic\BuildError;
use Thesis\Dic\ClosureModule;
use Thesis\Dic\DisposalFailed;
use Thesis\Dic\TaggedRef;
use Thesis\Dic\TaggedRefs;
use Thesis\Fixture\ApcuCache;
use Thesis\Fixture\Cache;
use Thesis\Fixture\CacheStore;
use Thesis\Fixture\CacheTag;
use Thesis\Fixture\ConflictingConsumer;
use Thesis\Fixture\Consumer;
use Thesis\Fixture\Counter;
use Thesis\Fixture\CounterAndNumbers;
use Thesis\Fixture\CounterHolder;
use Thesis\Fixture\CounterList;
use Thesis\Fixture\Holder;
use Thesis\Fixture\InvokableModule;
use Thesis\Fixture\Numbers;
use Thesis\Fixture\OptionalConsumer;
use Thesis\Fixture\Pair;
use Thesis\Fixture\PriorityTag;
use Thesis\Fixture\QualifiedConsumer;
use Thesis\Fixture\RedisCache;
use Thesis\Fixture\Suspending;
use Thesis\Fixture\WithDefault;
use Thesis\Fixture\Wrapper;
use function Amp\async;
use function Amp\delay;
use function Amp\Future\awaitAll;
use function Thesis\Dic\autowire;
use function Thesis\Fixture\concurrently;
use function Thesis\Fixture\ref;
use function Typhoon\Type\closureT;
use function Typhoon\Type\objectT;
use function Typhoon\Type\param;
use const Typhoon\Type\intT;

#[Test]
final readonly class DicTest
{
    public function value(): void
    {
        $value = Dic::build(static fn(Dic $dic) => $dic->value(1));

        Assert::same($value, 1);
    }

    public function valueRef(): void
    {
        $value = Dic::build(static function (Dic $dic) {
            $ref = $dic->value(1);

            return $dic->value($ref);
        });

        Assert::same($value, 1);
    }

    public function valueArrayRef(): void
    {
        $value = Dic::build(static function (Dic $dic) {
            $ref1 = $dic->value(1);
            $ref2 = $dic->value(2);

            return $dic->value([$ref1, $ref2]);
        });

        Assert::same($value, [1, 2]);
    }

    public function object(): void
    {
        $object = Dic::build(
            static fn(Dic $dic) => $dic->object(TestService::class),
        );

        Assert::equals($object, new TestService());
    }

    public function objectStaticFactory(): void
    {
        $object = Dic::build(
            static fn(Dic $dic) => $dic->object(TestService::class, TestService::new(...)),
        );

        Assert::equals($object, TestService::new());
    }

    public function objectMethodFactory(): void
    {
        $object = Dic::build(
            static fn(Dic $dic) => $dic->object(
                class: TestService::class,
                factory: $dic->object(TestService::class)->method('new'),
            ),
        );

        Assert::equals($object, TestService::new());
    }

    public function objectCallableArrayClassMethodFactory(): void
    {
        $object = Dic::build(
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

    public function objectCallableArrayObjectMethodFactory(): void
    {
        $object = Dic::build(
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

    public function objectRefMethodArrayFactory(): void
    {
        $object = Dic::build(
            static fn(Dic $dic) => $dic->object(
                class: TestService::class,
                factory: [$dic->object(TestService::class), 'new'],
            ),
        );

        Assert::equals($object, TestService::new());
    }

    public function objectCall(): void
    {
        $value = 123;

        $object = Dic::build(
            static fn(Dic $dic) => $dic
                ->object(TestService::class)
                ->call('set', [$value]),
        );

        Assert::same($object->value, $value);
    }

    public function objectCallWithVariadic(): void
    {
        $object = Dic::build(
            static fn(Dic $dic) => $dic
                ->object(TestService::class)
                ->call('setAll', variadic: [1, 2, 3]),
        );

        Assert::same($object->value, [1, 2, 3]);
    }

    public function dependencyDeclaredAfterDependent(): void
    {
        $object = Dic::build(
            static fn(Dic $dic) => $dic
                ->object(TestService::class)
                ->arg('value', $dic->object(TestService::class)),
        );

        Assert::equals($object->value, new TestService());
    }

    public function circularDependency(): void
    {
        $line = __LINE__;

        Expect::exception(BuildError::class)
            ->withMessage(\sprintf(
                <<<'MSG'
                    Circular dependency detected:

                      "Thesis\TestService" (tests/DicTest.php:%1$s)
                      └─ $value → "Thesis\TestService" (tests/DicTest.php:%2$s)
                         └─ $value → "Thesis\TestService" (tests/DicTest.php:%3$s)
                            └─ $value[0] → "Thesis\TestService" (tests/DicTest.php:%1$s)  ← cycle

                    Break the cycle by removing one of the dependencies above.
                    MSG,
                $line + 20,
                $line + 21,
                $line + 22,
            ));

        Dic::build(static function (Dic $dic) {
            $a = $dic->object(TestService::class);
            $b = $dic->object(TestService::class);
            $c = $dic->object(TestService::class);

            $a->arg('value', $b);
            $b->arg('value', $c);
            $c->arg('value', [$a]);

            return $a;
        });
    }

    #[ExpectException(\LogicException::class)]
    public function selfDependency(): void
    {
        Dic::build(static function (Dic $dic) {
            $service = $dic->object(TestService::class);

            return $service->arg('value', $service);
        });
    }

    #[ExpectException(\LogicException::class)]
    public function scopedCircularDependency(): void
    {
        Dic::build(static function (Dic $dic) {
            $a = $dic->object(TestService::class)->scoped();
            $b = $dic->object(TestService::class)->scoped();

            $a->arg('value', $b);
            $b->arg('value', $a);

            return $a;
        });
    }

    public function circularDependencyThroughScopedHandle(): void
    {
        Expect::exception(BuildError::class)
            ->withMessageContaining('Circular dependency detected');

        Dic::build(static function (Dic $dic) {
            $scoped = $dic
                ->object(TestService::class)
                ->scoped();
            $singleton = $dic
                ->object(TestService::class)
                ->arg('value', $dic->scoped($scoped));
            $scoped->arg('value', $singleton);

            return $singleton;
        });
    }

    public function objectChain(): void
    {
        $value = 123;

        $object = Dic::build(
            static fn(Dic $dic) => $dic
                ->object(TestService::class)
                ->chain('with', [$value]),
        );

        Assert::same($object->value, $value);
    }

    public function objectChainWithVariadic(): void
    {
        $object = Dic::build(
            static fn(Dic $dic) => $dic
                ->object(TestService::class)
                ->chain('withAll', variadic: [1, 2, 3]),
        );

        Assert::same($object->value, [1, 2, 3]);
    }

    public function singletonScopedDependency(): void
    {
        $line = __LINE__;

        Expect::exception(BuildError::class)
            ->withMessage(\sprintf(
                <<<'MSG'
                    Singleton "Thesis\TestService" (tests/DicTest.php:%1$s) cannot depend on a non-singleton service:

                      "Thesis\TestService" (tests/DicTest.php:%1$s)
                      └─ $factory → "Thesis\TestService" (tests/DicTest.php:%2$s)  ← Scoped

                    Make "Thesis\TestService" (tests/DicTest.php:%1$s) scoped (or canBeScoped()), or make the dependency a singleton.
                    MSG,
                $line + 20,
                $line + 17,
            ));

        Dic::build(static function (Dic $dic) {
            $scoped = $dic->object(TestService::class)->scoped();

            return $dic
                ->object(TestService::class)
                ->args([$scoped]);
        });
    }

    public function autowiresConstructorByBoundType(): void
    {
        $consumer = Dic::build(static function (Dic $dic) {
            $dic->object(RedisCache::class)->bind(objectT(Cache::class));

            return $dic->object(Consumer::class);
        });

        Assert::instanceOf($consumer->cache, RedisCache::class);
        Assert::same($consumer->cache->get('key'), 'redis');
    }

    public function bindWithQualifierSelectsImplementation(): void
    {
        $consumer = Dic::build(static function (Dic $dic) {
            $dic->object(RedisCache::class)->bind(objectT(Cache::class), 'redis');
            $dic->object(ApcuCache::class)->bind(objectT(Cache::class), 'apcu');

            return $dic->object(Consumer::class)->arg('cache', autowire('apcu'));
        });

        Assert::instanceOf($consumer->cache, ApcuCache::class);
    }

    public function singletonInstanceIsSharedBetweenDependents(): void
    {
        $pair = Dic::build(static function (Dic $dic) {
            $dic->object(Holder::class)->bind(objectT(Holder::class));

            return $dic->object(Pair::class);
        });

        Assert::same($pair->first, $pair->second);
    }

    public function bindSelfAutowiresByDeclaredClass(): void
    {
        $pair = Dic::build(static function (Dic $dic) {
            $dic->object(Holder::class)->bindSelf();

            return $dic->object(Pair::class);
        });

        Assert::same($pair->first, $pair->second);
    }

    public function defaultValueIsUsedWhenNotAutowirable(): void
    {
        $object = Dic::build(
            static fn(Dic $dic) => $dic->object(WithDefault::class),
        );

        Assert::same($object->number, 42);
    }

    public function argOverridesDefaultValue(): void
    {
        $object = Dic::build(
            static fn(Dic $dic) => $dic->object(WithDefault::class)->arg('number', 7),
        );

        Assert::same($object->number, 7);
    }

    public function variadicArguments(): void
    {
        $object = Dic::build(
            static fn(Dic $dic) => $dic->object(Numbers::class)->variadic([1, 2, 3]),
        );

        Assert::same($object->numbers, [1, 2, 3]);
    }

    public function emptyVariadicDefaultsToEmptyArray(): void
    {
        $object = Dic::build(
            static fn(Dic $dic) => $dic->object(Numbers::class),
        );

        Assert::same($object->numbers, []);
    }

    public function requireComposesSubmodule(): void
    {
        $value = Dic::build(
            static fn(Dic $dic) => $dic->import(
                new ClosureModule(static fn(Dic $inner) => $inner->value(42)),
            ),
        );

        Assert::same($value, 42);
    }

    public function buildAcceptsModule(): void
    {
        $value = Dic::build(new ClosureModule(static fn(Dic $dic) => $dic->value(42)));

        Assert::same($value, 42);
    }

    public function buildPrefersModuleConfigureOverInvoke(): void
    {
        $value = Dic::build(new InvokableModule());

        Assert::same($value, 'configure');
    }

    public function runPrefersModuleConfigureOverInvoke(): void
    {
        $value = Dic::run(
            new InvokableModule(),
            static fn(string $value) => $value,
        );

        Assert::same($value, 'configure');
    }

    public function lazyObjectIsUsable(): void
    {
        $counter = Dic::build(
            static fn(Dic $dic) => $dic->object(Counter::class)->lazy(),
        );

        Assert::instanceOf($counter, Counter::class);

        $counter->value = 5;

        Assert::same($counter->value, 5);
    }

    public function lazyOnNonInstantiableClassRejected(): void
    {
        Expect::exception(BuildError::class)->withMessageContaining('cannot be made lazy');

        Dic::build(
            static fn(Dic $dic) => $dic
                ->object(Cache::class, static fn(): Cache => new RedisCache())
                ->lazy(),
        );
    }

    public function lazyOnInternalClassRejected(): void
    {
        Expect::exception(BuildError::class)
            ->withMessageContaining('cannot be made lazy because it is an internal class');

        Dic::build(
            static fn(Dic $dic) => $dic
                ->object(\ArrayObject::class)
                ->lazy(),
        );
    }

    public function lazyOnClassExtendingInternalRejected(): void
    {
        Expect::exception(BuildError::class)
            ->withMessageContaining('cannot be made lazy because it extends the internal class "ArrayObject"');

        Dic::build(
            static fn(Dic $dic) => $dic
                ->object(CounterList::class)
                ->lazy(),
        );
    }

    public function lazyStdClassIsUsable(): void
    {
        $object = Dic::build(
            static fn(Dic $dic) => $dic
                ->object(\stdClass::class)
                ->lazy(),
        );

        Assert::instanceOf($object, \stdClass::class);
    }

    public function callOfUnknownMethodRejected(): void
    {
        Expect::exception(BuildError::class)
            ->withMessageContaining('::missing()" does not exist and cannot be called on the service');

        Dic::build(
            static fn(Dic $dic) => $dic
                ->object(Counter::class)
                ->call('missing'),
        );
    }

    public function chainOfUnknownMethodRejected(): void
    {
        Expect::exception(BuildError::class)
            ->withMessageContaining('::missing()" does not exist and cannot be called on the service');

        Dic::build(
            static fn(Dic $dic) => $dic
                ->object(Counter::class)
                ->chain('missing'),
        );
    }

    public function declaringServiceAfterBuildRejected(): void
    {
        $declare = Dic::build(
            static fn(Dic $dic) => $dic->value(static fn() => $dic->object(Counter::class)),
        );

        Expect::exception(BuildError::class)->withMessageContaining('configuration is frozen');

        $declare();
    }

    public function refFromAnotherBuildRejectedAsDependency(): void
    {
        $foreign = ref(new Counter());

        Expect::exception(BuildError::class)
            ->withMessageContaining('depends on a ref that is not registered in this container')
            ->withMessageContaining('$counter → ');

        Dic::build(
            static fn(Dic $dic) => $dic
                ->object(CounterHolder::class)
                ->arg('counter', $foreign),
        );
    }

    public function refFromAnotherBuildRejectedAsModuleResult(): void
    {
        $foreign = ref(new Counter());

        Expect::exception(BuildError::class)
            ->withMessageContaining('depends on a ref that is not registered in this container');

        Dic::build(static fn(Dic $dic) => $foreign);
    }

    public function configIsNotCloneable(): void
    {
        Expect::exception(\BadMethodCallException::class)->withMessageContaining('is not cloneable');

        Dic::build(static fn(Dic $dic) => clone $dic->object(Counter::class));
    }

    public function scopedWrapperProducesFreshInstancePerRun(): void
    {
        $scoped = Dic::build(
            static fn(Dic $dic) => $dic->scoped(
                $dic->object(Counter::class)->scoped(),
            ),
        );

        $first = $scoped->run(static fn(Counter $counter) => $counter);
        $second = $scoped->run(static fn(Counter $counter) => $counter);

        Assert::notSame($first, $second);
    }

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

    public function disposerExceptionsDoNotStopOtherDisposers(): void
    {
        $boom = new \RuntimeException('boom');
        $secondRan = false;
        $caught = null;

        try {
            Dic::run(
                static function (Dic $dic) use ($boom, &$secondRan) {
                    return $dic
                        ->object(Counter::class)
                        ->disposer(static function () use ($boom): never {
                            throw $boom;
                        })
                        ->disposer(static function () use (&$secondRan): void {
                            $secondRan = true;
                        });
                },
                static fn(Counter $counter) => null,
            );
        } catch (DisposalFailed $error) {
            $caught = $error;
        }

        Assert::true($secondRan);
        Assert::notNull($caught);
        Assert::same($caught->errors, [$boom]);
    }

    public function disposerExceptionDoesNotMaskMainError(): void
    {
        $mainError = new \RuntimeException('main');
        $disposerError = new \RuntimeException('disposer');
        $caught = null;

        try {
            Dic::run(
                static fn(Dic $dic) => $dic
                    ->object(Counter::class)
                    ->disposer(static function () use ($disposerError): never {
                        throw $disposerError;
                    }),
                static function (Counter $counter) use ($mainError): never {
                    throw $mainError;
                },
            );
        } catch (DisposalFailed $error) {
            $caught = $error;
        }

        Assert::same($caught->getPrevious(), $mainError);
        Assert::same($caught->errors, [$disposerError]);
    }

    public function unusedLazyServiceIsNotDisposed(): void
    {
        $disposed = false;

        Dic::run(
            static function (Dic $dic) use (&$disposed) {
                $counter = $dic
                    ->object(Counter::class)
                    ->lazy()
                    ->disposer(static function () use (&$disposed): void {
                        $disposed = true;
                    });

                return $dic->object(CounterHolder::class)->arg('counter', $counter);
            },
            static fn(CounterHolder $holder) => null,
        );

        Assert::false($disposed);
    }

    public function usedLazyServiceIsDisposed(): void
    {
        $disposed = false;

        Dic::run(
            static function (Dic $dic) use (&$disposed) {
                $counter = $dic
                    ->object(Counter::class)
                    ->lazy()
                    ->disposer(static function () use (&$disposed): void {
                        $disposed = true;
                    });

                return $dic->object(CounterHolder::class)->arg('counter', $counter);
            },
            static function (CounterHolder $holder): void {
                $holder->counter->value = 1; // touch the lazy proxy to initialize it
            },
        );

        Assert::true($disposed);
    }

    public function dependentIsDisposedBeforeItsDependency(): void
    {
        $log = [];

        Dic::run(
            static function (Dic $dic) use (&$log) {
                $counter = $dic
                    ->object(Counter::class)
                    ->disposer(static function () use (&$log): void {
                        $log[] = 'counter';
                    });

                return $dic
                    ->object(CounterHolder::class)
                    ->arg('counter', $counter)
                    ->disposer(static function () use (&$log): void {
                        $log[] = 'holder';
                    });
            },
            static fn(CounterHolder $holder) => null,
        );

        Assert::same($log, ['holder', 'counter']);
    }

    public function lazyServiceIsDisposedBeforeTheDependencyCreatedOnItsInitialization(): void
    {
        $log = [];

        Dic::run(
            static function (Dic $dic) use (&$log) {
                $counter = $dic
                    ->object(Counter::class)
                    ->disposer(static function () use (&$log): void {
                        $log[] = 'counter';
                    });

                return $dic
                    ->object(CounterHolder::class)
                    ->arg('counter', $counter)
                    ->lazy()
                    ->disposer(static function () use (&$log): void {
                        $log[] = 'holder';
                    });
            },
            // Reading a property initializes the lazy proxy, creating the counter after the holder that depends on it.
            static fn(CounterHolder $holder) => $holder->counter,
        );

        Assert::same($log, ['holder', 'counter']);
    }

    public function scopedServiceIsDisposedBeforeTheSingletonItDependsOn(): void
    {
        $log = [];

        Dic::run(
            static function (Dic $dic) use (&$log) {
                $counter = $dic
                    ->object(Counter::class)
                    ->disposer(static function () use (&$log): void {
                        $log[] = 'singleton';
                    });

                return $dic
                    ->object(CounterHolder::class)
                    ->scoped()
                    ->arg('counter', $counter)
                    ->disposer(static function () use (&$log): void {
                        $log[] = 'scoped';
                    });
            },
            static fn(CounterHolder $holder) => null,
        );

        Assert::same($log, ['scoped', 'singleton']);
    }

    public function taggedListCollectsTaggedServices(): void
    {
        $caches = Dic::build(static function (Dic $dic) {
            $dic->object(RedisCache::class)->tag(new CacheTag());
            $dic->object(ApcuCache::class)->tag(new CacheTag());

            return $dic->taggedList(CacheTag::class);
        });

        Assert::count($caches, 2);
        Assert::contains(
            array_map(static fn(Cache $cache) => $cache->get('key'), $caches),
            'redis',
        );
    }

    public function taggedListRespectsSort(): void
    {
        $caches = Dic::build(static function (Dic $dic) {
            $dic->object(RedisCache::class)->tag(new PriorityTag(2));
            $dic->object(ApcuCache::class)->tag(new PriorityTag(1));

            return $dic->taggedList(
                PriorityTag::class,
                static fn(TaggedRef $a, TaggedRef $b): int => $a->tag->priority <=> $b->tag->priority,
            );
        });

        Assert::same(
            array_map(static fn(Cache $cache) => $cache->get('key'), $caches),
            ['apcu', 'redis'],
        );
    }

    public function onResolveTagsReceivesTaggedRefs(): void
    {
        $found = null;

        Dic::build(static function (Dic $dic) use (&$found) {
            $dic->object(RedisCache::class)->tag(new CacheTag());

            $dic->onTagResolution(static function (TaggedRefs $taggedRefs) use (&$found): void {
                $found = $taggedRefs->find(CacheTag::class);
            });

            return $dic->value(null);
        });

        Assert::notNull($found);
        Assert::count($found, 1);
    }

    public function signatureWiresFunctionArguments(): void
    {
        $signature = closureT([param(intT, name: 'n')], intT);

        $function = Dic::build(static function (Dic $dic) use ($signature) {
            $dic->object(RedisCache::class)->bind(objectT(Cache::class));

            return $dic
                ->function(static fn(Cache $cache, int $n): int => \strlen($cache->get('key') ?? '') + $n)
                ->closure($signature);
        });

        Assert::same($function(10), 15);
    }

    public function signatureWithVariadicRuntimeParameter(): void
    {
        $signature = closureT([param(intT, variadic: true, name: 'numbers')], intT);

        $function = Dic::build(
            static fn(Dic $dic) => $dic
                ->function(static fn(int ...$numbers): int => array_sum($numbers))
                ->closure($signature),
        );

        Assert::same($function(1, 2, 3), 6);
    }

    public function attributeAutowireQualifierSelectsImplementation(): void
    {
        $consumer = Dic::build(static function (Dic $dic) {
            $dic->object(RedisCache::class)->bind(objectT(Cache::class), 'redis');
            $dic->object(ApcuCache::class)->bind(objectT(Cache::class), 'apcu');

            return $dic->object(QualifiedConsumer::class);
        });

        Assert::instanceOf($consumer->cache, ApcuCache::class);
    }

    public function attributeDoNotAutowireFallsBackToDefault(): void
    {
        $consumer = Dic::build(
            static fn(Dic $dic) => $dic->object(OptionalConsumer::class),
        );

        Assert::null($consumer->cache);
    }

    public function bindWithEnumQualifier(): void
    {
        $consumer = Dic::build(static function (Dic $dic) {
            $dic->object(RedisCache::class)->bind(objectT(Cache::class), CacheStore::Redis);
            $dic->object(ApcuCache::class)->bind(objectT(Cache::class), CacheStore::Apcu);

            return $dic->object(Consumer::class)->arg('cache', autowire(CacheStore::Apcu));
        });

        Assert::instanceOf($consumer->cache, ApcuCache::class);
    }

    public function canBeScopedStaysSingletonWithoutScopedDependencies(): void
    {
        $scoped = Dic::build(
            static fn(Dic $dic) => $dic->scoped(
                $dic->object(Counter::class)->canBeScoped(),
            ),
        );

        $first = $scoped->run(static fn(Counter $counter) => $counter);
        $second = $scoped->run(static fn(Counter $counter) => $counter);

        Assert::same($first, $second);
    }

    public function canBeScopedBecomesScopedWithScopedDependency(): void
    {
        $scoped = Dic::build(
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

    public function positionalArgument(): void
    {
        $object = Dic::build(
            static fn(Dic $dic) => $dic->object(WithDefault::class)->arg(0, 7),
        );

        Assert::same($object->number, 7);
    }

    public function positionalFactoryWiresDependencyAndVariadic(): void
    {
        $object = Dic::build(static function (Dic $dic) {
            $dic->object(Counter::class)->bind(objectT(Counter::class));

            return $dic->object(CounterAndNumbers::class)->variadic([1, 2]);
        });

        Assert::instanceOf($object->counter, Counter::class);
        Assert::same($object->numbers, [1, 2]);
    }

    public function eagerObjectIsUsable(): void
    {
        $counter = Dic::build(
            static fn(Dic $dic) => $dic->object(Counter::class)->lazy()->eager(),
        );

        Assert::instanceOf($counter, Counter::class);

        $counter->value = 3;

        Assert::same($counter->value, 3);
    }

    public function scopedRunDisposesAndRethrowsOnError(): void
    {
        $thrown = new \RuntimeException('boom');
        $captured = null;
        $rethrown = null;

        $scoped = Dic::build(
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

    public function scopedRunDisposesCreatedServicesWhenResolutionFails(): void
    {
        $thrown = new \RuntimeException('boom');
        $captured = null;
        $rethrown = null;
        $called = false;

        $scoped = Dic::build(
            static function (Dic $dic) use ($thrown, &$captured) {
                $counter = $dic
                    ->object(Counter::class)
                    ->scoped()
                    ->disposer(static function (Counter $counter, ?\Throwable $error) use (&$captured): void {
                        $captured = $error;
                    });

                return $dic->scoped(
                    $dic
                        ->object(CounterHolder::class, static fn(Counter $counter): never => throw $thrown)
                        ->scoped()
                        ->arg('counter', $counter),
                );
            },
        );

        try {
            $scoped->run(static function (CounterHolder $holder) use (&$called): void {
                $called = true;
            });
        } catch (\RuntimeException $error) {
            $rethrown = $error;
        }

        Assert::false($called);
        Assert::same($rethrown, $thrown);
        Assert::same($captured, $thrown);
    }

    public function taggedListFindsByTagInstance(): void
    {
        $tag = new CacheTag();

        $caches = Dic::build(static function (Dic $dic) use ($tag) {
            $dic->object(RedisCache::class)->tag($tag);
            $dic->object(ApcuCache::class)->tag(new CacheTag());

            return $dic->taggedList($tag);
        });

        Assert::count($caches, 1);
    }

    public function emptyTaggedListIsEmptyArray(): void
    {
        $caches = Dic::build(
            static fn(Dic $dic) => $dic->taggedList(CacheTag::class),
        );

        Assert::same($caches, []);
    }

    public function unboundDependencyWrapsCannotAutowire(): void
    {
        Expect::exception(BuildError::class)->withMessageContaining('cannot autowire');

        Dic::build(static fn(Dic $dic) => $dic->object(Consumer::class));
    }

    public function nonInstantiableClassRejected(): void
    {
        Expect::exception(BuildError::class)->withMessageContaining('is not instantiable');

        Dic::build(static fn(Dic $dic) => $dic->object(Cache::class));
    }

    public function factoryRefThatIsNotCallableRejected(): void
    {
        Expect::exception(BuildError::class)->withMessageContaining('is not callable');

        Dic::build(
            /** @phpstan-ignore argument.type */
            static fn(Dic $dic) => $dic->object(Counter::class, factory: $dic->value(42)),
        );
    }

    public function refMethodFactoryFails(): void
    {
        Expect::exception(BuildError::class)->withMessageContaining('is not callable');

        Dic::build(static function (Dic $dic) {
            $factory = $dic->object(TestService::class);

            return $dic->object(TestService::class, factory: [$factory, '__toString']);
        });
    }

    public function duplicateBindRejected(): void
    {
        Expect::exception(BuildError::class)->withMessageContaining('already bound');

        Dic::build(static function (Dic $dic) {
            $dic->object(RedisCache::class)->bind(objectT(Cache::class));
            $dic->object(ApcuCache::class)->bind(objectT(Cache::class));

            return $dic->value(null);
        });
    }

    public function bindUnsupportedTypeRejected(): void
    {
        Expect::exception(BuildError::class)->withMessageContaining('is not supported for binding');

        Dic::build(static function (Dic $dic) {
            $dic->value(new \ArrayObject())->bind(objectT(\ArrayObject::class, [intT]));

            return $dic->value(null);
        });
    }

    public function combinedAutowireAndDoNotAutowireRejected(): void
    {
        Expect::exception(BuildError::class)->withMessageContaining('combine #[Autowire] and #[DoNotAutowire]');

        Dic::build(static fn(Dic $dic) => $dic->object(ConflictingConsumer::class));
    }

    public function concurrentFibersShareOneSingletonCreatedByFactory(): void
    {
        $provide = Dic::build(
            static fn(Dic $dic) => $dic->provider(
                $dic->object(Suspending::class, Suspending::create(...)),
            ),
        );

        [$first, $second] = concurrently($provide);

        Assert::same($first, $second);
    }

    public function concurrentFibersShareOneSingletonCreatedByConstructor(): void
    {
        $provide = Dic::build(
            static fn(Dic $dic) => $dic->provider(
                $dic->object(Suspending::class),
            ),
        );

        [$first, $second] = concurrently($provide);

        Assert::same($first, $second);
    }

    public function concurrentFibersShareOneScopedInstanceWithinAScope(): void
    {
        $scoped = Dic::build(
            static fn(Dic $dic) => $dic->scoped(
                $dic->provider(
                    $dic
                        ->object(Suspending::class)
                        ->scoped(),
                ),
            ),
        );

        [$first, $second] = $scoped->run(concurrently(...));

        Assert::same($first, $second);
    }

    public function failedConcurrentCreationIsSharedAndThenRetried(): void
    {
        $created = 0;
        $error = new \RuntimeException('transient');

        $scoped = Dic::build(
            static function (Dic $dic) use (&$created, $error) {
                $singleton = $dic->object(Counter::class, static function () use (&$created, $error): Counter {
                    delay(0);

                    if (++$created === 1) {
                        throw $error;
                    }

                    return new Counter();
                });

                return $dic->scoped(
                    $dic
                        ->object(Wrapper::class)
                        ->args([$singleton])
                        ->scoped(),
                );
            },
        );

        [$errors] = awaitAll([
            async(static fn() => $scoped->run(static fn(Wrapper $wrapper) => $wrapper->value)),
            async(static fn() => $scoped->run(static fn(Wrapper $wrapper) => $wrapper->value)),
        ]);

        Assert::same($errors, [$error, $error]);
        Assert::instanceOf($scoped->run(static fn(Wrapper $wrapper) => $wrapper->value), Counter::class);
        Assert::same($created, 2);
    }

    public function scopeDisposalAwaitsCreationStillInProgress(): void
    {
        $disposed = [];

        $scoped = Dic::build(
            static function (Dic $dic) use (&$disposed) {
                return $dic->scoped(
                    $dic->provider(
                        $dic
                            ->object(Suspending::class)
                            ->scoped()
                            ->disposer(static function (Suspending $instance) use (&$disposed): void {
                                $disposed[] = $instance;
                            }),
                    ),
                );
            },
        );

        $creation = $scoped->run(static function (\Closure $provide) {
            $creation = async($provide);
            delay(0); // lets the creation start and suspend, so it outlives the scope

            return $creation;
        });

        Assert::same($disposed, [$creation->await()]);
    }
}
