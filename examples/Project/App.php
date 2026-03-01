<?php

declare(strict_types=1);

namespace Project;

use Amp\Postgres\PostgresConfig;
use Amp\Postgres\PostgresConnectionPool;
use Project\Thesis\HttpServerModule\Direct;
use Project\Thesis\HttpServerModule\Module as HttpServerModule;
use Project\Thesis\HttpServerModule\Server;
use Project\Thesis\LoggerModule\LoggerFactory;
use Project\Thesis\ORMModule\Module as ORMModule;
use Thesis\DIC;
use Thesis\DIC\Ref;
use function Thesis\Transaction\delegate;
use function Typhoon\Type\objectT;

final readonly class App
{
    /**
     * @return Ref<Server>
     */
    public function __invoke(DIC $dic): Ref
    {
        $dic->require(new Authentication\Module());

        $postgres = $dic
            ->object(PostgresConnectionPool::class)
            ->args([
                new PostgresConfig(
                    host: 'localhost',
                    user: 'thesis',
                    password: 'thesis',
                    database: 'thesis',
                ),
            ]);

        $dic->bind(objectT(PostgresConnectionPool::class), $postgres);

        $beginTx = $dic
            /** @phpstan-ignore argument.type */
            ->factory(static fn(PostgresConnectionPool $pg) => static fn() => delegate($pg->beginTransaction()))
            ->arg(0, $postgres);

        $dic->require(new ORMModule($beginTx));

        return $dic->require(
            new HttpServerModule(
                mode: new Direct(),
                logger: $dic->factory(LoggerFactory::stdOut(...)),
            ),
        );
    }
}
