<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Builder;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Test;
use Thesis\Dic\BuildError;
use Thesis\Dic\TaggedRefs;
use Thesis\Fixture\GreeterTag;
use Thesis\Fixture\RuGreeter;
use function Thesis\Fixture\ref;

#[Covers(Tags::class)]
final class TagsTest
{
    #[Test]
    public function onResolveListenerReceivesCollectedTags(): void
    {
        $tags = new Tags();
        $tags->add(ref(new RuGreeter()), new GreeterTag());

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

        Expect::exception(BuildError::class);

        $tags->add(ref(new RuGreeter()), new GreeterTag());
    }
}
