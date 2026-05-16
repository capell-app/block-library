<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Enums;

enum BlockBackground: string
{
    case Default = 'default';
    case Muted = 'muted';
    case Dark = 'dark';
    case Image = 'image';
}
