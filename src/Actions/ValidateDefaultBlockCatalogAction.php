<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Actions;

use Capell\BlockLibrary\Contracts\BlockDemoContentProvider;
use Capell\BlockLibrary\Contracts\BlockFixtureProvider;
use Capell\BlockLibrary\Data\BlockDefinitionData;
use Capell\BlockLibrary\Data\BlockFixtureData;
use Capell\BlockLibrary\Health\BlockLibraryHealthCheck;
use Capell\BlockLibrary\Support\BlockRegistry;
use Capell\BlockLibrary\Support\BuilderBlockDiscovery;
use Capell\BlockLibrary\Support\DefaultBlockCatalog;
use Capell\Core\Data\Diagnostics\DoctorCheckResultData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\View;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use Throwable;

final class ValidateDefaultBlockCatalogAction
{
    use AsFake;
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
            $this->checkAccessibilityContracts(),
            $this->checkSchemaLifecycleMetadata(),
            $this->checkFixtureAndDemoProviders(),
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

    private function checkAccessibilityContracts(): DoctorCheckResultData
    {
        $missing = [];
        $incomplete = [];

        foreach (DefaultBlockCatalog::keys() as $key) {
            $definition = $this->definitionFor($key);

            if (! $definition instanceof BlockDefinitionData) {
                $missing[] = $key;

                continue;
            }

            $contract = $definition->accessibilityContract;

            if (
                $contract->semanticRules === []
                || $contract->keyboardRules === []
                || $contract->contrastPairs === []
                || $contract->mediaRules === []
            ) {
                $incomplete[] = $key;
            }
        }

        $issues = [
            ...$this->formatIssues('missing definitions', $missing),
            ...$this->formatIssues('with incomplete accessibility contracts', $incomplete),
        ];

        if ($issues !== []) {
            return new DoctorCheckResultData(
                label: 'Block Library accessibility contracts',
                passed: false,
                message: 'Accessibility contract issues: ' . implode('; ', $issues) . '.',
                remediation: 'Declare semantic, keyboard, contrast, and media accessibility rules for every default block definition.',
            );
        }

        return new DoctorCheckResultData(
            label: 'Block Library accessibility contracts',
            passed: true,
            message: sprintf('All %d default catalog block definition(s) declare complete accessibility contracts.', count(DefaultBlockCatalog::keys())),
        );
    }

    private function checkSchemaLifecycleMetadata(): DoctorCheckResultData
    {
        $missing = [];
        $invalidVersions = [];
        $selfReplacing = [];
        $missingDeprecationNotes = [];

        foreach (DefaultBlockCatalog::keys() as $key) {
            $definition = $this->definitionFor($key);

            if (! $definition instanceof BlockDefinitionData) {
                $missing[] = $key;

                continue;
            }

            if ($definition->schemaVersion < 1) {
                $invalidVersions[] = $key;
            }

            if ($definition->replacementKey === $definition->key) {
                $selfReplacing[] = $key;
            }

            if ($definition->deprecated && ($definition->deprecationNote === null || trim($definition->deprecationNote) === '')) {
                $missingDeprecationNotes[] = $key;
            }
        }

        $issues = [
            ...$this->formatIssues('missing definitions', $missing),
            ...$this->formatIssues('with invalid schema versions', $invalidVersions),
            ...$this->formatIssues('that replace themselves', $selfReplacing),
            ...$this->formatIssues('deprecated without notes', $missingDeprecationNotes),
        ];

        if ($issues !== []) {
            return new DoctorCheckResultData(
                label: 'Block Library schema lifecycle metadata',
                passed: false,
                message: 'Schema lifecycle issues: ' . implode('; ', $issues) . '.',
                remediation: 'Declare schemaVersion >= 1 for every block and include deprecation notes plus a distinct replacement key for deprecated blocks.',
            );
        }

        return new DoctorCheckResultData(
            label: 'Block Library schema lifecycle metadata',
            passed: true,
            message: sprintf('All %d default catalog block definition(s) declare valid schema lifecycle metadata.', count(DefaultBlockCatalog::keys())),
        );
    }

