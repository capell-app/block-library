<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Tests\Fixtures\BuilderBlocks;

use Filament\Forms\Components\Builder\Block;

final class LegacyBuilderBlock
{
    public static function getBlockName(): string
    {
        return 'legacy';
    }

    public static function make(): Block
    {
        return Block::make('legacy');
    }
}
