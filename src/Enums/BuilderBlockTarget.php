<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Enums;

enum BuilderBlockTarget: string
{
    case AdminFilament = 'admin.filament';
    case FrontendBlade = 'frontend.blade';
    case FrontendLivewire = 'frontend.livewire';
}
