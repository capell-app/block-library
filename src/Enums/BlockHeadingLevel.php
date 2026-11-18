<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Enums;

use Filament\Support\Contracts\HasLabel;

enum BlockHeadingLevel: string implements HasLabel
{
    case One = 'h1';
    case Two = 'h2';
    case Three = 'h3';
    case Four = 'h4';
    case Five = 'h5';
    case Six = 'h6';

    public function getLabel(): string
    {
        return __('capell-block-library::blocks.fields.heading_level_' . $this->value);
    }
}
