<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\Ref;

/**
 * A single dependency edge: the target service ($ref) and a human-readable path
 * describing where it sits in the depending service ($path), e.g. `$value[0]`,
 * `()`, `->setLogger $logger`. An empty path means the ref is the value itself.
 *
 * The builder methods prepend a segment, so an enclosing factory wraps the
 * accessor produced by a nested one.
 *
 * @internal
 */
final readonly class Dependency
{
    /**
     * The ref itself is the value — a direct service reference.
     *
     * @param Ref<mixed> $ref
     */
    public static function of(Ref $ref): self
    {
        return new self($ref);
    }

    /**
     * The ref is invoked to produce the value — a factory service or a signature function.
     *
     * @param Ref<mixed> $ref
     */
    public static function factory(Ref $ref): self
    {
        return new self($ref, 'factory');
    }

    /**
     * @param Ref<mixed> $ref
     */
    private function __construct(
        public Ref $ref,
        public string $path = '',
    ) {}

    public function arg(string $name): self
    {
        return new self($this->ref, "\${$name}{$this->path}");
    }

    public function key(int|string $key): self
    {
        return new self($this->ref, "[{$key}]{$this->path}");
    }

    public function method(string $method): self
    {
        return new self($this->ref, "->{$method}({$this->path})");
    }
}
