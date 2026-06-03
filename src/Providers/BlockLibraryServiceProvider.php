<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Providers;

use Capell\BlockLibrary\Actions\RegisterBlockDefinitionProviderAction;
use Capell\BlockLibrary\Contracts\BlockDefinitionProvider;
use Capell\BlockLibrary\Support\BlockRegistry;
use Capell\BlockLibrary\Support\BuilderBlockDiscovery;
use Capell\BlockLibrary\Support\BuilderBlockRegistry;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

final class BlockLibraryServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-block-library';

    public static string $packageName = 'capell-app/block-library';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasTranslations()
            ->hasViews(self::$name);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(BlockRegistry::class);
        $this->app->singleton(BuilderBlockRegistry::class);
        $this->app->singleton(BuilderBlockDiscovery::class);

        $this->callAfterResolving(BlockRegistry::class, function (BlockRegistry $registry): void {
            foreach ($this->app->tagged(BlockDefinitionProvider::TAG) as $provider) {
                if (! $provider instanceof BlockDefinitionProvider) {
                    continue;
                }

                RegisterBlockDefinitionProviderAction::run($registry, $provider);
            }
        });
    }
}
