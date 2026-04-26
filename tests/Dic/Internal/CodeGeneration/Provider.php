<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\CodeGeneration;

/**
 * @template T
 */
final readonly class Provider
{
    /**
     * @param list<string> $snippets
     * @param callable(string): T $reflector
     */
    public function __construct(
        private array $snippets,
        private mixed $reflector,
    ) {}

    /**
     * @return \Generator<string, array{T}>
     */
    public function __invoke(): \Generator
    {
        foreach ($this->snippets as $snippet) {
            yield $snippet => [($this->reflector)($snippet)];
        }
    }
}
