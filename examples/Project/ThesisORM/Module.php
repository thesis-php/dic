<?php

declare(strict_types=1);

namespace Project\ThesisORM;

use Thesis\DIC;
use Thesis\DIC\Ref;
use Thesis\ORM\EntityManager;

/**
 * @template TTransaction of object
 */
final readonly class Module
{
    /**
     * @param Ref<callable(): TTransaction> $transactionFactory
     */
    public function __construct(
        private Ref $transactionFactory,
    ) {}

    /**
     * @return Ref<EntityManager<TTransaction>>
     */
    public function __invoke(DIC $dic): Ref
    {
        /** @phpstan-ignore return.type */
        return $dic
            ->object(EntityManager::class)
            ->args([$this->transactionFactory]);
    }
}
