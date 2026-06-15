<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Actions;

use Capell\BlockLibrary\Data\BlockDefinitionData;
use Capell\BlockLibrary\Health\BlockLibraryHealthCheck;
use Capell\BlockLibrary\Support\BlockRegistry;
use Capell\BlockLibrary\Support\BuilderBlockDiscovery;
use Capell\BlockLibrary\Support\DefaultBlockCatalog;
use Capell\Core\Data\Diagnostics\DoctorCheckResultData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\View;
use Lorisleiva\Actions\Concerns\AsObject;
use Throwable;

final class ValidateDefaultBlockCatalogAction
{
    use AsObject;

    private const string PACKAGE_NAME = 'capell-app/block-library';

    /**
     * @return Collection<int, DoctorCheckResultData>
     */
    public function handle(): Collection
    {
        return collect([
            $this->checkRegistry(),
            $this->checkCatalogDefinitions(),
            $this->checkTranslations(),
            $this->checkViews(),
            $this->checkBuilderBlocks(),
            $this->checkScreenshots(),
            $this->checkManifest(),
        ]);
    }

    private function checkRegistry(): DoctorCheckResultData
    {
        try {
            $registry = resolve(BlockRegistry::class);
        } catch (Throwable $throwable) {
            return new DoctorCheckResultData(
                label: 'Block Library registry binding',
                passed: false,
                message: 'Unable to resolve the content block registry.',
                remediation: $throwable->getMessage(),
            );
        }

        return new DoctorCheckResultData(
            label: 'Block Library registry binding',
            passed: true,
            message: sprintf('Content block registry is resolvable with %d registered block definition(s).', count($registry->all())),
        );
    }

    private function checkCatalogDefinitions(): DoctorCheckResultData
    {
        $missing = [];
        $unsafe = [];
        $wrongPackage = [];

        foreach (DefaultBlockCatalog::keys() as $key) {
            $definition = $this->definitionFor($key);

            if (! $definition instanceof BlockDefinitionData) {
                $missing[] = $key;

                continue;
            }

            if (! $definition->safeForPublicOutput) {
                $unsafe[] = $key;
            }

            if ($definition->sourcePackage !== self::PACKAGE_NAME) {
                $wrongPackage[] = $key;
            }
        }

        $issues = [
            ...$this->formatIssues('missing definitions', $missing),
            ...$this->formatIssues('not marked safe for public output', $unsafe),
            ...$this->formatIssues('with unexpected source package', $wrongPackage),
        ];

        if ($issues !== []) {
            return new DoctorCheckResultData(
                label: 'Block Library default catalog definitions',
                passed: false,
                message: 'Catalog definition issues: ' . implode('; ', $issues) . '.',
                remediation: 'Ensure DefaultBlockDefinitionProvider registers every DefaultBlockCatalog key as a public-safe block from capell-app/block-library.',
            );
        }

        return new DoctorCheckResultData(
            label: 'Block Library default catalog definitions',
            passed: true,
            message: sprintf('All %d default catalog block definition(s) are registered and public-safe.', count(DefaultBlockCatalog::keys())),
        );
    }

    private function checkTranslations(): DoctorCheckResultData
    {
        $missing = [];

        foreach (DefaultBlockCatalog::keys() as $key) {
            foreach (['label', 'description'] as $field) {
                $translationKey = 'capell-block-library::blocks.catalog.' . $key . '.' . $field;

                if (! Lang::has($translationKey)) {
                    $missing[] = $translationKey;
                }
            }
        }

        if ($missing !== []) {
            return new DoctorCheckResultData(
                label: 'Block Library catalog translations',
                passed: false,
                message: 'Missing translation keys: ' . implode(', ', $missing) . '.',
                remediation: 'Add the missing catalog label and description keys to resources/lang/en/blocks.php.',
            );
        }

        return new DoctorCheckResultData(
            label: 'Block Library catalog translations',
            passed: true,
            message: 'Every default catalog block has label and description translations.',
        );
    }

    private function checkViews(): DoctorCheckResultData
    {
        $missingPublicViews = [];
        $missingPreviewViews = [];

        foreach (DefaultBlockCatalog::keys() as $key) {
            $definition = $this->definitionFor($key);

            if (! $definition instanceof BlockDefinitionData) {
                continue;
            }

            if (! View::exists($definition->publicViewName())) {
                $missingPublicViews[] = $definition->publicViewName();
            }

            if (! View::exists($definition->previewViewName())) {
                $missingPreviewViews[] = $definition->previewViewName();
            }
        }

        $issues = [
            ...$this->formatIssues('missing public views', $missingPublicViews),
            ...$this->formatIssues('missing preview views', $missingPreviewViews),
        ];

        if ($issues !== []) {
            return new DoctorCheckResultData(
                label: 'Block Library catalog views',
                passed: false,
                message: 'Catalog view issues: ' . implode('; ', $issues) . '.',
                remediation: 'Add the missing public or preview Blade views under resources/views/blocks/catalog.',
            );
        }

        return new DoctorCheckResultData(
            label: 'Block Library catalog views',
            passed: true,
            message: 'Every default catalog block has resolvable public and preview views.',
        );
    }