    private function checkFixtureAndDemoProviders(): DoctorCheckResultData
    {
        $missingFixtureProviders = [];
        $emptyFixtures = [];
        $missingDemoProviders = [];
        $emptyDemoContent = [];

        foreach (DefaultBlockCatalog::keys() as $key) {
            $definition = $this->definitionFor($key);

            if (! $definition instanceof BlockDefinitionData) {
                continue;
            }

            if ($definition->fixtureProvider === null) {
                $missingFixtureProviders[] = $key;
            } elseif (! $this->fixtureProviderHasFixtures($definition)) {
                $emptyFixtures[] = $key;
            }

            if ($definition->demoContentProvider === null) {
                $missingDemoProviders[] = $key;
            } elseif (! $this->demoProviderHasContent($definition)) {
                $emptyDemoContent[] = $key;
            }
        }

        $issues = [
            ...$this->formatIssues('missing fixture providers', $missingFixtureProviders),
            ...$this->formatIssues('with empty fixtures', $emptyFixtures),
            ...$this->formatIssues('missing demo content providers', $missingDemoProviders),
            ...$this->formatIssues('with empty demo content', $emptyDemoContent),
        ];

        if ($issues !== []) {
            return new DoctorCheckResultData(
                label: 'Block Library fixture and demo providers',
                passed: false,
                message: 'Fixture or demo provider issues: ' . implode('; ', $issues) . '.',
                remediation: 'Attach package-owned fixture and demo content providers that return deterministic public-safe payloads for every default block.',
            );
        }

        return new DoctorCheckResultData(
            label: 'Block Library fixture and demo providers',
            passed: true,
            message: sprintf('All %d default catalog block definition(s) provide fixture and demo payloads.', count(DefaultBlockCatalog::keys())),
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
        $requiredScreenshots = $this->requiredScreenshotPaths();
        $missing = array_values(array_filter(
            $requiredScreenshots,
            fn (string $path): bool => ! File::exists($this->packagePath($path)),
        ));

        if ($missing !== []) {
            return new DoctorCheckResultData(
                label: 'Block Library catalog screenshots',
                passed: false,
                message: 'Missing required screenshot files: ' . implode(', ', $missing) . '.',
                remediation: 'Capture and commit the required screenshots declared in docs/screenshots.json.',
            );
        }

        return new DoctorCheckResultData(
            label: 'Block Library catalog screenshots',
            passed: true,
            message: sprintf('All %d required screenshot file(s) declared in docs/screenshots.json exist.', count($requiredScreenshots)),
        );
    }

    /**
     * @return list<string>
     */
    private function requiredScreenshotPaths(): array
    {
        $contractPath = $this->packagePath('docs/screenshots.json');

        if (! File::exists($contractPath)) {
            return ['docs/screenshots.json'];
        }

        /** @var array<string, mixed> $contract */
        $contract = json_decode(File::get($contractPath), associative: true, flags: JSON_THROW_ON_ERROR);
        $entries = is_array($contract['entries'] ?? null) ? $contract['entries'] : [];

        return array_values(collect($entries)
            ->filter(static fn (mixed $entry): bool => is_array($entry) && ($entry['required'] ?? false) === true)
            ->map(static fn (array $entry): string => (string) ($entry['screenshotPath'] ?? ''))
            ->filter(static fn (string $path): bool => $path !== '')
            ->map(static fn (string $path): string => str_replace('packages/block-library/', '', $path))
            ->all());
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
            $definition = ResolveBlockDefinitionAction::run($key);

            return $definition;
        } catch (Throwable) {
            return null;
        }
    }

    private function fixtureProviderHasFixtures(BlockDefinitionData $definition): bool
    {
        if ($definition->fixtureProvider === null || ! is_a($definition->fixtureProvider, BlockFixtureProvider::class, true)) {
            return false;
        }

        try {
            $provider = resolve($definition->fixtureProvider);

            if (! $provider instanceof BlockFixtureProvider) {
                return false;
            }

            foreach ($provider->fixtures($definition) as $fixture) {
                if ($fixture instanceof BlockFixtureData && $fixture->payload !== []) {
                    return true;
                }
            }
        } catch (Throwable) {
            return false;
        }

        return false;
    }

    private function demoProviderHasContent(BlockDefinitionData $definition): bool
    {
        if ($definition->demoContentProvider === null || ! is_a($definition->demoContentProvider, BlockDemoContentProvider::class, true)) {
            return false;
        }

        try {
            $provider = resolve($definition->demoContentProvider);

            return $provider instanceof BlockDemoContentProvider
                && $provider->demoContent($definition) !== [];
        } catch (Throwable) {
            return false;
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
