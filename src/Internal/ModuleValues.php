<?php

declare(strict_types=1);

namespace Thesis\DI\Internal;

use Thesis\DI\Id;

/**
 * @internal
 */
final class ModuleValues
{
    public static function create(): self
    {
        return new self([]);
    }

    /**
     * @param array<non-empty-string, mixed> $values
     */
    private function __construct(
        private array $values,
    ) {}

    public function isEmpty(): bool
    {
        return $this->values === [];
    }

    public function has(Id $id): bool
    {
        return \array_key_exists($id->toString(), $this->values);
    }

    public function get(Id $id): mixed
    {
        $idString = $id->toString();

        if (!\array_key_exists($id->toString(), $this->values)) {
            throw new \RuntimeException(\sprintf('No value with id "%s"', $idString));
        }

        return $this->values[$idString];
    }

    public function with(Id $id, mixed $value): self
    {
        $values = $this->values;
        $values[$id->toString()] = $value;

        return new self($values);
    }

    public function remove(Id $id): void
    {
        unset($this->values[$id->toString()]);
    }
}
