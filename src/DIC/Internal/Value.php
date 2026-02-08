<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal;

use Thesis\DIC\Tag;
use Thesis\DIC\ValueConfigurator;

/**
 * @internal
 *
 * @template T
 * @extends Resolvable<T, Value\Data<T>>
 * @implements ValueConfigurator<T>
 */
final class Value extends Resolvable implements ValueConfigurator
{
    /**
     * @param T $value
     */
    public function __construct(mixed $value)
    {
        parent::__construct(new Value\Data($value));
    }

    public function bindAs(string $class): static
    {
        // todo check class
        $this->data->bindings[] = $class;

        return $this;
    }

    public function tag(Tag $tag): static
    {
        $this->data->tags[] = $tag;

        return $this;
    }

    public function register(Autowiring $autowiring, Tags $tags): void
    {
        $autowiring->register($this, $this->data->bindings);
        $tags->register($this, $this->data->tags);
    }

    protected function toFactory(Autowiring $autowiring, Tags $tags): \Closure
    {
        $value = $this->data->value;

        return static fn() => $value;
    }
}
