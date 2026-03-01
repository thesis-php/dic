<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\AutowireableFactory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(Printer::class)]
final class PrinterTest extends TestCase
{
    #[TestWith(['#[X]'])]
    #[TestWith(['#[X("a", new stdClass)]'])]
    #[TestWith(['#[X("a", \Thesis\DIC\singleton)]'])]
    public function testAttribute(string $code): void
    {
        $reflection = $this->reflectAttribute($code);

        $printed = Printer::attribute($reflection);
        $printedReflection = $this->reflectAttribute($printed);

        self::assertSame($reflection->__toString(), $printedReflection->__toString());
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

    #[TestWith(['string $a'])]
    #[TestWith(['?string $a'])]
    #[TestWith(['string &$a'])]
    #[TestWith(['?string $a = null'])]
    #[TestWith(['?string &$a = null'])]
    #[TestWith(['?string $a = "b"'])]
    #[TestWith(['object $o = new stdClass()'])]
    #[TestWith(['object $o = new ArrayObject([1, 2, "a", new stdClass()])'])]
    #[TestWith(['string ...$strings'])]
    public function testParameter(string $code): void
    {
        $reflection = $this->reflectParameter($code);

        $printed = Printer::parameter($reflection);
        $printedReflection = $this->reflectParameter($printed);

        self::assertSame($reflection->__toString(), $printedReflection->__toString());
    }

    private function reflectParameter(string $code): \ReflectionParameter
    {
        $fn = eval("return fn ({$code}) => 1;");
        \assert($fn instanceof \Closure);

        return new \ReflectionParameter($fn, 0);
    }
}
