<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\Container;

use Thesis\DIC\Internal\Tagger;
use Thesis\DIC\Tags;

/**
 * @internal
 */
final class Dispatcher implements Subscriber
{
    /**
     * @var array<non-negative-int, callable(Tags): void>
     */
    private array $onResolveTags = [];

    public function onResolveTags(callable $listener): void
    {
        $this->onResolveTags[] = $listener;
    }

    public function resolveTags(Tagger $tagger): void
    {
        foreach ($this->onResolveTags as $key => $listener) {
            $listener($tagger);
            unset($this->onResolveTags[$key]);
        }
    }

    /**
     * @var array<non-negative-int, callable(ServiceRegistrar): void>
     */
    private array $onBeforeAssemble = [];

    public function onBeforeAssemble(callable $listener): void
    {
        $this->onBeforeAssemble[] = $listener;
    }

    public function beforeAssemble(ServiceRegistrar $registrar): void
    {
        foreach ($this->onBeforeAssemble as $key => $listener) {
            $listener($registrar);
            unset($this->onBeforeAssemble[$key]);
        }
    }

    /**
     * @var array<non-negative-int, callable(): void>
     */
    private array $onAfterAssemble = [];

    public function onAfterAssemble(callable $listener): void
    {
        $this->onAfterAssemble[] = $listener;
    }

    public function afterAssemble(): void
    {
        foreach ($this->onAfterAssemble as $key => $listener) {
            $listener();
            unset($this->onAfterAssemble[$key]);
        }
    }
}
