<?php

declare(strict_types=1);

namespace Thesis\DI;

use Thesis\DI\Internal\Location;

/**
 * @api
 */
final class ValueIsNotAvailable extends \RuntimeException
{
    /**
     * @internal
     * @param class-string<Module<*>> $module
     */
    public static function moduleIsNotRequired(string $module, Location $location): self
    {
        return new self(
            \sprintf('Module "%s" is not required in the container', $module),
            $location,
        );
    }

    /**
     * @internal
     */
    public static function idIsNotExported(ModuleId $id, Location $location): self
    {
        return new self(
            \sprintf('Id "%s" is not exported in module "%s"', $id->id->toString(), $id->module),
            $location,
        );
    }

    /**
     * @internal
     */
    public static function idIsNotDefined(ModuleId $id, Location $location): self
    {
        return new self(
            \sprintf('Id "%s" is not defined in module "%s"', $id->id->toString(), $id->module),
            $location,
        );
    }

    private function __construct(string $message, Location $location)
    {
        parent::__construct($message);
        $this->file = $location->file;
        $this->line = $location->line;
    }
}
