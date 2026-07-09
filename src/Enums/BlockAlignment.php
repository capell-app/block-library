<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Enums;

use Filament\Support\Contracts\HasLabel;

enum BlockAlignment: string implements HasLabel
{
    case Start = 'start';
    case Center = 'center';
    case End = 'end';

    public function getLabel(): string
    {
        return __('capell-block-library::blocks.fields.align_' . $this->value);
    }
}
