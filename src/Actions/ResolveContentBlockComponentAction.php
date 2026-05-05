<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Actions;

use Capell\ContentBlocks\Support\ContentBlockRegistry;
use Lorisleiva\Actions\Concerns\AsObject;

class ResolveContentBlockComponentAction
{
    use AsObject;

    public function handle(?string $configurator, string $fallbackComponent): string
    {
        if (blank($configurator)) {
            return $fallbackComponent;
        }

        $definition = resolve(ContentBlockRegistry::class)->getByConfigurator($configurator);

        return $definition?->component ?? $fallbackComponent;
    }
}
