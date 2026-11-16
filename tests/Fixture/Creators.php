<?php

declare(strict_types=1);

namespace Thesis\Fixture;

/**
 * Factory methods returning `self`, `static` and `parent`, to check declared factory return types.
 */
final class Creators extends Base
{
    public function __invoke(): Counter
    {
        return new Counter();
    }

    public static function self(): self
    {
        return new self();
    }

    public static function static(): static
    {
        return new self();
    }

    public static function staticOrCounter(): static|Counter // @phpstan-ignore return.unusedType
    {
        return new self();
    }

    /**
     * @return \Closure(): self
     */
    public static function selfClosure(): \Closure
    {
        return static fn(): self => new self();
    }

    public static function parent(): parent
    {
        return new ParentTyped();
    }
}
