<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Tests\Fixtures\BuilderBlocks;

use Capell\ContentBlocks\Contracts\FilamentBuilderBlock;
use Filament\Forms\Components\Builder\Block;

abstract class AbstractBuilderBlock implements FilamentBuilderBlock
{
    public static function getBuilderBlockName(): string
    {
        return 'abstract';
    }

    public static function make(): Block
    {
        return Block::make('abstract');
    }
}
