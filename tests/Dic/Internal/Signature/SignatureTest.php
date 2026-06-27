<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Signature;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use Thesis\Dic\Internal\Signature;
use Thesis\Fixture\Cache;
use Thesis\Fixture\Consumer;
use Thesis\Fixture\Holder;
use Thesis\Fixture\Numbers;
use Thesis\Fixture\WithDefault;
use function Typhoon\Type\closureT;
use function Typhoon\Type\param;
use const Typhoon\Type\intT;

#[Covers(Signature::class)]
#[Covers(ReflectionFunctionSignature::class)]
#[Covers(ImplicitConstructorSignature::class)]
#[Covers(ReflectionParameter::class)]
#[Covers(ClosureSignature::class)]
#[Covers(ClosureParameter::class)]
final class SignatureTest
{
    #[Test]
    public function constructorParametersAreReflected(): void
    {
        $info = Signature::ofConstructor(new \ReflectionClass(Consumer::class));
        $parameters = $info->parameters;

        Assert::count($parameters, 1);
        \assert($parameters !== []);

        $parameter = $parameters[0];

        Assert::same($parameter->name, 'cache');
        Assert::false($parameter->isVariadic);

        Assert::same($parameter->bindingType->string, strtolower(Cache::class));
    }

    #[Test]
    public function classWithoutConstructorHasNoParameters(): void
    {
        $info = Signature::ofConstructor(new \ReflectionClass(Holder::class));

        Assert::same($info->parameters, []);
    }

    #[Test]
    public function variadicParameterIsDetected(): void
    {
        $info = Signature::ofConstructor(new \ReflectionClass(Numbers::class));
        $parameters = $info->parameters;
        \assert($parameters !== []);

        Assert::true($parameters[0]->isVariadic);

        $variadicParameter = $info->variadicParameter;
        Assert::notNull($variadicParameter);
        Assert::same($variadicParameter->name, 'numbers');
    }

    #[Test]
    public function defaultValueIsReflected(): void
    {
        $info = Signature::ofConstructor(new \ReflectionClass(WithDefault::class));
        $parameters = $info->parameters;
        \assert($parameters !== []);

        $parameter = $parameters[0];

        Assert::true($parameter->hasDefaultValue);

        $defaultValue = $parameter->defaultValue;
        Assert::notNull($defaultValue);
        Assert::same($defaultValue->create(), 42);
    }

    #[Test]
    public function findParameterByNameAndPosition(): void
    {
        $info = Signature::ofConstructor(new \ReflectionClass(Consumer::class));

        Assert::notNull($info->findParameter('cache'));
        Assert::same($info->findParameter(0), $info->findParameter('cache'));
        Assert::null($info->findParameter('missing'));
        Assert::null($info->findParameter(5));
    }

    #[Test]
    public function ofCallableReflectsClosureParameters(): void
    {
        $info = Signature::ofCallable(static fn(int $a, string $b) => null);
        $parameters = $info->parameters;

        Assert::count($parameters, 2);
        \assert(isset($parameters[0], $parameters[1]));

        Assert::same($parameters[0]->name, 'a');
        Assert::same($parameters[1]->name, 'b');
    }

    #[Test]
    public function ofSignatureReflectsTyphoonSignature(): void
    {
        $info = Signature::ofClosure(closureT([param(intT, name: 'x')], intT));
        $parameters = $info->parameters;

        Assert::count($parameters, 1);
        \assert($parameters !== []);

        Assert::same($parameters[0]->name, 'x');
    }
}
