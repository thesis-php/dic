<?php

declare(strict_types=1);

namespace Thesis\Dic;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use Thesis\Dic\Internal\Signature;
use function Thesis\Fixture\ref;

#[Covers(Error::class)]
final class ErrorTest
{
    #[Test]
    public function cannotAutowireMessage(): void
    {
        $parameter = Signature::ofCallable(static fn(int $value) => null)->findParameter(0);
        Assert::notNull($parameter);

        $error = Error::cannotAutowireNoCandidate($parameter);

        Assert::same($error->getMessage(), \sprintf('Cannot autowire "%s": no autowiring candidate found', $parameter));
    }

    #[Test]
    public function configurationFrozenMessage(): void
    {
        $error = Error::configurationFrozen();

        Assert::same(
            $error->getMessage(),
            'Cannot configure the container: configuration is frozen once it starts building',
        );
    }

    #[Test]
    public function invalidServiceFactoryWrapsPreviousWithRefAndDetail(): void
    {
        $ref = ref();
        $previous = Error::configurationFrozen();
        $error = Error::invalidServiceFactory($ref, $previous);

        Assert::same($error->getPrevious(), $previous);
        Assert::same($error->getMessage(), \sprintf('Invalid factory for %s: %s', $ref, lcfirst($previous->getMessage())));
    }

    #[Test]
    public function fileAndLinePointAtFactoryCallSite(): void
    {
        $line = __LINE__ + 1;
        $error = Error::configurationFrozen();

        Assert::same($error->getFile(), __FILE__);
        Assert::same($error->getLine(), $line);
    }

    #[Test]
    public function fileAndLinePointAtCallSiteThroughDelegatingFactory(): void
    {
        $parameter = Signature::ofCallable(static fn(int $value) => null)->findParameter(0);
        Assert::notNull($parameter);

        // cannotAutowireNoCandidate() delegates through the private cannotAutowire()
        // helper, so there is an extra in-file frame between the call and `new self`.
        $line = __LINE__ + 1;
        $error = Error::cannotAutowireNoCandidate($parameter);

        Assert::same($error->getFile(), __FILE__);
        Assert::same($error->getLine(), $line);
    }
}
