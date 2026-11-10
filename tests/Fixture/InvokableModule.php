<?php

declare(strict_types=1);

namespace Thesis\Fixture;

use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

/**
 * A module that is also callable: the root entry points must call configure(), not __invoke().
 *
 * @implements Module<Ref<string>>
 */
final readonly class InvokableModule implements Module
{
    public function configure(Dic $dic): mixed
    {
        return $dic->value('configure');
    }

    /**
     * @return Ref<string>
     */
    public function __invoke(Dic $dic): Ref
    {
        return $dic->value('__invoke');
    }
}
