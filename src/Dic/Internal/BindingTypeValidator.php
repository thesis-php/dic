<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\Exception\BindingTypeNotSupported;
use Typhoon\Type;
use Typhoon\Type\Visitor\Fallback;

/**
 * @internal
 *
 * @extends Fallback<bool>
 */
final class BindingTypeValidator extends Fallback
{
    public static function validate(Type $type): void
    {
        /** @var self */
        static $validator = new self();

        if (!$type->accept($validator)) {
            throw new BindingTypeNotSupported($type);
        }
    }

    private function __construct() {}

    #[\Override]
    public function nullT(Type\NullT $type): mixed
    {
        return true;
    }

    #[\Override]
    public function falseT(Type\FalseT $type): mixed
    {
        return true;
    }

    #[\Override]
    public function trueT(Type\TrueT $type): mixed
    {
        return true;
    }

    #[\Override]
    public function boolT(Type\BoolT $type): mixed
    {
        return true;
    }

    #[\Override]
    public function intT(Type\IntT $type): mixed
    {
        return true;
    }

    #[\Override]
    public function floatT(Type\FloatT $type): mixed
    {
        return true;
    }

    #[\Override]
    public function stringT(Type\StringT $type): mixed
    {
        return true;
    }

    #[\Override]
    public function arrayBareT(Type\ArrayBareT $type): mixed
    {
        return true;
    }

    #[\Override]
    public function objectT(Type\ObjectT $type): mixed
    {
        return true;
    }

    #[\Override]
    public function namedObjectT(Type\NamedObjectT $type): mixed
    {
        return $type->templateArguments === [];
    }

    #[\Override]
    public function iterableBareT(Type\IterableBareT $type): mixed
    {
        return true;
    }

    #[\Override]
    public function callableBareT(Type\CallableBareT $type): mixed
    {
        return true;
    }

    #[\Override]
    public function mixedT(Type\MixedT $type): mixed
    {
        return true;
    }

    protected function fallback(Type $type): bool
    {
        return false;
    }
}
