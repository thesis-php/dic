<?php

declare(strict_types=1);

namespace Project\Authentication;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Postgres\PostgresLink;
use Project\Thesis\HttpServerModule\Route;
use Thesis\DIC\Mapping\Scoped;
use const Project\Thesis\HttpServerModule\GET;

#[Scoped]
final readonly class Authenticate
{
    public function __construct(
        private PostgresLink $link,
    ) {}

    #[Route(GET, '/authenticate')]
    public function __invoke(PostgresLink $link, Request $request): Response
    {
        /** @var array{id: string} */
        $objectTxId = $this->link
            ->query('SELECT pg_current_xact_id() as id')
            ->fetchRow();

        /** @var array{id: string} */
        $methodTxId = $link
            ->query('SELECT pg_current_xact_id() as id')
            ->fetchRow();

        return new Response(
            body: <<<TXT
                Method: {$request->getMethod()}
                Path: {$request->getUri()->getPath()}
                Query: {$request->getUri()->getQuery()}
                object tx id: {$objectTxId['id']}
                method tx id: {$methodTxId['id']}
                TXT,
        );
    }
}
