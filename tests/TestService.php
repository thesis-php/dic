<?php

declare(strict_types=1);

namespace Thesis;

final class TestService
{
    public static function new(): self
    {
        return new self(__METHOD__);
    }

    public function __construct(
        public readonly string $factory = __METHOD__,
        public private(set) mixed $value = null,
    ) {}

    public function set(mixed $value): void
    {
        $this->value = $value;
    }

    public function with(mixed $value): self
    {
        return new self(
            factory: $this->factory,
            value: $value,
        );
    }

    public function setAll(int ...$values): void
    {
        $this->value = array_values($values);
    }

    public function withAll(int ...$values): self
    {
        return new self(
            factory: $this->factory,
            value: array_values($values),
        );
    }
}
