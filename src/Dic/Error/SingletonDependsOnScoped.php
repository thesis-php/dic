<?php

declare(strict_types=1);

namespace Thesis\Dic\Error;

use Thesis\Dic\Error;
use Thesis\Dic\Internal\Dependency;
use Thesis\Dic\Internal\Lifetime;
use Thesis\Dic\Ref;

/**
 * @api
 */
final class SingletonDependsOnScoped extends Error
{
    /**
     * @internal
     *
     * @param Ref<mixed> $singleton
     * @param non-empty-list<array{Dependency, Lifetime}> $dependencies [dependency, configured lifetime]
     */
    public function __construct(Ref $singleton, array $dependencies)
    {
        $lastIndex = array_key_last($dependencies);

        $lines = ["  {$singleton}"];

        foreach ($dependencies as $index => [$dependency, $lifetime]) {
            $branch = $index === $lastIndex ? '└─' : '├─';
            $edge = $dependency->path === '' ? '' : "{$dependency->path} → ";
            $lines[] = "  {$branch} {$edge}{$dependency->ref}  ← {$lifetime->name}";
        }

        parent::__construct(\sprintf(
            <<<'TEXT'
                Singleton %s cannot depend on non-singleton services:

                %s

                Make %1$s scoped (or canBeScoped()), or make these dependencies singletons.
                TEXT,
            $singleton,
            implode("\n", $lines),
        ));
    }
}
