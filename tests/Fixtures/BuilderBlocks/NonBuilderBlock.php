<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Tests\Fixtures\BuilderBlocks;

final class NonBuilderBlock
{
    public static function getBuilderBlockName(): string
    {
        return 'ignored';
    }
}
