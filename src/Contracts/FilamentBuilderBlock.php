<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Contracts;

use Filament\Forms\Components\Builder\Block;

interface FilamentBuilderBlock
{
    public static function getBuilderBlockName(): string;

    public static function make(): Block;
}
