<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Tests;

use Capell\BlockLibrary\Providers\BlockLibraryServiceProvider;
use Capell\Tests\AbstractTestCase;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Livewire\LivewireServiceProvider;
use Override;

abstract class BlockLibraryTestCase extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Blade::anonymousComponentPath(dirname(__DIR__) . '/resources/views', 'capell-block-library');
    }

    protected function getPackageServiceName(): string
    {
        return 'capell-block-library';
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
            BlockLibraryServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }
}
