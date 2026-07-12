<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Health;

use Capell\BlockLibrary\Actions\ValidateDefaultBlockCatalogAction;
use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;
use Capell\Core\Data\Diagnostics\DoctorCheckResultData;
use Illuminate\Support\Collection;

final class BlockLibraryHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^0.0';
    }

    /**
     * @return Collection<int, DoctorCheckResultData>
     */
    public static function runDiagnostics(): Collection
    {
        $diagnostics = ValidateDefaultBlockCatalogAction::run();

        return $diagnostics instanceof Collection ? $diagnostics : collect();
    }

    public static function passed(): bool
    {
        return self::runDiagnostics()->every(
            static fn (DoctorCheckResultData $check): bool => $check->passed,
        );
    }
}
