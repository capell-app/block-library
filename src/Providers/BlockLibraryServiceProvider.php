<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Providers;

use Capell\Admin\Contracts\Widgets\FilamentWidget;
use Capell\Admin\Facades\CapellAdmin;
use Capell\BlockLibrary\Actions\RegisterBlockDefinitionProviderAction;
use Capell\BlockLibrary\Actions\SanitizeBlockHtmlAction;
use Capell\BlockLibrary\Contracts\BlockDefinitionProvider;
use Capell\BlockLibrary\Enums\BuilderBlockTarget;
use Capell\BlockLibrary\Support\BlockRegistry;
use Capell\BlockLibrary\Support\BuilderBlockDiscovery;
use Capell\BlockLibrary\Support\BuilderBlockRegistry;
use Capell\BlockLibrary\Support\DefaultBlockDefinitionProvider;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Illuminate\Support\Facades\Blade;
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

        $this->app->tag([DefaultBlockDefinitionProvider::class], BlockDefinitionProvider::TAG);

        $this->callAfterResolving(BlockRegistry::class, function (BlockRegistry $registry): void {
            foreach ($this->app->tagged(BlockDefinitionProvider::TAG) as $provider) {
                if (! $provider instanceof BlockDefinitionProvider) {
                    continue;
                }

                RegisterBlockDefinitionProviderAction::run($registry, $provider);
            }
        });

        $this->callAfterResolving(BuilderBlockDiscovery::class, function (BuilderBlockDiscovery $discovery): void {
            $discovery->registerDiscoverableBlocks(
                __DIR__ . '/../Filament/BuilderBlocks',
                'Capell\\BlockLibrary\\Filament\\BuilderBlocks',
            );
        });
    }

    public function packageBooted(): void
    {
        $blockDiscovery = resolve(BuilderBlockDiscovery::class);
        $blockDiscovery->filamentBlocks();

        foreach (resolve(BuilderBlockRegistry::class)->allForTarget(BuilderBlockTarget::AdminFilament) as $blockClass) {
            if (is_a($blockClass, FilamentWidget::class, true)) {
                CapellAdmin::registerWidget($blockClass);
            }
        }

        Blade::directive(
            'safeBlockHtml',
            static fn (string $expression): string => sprintf('<?php echo \\%s::run(%s); ?>', SanitizeBlockHtmlAction::class, $expression),
        );

        Blade::anonymousComponentPath(__DIR__ . '/../../resources/views', 'capell-block-library');
    }
}
