<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\AutowireableFactory;

use function Typhoon\Formatter\formatReflectedParameter;

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
        return \sprintf('#[%s]', self::attributeWithoutBrackets($attribute));
    }

    /**
     * @param list<\ReflectionAttribute<*>> $attributes
     * @return ($attributes is array{} ? '' : non-empty-string)
     */
    public static function attributeGroup(array $attributes): string
    {
        if ($attributes === []) {
            return '';
        }

        return \sprintf('#[%s]', implode(', ', array_map(self::attributeWithoutBrackets(...), $attributes)));
    }

    /**
     * @param \ReflectionAttribute<*> $attribute
     * @return non-empty-string
     */
    private static function attributeWithoutBrackets(\ReflectionAttribute $attribute): string
    {
        preg_match_all('/Argument #\d+ \[ (.+) ]/', (string) $attribute, $matches);

        return \sprintf('%s(%s)', $attribute->name, implode(', ', $matches[1]));
    }

    /**
     * @return non-empty-string
     */
    public static function parameter(\ReflectionParameter $parameter): string
    {
        if (preg_match('/^Parameter #\d+ \[ <(?:required|optional)> (.+) ]$/', (string) $parameter, $matches) !== 1) {
            throw new \LogicException(\sprintf('Failed to parse parameter `%s`', formatReflectedParameter($parameter)));
        }

        return $matches[1];
    }

    /**
     * @return ($type is null ? '' : non-empty-string)
     */
    public static function type(?\ReflectionType $type): string
    {
        /** @phpstan-ignore match.unhandled */
        return match (true) {
            $type === null => '',
            $type instanceof \ReflectionNamedType => self::namedType($type),
            $type instanceof \ReflectionIntersectionType => implode('&', array_map(
                self::type(...),
                $type->getTypes(),
            )),
            $type instanceof \ReflectionUnionType => implode('|', array_map(
                self::type(...),
                $type->getTypes(),
            )),
        };
    }

    /**
     * @return non-empty-string
     */
    private static function namedType(\ReflectionNamedType $type): string
    {
        $name = $type->getName();
        \assert($name !== '');

        if ($type->allowsNull()) {
            return match (strtolower($name)) {
                'void', 'null', 'mixed' => $name,
                default => '?' . $name,
            };
        }

        return $name;
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
            $code .= self::attribute($attribute) . "\n";
        }

        if ($static) {
            $code .= 'static ';
        }

        $code .= \sprintf('function(%s)', implode('', array_map(self::parameter(...), $parameters)));

        if ($usedVariables !== []) {
            $code .= \sprintf(' use(%s)', implode(', ', array_map(self::usedVariable(...), $usedVariables)));
        }

        if ($returnType !== null) {
            $code .= ': ' . self::type($returnType);
        }

        if ($body === '') {
            return $code . ' {}';
        }

        return "{$code} {\n    {$body}\n}";
    }

    /**
     * @return non-empty-string
     */
    private static function usedVariable(UsedVariable $var): string
    {
        return \sprintf('%s$%s', $var->byReference ? '&' : '', $var->name);
    }
}
