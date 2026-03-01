<?php

declare(strict_types=1);

namespace Project\Thesis\LoggerModule;

use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use function Amp\ByteStream\getStdout;

final readonly class LoggerFactory
{
    public static function stdOut(string $name = 'app'): LoggerInterface
    {
        $handler = new StreamHandler(getStdout());
        $handler->setFormatter(new ConsoleFormatter());

        return new Logger($name, [$handler]);
    }
}
