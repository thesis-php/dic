<?php

declare(strict_types=1);

namespace Thesis\DI;

use Thesis\DI\Internal\Call;
use Thesis\DI\Internal\Construct;
use Thesis\DI\Internal\DefaultArgument;
use Thesis\DI\Internal\Location;
use Thesis\DI\Internal\TaggedList;
use Thesis\DI\Internal\Value;

/**
 * @api
 * @template T
 * @param T $value
 * @return Recipe<T>
 */
function value(mixed $value): Recipe
{
    return new Value($value);
}

/**
 * @api
 * @template TReturn
 * @param callable(never, never, never, never, never): TReturn $factory
 * @return FunctionRecipe<TReturn>
 */
function call(callable $factory): FunctionRecipe
{
    return new Call($factory(...));
}

/**
 * @api
 * @template T of object
 * @param class-string<T> $class
 * @return FunctionRecipe<T>
 */
function construct(string $class): FunctionRecipe
{
    return new Construct($class);
}

/**
 * @api
 * @template TModule of Module
 * @template T
 * @param class-string<TModule> $module
 * @param Id<T> $id
 * @return ModuleId<TModule, T>
 */
function moduleId(string $module, Id $id): ModuleId
{
    return new ModuleId($module, $id);
}

/**
 * @api
 * @template T of object
 * @param class-string<T> $class
 * @return Id<T>
 */
function objectId(string $class): Id
{
    /** @var Id<T> */
    return new Id($class, Location::caller());
}

/**
 * @api
 * @template T
 * @template TTag of Tag<T>
 * @param class-string<TTag> $tag
 * @return Recipe<list<T>>
 */
function taggedList(string $tag): Recipe
{
    return new TaggedList($tag, Location::caller());
}

const defaultArgument = DefaultArgument::Value;
