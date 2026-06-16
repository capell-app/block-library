<?php

declare(strict_types=1);

use Capell\BlockLibrary\Health\BlockLibraryHealthCheck;
use Capell\Core\Data\Diagnostics\DoctorCheckResultData;

it('returns a compatible capell api version', function (): void {
    expect(BlockLibraryHealthCheck::compatibleCapellApiVersion())->toBe('^4.0');
});

it('runs actionable catalog diagnostics', function (): void {
    $results = BlockLibraryHealthCheck::runDiagnostics();

    expect($results)->toHaveCount(10)
        ->and($results->every(static fn (mixed $check): bool => $check instanceof DoctorCheckResultData))->toBeTrue()
        ->and($results->pluck('label')->all())->toBe([
            'Block Library registry binding',
            'Block Library default catalog definitions',
            'Block Library accessibility contracts',
            'Block Library schema lifecycle metadata',
            'Block Library fixture and demo providers',
            'Block Library catalog translations',
            'Block Library catalog views',
            'Block Library Filament builder blocks',
            'Block Library catalog screenshots',
            'Block Library manifest health declaration',
        ]);
});

it('passes health diagnostics for the shipped default catalog', function (): void {
    $results = BlockLibraryHealthCheck::runDiagnostics();

    expect(BlockLibraryHealthCheck::passed())->toBeTrue();

    foreach ($results as $result) {
        expect($result->passed)->toBeTrue($result->label . ': ' . $result->message);
    }
});
