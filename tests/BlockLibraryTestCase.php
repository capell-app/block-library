<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Tests;

use Capell\ContentBlocks\Providers\ContentBlocksServiceProvider;
use Capell\Tests\AbstractTestCase;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use Override;

abstract class BlockLibraryTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-content-blocks';
    }

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            ContentBlocksServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }
}
