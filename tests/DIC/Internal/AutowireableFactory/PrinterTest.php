<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\AutowireableFactory;

use Testo\Assert;
use Testo\Data\DataSet;
use Testo\Test;

final readonly class PrinterTest
{
    #[Test]
    #[DataSet(['#[X]'])]
    #[DataSet(['#[X("a", new stdClass)]'])]
    #[DataSet(['#[X("a", \Thesis\DIC\singleton)]'])]
    public function testAttribute(string $code): void
    {
        $reflection = $this->reflectAttribute($code);

        $printed = Printer::attribute($reflection);
        $printedReflection = $this->reflectAttribute($printed);

        Assert::same($printedReflection->__toString(), $reflection->__toString());
    }

    /**
     * @return \ReflectionAttribute<*>
     */
    private function reflectAttribute(string $code): \ReflectionAttribute
    {
        $object = eval("return new {$code} class {};");
        \assert(\is_object($object));

        $attributes = new \ReflectionClass($object)->getAttributes();
        \assert(isset($attributes[0]));

        return $attributes[0];
    }

    #[Test]
    #[DataSet(['string $a'])]
    #[DataSet(['?string $a'])]
    #[DataSet(['string &$a'])]
    #[DataSet(['?string $a = null'])]
    #[DataSet(['?string &$a = null'])]
    #[DataSet(['?string $a = "b"'])]
    #[DataSet(['object $o = new stdClass()'])]
    #[DataSet(['object $o = new ArrayObject([1, 2, "a", new stdClass()])'])]
    #[DataSet(['string ...$strings'])]
    public function testParameter(string $code): void
    {
        $reflection = $this->reflectParameter($code);

        $printed = Printer::parameter($reflection);
        $printedReflection = $this->reflectParameter($printed);

        Assert::same($printedReflection->__toString(), $reflection->__toString());
    }

    private function reflectParameter(string $code): \ReflectionParameter
    {
        $fn = eval("return fn ({$code}) => 1;");
        \assert($fn instanceof \Closure);

        return new \ReflectionParameter($fn, 0);
    }
}
