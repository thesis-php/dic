<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal;

use Thesis\DIC\Tag;
use Thesis\DIC\TaggedValue;

/**
 * @internal
 */
final class TaggedValues
{
    /**
     * @var list<TaggedValue<*, *>>
     */
    public private(set) array $list = [];

    /**
     * @template T
     * @template TTag of Tag<T>
     * @param T $value
     * @param TTag $tag
     */
    public function register(mixed $value, Tag $tag): void
    {
        $this->list[] = new TaggedValue($value, $tag);
    }

    public function registerFromAttributes(mixed $value): void
    {
        if (\is_object($value)) {
            $reflection = new \ReflectionClass($value);

            foreach (self::reflectTags($reflection) as $tag) {
                $this->register($value, $tag);
            }

            foreach ($reflection->getMethods() as $method) {
                foreach (self::reflectTags($method) as $tag) {
                    if ($method->isStatic()) {
                        $this->register(($value::class)::{$method->name}(...), $tag); // @phpstan-ignore staticMethod.dynamicName
                    } else {
                        $this->register($value->{$method->name}(...), $tag); // @phpstan-ignore method.dynamicName
                    }
                }
            }

            return;
        }

        if (\is_callable($value)) {
            foreach (self::reflectTags(new \ReflectionFunction($value(...))) as $tag) {
                $this->register($value, $tag);
            }

            return;
        }
    }

    /**
     * @param \ReflectionClass<*>|\ReflectionFunctionAbstract $reflection
     * @return \Generator<int, Tag<mixed>>
     */
    private static function reflectTags(\ReflectionClass|\ReflectionFunctionAbstract $reflection): \Generator
    {
        foreach ($reflection->getAttributes(Tag::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            yield $attribute->newInstance();
        }
    }
}
