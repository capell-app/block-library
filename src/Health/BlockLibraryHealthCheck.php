<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class BlockLibraryHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
