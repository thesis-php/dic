<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\CodeGeneration;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Data\DataSet;
use Testo\Test;

#[Covers(Printer::class)]
final readonly class PrinterTest
{
    /**
     * @param \ReflectionAttribute<*> $attribute
     */
    #[Test]
    #[DataProvider(new Provider([
        '#[X]',
        '#[X("a", new X)]',
        '#[X("a", CONSTANT_X)]',
    ], [Evaluator::class, 'reflectionAttribute']))]
    public function testAttribute(\ReflectionAttribute $attribute): void
    {
        $printed = Printer::attribute($attribute);

        Assert::same(
            (string) Evaluator::reflectionAttribute($printed),
            (string) $attribute,
        );
    }

    #[Test]
    #[DataProvider(new Provider([
        'string $a',
        '?string $a',
        'string &$a',
        '?string $a = null',
        '?string &$a = null',
        '?string $a = "b"',
        'object $o = new stdClass()',
        'object $o = new ArrayObject([1, 2, "a", new stdClass()])',
        'string ...$strings',
    ], [Evaluator::class, 'reflectionParameter']))]
    public function testParameter(\ReflectionParameter $parameter): void
    {
        $printed = Printer::parameter($parameter);

        Assert::same(
            (string) Evaluator::reflectionParameter($printed),
            (string) $parameter,
        );
    }

    /**
     * @phpstan-ignore missingType.callable
     */
    #[Test]
    #[DataProvider(new Provider([
        'fn () => 1',
        'static fn () => 1',
        'fn (string $a) => 1',
        'fn (string $a, int $b) => 1',
        'fn (string $a, int ...$b) => 1',
        'fn (string $a, int $b = 1) => 1',
        'fn (): string => 1',
        'fn (): ?int => 1',
        'fn (): int|string => 1',
        'fn (): Throwable&Countable => 1', '#[A] fn () => 1',
        'function () use ($a): void {}',
        'function () use (&$a): void {}',
        'fn (#[X] string $a) => 1',
        'fn (#[X(1, X)] string $a, #[Y(1, Y)] string $b) => 1',
    ], [Evaluator::class, 'closure']))]
    public function closure(\Closure $closure): void
    {
        $reflection = new \ReflectionFunction($closure);
        $usedVars = $reflection->getClosureUsedVariables();

        $printed = Printer::closure(
            attributes: $reflection->getAttributes(),
            static: $reflection->isStatic(),
            parameters: $reflection->getParameters(),
            usedVariables: array_map(
                static fn(string $name) => new UsedVariable(
                    /** @phpstan-ignore argument.type */
                    name: $name,
                    byReference: \ReflectionReference::fromArrayElement($usedVars, $name) !== null,
                ),
                array_keys($reflection->getClosureUsedVariables()),
            ),
            returnType: $reflection->getReturnType(),
        );

        Assert::same(
            (string) new \ReflectionFunction(Evaluator::closure($printed)),
            (string) $reflection,
        );
    }

    #[Test]
    #[DataSet(['', 'static function() {}'])]
    #[DataSet(['echo 1;', 'static function() { echo 1; }'])]
    public function closureBody(string $body, string $expected): void
    {
        $printed = Printer::closure(body: $body);

        Assert::same($printed, $expected);
    }
}
