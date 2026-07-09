<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Enums;

use Filament\Support\Contracts\HasLabel;

enum BlockColumnCount: string implements HasLabel
{
    case Two = '2';
    case Three = '3';
    case Four = '4';

    public function getLabel(): string
    {
        return __('capell-block-library::blocks.fields.columns_' . $this->value);
    }
}
