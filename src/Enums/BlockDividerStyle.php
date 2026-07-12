<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Enums;

use Filament\Support\Contracts\HasLabel;

enum BlockDividerStyle: string implements HasLabel
{
    case Line = 'line';
    case Dots = 'dots';

    public function getLabel(): string
    {
        return __('capell-block-library::blocks.fields.style_' . $this->value);
    }
}
