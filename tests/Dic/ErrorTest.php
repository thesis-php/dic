<?php

declare(strict_types=1);

namespace Thesis\Dic;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Test;
use Thesis\Dic;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Builder;
use Thesis\Fixture\ConflictingConsumer;
use Thesis\Fixture\Consumer;
use Thesis\Fixture\Counter;
use Thesis\Fixture\EnGreeter;
use Thesis\Fixture\Greeter;
use Thesis\Fixture\RuGreeter;
use Thesis\TestService;
use function Typhoon\Type\objectT;
use function Typhoon\Type\stringify;
use const Typhoon\Type\intT;

#[Covers(Error\UnknownRef::class)]
#[Covers(Error\UnsupportedBindingType::class)]
#[Covers(Error\ConfigurationFrozen::class)]
#[Covers(Error\InvalidConfigurationError::class)]
#[Covers(Error\CannotAutowire::class)]
#[Covers(Error\InvalidArgument::class)]
#[Covers(Error\ConfigurationError::class)]
#[Covers(Error\RuntimeError::class)]
#[Covers(Error\CircularDependency::class)]
#[Covers(Error\SingletonDependsOnScoped::class)]
final class ErrorTest
{
    #[Test]
    public function unknownRefIsRuntimeError(): void
    {
        $ref = self::ref();
        $error = new Error\UnknownRef($ref);

        Assert::instanceOf($error, Error\RuntimeError::class);
        Assert::instanceOf($error, \RuntimeException::class);
        Assert::instanceOf($error, Error::class);
        Assert::same($error->getMessage(), \sprintf('%s is not registered', $ref));
    }

    #[Test]
    public function unsupportedTypeIsConfigurationError(): void
    {
        $type = objectT(\ArrayObject::class, [intT]);
        $error = new Error\UnsupportedBindingType($type);

        Assert::instanceOf($error, Error\ConfigurationError::class);
        Assert::instanceOf($error, \LogicException::class);
        Assert::instanceOf($error, Error::class);
        Assert::same($error->getMessage(), \sprintf('Type "%s" is not supported for binding', stringify($type)));
    }

    #[Test]
    public function configurationFrozenMessage(): void
    {
        $ref = self::ref();
        $error = new Error\ConfigurationFrozen($ref);

        Assert::instanceOf($error, Error\ConfigurationError::class);
        Assert::same(
            $error->getMessage(),
            \sprintf('Cannot configure %s: configuration is frozen once the container starts building', $ref),
        );
    }

    #[Test]
    public function invalidConfigurationAppendsPreviousDetail(): void
    {
        $ref = self::ref();
        $previous = new \RuntimeException('detail');
        $error = new Error\InvalidConfigurationError($ref, $previous);

        Assert::instanceOf($error, Error\ConfigurationError::class);
        Assert::same($error->getPrevious(), $previous);
        Assert::same($error->getMessage(), \sprintf('Invalid configuration for %s: detail', $ref));
    }

    #[Test]
    public function invalidConfigurationWithoutDetail(): void
    {
        $ref = self::ref();
        $error = new Error\InvalidConfigurationError($ref, new \RuntimeException(''));

        Assert::same($error->getMessage(), \sprintf('Invalid configuration for %s', $ref));
    }

    #[Test]
    public function cannotAutowireIsConfigurationError(): void
    {
        $error = new Error\CannotAutowire('nope');

        Assert::instanceOf($error, Error\ConfigurationError::class);
        Assert::instanceOf($error, Error::class);
        Assert::same($error->getMessage(), 'nope');
    }

    #[Test]
    public function invalidArgumentIsConfigurationError(): void
    {
        $error = new Error\InvalidArgument('bad');

        Assert::instanceOf($error, Error\ConfigurationError::class);
        Assert::instanceOf($error, Error::class);
        Assert::same($error->getMessage(), 'bad');
    }

    // todo
    // #[Test]
    // public function unboundDependencyWrapsCannotAutowire(): void
    // {
    //     $caught = null;
    //
    //     try {
    //         Dic::assemble(static fn(Dic $dic) => $dic->object(Consumer::class));
    //     } catch (Error\InvalidConfiguration $error) {
    //         $caught = $error;
    //     }
    //
    //     Assert::notNull($caught);
    //     Assert::instanceOf($caught->getPrevious(), Error\CannotAutowire::class);
    // }

    #[Test]
    public function circularDependencyIsReported(): void
    {
        Expect::exception(Error\CircularDependency::class)
            ->withMessageContaining('Circular dependency detected');

        Dic::assemble(static function (Dic $dic) {
            $service = $dic->object(TestService::class);

            return $service->arg('value', $service);
        });
    }

    #[Test]
    public function singletonDependingOnScopedIsReported(): void
    {
        Expect::exception(Error\SingletonDependsOnScoped::class)
            ->withMessageContaining('cannot depend on non-singleton services');

        Dic::assemble(static function (Dic $dic) {
            $scoped = $dic->object(TestService::class)->scoped();

            return $dic->object(TestService::class)->args([$scoped]);
        });
    }

    #[Test]
    public function nonInstantiableClassRejected(): void
    {
        Expect::exception(Error\InvalidArgument::class)->withMessageContaining('is not instantiable');

        Dic::assemble(static fn(Dic $dic) => $dic->object(Greeter::class));
    }

    #[Test]
    public function factoryRefThatIsNotCallableRejected(): void
    {
        Expect::exception(Error\InvalidArgument::class)->withMessageContaining('is not callable');

        Dic::assemble(
            /** @phpstan-ignore argument.type */
            static fn(Dic $dic) => $dic->object(Counter::class, factory: $dic->value(42)),
        );
    }

    #[Test]
    public function duplicateBindRejected(): void
    {
        Expect::exception(Error\InvalidArgument::class)->withMessageContaining('already bound');

        Dic::assemble(static function (Dic $dic) {
            $dic->object(EnGreeter::class)->bind(objectT(Greeter::class));
            $dic->object(RuGreeter::class)->bind(objectT(Greeter::class));

            return $dic->value(null);
        });
    }

    #[Test]
    public function bindUnsupportedTypeRejected(): void
    {
        Expect::exception(Error\UnsupportedBindingType::class);

        Dic::assemble(static function (Dic $dic) {
            $dic->value(new \ArrayObject())->bind(objectT(\ArrayObject::class, [intT]));

            return $dic->value(null);
        });
    }

    #[Test]
    public function combinedAutowireAndDoNotAutowireRejected(): void
    {
        $caught = null;

        try {
            Dic::assemble(static fn(Dic $dic) => $dic->object(ConflictingConsumer::class));
        } catch (Error\InvalidConfigurationError $error) {
            $caught = $error;
        }

        Assert::notNull($caught);
        Assert::instanceOf($caught->getPrevious(), Error\InvalidArgument::class);
    }

    /**
     * @return Ref<int>
     */
    private static function ref(): Ref
    {
        return new Configuration\ValueConfig(
            builder: new Builder(),
            autowiring: new Autowiring(),
            value: 1,
            declaredAt: Location::caller(),
        );
    }
}
