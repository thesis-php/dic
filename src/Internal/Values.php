<?php

declare(strict_types=1);

namespace Thesis\DI\Internal;

use Thesis\DI\Module;
use Thesis\DI\ModuleId;

/**
 * @internal
 * @psalm-internal Thesis\DI
 */
final readonly class Values
{
    public static function create(): self
    {
        return new self([]);
    }

    /**
     * @param array<class-string<Module>, ModuleValues> $values
     */
    private function __construct(
        private array $values,
    ) {}

    /**
     * @param class-string<Module> $module
     */
    public function hasModule(string $module): bool
    {
        return isset($this->values[$module]);
    }

    public function get(ModuleId $id): mixed
    {
        if (!isset($this->values[$id->module])) {
            throw new \RuntimeException(\sprintf('No values for module %s', $id->module));
        }

        return $this->values[$id->module]->get($id->id);
    }

    /**
     * @param class-string<Module> $module
     */
    public function with(string $module, ModuleValues $values): self
    {
        $allValues = $this->values;
        $allValues[$module] = $values;

        return new self($allValues);
    }

    public function remove(ModuleId $id): void
    {
        if (isset($this->values[$id->module])) {
            $this->values[$id->module]->remove($id->id);
        }
    }
}
