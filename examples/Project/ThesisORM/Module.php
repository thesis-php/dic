<?php

declare(strict_types=1);

namespace Project\ThesisORM;

use Thesis\DIC;
use Thesis\DIC\Reference;
use Thesis\ORM\EntityManager;

/**
 * @template TTransaction of object
 */
final readonly class Module
{
    /**
     * @param Reference<callable(): TTransaction> $transactionFactory
     */
    public function __construct(
        private Reference $transactionFactory,
    ) {}

    /**
     * @return Reference<EntityManager<TTransaction>>
     */
    public function __invoke(DIC $dic): Reference
    {
        /** @phpstan-ignore return.type */
        return $dic
            ->object(EntityManager::class)
            ->args([$this->transactionFactory]);
    }
}
