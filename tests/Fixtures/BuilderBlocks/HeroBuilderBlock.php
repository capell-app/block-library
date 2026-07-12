<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Tests\Fixtures\BuilderBlocks;

use Capell\BlockLibrary\Contracts\FilamentBuilderBlock;
use Filament\Forms\Components\Builder\Block;

final class HeroBuilderBlock implements FilamentBuilderBlock
{
    public static function getBuilderBlockName(): string
    {
        return 'hero';
    }

    public static function make(): Block
    {
        return Block::make('hero');
    }
}
