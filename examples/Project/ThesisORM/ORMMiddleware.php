<?php

declare(strict_types=1);

namespace Project\ThesisORM;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Project\HttpServer\Middleware;
use Project\HttpServer\Pipeline;
use Thesis\ORM\EntityManager;
use Thesis\ORM\UnitOfWork;

/**
 * @template TTransaction of object
 */
final readonly class ORMMiddleware implements Middleware
{
    /**
     * @param EntityManager<TTransaction> $entityManager
     */
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function handleRequest(Request $request, Pipeline $pipeline): Response
    {
        return $this->entityManager->inTransaction(
            static fn(UnitOfWork $unitOfWork) => $pipeline
                ->with($unitOfWork)
                ->with($unitOfWork->transaction)
                ->handleRequest($request),
        );
    }
}
