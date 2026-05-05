<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Actions;

use Capell\ContentBlocks\Contracts\ContentBlockDefinitionProvider;
use Capell\ContentBlocks\Support\ContentBlockRegistry;
use Lorisleiva\Actions\Concerns\AsObject;

class RegisterContentBlockDefinitionProviderAction
{
    use AsObject;

    public function handle(ContentBlockRegistry $registry, ContentBlockDefinitionProvider $provider): void
    {
        foreach ($provider->definitions() as $definition) {
            $registry->register($definition);
        }
    }
}
