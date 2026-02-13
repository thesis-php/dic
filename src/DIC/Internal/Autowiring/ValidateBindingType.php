<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\Autowiring;

use Typhoon\Type;
use Typhoon\Type\ArrayBareT;
use Typhoon\Type\BoolT;
use Typhoon\Type\CallableBareT;
use Typhoon\Type\FalseT;
use Typhoon\Type\FloatT;
use Typhoon\Type\IntT;
use Typhoon\Type\IterableBareT;
use Typhoon\Type\MixedT;
use Typhoon\Type\NamedObjectT;
use Typhoon\Type\NullT;
use Typhoon\Type\ObjectT;
use Typhoon\Type\StringT;
use Typhoon\Type\TrueT;
use Typhoon\Type\Visitor\Fallback;

/**
 * @internal
 *
 * @extends Fallback<bool>
 */
final class ValidateBindingType extends Fallback
{
    #[\Override]
    public function nullT(NullT $type): mixed
    {
        return true;
    }

    #[\Override]
    public function falseT(FalseT $type): mixed
    {
        return true;
    }

    #[\Override]
    public function trueT(TrueT $type): mixed
    {
        return true;
    }

    #[\Override]
    public function boolT(BoolT $type): mixed
    {
        return true;
    }

    #[\Override]
    public function intT(IntT $type): mixed
    {
        return true;
    }

    #[\Override]
    public function floatT(FloatT $type): mixed
    {
        return true;
    }

    #[\Override]
    public function stringT(StringT $type): mixed
    {
        return true;
    }

    #[\Override]
    public function arrayBareT(ArrayBareT $type): mixed
    {
        return true;
    }

    #[\Override]
    public function objectT(ObjectT $type): mixed
    {
        return true;
    }

    #[\Override]
    public function namedObjectT(NamedObjectT $type): mixed
    {
        return $type->templateArguments === [];
    }

    #[\Override]
    public function iterableBareT(IterableBareT $type): mixed
    {
        return true;
    }

    #[\Override]
    public function callableBareT(CallableBareT $type): mixed
    {
        return true;
    }

    #[\Override]
    public function mixedT(MixedT $type): mixed
    {
        return true;
    }

    protected function fallback(Type $type): mixed
    {
        return false;
    }
}
