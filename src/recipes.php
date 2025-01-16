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
 * @template TValue
 * @param TValue $value
 * @return Recipe<TValue>
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
 * @template TValue of object
 * @param class-string<TValue> $class
 * @return FunctionRecipe<TValue>
 */
function construct(string $class): FunctionRecipe
{
    return new Construct($class);
}

/**
 * @api
 * @template TModule of Module
 * @template TValue
 * @param class-string<TModule> $module
 * @param Id<TValue> $id
 * @return ModuleId<TModule, TValue>
 */
function moduleId(string $module, Id $id): ModuleId
{
    return new ModuleId($module, $id);
}

/**
 * @api
 * @template TValue of object
 * @param class-string<TValue> $class
 * @return Id<TValue>
 */
function objectId(string $class): Id
{
    /** @var Id<TValue> */
    return new Id($class, Location::caller());
}

/**
 * @api
 * @template TValue
 * @template TTag of Tag<TValue>
 * @param class-string<TTag> $tag
 * @return Recipe<list<TValue>>
 */
function taggedList(string $tag): Recipe
{
    return new TaggedList($tag, Location::caller());
}

const defaultArgument = DefaultArgument::Value;
