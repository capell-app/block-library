<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Tests\Fixtures\BuilderBlocks;

use Capell\BlockLibrary\Contracts\FilamentBuilderBlock;
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
