<?php

declare(strict_types=1);

namespace Thesis\Dic;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use Thesis\Dic\Internal\Dependency;
use Thesis\Dic\Internal\Signature;
use Thesis\Fixture\CounterList;
use function Thesis\Fixture\ref;

#[Test]
#[Covers(BuildError::class)]
final class BuildErrorTest
{
    public function cannotAutowireMessage(): void
    {
        $parameter = Signature::ofCallable(static fn(int $value) => null)->findParameter(0);
        Assert::notNull($parameter);

        $error = BuildError::cannotAutowireNoCandidate($parameter);

        Assert::same($error->getMessage(), \sprintf('Cannot autowire "%s": no autowiring candidate found', $parameter));
    }

    public function configurationFrozenMessage(): void
    {
        $error = BuildError::configurationFrozen();

        Assert::same(
            $error->getMessage(),
            'Cannot configure the container: configuration is frozen once it starts building',
        );
    }

    public function invalidServiceFactoryWrapsPreviousWithRefAndDetail(): void
    {
        $ref = ref();
        $previous = BuildError::configurationFrozen();
        $error = BuildError::invalidServiceFactory($ref, $previous);

        Assert::same($error->getPrevious(), $previous);
        Assert::same($error->getMessage(), \sprintf('Invalid factory for %s: %s', $ref, lcfirst($previous->getMessage())));
    }

    public function unregisteredDependencyRendersTheEdge(): void
    {
        $ref = ref();
        $foreign = ref();

        $error = BuildError::unregisteredDependency($ref, Dependency::of($foreign)->arg('logger'));

        Assert::same($error->getMessage(), <<<TEXT
            {$ref} depends on a ref that is not registered in this container:

              {$ref}
              └─ \$logger → {$foreign}  ← not registered

            A ref belongs to the build that declared it and cannot be reused in another one.
            TEXT);
    }

    public function unregisteredDependencyWithEmptyPathRendersJustTheNode(): void
    {
        $ref = ref();
        $foreign = ref();

        $error = BuildError::unregisteredDependency($ref, Dependency::of($foreign));

        Assert::same($error->getMessage(), <<<TEXT
            {$ref} depends on a ref that is not registered in this container:

              {$ref}
              └─ {$foreign}  ← not registered

            A ref belongs to the build that declared it and cannot be reused in another one.
            TEXT);
    }

    public function lazyClassInternalMessages(): void
    {
        $internal = new \ReflectionClass(\ArrayObject::class);
        $extending = new \ReflectionClass(CounterList::class);

        Assert::same(
            BuildError::lazyClassInternal($internal, $internal)->getMessage(),
            'Class "ArrayObject" cannot be made lazy because it is an internal class',
        );
        Assert::same(
            BuildError::lazyClassInternal($extending, $internal)->getMessage(),
            'Class "Thesis\Fixture\CounterList" cannot be made lazy because it extends the internal class "ArrayObject"',
        );
    }

    public function calledMethodNotFoundMessage(): void
    {
        $error = BuildError::calledMethodNotFound(new \ReflectionClass(CounterList::class), 'missing');

        Assert::same(
            $error->getMessage(),
            'Method "Thesis\Fixture\CounterList::missing()" does not exist and cannot be called on the service',
        );
    }

    public function fileAndLinePointAtFactoryCallSite(): void
    {
        $line = __LINE__ + 1;
        $error = BuildError::configurationFrozen();

        Assert::same($error->getFile(), __FILE__);
        Assert::same($error->getLine(), $line);
    }

    public function fileAndLinePointAtCallSiteThroughDelegatingFactory(): void
    {
        $parameter = Signature::ofCallable(static fn(int $value) => null)->findParameter(0);
        Assert::notNull($parameter);

        // cannotAutowireNoCandidate() delegates through the private cannotAutowire()
        // helper, so there is an extra in-file frame between the call and `new self`.
        $line = __LINE__ + 1;
        $error = BuildError::cannotAutowireNoCandidate($parameter);

        Assert::same($error->getFile(), __FILE__);
        Assert::same($error->getLine(), $line);
    }
}
