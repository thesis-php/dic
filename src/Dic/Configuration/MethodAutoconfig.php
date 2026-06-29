<?php

declare(strict_types=1);

namespace Thesis\Dic\Configuration;

/**
 * @api
 */
final readonly class MethodAutoconfig
{
    public Attributes $attributes;

    /**
     * @param ObjectConfig<object> $object
     */
    public function __construct(
        private ObjectConfig $object,
        public \ReflectionMethod $reflection,
    ) {
        $this->attributes = new Attributes($reflection);
    }

    public function autoconfigure(): void
    {
        $this->object->method($this->reflection->name);
    }
}
