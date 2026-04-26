<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\CodeGeneration;

use function Thesis\Formatter\formatReflectedParameter;

/**
 * @internal
 */
final readonly class Printer
{
    /**
     * @param \ReflectionAttribute<*> $attribute
     * @return non-empty-string
     */
    public static function attribute(\ReflectionAttribute $attribute): string
    {
        $string = (string) $attribute;

        $header = "Attribute [ {$attribute->name} ]";

        if (!str_starts_with($string, $header)) {
            throw new \LogicException(\sprintf(
                'Failed to parse attribute `%s`: unexpected reflection string `%s`',
                $attribute->name,
                $attribute,
            ));
        }

        $string = substr($string, \strlen($header));

        if ($string === "\n") {
            return "#[{$attribute->name}]";
        }

        if (preg_match("/^ {\n  +- Arguments \\[(\\d+)]/", $string, $matches) !== 1) {
            throw new \LogicException(\sprintf(
                'Failed to parse attribute `%s`: unexpected reflection string `%s`',
                $attribute->name,
                $attribute,
            ));
        }

        $expectedNumber = (int) $matches[1];

        if (preg_match_all('/^ {4}Argument #\d+ \[ (.+) ]/m', $string, $matches) !== $expectedNumber) {
            throw new \LogicException(\sprintf(
                'Failed to parse attribute `%s`: unexpected reflection string `%s`',
                $attribute->name,
                $attribute,
            ));
        }

        return \sprintf('#[%s(%s)]', $attribute->name, implode(', ', $matches[1]));
    }

    /**
     * @return non-empty-string
     */
    public static function parameter(\ReflectionParameter $parameter): string
    {
        if (preg_match('/^Parameter #\d+ \[ <\w+> (.+) ]$/', (string) $parameter, $matches) !== 1) {
            throw new \LogicException(\sprintf(
                'Failed to parse parameter `%s`: unexpected reflection string `%s`',
                formatReflectedParameter($parameter),
                $parameter,
            ));
        }

        return $matches[1];
    }

    /**
     * @param list<\ReflectionAttribute<*>> $attributes
     * @param list<\ReflectionParameter> $parameters
     * @param list<UsedVariable> $usedVariables
     * @return non-empty-string
     */
    public static function closure(
        array $attributes = [],
        bool $static = true,
        array $parameters = [],
        array $usedVariables = [],
        ?\ReflectionType $returnType = null,
        string $body = '',
    ): string {
        $code = '';

        foreach ($attributes as $attribute) {
            $code .= self::attribute($attribute) . ' ';
        }

        if ($static) {
            $code .= 'static ';
        }

        $code .= \sprintf('function(%s)', implode(', ', array_map(self::parameter(...), $parameters)));

        if ($usedVariables !== []) {
            $code .= \sprintf(' use(%s)', implode(', ', array_map(self::usedVariable(...), $usedVariables)));
        }

        if ($returnType !== null) {
            $code .= ': ' . $returnType;
        }

        if ($body === '') {
            return $code . ' {}';
        }

        return "{$code} { {$body} }";
    }

    /**
     * @return non-empty-string
     */
    private static function usedVariable(UsedVariable $var): string
    {
        return \sprintf('%s$%s', $var->byReference ? '&' : '', $var->name);
    }
}
