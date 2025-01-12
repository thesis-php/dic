<?php

declare(strict_types=1);

namespace Thesis\DI\Internal;

use Thesis\DI\Id;
use Thesis\DI\Module;

/**
 * @internal
 * @psalm-internal Thesis\DI
 */
final class InvalidConfig extends \LogicException
{
    /**
     * @param non-empty-string $id
     */
    public static function invalidId(string $id, Location $location): self
    {
        return new self(
            message: \sprintf('Definition id "%s" is invalid', $id),
            location: $location,
        );
    }

    /**
     * @param class-string<Module> $module
     */
    public static function moduleIsAlreadyRequired(string $module, Location $location): self
    {
        return new self(
            message: \sprintf('Module %s is already required', $module),
            location: $location,
        );
    }

    /**
     * @param class-string<Module> $module
     */
    public static function moduleClassMustBeFinal(string $module, Location $location): self
    {
        return new self(
            message: \sprintf('Module class %s must be final', $module),
            location: $location,
        );
    }

    public static function classDoesNotExist(string $class, Location $location, ?\Throwable $previous = null): self
    {
        return new self(
            message: \sprintf('Class "%s" does not exist', $class),
            location: $location,
            previous: $previous,
        );
    }

    /**
     * @param class-string<Module> $module
     */
    public static function idNotRegistered(string $module, Id $id, Location $location): self
    {
        return new self(
            message: \sprintf('Value with id "%s" is not available in module %s', $id->toString(), $module),
            location: $location,
        );
    }

    /**
     * @param class-string $class
     */
    public static function classNotInstantiable(string $class, Location $location): self
    {
        return new self(
            message: \sprintf('Class %s is not instantiable', $class),
            location: $location,
        );
    }

    /**
     * @param class-string $class
     */
    public static function classDoesNotHaveConstructor(string $class, Location $location): self
    {
        return new self(
            message: \sprintf('%s does not have a constructor', $class),
            location: $location,
        );
    }

    /**
     * @param non-empty-list<array-key> $names
     */
    public static function functionDoesNotHaveParameters(
        \ReflectionFunctionAbstract $function,
        array $names,
        Location $location,
    ): self {
        $single = \count($names) === 1;

        return new self(
            message: \sprintf(
                '%s does not have parameter%s %s',
                ReflectionStringifier::stringifySymbol($function),
                $single ? '' : 's',
                implode(', ', array_map(
                    static fn(int|string $name): string => \is_int($name) ? (string) $name : '$' . $name,
                    $names,
                )),
            ),
            location: $location,
        );
    }

    public static function argumentConfiguredTwice(
        \ReflectionFunctionAbstract $function,
        int $index,
        string $name,
        Location $location,
    ): self {
        return new self(
            message: \sprintf(
                'Argument %s for %s is configured twice: via index %d and via name %1$s',
                $name,
                ReflectionStringifier::stringifySymbol($function),
                $index,
            ),
            location: $location,
        );
    }

    public static function requiredArgumentMissing(
        \ReflectionFunctionAbstract $function,
        string $parameter,
        Location $location,
    ): self {
        return new self(
            message: \sprintf(
                'Argument missing for required parameter $%s of %s',
                $parameter,
                ReflectionStringifier::stringifySymbol($function),
            ),
            location: $location,
        );
    }

    public static function cannotAutowire(
        \ReflectionFunctionAbstract $function,
        string $parameter,
        ?\ReflectionType $type,
        Location $location,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot autowire parameter %s$%s of %s',
                $type === null ? '' : ReflectionStringifier::stringifyType($type) . ' ',
                $parameter,
                ReflectionStringifier::stringifySymbol($function),
            ),
            location: $location,
        );
    }

    public static function cannotInferClassFromType(null|string|\ReflectionType $type, Location $location): self
    {
        return new self(
            message: \sprintf('Cannot infer type from type %s', match (true) {
                $type === null => '',
                \is_string($type) => $type,
                default => ReflectionStringifier::stringifyType($type),
            }),
            location: $location,
        );
    }

    private function __construct(string $message, Location $location, ?\Throwable $previous = null)
    {
        parent::__construct($message, previous: $previous);
        $this->file = $location->file;
        $this->line = $location->line;
    }
}