    private function checkBuilderBlocks(): DoctorCheckResultData
    {
        try {
            $blocks = resolve(BuilderBlockDiscovery::class)->filamentBlocks();
        } catch (Throwable $throwable) {
            return new DoctorCheckResultData(
                label: 'Block Library Filament builder blocks',
                passed: false,
                message: 'Unable to discover Filament builder blocks.',
                remediation: $throwable->getMessage(),
            );
        }

        $blockNames = collect($blocks)
            ->map(static fn (mixed $block): string => method_exists($block, 'getName') ? (string) $block->getName() : '')
            ->filter(static fn (string $name): bool => $name !== '')
            ->values()
            ->all();

        $missing = array_values(array_diff(DefaultBlockCatalog::keys(), $blockNames));

        if ($missing !== []) {
            return new DoctorCheckResultData(
                label: 'Block Library Filament builder blocks',
                passed: false,
                message: 'Missing builder blocks: ' . implode(', ', $missing) . '.',
                remediation: 'Add or register matching Filament Builder block classes for every DefaultBlockCatalog key.',
            );
        }

        return new DoctorCheckResultData(
            label: 'Block Library Filament builder blocks',
            passed: true,
            message: sprintf('All %d default catalog block(s) have discoverable Filament Builder blocks.', count(DefaultBlockCatalog::keys())),
        );
    }

    private function checkScreenshots(): DoctorCheckResultData
    {
        $missing = [];

        foreach (DefaultBlockCatalog::keys() as $key) {
            foreach (DefaultBlockCatalog::screenshots($key) as $screenshot) {
                if (! File::exists($this->packagePath($screenshot->path))) {
                    $missing[] = $screenshot->path;
                }
            }
        }

        if ($missing !== []) {
            return new DoctorCheckResultData(
                label: 'Block Library catalog screenshots',
                passed: false,
                message: 'Missing screenshot files: ' . implode(', ', $missing) . '.',
                remediation: 'Capture and commit the missing admin/frontend catalog screenshots under docs/screenshots.',
            );
        }

        return new DoctorCheckResultData(
            label: 'Block Library catalog screenshots',
            passed: true,
            message: sprintf('All %d default catalog screenshot file(s) exist.', count(DefaultBlockCatalog::keys()) * 2),
        );
    }

    private function checkManifest(): DoctorCheckResultData
    {
        $manifestPath = $this->packagePath('capell.json');

        if (! File::exists($manifestPath)) {
            return new DoctorCheckResultData(
                label: 'Block Library manifest health declaration',
                passed: false,
                message: 'capell.json is missing.',
                remediation: 'Restore the package manifest with BlockLibraryHealthCheck declared under healthChecks.',
            );
        }

        /** @var array<string, mixed> $manifest */
        $manifest = json_decode(File::get($manifestPath), associative: true, flags: JSON_THROW_ON_ERROR);
        $healthChecks = is_array($manifest['healthChecks'] ?? null) ? $manifest['healthChecks'] : [];
        $healthCheck = collect($healthChecks)->first(
            static fn (mixed $check): bool => is_array($check)
                && ($check['class'] ?? null) === BlockLibraryHealthCheck::class,
        );

        if (! is_array($healthCheck)) {
            return new DoctorCheckResultData(
                label: 'Block Library manifest health declaration',
                passed: false,
                message: 'BlockLibraryHealthCheck is not declared in capell.json healthChecks.',
                remediation: 'Add Capell\\BlockLibrary\\Health\\BlockLibraryHealthCheck to capell.json healthChecks.',
            );
        }

        if (($healthCheck['severity'] ?? null) !== 'critical') {
            return new DoctorCheckResultData(
                label: 'Block Library manifest health declaration',
                passed: false,
                message: 'BlockLibraryHealthCheck is declared without critical severity.',
                remediation: 'Keep the Block Library catalog health check critical because it protects shared public block output.',
            );
        }

        return new DoctorCheckResultData(
            label: 'Block Library manifest health declaration',
            passed: true,
            message: 'The manifest declares BlockLibraryHealthCheck as a critical shared health check.',
        );
    }

    private function definitionFor(string $key): ?BlockDefinitionData
    {
        try {
            return ResolveBlockDefinitionAction::run($key);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private function formatIssues(string $label, array $values): array
    {
        if ($values === []) {
            return [];
        }

        return [$label . ': ' . implode(', ', $values)];
    }

    private function packagePath(string $path): string
    {
        return dirname(__DIR__, 2) . '/' . ltrim($path, '/');
    }
}
