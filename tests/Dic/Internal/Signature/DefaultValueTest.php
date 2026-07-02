<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Signature;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(ReflectionDefaultValue::class)]
#[Covers(ClosureDefaultValue::class)]
final class DefaultValueTest
{
    public function reflectionDefaultValueCreatesScalar(): void
    {
        $defaultValue = new ReflectionDefaultValue(self::parameter(static fn(int $x = 42) => null));

        Assert::same($defaultValue->create(), 42);
        Assert::same($defaultValue->print(), '42');
    }

    public function reflectionDefaultValuePrintsString(): void
    {
        $defaultValue = new ReflectionDefaultValue(self::parameter(static fn(string $x = 'hi') => null));

        Assert::same($defaultValue->create(), 'hi');
        Assert::same($defaultValue->print(), "'hi'");
    }

    public function reflectionDefaultValuePrintsNull(): void
    {
        $defaultValue = new ReflectionDefaultValue(self::parameter(static fn(?int $x = null) => null));

        Assert::null($defaultValue->create());
        Assert::same($defaultValue->print(), 'NULL');
    }

    public function signatureDefaultValueCreatesItself(): void
    {
        $defaultValue = ClosureDefaultValue::Value;

        Assert::same($defaultValue->create(), $defaultValue);
        Assert::same($defaultValue->print(), ClosureDefaultValue::class . '::Value');
    }

    private static function parameter(callable $function): \ReflectionParameter
    {
        $parameters = new \ReflectionFunction($function(...))->getParameters();
        \assert(isset($parameters[0]));

        return $parameters[0];
    }
}
