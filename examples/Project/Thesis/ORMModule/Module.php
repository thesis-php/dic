<?php

declare(strict_types=1);

namespace Project\Thesis\ORMModule;

use Project\Thesis\HttpServerModule\AsMiddleware;
use Thesis\DIC;
use Thesis\DIC\Ref;
use Thesis\ORM\EntityManager;
use Thesis\Transaction;

/**
 * @template TTransaction of Transaction
 */
final readonly class Module
{
    /**
     * @param Ref<callable(): TTransaction> $transactionFactory
     */
    public function __construct(
        private Ref $transactionFactory,
        private float $middlewarePriority = 0,
    ) {}

    /**
     * @return Ref<EntityManager<TTransaction>>
     */
    public function __invoke(DIC $dic): Ref
    {
        $em = $dic
            ->object(EntityManager::class)
            ->args([$this->transactionFactory]);

        $dic->object(ORMMiddleware::class)
            ->args([$em])
            ->tag(new AsMiddleware($this->middlewarePriority));

        /** @phpstan-ignore return.type */
        return $em;
    }
}
