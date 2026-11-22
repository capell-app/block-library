<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Providers;

use Capell\Admin\Contracts\Widgets\BlockPickerMetadataProvider;
use Capell\Admin\Contracts\Widgets\FilamentWidget;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Components\Forms\Editor\ContentBuilder;
use Capell\Admin\Filament\Widgets\ContentFilamentWidget;
use Capell\Admin\Support\Widgets\WidgetDiscovery;
use Capell\BlockLibrary\Actions\PruneUnreferencedBuilderBlockMediaAction;
use Capell\BlockLibrary\Actions\RegisterBlockDefinitionProviderAction;
use Capell\BlockLibrary\Actions\SanitizeBlockHtmlAction;
use Capell\BlockLibrary\Actions\SeparateClonedBuilderBlockMediaAction;
use Capell\BlockLibrary\Contracts\BlockDefinitionProvider;
use Capell\BlockLibrary\Enums\BuilderBlockTarget;
use Capell\BlockLibrary\Support\BlockPickerMetadataAdapter;
use Capell\BlockLibrary\Support\BlockRegistry;
use Capell\BlockLibrary\Support\BuilderBlockDiscovery;
use Capell\BlockLibrary\Support\BuilderBlockRegistry;
use Capell\BlockLibrary\Support\DefaultBlockDefinitionProvider;
use Capell\BlockLibrary\View\Components\BuilderBlock;
use Capell\Core\Models\Translation;
use Capell\Core\Support\Extensions\ExtensionContributionReceiptRegistry;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\FileViewFinder;
use Override;
use Spatie\LaravelPackageTools\Package;

final class BlockLibraryServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-block-library';

    public static string $packageName = 'capell-app/block-library';

    #[Override]
    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasTranslations()
            ->hasViews(self::$name);
    }

    #[Override]
    public function packageRegistered(): void
    {
        $this->app->singleton(BlockRegistry::class);
        $this->app->singleton(BuilderBlockRegistry::class);
        $this->app->singleton(BuilderBlockDiscovery::class);
    }

    /**
     * `view:cache` compiles this package's registered views whether or not it is
     * installed, so the Blade vocabulary they use never depends on install state.
     */
    #[Override]
    public function packageBooted(): void
    {
        Blade::directive(
            'safeBlockHtml',
            static fn (string $expression): string => sprintf('<?php echo \\%s::run(%s); ?>', SanitizeBlockHtmlAction::class, $expression),
        );
        Blade::component(BuilderBlock::class, 'capell-block-library::builder-block');
        $this->registerAnonymousComponentPathOnce(__DIR__ . '/../../resources/views', 'capell-block-library');
    }

    #[Override]
    protected function bootInstalledRuntime(): void
    {
        ContentBuilder::configureUsing(static function (ContentBuilder $component): void {
            $component->afterStateUpdated(static function (ContentBuilder $component): void {
                SeparateClonedBuilderBlockMediaAction::run($component);
            });
            // Saved content is the only safe source of truth for which media is still used. A save removes only
            // the collections it dropped, so another editor's unsaved clone keeps its media.
            $component->saveRelationshipsUsing(static function (ContentBuilder $component): void {
                $owner = $component->getRecord();
                if ($owner instanceof Translation) {
                    PruneUnreferencedBuilderBlockMediaAction::run($owner, PruneUnreferencedBuilderBlockMediaAction::takeSavedReferences($owner));
                }
            });
        });
        Translation::updating(static function (Translation $translation): void {
            if ($translation->isDirty('content')) {
                PruneUnreferencedBuilderBlockMediaAction::rememberSavedReferences($translation);
            }
        });
        $receipts = $this->app->make(ExtensionContributionReceiptRegistry::class);
        // Builder blocks are admin contributions, so the receipts need the admin context as well as the runtime one.
        $contexts = [...$receipts->activeContexts(), ...$receipts->providerContexts(self::class)];

        // Catalog definitions need booted translation namespaces in both installation paths.
        $this->app->booted(function () use ($receipts, $contexts): void {
            $receipts->withContexts($contexts, function (): void {
                $this->app->tag([DefaultBlockDefinitionProvider::class], BlockDefinitionProvider::TAG);
                $this->app->tag([BlockPickerMetadataAdapter::class], BlockPickerMetadataProvider::TAG);

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

                $blockDiscovery = resolve(BuilderBlockDiscovery::class);
                $blockDiscovery->filamentBlocks();

                // Admin owns the content alias; retain the catalog block registration receipt below.
                resolve(WidgetDiscovery::class)->registerAuthoritative(ContentFilamentWidget::class);

                foreach (resolve(BuilderBlockRegistry::class)->allForTarget(BuilderBlockTarget::AdminFilament) as $blockClass) {
                    if (! is_a($blockClass, FilamentWidget::class, true)) {
                        continue;
                    }

                    // A block from another package reaches the editor here but is that package's contribution:
                    // it records its own builder-block receipt, so this package never claims it.
                    if (str_starts_with($blockClass, 'Capell\\BlockLibrary\\')) {
                        CapellAdmin::registerBuilderBlock($blockClass);

                        continue;
                    }

                    resolve(WidgetDiscovery::class)->register($blockClass);
                }

            });
        });
    }

    /**
     * A repeated boot must not append the path again. Blade keeps the path and the
     * view finder keeps its hashed namespace; either can be rebuilt without the other.
     */
    private function registerAnonymousComponentPathOnce(string $path, string $prefix): void
    {
        foreach (Blade::getAnonymousComponentPaths() as $registered) {
            if ($registered['path'] !== $path || $registered['prefix'] !== $prefix) {
                continue;
            }

            $finder = $this->app->make(ViewFactory::class)->getFinder();
            $hints = $finder instanceof FileViewFinder ? $finder->getHints() : [];
            $canonicalPath = realpath($path) ?: $path;

            if (! in_array($canonicalPath, $hints[$registered['prefixHash']] ?? [], true)) {
                $finder->addNamespace($registered['prefixHash'], $path);
            }

            return;
        }

        Blade::anonymousComponentPath($path, $prefix);
    }
}
