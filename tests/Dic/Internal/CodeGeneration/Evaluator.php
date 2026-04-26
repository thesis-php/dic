<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\CodeGeneration;

final readonly class Evaluator
{
    /**
     * @return \ReflectionAttribute<*>
     */
    public static function reflectionAttribute(string $attribute): \ReflectionAttribute
    {
        $object = eval("return new {$attribute} class {};");
        \assert(\is_object($object));

        $attributes = new \ReflectionClass($object)->getAttributes();
        \assert(isset($attributes[0]));

        return $attributes[0];
    }

    public static function reflectionParameter(string $parameter): \ReflectionParameter
    {
        $fn = eval("return fn ({$parameter}) => 1;");
        \assert($fn instanceof \Closure);

        return new \ReflectionParameter($fn, 0);
    }

    /**
     * @phpstan-ignore missingType.callable
     */
    public static function closure(string $function): \Closure
    {
        $fn = eval("\$a = 1; return {$function};");
        \assert($fn instanceof \Closure);

        return $fn;
    }

    private function __construct() {}
}
