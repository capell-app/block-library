<?php

declare(strict_types=1);

use Capell\Admin\Filament\Widgets\ContentFilamentWidget;
use Capell\Admin\Support\Widgets\WidgetDiscovery;
use Capell\BlockLibrary\Enums\BuilderBlockTarget;
use Capell\BlockLibrary\Filament\BuilderBlocks\ContentBlock;
use Capell\BlockLibrary\Providers\BlockLibraryServiceProvider;
use Capell\BlockLibrary\Support\BlockRegistry;
use Capell\BlockLibrary\Support\BuilderBlockDiscovery;
use Capell\BlockLibrary\Support\BuilderBlockRegistry;
use Capell\BlockLibrary\Support\DefaultBlockCatalog;
use Capell\Core\Actions\InstallPackageAction;
use Capell\Core\Actions\RuntimeRefresh\RefreshInstalledPackageRuntimeAction;
use Capell\Core\Data\Extensions\ExtensionContributionReceiptData;
use Capell\Core\Enums\ExtensionContributionType;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Extensions\ExtensionContributionReceiptRegistry;
use Capell\Core\Testing\Contracts\InstalledRuntimeContract;
use Capell\Tests\Support\InstalledRuntimeParity;
use Capell\Tests\Support\InstalledRuntimeState;
use Capell\Tests\Support\InstalledRuntimeTestCase;
use Illuminate\Foundation\Application;
use Illuminate\View\Compilers\BladeCompiler;

it('matches fresh installed boot after in-process installation and repeated refresh', function (): void {
    InstalledRuntimeParity::assertPackage('block-library');
});

it('preserves fresh installed surfaces after real installation and repeated refresh', function (): void {
    InstalledRuntimeState::run(static function (): void {
        InstalledRuntimeContract::assertParity(
            provider: BlockLibraryServiceProvider::class,
            boot: static fn (bool $installed): Application => InstalledRuntimeTestCase::boot(
                InstalledRuntimeParity::manifest('block-library'),
                $installed,
                sourceRoot: dirname(__DIR__, 3),
            ),
            snapshot: static function (Application $app): array {
                $app->make(BuilderBlockDiscovery::class)->filamentBlocks();
                $widgets = array_filter($app->make(WidgetDiscovery::class)->registeredWidgets(), static fn (string $widget): bool => str_starts_with($widget, 'Capell\\BlockLibrary\\'));
                ksort($widgets);

                return [
                    'definitions' => serialize($app->make(BlockRegistry::class)->all()),
                    'builderBlocks' => $app->make(BuilderBlockRegistry::class)->allForTarget(BuilderBlockTarget::AdminFilament),
                    'widgets' => $widgets,
                    'receipts' => array_map(
                        static fn (ExtensionContributionReceiptData $receipt): array => $receipt->toArray(),
                        $app->make(ExtensionContributionReceiptRegistry::class)->forPackage(BlockLibraryServiceProvider::$packageName),
                    ),
                    'contentWidget' => $app->make(WidgetDiscovery::class)->registeredWidgets()['content'] ?? null,
                    'safeHtml' => $app->make(BladeCompiler::class)->compileString('@safeBlockHtml($html)'),
                    'anonymousComponents' => $app->make(BladeCompiler::class)->getAnonymousComponentPaths(),
                ];
            },
            install: static function (Application $app): void {
                InstalledRuntimeTestCase::prepareLateInstallMigrations($app);
                InstallPackageAction::run(CapellCore::getPackage(BlockLibraryServiceProvider::$packageName));
            },
            refresh: static function (): void {
                $package = CapellCore::getPackage(BlockLibraryServiceProvider::$packageName);
                RefreshInstalledPackageRuntimeAction::run($package);
                RefreshInstalledPackageRuntimeAction::run($package);
            },
            assertAbsent: static function (Application $app): void {
                expect($app->make(BlockRegistry::class)->all())->toBe([]);
            },
            assertDeclared: static function (Application $app): void {
                $contentReceipts = array_values(array_filter(
                    $app->make(ExtensionContributionReceiptRegistry::class)->forPackage(BlockLibraryServiceProvider::$packageName),
                    static fn (ExtensionContributionReceiptData $receipt): bool => $receipt->type === ExtensionContributionType::DashboardFilamentWidget
                        && $receipt->key === 'widget:' . ContentBlock::class,
                ));

                expect($contentReceipts)->toHaveCount(1)
                    ->and($contentReceipts[0]->implementation)->toBe(ContentBlock::class)
                    ->and(array_keys($app->make(BlockRegistry::class)->all()))->toBe(DefaultBlockCatalog::keys())
                    ->and($app->make(WidgetDiscovery::class)->registeredWidgets()['content'] ?? null)->toBe(ContentFilamentWidget::class);
            },
        );
    });
});
