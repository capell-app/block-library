<?php

declare(strict_types=1);

use Capell\BlockLibrary\Providers\BlockLibraryServiceProvider;
use Capell\BlockLibrary\Support\BlockRegistry;
use Capell\BlockLibrary\Support\BuilderBlockDiscovery;
use Capell\BlockLibrary\Support\DefaultBlockCatalog;
use Capell\Core\Actions\DisablePackageAction;
use Capell\Core\Actions\EnablePackageAction;
use Capell\Core\Actions\InstallPackageAction;
use Capell\Core\Actions\RuntimeRefresh\RefreshInstalledPackageRuntimeAction;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\InstalledRuntimeLifecycle;
use Capell\Tests\Support\InstalledRuntimeParity;
use Capell\Tests\Support\InstalledRuntimeState;
use Capell\Tests\Support\InstalledRuntimeTestCase;
use Capell\Tests\Support\PackageInstallationSurfaceSnapshot;
use Capell\Tests\Support\PackageInstallationTestCase;
use Illuminate\Foundation\Application;
use Illuminate\View\Compilers\BladeCompiler;

it('preserves installed surfaces after repeated provider registration', function (): void {
    PackageInstallationTestCase::assertInProcessInstallation('block-library', function (Application $app, Closure $refresh): void {
        $refresh();
        $installed = PackageInstallationSurfaceSnapshot::capture($app);

        for ($registration = 0; $registration < 2; $registration++) {
            $provider = new BlockLibraryServiceProvider($app);
            $provider->register();
            $provider->callBootedCallbacks();
            $app->make(InstalledRuntimeLifecycle::class)->refresh(BlockLibraryServiceProvider::$packageName);
            $actual = PackageInstallationSurfaceSnapshot::capture($app);
            foreach ($installed as $surface => $expected) {
                expect($actual[$surface])->toBe($expected, $surface);
            }
        }
    });
});

it('preserves installed surfaces through disable and enable cycles', function (): void {
    InstalledRuntimeState::run(static function (): void {
        $app = InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('block-library'), true, sourceRoot: dirname(__DIR__, 2));
        $installed = PackageInstallationSurfaceSnapshot::capture($app);
        $package = CapellCore::getPackage(BlockLibraryServiceProvider::$packageName);

        for ($cycle = 0; $cycle < 2; $cycle++) {
            DisablePackageAction::run($package);
            expect(CapellCore::isPackageEnabled($package->name))->toBeFalse();
            $app->make(InstalledRuntimeLifecycle::class)->refresh($package->name);
            EnablePackageAction::run($package);
            expect(CapellCore::isPackageEnabled($package->name))->toBeTrue();
            $actual = PackageInstallationSurfaceSnapshot::capture($app);
            foreach ($installed as $surface => $expected) {
                expect($actual[$surface])->toBe($expected, $surface);
            }
        }
    });
});

it('populates pre-resolved block registries and Blade directives during late installation', function (): void {
    InstalledRuntimeState::run(static function (): void {
        $app = InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('block-library'), false, sourceRoot: dirname(__DIR__, 2));
        $package = CapellCore::getPackage(BlockLibraryServiceProvider::$packageName);
        $registry = $app->make(BlockRegistry::class);
        $discovery = $app->make(BuilderBlockDiscovery::class);
        $blade = $app->make(BladeCompiler::class);
        expect($registry->all())->toBe([])
            ->and($discovery->filamentBlocks())->toBe([])
            ->and($blade->getCustomDirectives())->not->toHaveKey('safeBlockHtml');

        InstalledRuntimeTestCase::prepareLateInstallMigrations($app);
        InstallPackageAction::run($package);
        expect($app->make(BlockRegistry::class))->toBe($registry)
            ->and($app->make(BuilderBlockDiscovery::class))->toBe($discovery)
            ->and(array_keys($registry->all()))->toBe(DefaultBlockCatalog::keys())
            ->and($discovery->filamentBlocks())->toHaveCount(count(DefaultBlockCatalog::keys()))
            ->and($blade->compileString('@safeBlockHtml($html)'))->toContain('SanitizeBlockHtmlAction::run($html)');

        $registered = $registry->all();
        RefreshInstalledPackageRuntimeAction::run($package);
        expect($registry->all())->toBe($registered);
    });
});
