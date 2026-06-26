<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Builder;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Test;
use Thesis\Dic\Error;
use Thesis\Dic\TaggedRefs;
use Thesis\Fixture\Greeter;
use Thesis\Fixture\GreeterTag;
use function Thesis\Fixture\ref;
use function Typhoon\Type\objectT;

#[Covers(Tags::class)]
final class TagsTest
{
    #[Test]
    public function onResolveListenerReceivesCollectedTags(): void
    {
        $tags = new Tags();
        $tags->add(ref(objectT(Greeter::class)), new GreeterTag());

        $found = null;
        $tags->onResolution(static function (TaggedRefs $taggedRefs) use (&$found): void {
            $found = $taggedRefs->find(GreeterTag::class);
        });

        $tags->resolve();

        Assert::notNull($found);
        Assert::count($found, 1);
    }

    #[Test]
    public function addingTagAfterResolveIsRejected(): void
    {
        $tags = new Tags();
        $tags->resolve();

        Expect::exception(Error::class);

        $tags->add(ref(objectT(Greeter::class)), new GreeterTag());
    }
}
